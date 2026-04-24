# データベース設計・性能評価レポート

## 【Critical】最優先対応必須の問題

### 1. BroadcastMessageAction で `Friend::all()` — 全件フルスキャン
**ファイル:** `app/Actions/Broadcast/BroadcastMessageAction.php:21`

```php
$friends = Friend::all();   // 100万行を全件メモリ展開
foreach ($friends as $friend) {
    if ($sendTo === 'all') { ... }
    elseif ($friend->mark->value === $sendTo) { ... }
}
```

`mark` フィルタをアプリ側で行っており、DBに条件を渡していない。友達10万件時にPHP側で約100万レコードをメモリ展開し、各友達ごとにLINE APIコール＋Talk INSERT＋MediaCopyが連続発火。タイムアウト・OOM確定。

修正方針:
```php
$query = Friend::query();
if ($sendTo !== 'all') {
    $query->where('mark', $sendTo);
}
$query->chunkById(200, function ($friends) use ($message) {
    foreach ($friends as $friend) {
        dispatch(new SendMessageToFriendJob($message, $friend->id));
    }
});
```
加えて `friends.mark` への単独インデックスが存在しない。

### 2. `message_deliveries` テーブルにインデックスが皆無
**ファイル:** `database/migrations/2024_03_11_130646_create_message_deliveries_table.php`

`message_id`, `message_type`, `delivery_id`, `delivery_type` のいずれにもインデックスが張られていない。Polymorphic Morph系は `(delivery_type, delivery_id)` の複合インデックスが必須。

```sql
ALTER TABLE message_deliveries
  ADD INDEX idx_delivery_morph (delivery_type, delivery_id),
  ADD INDEX idx_message_morph  (message_type, message_id),
  ADD INDEX idx_delivery_date  (delivery_date);
```

### 3. `talks` テーブルの Polymorphic に複合インデックスなし
**ファイル:** `database/migrations/2024_02_10_043355_create_talks_table.php`

`sender_id`, `sender_type`, `receiver_id`, `receiver_type` すべて無インデックス。チャット画面で特定友達とのやりとりを取得する際 (`receiver_id = ? AND receiver_type = ?`) にフルスキャンが発生。

```sql
ALTER TABLE talks
  ADD INDEX idx_sender_morph   (sender_type, sender_id),
  ADD INDEX idx_receiver_morph (receiver_type, receiver_id),
  ADD INDEX idx_created_at     (created_at);
```

## 【High】早期対応が必要な問題

### 4. スキーマドリフトの根本原因 — `friends_name_unique` の誤設計
**ファイル:** `2024_02_10_042526_create_friends_table.php` → `2024_08_19_123309_alter_friends_table_update_constraints.php`

初期設計では `name` に `UNIQUE` を付与していたが、実際の業務では同名ユーザーが存在し得るため半年後に破壊的変更が必要になった。本来 `user_id`（LINE の `userId`）がビジネスキーであり、最初から `UNIQUE` を付けるべき列だった。

### 5. `friends.points` の型不整合 — `float` vs 整数
**ファイル:** `2024_05_28_121915_add_referral_and_points_to_friends_table.php:19`

`friends.points` は `float`、`award_points_logs.awarded_points` も `float`。ポイント残高に浮動小数点を使うと丸め誤差が累積する。`DECIMAL(12,2)` への変更が必要。`ManagePointsAction` でも `$friend->points + $data['points']` と単純加算しており、競合更新に脆弱 (楽観ロック未使用)。

### 6. `activity_log` テーブルのスケール問題
**ファイル:** `database/migrations/2024_03_01_111902_create_activity_log_table.php`

`Broadcast`、`Message` 等が `LogsActivity` を使用。配信量が増えると最も急成長するテーブルになる。`created_at` のレンジパーティションと TTL アーカイブポリシーが未設定。`properties` カラム (JSON) に対するクエリも無インデックス。

### 7. N+1 問題 — BroadcastResource EditForm
**ファイル:** `app/Filament/Resources/BroadcastResource.php:54, 97, 333, 343`

`$record->messageDelivery->message->name` のような 3 階層アクセスが `formatStateUsing` 内に複数あり、Filament がレコード一覧を表示する際に broadcast 件数分だけクエリが発火。`with(['messageDelivery.message'])` の Eager Load が存在しない。

### 8. `friend_coupons` テーブルの複合ユニーク制約なし
**ファイル:** `database/migrations/2024_06_13_104150_create_friend_coupons_table.php`

`friend_id` と `coupon_id` の組み合わせに UNIQUE 制約がなく、同一ユーザーが同一クーポンを重複取得できる。

```sql
ALTER TABLE friend_coupons
  ADD UNIQUE INDEX uq_friend_coupon (friend_id, coupon_id),
  ADD INDEX idx_status (status);
```

## 【Medium】計画的に対処すべき問題

### 9. RichMenu `parent_id` — Adjacency List の限界
**ファイル:** `2024_06_14_143114_alter_table_add_parent_id_column_into_rich_menu_table.php`

隣接リスト (Adjacency List) は深さ 2 レベルまでは問題ないが、階層が増えると再帰クエリが必要。`parent_id` にインデックスが張られていない。

### 10. ENUM 代替の `string` カラム多用
`broadcasts.send_to`、`broadcasts.repeat`、`talks.flag`、`friend_coupons.status`、`coupons.amount_type`、`coupons.coupon_type` などすべて `string` 型。DBレベルでの ENUM 制約がなく、不正値がそのまま保存できる状態。

### 11. Soft Delete 未導入
`Friend`、`Broadcast`、`Message`、`Coupon` 等すべて物理削除のみ。誤削除した場合の復元手段がない。最低限 `Friend` と `Broadcast` には `SoftDeletes` が必要。

## 【Low】改善余地はあるが緊急ではない問題

### 12. `talks.message` JSON カラムの肥大化
各 Talk レコードが LINE Messaging API のリクエストオブジェクト全体を JSON 保存している可能性。長期的にはメッセージ本文のみ抽出して正規化するか、JSON カラムに対して生成カラムでインデックス化を検討。

### 13. Full-text Search 未実装
`Friend` の `name` 検索は現状 `LIKE '%...%'` になりうる。友達 10 万件以上では MySQL の FULLTEXT インデックスか Laravel Scout + Meilisearch が必要。

## 追加すべきインデックスの具体提案

```php
// database/migrations/2024_xx_xx_add_performance_indexes.php
Schema::table('talks', function (Blueprint $table) {
    $table->index(['sender_type', 'sender_id'],   'idx_sender_morph');
    $table->index(['receiver_type', 'receiver_id'], 'idx_receiver_morph');
    $table->index('created_at');
    $table->index('read_at');
});

Schema::table('message_deliveries', function (Blueprint $table) {
    $table->index(['delivery_type', 'delivery_id'], 'idx_delivery_morph');
    $table->index(['message_type', 'message_id'],   'idx_message_morph');
    $table->index('delivery_date');
});

Schema::table('friends', function (Blueprint $table) {
    $table->index('mark');
    $table->index('referred_by');
    $table->index('points');
});

Schema::table('friend_coupons', function (Blueprint $table) {
    $table->unique(['friend_id', 'coupon_id'], 'uq_friend_coupon');
    $table->index('status');
});

Schema::table('rich_menus', function (Blueprint $table) {
    $table->index('parent_id');
    $table->index('rich_menu_set_id');
});

Schema::table('award_points_logs', function (Blueprint $table) {
    $table->index(['friend_id', 'created_at'], 'idx_friend_timeline');
});
```

## スケール予測

| 友達数 | 主なボトルネック |
|--------|----------------|
| 〜1万 | `BroadcastMessageAction` の同期処理でタイムアウト |
| 10万 | `Friend::all()` でメモリ枯渇。`talks` フルスキャンで P95 > 5秒。`activity_log` が週次で数百万行に |
| 100万 | `message_deliveries` が億行超。インデックスなしでは全配信が数時間。`friends.mark` フィルタが効かず全件スキャンが常態化 |

## データモデル再設計の推奨

1. `friends.user_id` → `line_user_id` にリネーム (マルチテナント対応時の衝突防止)
2. `points` を `DECIMAL(12,2)` に変更、`DB::raw('points + ?')` で楽観ロック
3. `BroadcastMessageAction` を Queue Job アーキテクチャに変更 (`BroadcastJob` → `SendMessageToFriendJob` の 2 層)
4. `message_deliveries` の Polymorphic を分離 — ENUM カラムに変換するか、型別テーブルに分離
5. Soft Delete の導入
6. `activity_log` の TTL パーティション (90日以上は月次バッチでアーカイブ)

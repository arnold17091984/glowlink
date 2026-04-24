# Laravel アーキテクト レビュー

## 推奨アーキテクチャの目標図

```
app/
├── Domains/
│   ├── Messaging/          # AutoResponse, Broadcast, ScenarioDelivery, Talk
│   │   ├── Actions/
│   │   ├── Events/
│   │   ├── Jobs/
│   │   └── Models/
│   ├── Loyalty/            # Coupon, FriendCoupon, AwardPoints
│   │   ├── Actions/
│   │   ├── States/         # State Machine (RedeemCouponStatus)
│   │   └── Models/
│   ├── Referral/           # Referral, Friend(referral側面)
│   │   └── Actions/
│   └── LineIntegration/    # LINE API ラッパー層
│       ├── Requests/       # BuildXxxRequestAction群の統合
│       └── Gateway/        # LINEMessagingApi ファサード抽象化
├── Models/                 # 共有Eloquentモデル
├── Http/Controllers/       # 薄いコントローラー
└── Filament/               # UI専用(ビジネスロジック禁止)
```

## Critical 問題

### 1. `BroadcastMessageAction` の全件ロードによるメモリ爆発
**ファイル:** `app/Actions/Broadcast/BroadcastMessageAction.php:21`
`$friends = Friend::all();` で友達数が数千件に達した時点でジョブがタイムアウトまたはメモリ超過。`BroadcastingJob` がこのActionを呼ぶため、Queueワーカーごと落ちる。`Friend::query()->lazy()` または `cursor()` に変更し、1件ずつ個別ジョブ(`SendMessageToFriendJob`)にチェーンするJob Batchingパターンが必須。同じ問題が `BroadcastCommand::handle()` の `Broadcast::all()` にも存在 (`app/Console/Commands/BroadcastCommand.php:36`)。

### 2. `RedeemRewardAction` の巨大単一メソッドとレース条件
**ファイル:** `app/Actions/Coupon/RedeemRewardAction.php`
167行の `execute()` が検証・抽選・ポイント控除・プッシュ通知を一括処理。`isInLimit()` チェック(201行)とトランザクション開始(121行)の間にレース条件があり、高トラフィック時に上限超過が発生。`SELECT ... FOR UPDATE` または `DB::lockForUpdate()` による悲観的ロックが必要。また、メソッド末尾の `$isWin` 変数が lottery 非設定時に未定義のまま return される(183行)バグ。

## High 問題

### 3. Filament Pageにビジネスロジックが混入
**ファイル:** `app/Filament/Resources/BroadcastResource/Pages/CreateBroadcast.php`
`handleRecordCreation()` 内でRepeatEnum判定、`is_active` 制御、`last_date` 設定、`BroadcastingJob::dispatch()`、`BroadcastCommand::updateNextDate()` の直接呼び出しが全て混在。`CreateBroadcastWithDeliveryAction` を作成し、このPageはデータを渡すだけにする。

### 4. `ScenarioDeliveriesJob` が `BroadcastingJob` の完全コピー
両ジョブは完全に同一のコード。リトライ設定(`$tries`)、タイムアウト設定(`$timeout`)、失敗時ハンドラー(`failed()`)が両方とも未設定。LINE API 呼び出しが失敗した場合、ジョブは無限リトライまたは即座に失敗。

### 5. `AutoResponseAction` でのポリモーフィック解決に `app()` を乱用
**ファイル:** `app/Actions/AutoResponse/AutoResponseAction.php:47`
`case app($messageDelivery->message_type) instanceof Message:` のように、`message_type` カラムに格納されたFQCN文字列をそのまま `app()` でインスタンス化。外部から不正なクラス名が注入された場合、任意クラスがインスタンス化されるセキュリティリスク。`MessageTypeEnum` への対応付けで解決すべき。

### 6. `bootstrap/app.php` が Laravel 10 形式のまま
Laravel 11 では `bootstrap/app.php` が `Application::configure()` チェーン形式に刷新され、`Http/Kernel.php`、`Console/Kernel.php`、`Exceptions/Handler.php` は廃止。本プロジェクトはこれら3ファイルが全て残存し、Laravel 11 の新機能が利用できていない。

## Medium 問題

### 7. DTO の型安全性が不完全
**ファイル:** `app/DataTransferObjects/BroadcastData.php`
`repeat` が `string` 型宣言で、`RepeatEnum` を使っていない。DTOがEnumを受け取らないため、型システムによる保護が無効化。

### 8. `Coupon` モデルに重複castと型不整合
**ファイル:** `app/Models/Coupon.php:92-93`
`'is_limited' => 'boolean',` が重複定義。`unlimited` カラムは `bool` のプロパティ宣言がある一方で `$casts` に含まれず、`is_limited` は重複定義、`from`/`till` は `datetime` castがなくstring型のまま。

### 9. `MessageDelivery` の双方向ポリモーフィック設計
`delivery` (Broadcast/ScenarioDelivery 側) と `message` (Message/RichMessage/RichVideo/RichCard 側) の2つのMorphToが1テーブルに同居。クエリが複雑化し、eager loadingが困難。`message_type` カラムに格納されたFQCNはマイグレーション時にクラス名変更で破損するリスク。Morphマップ(`Relation::morphMap()`)が未登録。

### 10. `BroadcastCommand::updateNextDate()` のif文チェーン
**ファイル:** `app/Console/Commands/BroadcastCommand.php:84-111`
`RepeatEnum` のmatchではなく複数の `if` 文が並列。新しいRepeatEnum値追加時に対応漏れが発生。

### 11. ハードコードされたS3 URL
**ファイル:** `app/Actions/Coupon/RedeemRewardAction.php:37,52,65,78,97,168,181`
`https://betrnk-tours-bucket.s3.amazonaws.com/liff/bird.png` がAction内に7箇所ハードコード。環境変数または設定ファイルへの移行が必要。

### 12. Event / Observerが全く未定義
**ファイル:** `app/Providers/EventServiceProvider.php`
`ReferralAccepted`、`CouponRedeemed`、`BroadcastSent`、`FriendRegistered` 等のドメインイベントが一切存在しない。ポイント付与・通知送信・ログ記録がActionに直接書かれているため、横断的関心事が全ActionにコピーされるFat Action化が進行中。

## Low 問題

### 13. テストが実質ゼロ
`tests/Feature/ExampleTest.php`、`tests/Unit/ExampleTest.php` の2ファイルのみ。`RedeemRewardAction` の複雑な条件分岐、`ReferralAction` のトランザクション、`BroadcastCommand` のスケジューリングロジックはいずれもテストが必須。

### 14. Laravel Horizon / Pulse 未導入
`composer.json` の `post-update-cmd` に `horizon-assets` 公開コマンドがあるにも関わらず、`horizon/laravel-horizon` パッケージが `require` に存在しない。

## 段階的リファクタリングロードマップ

### 短期 (1-2週間): 本番障害リスクの排除
1. `BroadcastMessageAction::execute()` を `Friend::query()->lazy()` + 個別Job Dispatchに変更
2. `RedeemRewardAction` のレース条件を `lockForUpdate()` で修正、`$isWin` 未定義バグを修正
3. `Relation::morphMap()` を `AppServiceProvider` に追加
4. `Coupon` モデルの重複castと `from`/`till` のdatetime cast追加
5. Job に `$tries = 3`、`$timeout = 60`、`failed()` メソッドを追加

### 中期 (1-2ヶ月): アーキテクチャの整理
1. `CreateBroadcast` Page からビジネスロジックを `CreateBroadcastWithDeliveryAction` へ移行
2. `ScenarioDeliveriesJob` を `BroadcastingJob` に統合 (または共通基底クラス化)
3. ドメインイベント導入: `CouponRedeemed`、`ReferralAccepted`、`FriendRegistered`
4. `AutoResponseAction` の `app()` 乱用を `MessageTypeResolver` サービスに置換
5. `RedeemRewardAction` を `ValidateCouponAction` + `ProcessRedemptionAction` + `NotifyRedemptionAction` に分割
6. Laravel Horizon 導入、Queue監視基盤の整備

### 長期 (3ヶ月以上): 設計の進化
1. `app/Domains/` 構造へのモジュール分割 (Messaging / Loyalty / Referral / LineIntegration)
2. `FriendCoupon` に `RedeemCouponStatus` State Machineを導入 (不正遷移防止)
3. Pest PHP によるテストスイート構築 (目標カバレッジ80%以上)
4. LINE API 呼び出しを `LineGateway` インターフェース背後に隠蔽 (テスト容易性向上)
5. マルチテナント対応の検討 (`stancl/tenancy` または `team_id` カラム方式)

# Filament 管理画面 UI/UX デザインレビュー

## Critical — 即座に対処すべき問題

### C-1. Dashboard が実質的に空
**ファイル:** `app/Providers/Filament/AdminPanelProvider.php:69-71`

現在 `AccountWidget` のみ登録。マーケター向けKPIが皆無のため、画面を開いた瞬間に「この管理画面は何ができるのか」が全く伝わらない。

**推奨 KPI 設計:**

| Widget | 指標 | 参照テーブル |
|---|---|---|
| StatsOverview | 友だち総数 / 今月増減 | friends |
| StatsOverview | 配信済メッセージ数 (当月) | message_deliveries |
| StatsOverview | クーポン利用率 (有効/発行) | friend_coupons |
| StatsOverview | 紹介成立件数 (当月) | referrals |
| LineChart | 友だち増加推移 (過去30日) | friends.created_at |
| BarChart | チャンネル別配信数 (broadcast/scenario/auto) | — |
| TableWidget | 今後7日間の配信スケジュール | broadcasts, scenario_deliveries |

### C-2. Rich Menu の WYSIWYG が「プレビュー表示のみ」で編集不可
**ファイル:** `app/Filament/Resources/RichMenuResource.php:119-193`, `resources/views/forms/components/rich-menu-layout.blade.php`

現状は SVG をオーバーレイ表示するだけで、タップ領域 (actions の A/B/C…) とレイアウト上のセルが視覚的に対応していない。

**実装提案: LINE Rich Menu WYSIWYG エディタ**

アプローチ: Alpine.js + SVG クリッカブルオーバーレイ
- アップロード済み画像を background として表示
- SVG で各セル境界線を描画 (layout_no に応じて座標を切替)
- 各セルをクリックすると左側 Repeater の該当アイテムをフォーカス
- 選択中セルをハイライト (stroke + fill-opacity)
- アルファベットラベル (A, B, C...) を各セル中央に表示

### C-3. ナビゲーショングループのラベルが全英語
**ファイル:** `app/Providers/Filament/AdminPanelProvider.php:46-62`

```php
NavigationGroup::make('Friend Management')->label('友だち管理'),
NavigationGroup::make('Messaging')->label('メッセージ配信'),
NavigationGroup::make('Outreach')->label('キャンペーン'),
NavigationGroup::make('Rich Media')->label('リッチコンテンツ'),
NavigationGroup::make('Utilities')->label('設定・ユーティリティ'),
```

## High — 優先度高

### H-1. CouponResource のフォーム構造が認知負荷過大
**ファイル:** `app/Filament/Resources/CouponResource.php:27-151`

`Group::make()` を入れ子にした 4カラムレイアウトが崩れやすく、`Placeholder` でラベルを擬似的に作っている箇所 (L37, L43, L49) は Filament の意図した使い方ではない。`is_lottery` トグルと `win_rate` テキスト入力の関連性も視覚的に伝わらない。

### H-2. BroadcastResource の繰り返し配信設定が直感的でない
**ファイル:** `app/Filament/Resources/BroadcastResource.php:156-226`

`repeat` → `every` の2段階 Select はユーザーが「毎週月曜日」等の自然言語で設定したいニーズに応えていない。`Wizard` コンポーネントで「①宛先選択 → ②コンテンツ選択 → ③配信スケジュール」の3ステップに分割推奨。

### H-3. Table の情報密度不足と検索機能の欠如
- `BroadcastResource` テーブルに `start_date` カラムなし — 配信日が一覧で見えない
- `CouponResource` テーブルに `is_limited` カラムが2回重複して定義されている (L170, L172)
- `FriendResource` / `CouponResource` など主要テーブルにフィルターが未実装 (`->filters([//])`)
- `BroadcastResource` テーブルに `SearchFilter` / `SelectFilter` 皆無

### H-4. IndividualTalk (チャット) UIのインラインスタイル乱用
**ファイル:** `resources/views/filament/resources/individual-talk-resource/pages/messages.blade.php`

全スタイリングが `style=""` インライン属性で記述されており Tailwind クラスが一切使われていない。ダークモード対応が `color: black` ハードコードで破綻する (L136, L172)。`wire:poll.keep-alive` によるポーリング間隔が指定されておらず、デフォルト 2 秒ポーリングがサーバー負荷を招く。

## Medium — 品質改善

### M-1. Rich Message / Rich Card のプレビューが静的な SVG オーバーレイのみ
**ファイル:** `app/Filament/Resources/RichMessageResource.php:196-206`

SVG をインライン HTML で `position: absolute` で重ねるだけで、実際の LINE 上での表示サイズや比率が確認できない。

### M-2. ScenarioDelivery の配信ステップがタイムライン表示されない
**ファイル:** `app/Filament/Resources/ScenarioDeliveryResource.php:55-190`

`Repeater` でメッセージと `delivery_date` を列挙しているが、「配信 Day 1 → Day 3 → Day 7」の時系列構造が視覚化されていない。

### M-3. RichCard のネスト Repeater が深すぎる
**ファイル:** `app/Filament/Resources/RichCardResource.php:35-151`

`card` Repeater の中に `button` Repeater がネストされており、認知負荷が非常に高い。カード 3 枚 × ボタン 3 個の場合、最大 9 アイテムの Repeater が展開される。

**推奨プラグイン:** `awcodes/filament-table-repeater` — グリッド形式でボタン設定を横並びに表示可能。

### M-4. tailwind.config.js の content パスにタイポ
**ファイル:** `tailwind.config.js:9`

```js
'./resources/views/filament/resources/individual-talk-resource/*.bladed.php'
// 正: *.blade.php
```

`.bladed.php` というパターンはファイルにマッチしないため、IndividualTalk のカスタムビュー内で Tailwind クラスを追加しても CSS が生成されない。

### M-5. FriendResource のセグメント操作 UI 不足
`FlagEnum` (mark) でセグメント分類しているが、一括でマーク変更するバルクアクションが未実装。フィルターも空。

```php
->filters([
    SelectFilter::make('mark')->options(FlagEnum::class),
    Filter::make('has_points')->query(fn($q) => $q->where('points', '>', 0)),
])
->bulkActions([
    BulkAction::make('update_mark')
        ->form([Select::make('mark')->options(FlagEnum::class)->required()])
        ->action(fn(Collection $records, array $data) => $records->each->update(['mark' => $data['mark']])),
])
```

## Low — 長期的改善

### L-1. ダークモード / テーマカスタマイズが最小限
**ファイル:** `app/Providers/Filament/AdminPanelProvider.php:34-41`

primary カラーに `#21D59B` (16進数文字列) を指定しているが、Filament の `Color::` ユーティリティを通していない。

### L-2. 日本語フォント未指定
現状 Filament デフォルトの Inter フォントが使用される。日本語テキストはシステムフォントフォールバックとなりブランド一貫性が失われる。推奨: Noto Sans JP または BIZ UDPGothic。

### L-3. 空状態・スケルトン・マイクロインタラクション
Filament の `->emptyStateHeading()` / `->emptyStateDescription()` / `->emptyStateIcon()` が全テーブルで未設定。

## 推奨 Filament プラグイン

| プラグイン | 用途 | 優先度 |
|---|---|---|
| `awcodes/filament-table-repeater` | RichCard のネストリピーターをグリッド化 | High |
| `bezhansalleh/filament-shield` | Role/Permission 管理 | High |
| `saade/filament-fullcalendar` | 配信スケジュールのカレンダー表示 | High |
| `awcodes/filament-curator` | メディアライブラリ一元管理 | Medium |
| `rmsramos/activitylog` | ActivityLog の Filament 統合強化 | Medium |

## 総評

本プロジェクトは Filament 3 の標準コンポーネントを概ね正しく使えているが、**プレビュー・ビジュアライゼーション・フィードバック**の3層が弱い。特に LINE というビジュアルメディアを扱う SaaS にも関わらず「作ったものがどう見えるか」をリアルタイムで確認する手段が不完全なまま保存まで到達してしまう設計が「UI/UXが最悪」という評価の根本原因。

最優先の実装順序: Dashboard KPI Widget → テーブルフィルター修正 (tailwind.config.js タイポ含む) → Rich Menu WYSIWYG → Broadcast Wizard フロー → カレンダー配信ビュー。

# betrnk-growlink 10名専門家チーム 監査レポート

## 目次

| # | 担当 | ファイル | Critical 指摘数 |
|---|---|---|---|
| 1 | **LINE API スーパースペシャリスト** | [01-line-api-specialist.md](01-line-api-specialist.md) | 3 |
| 2 | **Laravel アーキテクト** | [02-laravel-architect.md](02-laravel-architect.md) | 2 |
| 3 | **DB / スキーマ最適化** | [03-database-optimizer.md](03-database-optimizer.md) | 3 |
| 4 | **セキュリティ監査官** | [04-security-auditor.md](04-security-auditor.md) | 4 |
| 5 | **UI デザイナー** | [05-ui-designer.md](05-ui-designer.md) | 3 |
| 6 | **UX リサーチャー** | [06-ux-researcher.md](06-ux-researcher.md) | 1 (致命的) |
| 7 | **フロントエンド / デザインシステム** | [07-frontend-design.md](07-frontend-design.md) | 2 |
| 8 | **パフォーマンスエンジニア** | [08-performance.md](08-performance.md) | 3 |
| 9 | **QA スペシャリスト** | [09-qa-strategy.md](09-qa-strategy.md) | 3 |
| 10 | **PM / グロース戦略家** | [10-product-strategy.md](10-product-strategy.md) | — (戦略層) |

## 統合診断サマリー

ユーザーの認識「UI/UX・実用性・機能性すべて最悪」は**正確**。特に以下 3 層が同時に壊れている。

| 層 | 壊れ方 | 根本原因 |
|---|---|---|
| **配信エンジン** | 友達1万人超で事実上動かない | `Friend::all()` + Push per Friend + QUEUE=sync + リトライ0 |
| **セキュリティ** | 金銭・個人情報を扱うのに穴だらけ | Webhook 署名検証なし、APP_KEY 露出、RBAC 不在、PII 垂れ流し |
| **UI/UX** | 「作ったものがどう見えるか」が最後まで分からない | Dashboard 空、プレビュー静的、フィルター空、英語ナビ、Wizard 不在 |

## 全員が重複指摘した Critical 問題 TOP 10

| # | 問題 | 対応状況 |
|---|---|---|
| 1 | `BroadcastMessageAction` `Friend::all()` → OOM | 🟡 要コード変更 (Multicast化) |
| 2 | Push per Friend (Multicast 未使用)、500倍遅い | 🟡 要コード変更 |
| 3 | LINE Webhook 署名検証の欠落 | ✅ **本日実装完了** |
| 4 | `QUEUE_CONNECTION=sync` | 🔴 .env 変更 (破壊的) |
| 5 | Job `$tries`/`$backoff`/`failed()` 未定義 | ✅ **本日実装完了** |
| 6 | Dashboard が `AccountWidget` のみ | ✅ **本日実装完了** (3 Widget 追加) |
| 7 | 主要 Resource の `->filters([])` が空 | ✅ **本日実装完了** (Friend/Broadcast/Coupon) |
| 8 | LINE Rich Content WYSIWYG プレビュー不在 | 🟡 プロトタイプのみ実装 |
| 9 | `RedeemRewardAction` `$isWin` 未定義バグ | ✅ **本日実装完了** |
| 10 | `.env.example` に APP_KEY 露出 | ✅ **本日実装完了** (要ローテ) |

## 本日の実装済み変更 (非破壊)

| 変更 | ファイル |
|---|---|
| Navigation 日本語化 | [app/Providers/Filament/AdminPanelProvider.php](../../app/Providers/Filament/AdminPanelProvider.php) |
| tailwind.config.js タイポ修正 | [tailwind.config.js](../../tailwind.config.js) |
| `.env.example` の `APP_KEY` 空欄化 | [.env.example](../../.env.example) |
| BroadcastingJob hardening | [app/Jobs/BroadcastingJob.php](../../app/Jobs/BroadcastingJob.php) |
| ScenarioDeliveriesJob hardening | [app/Jobs/ScenarioDeliveriesJob.php](../../app/Jobs/ScenarioDeliveriesJob.php) |
| RedeemRewardAction `$isWin` 初期化 | [app/Actions/Coupon/RedeemRewardAction.php](../../app/Actions/Coupon/RedeemRewardAction.php) |
| CouponResource 重複列削除 + フィルター追加 + 日本語化 | [app/Filament/Resources/CouponResource.php](../../app/Filament/Resources/CouponResource.php) |
| FriendResource フィルター + 一括ステータス変更 + 検索 | [app/Filament/Resources/FriendResource.php](../../app/Filament/Resources/FriendResource.php) |
| BroadcastResource start_date 列 + フィルター + RichCard/RichVideo プレビュー | [app/Filament/Resources/BroadcastResource.php](../../app/Filament/Resources/BroadcastResource.php) |
| LINE Webhook 署名検証 middleware | [app/Http/Middleware/VerifyLineSignature.php](../../app/Http/Middleware/VerifyLineSignature.php) |
| Kernel に `line.signature` alias 登録 | [app/Http/Kernel.php](../../app/Http/Kernel.php) |
| `/messages` を CSRF 除外 | [app/Http/Middleware/VerifyCsrfToken.php](../../app/Http/Middleware/VerifyCsrfToken.php) |
| MessagesController に署名検証 + follow/unfollow ハンドラ | [app/Http/Controllers/MessagesController.php](../../app/Http/Controllers/MessagesController.php) |
| `line-webhook` RateLimiter 登録 | [app/Providers/RouteServiceProvider.php](../../app/Providers/RouteServiceProvider.php) |
| config の `X-Foo:Bar` 残骸削除 | [config/line-bot.php](../../config/line-bot.php) |
| Dashboard Widget 3 本追加 | [app/Filament/Widgets/](../../app/Filament/Widgets/) |
| Dashboard Widget を PanelProvider に登録 | [app/Providers/Filament/AdminPanelProvider.php](../../app/Providers/Filament/AdminPanelProvider.php) |
| パフォーマンス用インデックス マイグレーション | [database/migrations/2026_04_24_000001_add_performance_indexes.php](../../database/migrations/2026_04_24_000001_add_performance_indexes.php) |
| LINE Rich Menu WYSIWYG プロトタイプ | [resources/views/components/line/rich-menu-editor.blade.php](../../resources/views/components/line/rich-menu-editor.blade.php) |

## 破壊的変更 (ユーザー確認必要)

以下は本番環境への影響が大きいため手動で実施する必要あり。

### 🔴 APP_KEY ローテーション
```bash
php artisan key:generate --force
```
既存の `encrypted` カラム (LINE アクセストークン等) は旧鍵で復号→新鍵で再暗号化が必要。実行前に既存暗号化データを復号・バックアップすること。

### 🔴 QUEUE_CONNECTION=redis への変更
Redis サーバーが稼働していること。Laravel Horizon も同時導入推奨。
```bash
composer require laravel/horizon
php artisan horizon:install
```

### 🔴 パフォーマンス用インデックス migration 実行
100万行級のテーブルでは ALTER TABLE がロックするため、メンテナンスウィンドウまたは `pt-online-schema-change` を使用:
```bash
php artisan migrate
```

### 🟠 BroadcastMessageAction の Multicast 化
Push per Friend → Multicast (500件/req) への書き換えは**配信挙動が変わる**ため、開発環境で検証後に本番適用:
- `LINEMessagingApi::multicast()` を使用
- `X-Line-Retry-Key` (UUID) を付与して冪等性担保
- `mark` セグメントは将来的に LINE Audience に同期

### 🟠 composer パッケージ追加 (推奨)
```bash
composer require laravel/horizon laravel/pulse laravel/reverb
composer require bezhansalleh/filament-shield spatie/laravel-permission
composer require pragmarx/google2fa-laravel
composer require bepsvpt/secure-headers
```

## 第二セッションの追加実装 (2026-04-24)

ユーザーからの「(1)〜(5) 全部やる」指示に基づき以下を実装。全 25 ファイルが `php -l` 通過。

### Stream 1: Multicast + Bus::batch による配信 500 倍化
- [app/Actions/Broadcast/MulticastBroadcastAction.php](../../app/Actions/Broadcast/MulticastBroadcastAction.php) — `Friend::chunkById(500)` + `Bus::batch()` + UUID Retry Key で冪等配信
- [app/Jobs/SendMulticastChunkJob.php](../../app/Jobs/SendMulticastChunkJob.php) — 500件/req の Multicast ワーカー、指数バックオフ、Sentry 連携

### Stream 2: テスト基盤構築
- [tests/Concerns/FakesLineApi.php](../../tests/Concerns/FakesLineApi.php) — LINE API モック + 署名付き Webhook 送信ヘルパ
- [tests/TestCase.php](../../tests/TestCase.php) — LINE 設定デフォルト値の自動投入
- 7 Factory 実装: [Friend](../../database/factories/FriendFactory.php), [Coupon](../../database/factories/CouponFactory.php), [Broadcast](../../database/factories/BroadcastFactory.php), [Message](../../database/factories/MessageFactory.php), [FriendCoupon](../../database/factories/FriendCouponFactory.php), [Referral](../../database/factories/ReferralFactory.php), [AwardPointsLogs](../../database/factories/AwardPointsLogsFactory.php) — 全て state メソッド付き
- サンプルテスト 3 本:
  - [LineSignatureTest.php](../../tests/Feature/Webhook/LineSignatureTest.php) — 署名検証 (5 ケース)
  - [RedeemRewardWinRateTest.php](../../tests/Unit/Actions/Coupon/RedeemRewardWinRateTest.php) — 10,000 試行統計テスト
  - [MulticastBroadcastActionTest.php](../../tests/Feature/Actions/Broadcast/MulticastBroadcastActionTest.php) — chunk 分割 / セグメント / Retry Key 検証

### Stream 3: Broadcast Wizard 化
- [CreateBroadcast.php](../../app/Filament/Resources/BroadcastResource/Pages/CreateBroadcast.php) — 4 ステップ Wizard (対象選択 → コンテンツ → スケジュール → 確認) + **対象者数ライブカウント** + プレビュー
- 既存の Edit フロー / ビジネスロジック (handleRecordCreation) は非破壊で維持

### Stream 4: LineGateway + ドメイン再編スキャフォールド
- [LineGateway.php](../../app/Domains/LineIntegration/Gateway/LineGateway.php) — 境界インターフェース
- [LineMessagingApiGateway.php](../../app/Domains/LineIntegration/Gateway/LineMessagingApiGateway.php) — プロダクション実装 (SDK v9 `WithHttpInfo` 系を使用)
- [FakeLineGateway.php](../../app/Domains/LineIntegration/Gateway/FakeLineGateway.php) — テスト用フェイク + `assertPushed`/`assertMulticasted`
- [LineIntegrationServiceProvider.php](../../app/Domains/LineIntegration/LineIntegrationServiceProvider.php) — [config/app.php](../../config/app.php) に登録済
- [DOMAIN-MIGRATION-PLAN.md](DOMAIN-MIGRATION-PLAN.md) — 5 フェーズ 約 2.5 ヶ月の詳細移行計画

### Stream 5: LIFF クーポンウォレット PoC
- [WalletController.php](../../app/Http/Controllers/Liff/WalletController.php) — GET `/liff/wallet`
- [CouponApiController.php](../../app/Http/Controllers/Liff/CouponApiController.php) — GET `/liff/api/coupons/mine` + POST `/liff/api/coupons/redeem`、**LINE Verify API で id_token 検証**
- [wallet.blade.php](../../resources/views/liff/wallet.blade.php) — LIFF SDK 統合、スマホ最適化、既存 RedeemRewardAction を活用
- [config/line-bot.php](../../config/line-bot.php) に `liff_id` 追加
- [RouteServiceProvider](../../app/Providers/RouteServiceProvider.php) に `liff-api` レートリミッタ

### 追加の手動セットアップ

以下は **LINE Developers Console** 側での作業が必要:
1. LIFF アプリを新規作成し、Endpoint URL に `https://<your-domain>/liff/wallet` を指定
2. 発行された LIFF ID を `.env` に `LIFF_ID=xxxx-xxxxxxxx` として追加
3. Scope は `profile openid` を要求

## 残タスク (長期)

- LineMessagingApiGateway への全呼び出し移行 (現状は MulticastBroadcastAction / SendMulticastChunkJob が未だ Facade 直呼び)
- Laravel 11 bootstrap 形式への移行 (`bootstrap/app.php` が Laravel 10 のまま)
- Messaging / Loyalty / Referral ドメインの切り出し (DOMAIN-MIGRATION-PLAN.md 参照)
- Filament Shield + 2FA 導入
- Sentry PII スクラビング
- S3 private + 署名付き URL
- LINE Narrowcast / Audience / Insight API 統合
- AI メッセージ生成 / A/B テスト / 予測分析

詳細は各専門家のレポートを参照。

# ドメイン駆動リアーキテクチャ計画

## 目標ディレクトリ構成

```
app/
├── Domains/
│   ├── Messaging/
│   │   ├── Actions/           # Broadcast/, AutoResponse/, ScenarioDelivery/ から移動
│   │   ├── Events/            # BroadcastSent, ScenarioStarted など
│   │   ├── Jobs/              # BroadcastingJob, ScenarioDeliveriesJob, SendMulticastChunkJob
│   │   ├── Models/            # Broadcast, Message, MessageDelivery, ScenarioDelivery, Talk, AutoResponse
│   │   └── MessagingServiceProvider.php
│   ├── Loyalty/
│   │   ├── Actions/           # Coupon/, Friend/ManagePointsAction など
│   │   ├── Events/            # CouponRedeemed, PointsAwarded, CouponLotteryLost
│   │   ├── Models/            # Coupon, FriendCoupon, AwardPointsLogs
│   │   ├── States/            # RedeemCouponStatus (state machine)
│   │   └── LoyaltyServiceProvider.php
│   ├── Referral/
│   │   ├── Actions/           # Referral/
│   │   ├── Events/            # ReferralAccepted
│   │   ├── Models/            # Referral
│   │   └── ReferralServiceProvider.php
│   ├── LineIntegration/       # ✅ 既にスキャフォールド済み
│   │   ├── Gateway/           # LineGateway, LineMessagingApiGateway, FakeLineGateway
│   │   ├── Actions/           # (現 app/Actions/LineMessage/ と LineMessagingRequest/ を集約)
│   │   ├── Webhook/           # WebhookParser 活用、イベント別 Handler
│   │   └── LineIntegrationServiceProvider.php
│   └── Friend/                # Friend モデルは Messaging/Loyalty の境界横断なので単独ドメインに
│       ├── Models/            # Friend
│       └── Actions/           # StoreFriend, ManagePoints など
├── Filament/                  # UI 層。ビジネスロジックは Domain の Action を呼ぶのみ
├── Http/
│   ├── Controllers/
│   │   └── Liff/              # LIFF クーポンウォレット等、公開 HTTP 層
│   └── Middleware/
└── Providers/
```

## 移行アプローチ

### フェーズ 1: 境界の明示（本セッション完了済）
✅ `app/Domains/LineIntegration/Gateway/{LineGateway,LineMessagingApiGateway,FakeLineGateway}.php`
✅ `LineIntegrationServiceProvider.php`
→ 以降の変更は全てこのインターフェース経由にする。

### フェーズ 2: LineIntegration ドメインの集約 (1-2 週間)
1. `config/app.php` の `providers` 配列に `App\Domains\LineIntegration\LineIntegrationServiceProvider::class` を追加
2. 既存の `app/Actions/LineMessage/*` と `app/Actions/LineMessagingRequest/*` を `app/Domains/LineIntegration/Actions/` に移動 (namespace も合わせる)
3. それらを呼び出している全箇所 (MessageDeliveriesAction, PushMessageAction, Reply 系) を検索置換
4. `BroadcastMessageAction` 含め、LINE Facade 直呼びを `LineGateway` 経由に置換
5. Messaging API のテストを `FakeLineGateway` + `app()->instance()` に統一

### フェーズ 3: Loyalty ドメインの切り出し (2-3 週間)
1. `app/Domains/Loyalty/` 作成
2. `Coupon`, `FriendCoupon`, `AwardPointsLogs` モデル移動
3. `RedeemRewardAction` を 3 分割:
   - `ValidateCouponAction` (期限/ポイント/上限チェック)
   - `ProcessRedemptionAction` (トランザクション + ポイント減算 + FriendCoupon 作成)
   - `RunCouponLotteryAction` (抽選判定)
4. `RedeemCouponStatus` を State Machine パターンで実装 (spatie/laravel-model-states 推奨)
5. ドメインイベント `CouponRedeemed` を発行し、Listener で Push / AwardPointsLogs 記録を分離

### フェーズ 4: Referral & Messaging 分離 (2-3 週間)
1. `Referral` 関連は `app/Domains/Referral/` へ
2. `Broadcast`, `Message`, `ScenarioDelivery` は `app/Domains/Messaging/` へ
3. それぞれにドメインイベントを定義し、Listener で横断操作 (ポイント付与、通知) を行う

### フェーズ 5: Filament 層のアクション委譲 (1 週間)
1. `app/Filament/Resources/*/Pages/*` 内で直接モデルを操作している箇所を、全て Domain の Action 呼び出しに置換
2. `CreateBroadcast::handleRecordCreation()` は既に部分的に Action を使っているが、Wizard 化後は全ロジックを `CreateBroadcastWithDeliveryAction` に集約

## 循環依存の回避

ドメイン間の依存は原則として一方向:
```
   Friend (基盤)
     ↑
 ┌──────┼──────┐
Messaging  Loyalty  Referral
     ↑        ↑        ↑
     └────────┼────────┘
          LineIntegration
```

- ドメインをまたぐ通信はすべて **Event + Listener** 経由
- `Loyalty` が `Messaging` を直接 import しない (ポイント付与後の通知は CouponRedeemed イベント経由で Messaging の Listener が処理)

## マルチテナント移行の布石

上記ドメイン分割が完了すれば、各ドメインのモデルに `brand_id` カラムを追加し、グローバルスコープで自動的にテナント分離可能。`stancl/tenancy` 導入時の変更範囲が各ドメインに局所化される。

## 想定工数

| フェーズ | 工数 | 依存 |
|---|---|---|
| 1 (スキャフォールド) | 完了 | — |
| 2 (LineIntegration 集約) | 1-2 週 | 1 |
| 3 (Loyalty 切り出し) | 2-3 週 | 2 |
| 4 (Referral/Messaging 分離) | 2-3 週 | 3 |
| 5 (Filament 委譲) | 1 週 | 4 |

合計: **約 2-2.5 ヶ月** を見込む。段階的に進め、各フェーズ完了時点で回帰テスト (本セッションで整備した Pest テスト) を必ず実行。

## ロールバック戦略

各フェーズは独立した PR (または少なくとも commit 単位) に分割し、問題が出たら個別に revert できる状態を維持する。namespace 変更は find/replace で完了するため機械的にロールバック可能。

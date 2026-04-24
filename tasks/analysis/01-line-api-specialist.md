# LINE Messaging API 実装レビュー

## Critical（即時対応・本番事故リスク）

### C1. Webhook 署名検証 完全欠落
`app/Http/Controllers/MessagesController.php` は `X-Line-Signature` を一切検証していない。**LINEを装った任意のHTTPリクエストで友達作成・Talk保存・AutoResponseが発火**し、DBの汚染・返信枠消費・LINE利用停止の危険。`/messages` は `routes/web.php` 経由のため CSRF + `auth` 無しでの POST を前提にしておらず、`VerifyCsrfToken` 除外も明示されていない。

修正:
```php
// app/Http/Middleware/VerifyLineSignature.php
public function handle($request, Closure $next) {
    $signature = $request->header('X-Line-Signature');
    $hash = base64_encode(hash_hmac('sha256',
        $request->getContent(), config('line-bot.channel_secret'), true));
    abort_unless(hash_equals($hash, (string)$signature), 401);
    return $next($request);
}
```
`bootstrap/app.php` で `/messages` に適用 + `VerifyCsrfToken::$except` に追加。SDK v9 同梱の `LINE\Webhook\WebhookParser::parseEventRequest()` を使えばイベントのパースも堅牢になる。

### C2. BroadcastMessageAction が N+1 Push を全送信
`Friend::all()` をループし1人ずつ `MessageDeliveriesAction` を呼んでいる。**全員送信なのに Push API を N 回叩くのは LINE公式 TOS違反レベルの非効率**で、月間メッセージ数上限を即消費し、2,000req/sec のレート超過で 429 が頻発する。

- 全員送信 → `broadcastMessage()`（1 API コール、冪等キー対応）
- セグメント送信 → `multicast()`（最大500 userIdまで/コール、`chunk(500)` で分割）
- `friend->mark` によるセグメントは本来 **Audience + Narrowcast** に載せるべき

```php
Friend::query()->where('mark', $sendTo)->pluck('user_id')
  ->chunk(500)->each(fn($ids) =>
      LINEMessagingApi::multicast(new MulticastRequest([
          'to' => $ids->values()->all(),
          'messages' => $messages,
      ]), retryKey: (string) Str::uuid()));
```

### C3. LINE API 呼び出しに例外ハンドリング/リトライなし
`app/Actions/LineMessage/PushMessageAction.php`、`ReplyMessageAction.php`、Reply系4ファイルすべてが `if (!$response) throw ModelNotFoundException('message not go through')` 止まり。

問題:
- SDK v9 の `pushMessage()` は**常にオブジェクトを返し `null` にならない**ため、この分岐は**永遠にfalse = エラー検知不能**
- HTTPステータス・`X-Line-Retry-Key`・429/500系に対する指数バックオフなし
- `ModelNotFoundException` は意味論的に誤り

修正: `pushMessageWithHttpInfo()` を使い `[response, statusCode, headers]` で受け取り、429/500系 は Job の `backoff()` + `retryUntil()` で指数的にリトライ。`X-Line-Retry-Key` ヘッダに UUID を付け冪等化。

## High

### H1. Reply と Push の使い分けが誤り／ReplyToken 15秒制限考慮なし
Reply は **Webhookイベント発生から30秒以内かつ1回きり無料**。`ReplyCouponAction.php` は `RedeemRewardAction` の重い DB トランザクション後に Reply を送っており、**replyToken 期限切れ時に料金発生Push枠を消費するフォールバックも無い**。抽選クーポンは非同期化し、replyTokenが失効した場合は Push にフェイルオーバーするロジックが必要。

### H2. Broadcast の Job 分割・冪等性欠如
`BroadcastingJob.php` は 1 Job で全友達を順次処理。友達数が増えると Job タイムアウト・途中失敗時の二重送信が発生。
- `tries`, `backoff`, `uniqueFor`（ShouldBeUnique）を設定
- 500人単位で子ジョブに分割 (`Bus::batch`)
- `retryKey` (UUID) を MulticastRequest に付与し、再試行時の重複配信を LINE 側で排除

### H3. メディアURL が LINE CDN 要件を満たしていない
`BuildPushMessageRequestAction.php` は image/video で `originalContentUrl == previewImageUrl` を同じURLに設定、audio の `duration` を固定 `"300000"` ミリ秒（5分）でハードコード。LINE仕様:
- image: JPEG/PNG、originalは最大10MB、previewは240x240以下・1MB以下 **別URL必須**
- video: mp4、最大200MB、`trackingId` 未指定
- audio: m4a のみ、duration は実測必須

FFmpeg/`getID3` で実測、Spatie Media Library のconversions で preview を自動生成すべき。

### H4. 配信チャネル選定が Narrowcast 未使用
`mark` セグメントは **LINE Audience Group** に同期し `NarrowcastRequest`（demographic/operator/audience/retargeting 条件指定可）で配信すべき。これで**開封率・クリック率のInsightが自動計測**され、Friend全件ループ＋Pushの現状から劇的に効率化される。

### H5. 友達・フォロー・アンフォロー・ジョイン等のイベント処理欠落
`MessagesController.php` は `event.type === 'message'` しか分岐していない。以下が全て無視されている:
- `follow` / `unfollow`（友達追加解除、ブロック検知でFriend.activeフラグ更新必須）
- `postback`（Rich Menu タップ、Flex Button のデータ取得）
- `memberJoined` / `memberLeft`（グループ運用時）
- `beacon`、`accountLink`（LINE Login紐付け）
- `videoPlayComplete`（RichVideo視聴完了トリガ）

`follow` を無視しているため、**ブロック済み友達にPushを投げ続け課金**されている可能性大。

## Medium

### M1. Flex Message / Quick Reply / Imagemap の完全未使用
`BuildReplyMessageRequestAction.php` と `BuildPushMessageRequestAction.php` は **text/image/video/audio の4種のみ**。Flex Message（JSON記述のカルーセル/クーポン表示）、Quick Reply（サジェストチップ）、Imagemap（タップ領域付き画像）を一切送っていない。クーポンSaaSとしてこれは致命的でCVRを半分以下にしている。

### M2. Rich Menu の per-user alias・A/Bテスト未対応
`CreateRichMenuLineJob.php` / `DeleteRichMenuLineJob.php` は単一メニューを扱うのみ。SDK v9 の `linkRichMenuIdToUser`, `createRichMenuAlias`, `setDefaultRichMenu` を使い、**ランク別メニュー切替**や**タブ切替UI**（親子Rich Menu + `richmenuswitch` action）が可能。`rich_menus.parent_id` カラムがあるのに使い切れていない。

### M3. LINE Content API のブロブ取得が未活用
`GetMessageContentAction.php` は `getRealPath()` を返すだけで、呼び出し側（MessagesController の`if`文）がコメントアウト済。友達が送った画像・動画・音声をDBに保存できておらず、**サポートチャット運用が破綻**。

### M4. LINE Login / LIFF 未導入
ポイント・リファーラル管理SaaSなのに Web での本人確認が LINE ID 紐付けできていない。`LinkController.php` `ReferralController.php` は存在するが LINE Login SDK の `id_token` 検証は未実装と推察。LIFFを導入すれば:
- クーポン詳細ページを LINE 内ブラウザで表示（`liff.getAccessToken()`でuser_id取得）
- `liff.sendMessages()` でシェア促進リファーラル
- `liff.scanCodeV2()` で店舗QRコード消込

### M5. Insight / Statistics API 未呼び出し
配信効果計測が皆無。`getNumberOfSentBroadcastMessages`, `getMessageDeliveries`, `getNumberOfFollowers`, `getFriendsDemographics`, `getInsightMessageEvent(requestId)` を日次Scheduleで叩きダッシュボード化すべき。

## Low

### L1. `config/line-bot.php` の `X-Foo: Bar` が残骸
テンプレ由来の不要ヘッダが本番送信されている。削除。

### L2. `routes/api.php` が未使用
LIFF用 API エンドポイントを後述の拡張で入れる前提で、`Sanctum` + LINE id_token ミドルウェアを整備。

### L3. `env('MEDIA_DISK')` をランタイム参照
`ReplyMessageAction.php` L50 が `env()` 直呼び。`config()`経由に。

### L4. SDK v9 の `WebhookParser`・型付きEventクラス未使用
`$data['events']` を生配列で扱っており型安全性ゼロ。v9 の `MessageEvent`, `FollowEvent`, `PostbackEvent` クラスで受けるべき。

## LINE API を最大限活用した機能拡張提案

1. **LIFFクーポンウォレット**: `/liff/coupons` を Vue/React で実装、`liff.init` → `getIDToken` 検証 → Friend に紐付け → 抽選/消込/残ポイント表示。Flex Message のボタンから `uri` action でLIFFを開き即配布。
2. **Narrowcast + Audience Sync**: `friends.mark` 変化を Observer で検知し `createAudienceGroup` / `addAudienceToAudienceGroup` で LINE Audience に同期。配信は `narrowcast` に全移行、A/B テスト(`recipient.and/or/not`演算子)で件名分割配信。
3. **リッチメニュー per-user切替**: 会員ランク変動時に `linkRichMenuIdToUser`、タブ式UIは `richmenuswitch` action + `createRichMenuAlias`で実装。
4. **Postback駆動のクーポン抽選**: Flex Message の Button に `postback` data=`coupon:gacha:{id}` を付け、Webhookで抽選 → Reply Flex で結果表示 → 未使用なら Push リマインド。
5. **Insight Dashboard**: Filament に `Widget` を追加、`getInsightMessageEvent`/`getFriendsDemographics` を毎時キャッシュし、ブランド別にKPI可視化。
6. **LINE Login + Sanctum SSO**: ブランド管理者でも友達でも`id_token`で一貫ログイン、リファーラル追跡に `state` パラメータを利用。
7. **videoPlayComplete** でRichVideo視聴完了 → ポイント付与のゲーミフィケーション。
8. **Messaging API Quota監視**: `getMessageQuota` + `getMessageQuotaConsumption` を毎時 Scheduler でチェックし 80% 超でSlackアラート、Broadcastを自動停止。

Critical 3点を先に着手すれば本番のセキュリティ・コスト事故を確実に防げる。High帯はSaaSの利益率に直結するため2スプリント以内での着手を推奨。

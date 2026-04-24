# セキュリティ監査レポート

## エグゼクティブサマリー

LINE友達 (Friend) の個人情報・ポイントを扱う性質上、**個人情報保護法 (APPI) 及びGDPRの対象**となるため、下記 Critical 項目は即時対応が必要。

## Critical (即時対応・48時間以内)

### C-1. `.env.example` に本番鍵が混入している疑い
- 該当: `.env.example:3`
- 問題: `APP_KEY=base64:r6DLrlnBkaeWVN0hGehA282fehq95a0NTaLMBCOxhQk=` がハードコード。同じ鍵が本番で使われていればセッション改ざん・暗号化カラム復号が可能。
- 修正:
  ```bash
  # .env.example の APP_KEY は空欄にする
  APP_KEY=
  # 全環境でローテート
  php artisan key:generate --force
  ```
  既存の `encrypted` カラム (アクセストークン等) は再暗号化が必要。

### C-2. LINE Webhook 署名検証の欠落リスク
- 攻撃者が偽友達イベント・ポイント付与イベントを注入可能。
- 必須実装:
  ```php
  $signature = $request->header('X-Line-Signature');
  $hash = base64_encode(hash_hmac('sha256', $request->getContent(), config('services.line.channel_secret'), true));
  abort_unless(hash_equals($hash, $signature ?? ''), 401);
  ```
  または `linecorp/line-bot-sdk` の `SignatureValidator::validateSignature()` を使用。Webhookルートは `VerifyCsrfToken` の `$except` に追加し、代わりに専用 Middleware (`ValidateLineSignature`) を必ず配置。

### C-3. API ルート設計 - Rate Limit 未適用
- `routes/api.php` には `auth:sanctum` の `/user` のみ。
- Webhook ルートには必ず `throttle:line-webhook` (60/min 程度) を設定。
  ```php
  RateLimiter::for('line-webhook', fn($req) => Limit::perMinute(300)->by($req->ip()));
  ```

### C-4. `APP_DEBUG=true` かつ `laravel-debugbar` / `ignition` が本番混入
- `.env.example` は `APP_DEBUG=true`。誤って本番で debug=true になると `ignition` がスタックトレース・ENV・DB値を露出。
- 修正: `.env.production` は必ず `APP_DEBUG=false`、デプロイ時 `composer install --no-dev --optimize-autoloader`。

## High (30日以内)

### H-1. Filament 認可/Policy/2FA 未確認
- `spatie/laravel-activitylog` は入っているが、`filament-shield` / `spatie/laravel-permission` が `composer.json` に無い。つまり **RBAC が未導入の可能性大**。
- 推奨導入: `bezhansalleh/filament-shield`, `pragmarx/google2fa-laravel`。
  ```bash
  composer require bezhansalleh/filament-shield spatie/laravel-permission
  php artisan shield:install --fresh
  ```

### H-2. Sentry PII スクラビング未設定の疑い
- LINE userId, 氏名, メッセージ本文が `before_send` で除去されていないと Sentry 側に個人情報蓄積 → APPI第27条（第三者提供）違反の可能性。
- 修正:
  ```php
  'send_default_pii' => false,
  'before_send' => function (\Sentry\Event $event): ?\Sentry\Event {
      $req = $event->getRequest();
      foreach (['line_user_id','name','message','access_token'] as $k) {
          if (isset($req['data'][$k])) $req['data'][$k] = '[Filtered]';
      }
      $event->setRequest($req);
      return $event;
  },
  ```

### H-3. Sanctum トークン有効期限未設定
- デフォルトで `expiration => null`（無期限）。漏洩時のブラスト半径が大。
- 修正: `'expiration' => 60 * 24 * 7` (7日)、`token_prefix` 設定で GitHub シークレットスキャン対応。

### H-4. Filament パスワードポリシー/ログイン試行制限
- Filament 3 はデフォルトでパスワード強度ルール無し、ログイン失敗ロックアウト無し。
- 対応:
  ```php
  ->authPasswordBroker('users')
  ->login()
  ->middleware(['throttle:5,1'], isPersistent: true)
  ```

### H-5. S3 直公開可能性
- バケットが `public-read` のままだと URL 類推で流出。
- 修正: `media` disk を `visibility => 'private'`、取得は `Storage::temporaryUrl($path, now()->addMinutes(10))` で署名URL。

### H-6. 依存パッケージ既知脆弱性リスク
- `league/flysystem-aws-s3-v3 3.0` 固定は脆弱性パッチを受け取らない致命的構成。`^3.28` に変更し、`composer audit` を CI 必須化。

## Medium (60-90日)

### M-1. 友達データ監査ログ粒度
- APPI 開示請求対応として参照イベントも `viewed` として記録。

### M-2. Mass Assignment
- Friend モデルは `$fillable` を厳格定義し、`line_user_id`, `points` 等の重要カラムは必ず Action 層で上書き。`$guarded = []` は禁止。

### M-3. IDOR
- Filament Resource の URL は `/admin/friends/{id}` の連番露出。ULID/UUID に変更:
  ```php
  public function getRouteKeyName(): string { return 'ulid'; }
  ```

### M-4. SQLインジェクション
- `spatie/laravel-query-builder` 使用時は `allowedFilters/Sorts` で **ホワイトリスト必須**。`whereRaw`, `DB::raw`, `selectRaw` はプロジェクト全体で grep。

### M-5. ファイルアップロード検証
- Media Library 使用時 `registerMediaCollections` で `acceptsMimeTypes(['image/jpeg','image/png'])` と `->maxFilesize(5 * 1024 * 1024)`。

### M-6. CSP / セキュリティヘッダー
- `bepsvpt/secure-headers` 未導入。`Content-Security-Policy`, `Strict-Transport-Security`, `X-Frame-Options: DENY`, `Referrer-Policy: strict-origin-when-cross-origin` を middleware で付与。

### M-7. CORS 設定
- デフォルトで `allowed_origins => ['*']` は危険。明示ドメインのみ、`supports_credentials => true`。

## Low

### L-1. ログ漏洩
- `LINE_BOT_CHANNEL_ACCESS_TOKEN` や `access_token` を `Log::info()` していないか grep。

### L-2. Session
- 本番は `redis` 推奨、`SESSION_SECURE_COOKIE=true`, `SESSION_SAME_SITE=lax`。

## コンプライアンス観点

| 要件 | APPI | GDPR | 状況 |
|---|---|---|---|
| 取得目的の明示 | 第21条 | Art.13 | プライバシーポリシー要整備 |
| 安全管理措置 | 第23条 | Art.32 | 暗号化・RBAC不足 |
| 開示/訂正/削除請求 | 第33-35条 | Art.15-17 | Friend削除フロー・エクスポート機能要実装 |
| 保有期間 | - | Art.5(1)(e) | `friends.deleted_at` + 退会後180日物理削除ジョブ |
| 越境移転 | 第28条 | Chapter V | Sentry (独) / AWS リージョン明示 |
| 漏洩通知 | 第26条 | Art.33 | 72時間以内通知フロー策定 |

## 推奨パッケージ

```bash
composer require bezhansalleh/filament-shield
composer require spatie/laravel-permission
composer require pragmarx/google2fa-laravel
composer require bepsvpt/secure-headers
composer require spatie/laravel-backup
composer require --dev enlightn/enlightn
```

## 90日セキュリティロードマップ

### Day 1-14 (Critical)
1. `APP_KEY` 全環境ローテーション + `.env.example` サニタイズ
2. LINE Webhook 署名検証 middleware 実装・CI テスト追加
3. 本番 `APP_DEBUG=false` 強制 + debugbar/ignition 本番除外確認
4. Webhook / API 全ルートに Rate Limiter 設定
5. `composer audit` を GitHub Actions 必須ジョブ化

### Day 15-45 (High)
6. Filament Shield + spatie/permission 導入、Friend/User/Point ごとに Policy
7. 管理者 2FA (google2fa) 必須化
8. Sentry `before_send` で PII スクラビング、`send_default_pii=false`
9. Sanctum トークン 7日期限 + プレフィックス
10. S3 private bucket 移行 + 署名付きURL へ切替

### Day 46-75 (Medium)
11. Friend の ULID 化で IDOR 解消
12. `whereRaw` / `DB::raw` 全件監査
13. CSP / HSTS / X-Frame-Options ヘッダ追加
14. Media Library MIME/サイズ検証 + ウイルススキャン
15. Activity Log に `viewed` イベント追加 (開示請求対応)

### Day 76-90 (Compliance)
16. プライバシーポリシー・利用規約改訂、越境移転同意取得
17. Friend データ削除/エクスポート API 実装 (APPI第33-35条, GDPR Art.15-17)
18. 漏洩時 72時間通知手順・CSIRTランブック策定
19. 年次ペネトレーションテスト契約、四半期内部監査サイクル開始
20. 従業員向け個人情報取扱い研修・アクセス棚卸し (四半期毎)

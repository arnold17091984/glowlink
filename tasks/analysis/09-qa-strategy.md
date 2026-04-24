# テスト戦略・品質保証設計

## 現状ギャップ評価

### Critical (即時対応必須)
- **LINE Webhook署名検証が未テスト** — `/messages` エンドポイントは署名検証コードが存在せず、悪意あるリクエストを受け入れる状態
- **`RedeemRewardAction` の抽選ロジックが検証ゼロ** — `mt_rand(0, 100) <= win_rate` による確率判定は統計的テストなしでは正確性を保証できない
- **Factoryが全て空** — `CouponFactory`、`FriendFactory` など既存11ファイル全ての `definition()` が空配列を返す

### High (Week 1-4)
- `ReferralAction` の二重登録防止ロジックが未テスト
- `BroadcastCommand` のスケジューリング分岐が未テスト
- `MessageDeliveriesAction` が `LINEMessagingApi` ファサードを直接呼び出し

### Medium (Week 5-10)
- Filament Resource の CRUD / Form バリデーション未テスト
- `ScenarioDeliveriesCommand` の状態遷移 (PENDING → ONGOING → COMPLETED) 未テスト
- `AutoResponseAction` のキーワードマッチングロジック未テスト

### Low (Week 11-12)
- Visual regression (Filament UI)
- Accessibility (axe-core)
- Mutation testing

## LINE API Fake 戦略

`LINEMessagingApi` は Laravel Facade として登録されているため、`Mockery` と `Http::fake()` の二段構えで対応。

**推奨アーキテクチャ: `FakesLineApi` トレイト**

```php
// tests/Concerns/FakesLineApi.php
namespace Tests\Concerns;

use LINE\Laravel\Facades\LINEMessagingApi;

trait FakesLineApi
{
    protected function fakeLineApi(): \Mockery\MockInterface
    {
        $mock = \Mockery::mock(\LINE\Clients\MessagingApi\Api\MessagingApiApi::class);
        $mock->shouldReceive('pushMessage')->andReturn(new \stdClass());
        $mock->shouldReceive('replyMessage')->andReturn(new \stdClass());
        $mock->shouldReceive('getProfile')->andReturn([
            'userId' => 'Utest123',
            'displayName' => 'Test User',
            'pictureUrl' => 'https://example.com/pic.jpg',
        ]);
        $this->app->instance(\LINE\Clients\MessagingApi\Api\MessagingApiApi::class, $mock);
        return $mock;
    }

    protected function signedWebhookRequest(array $payload): \Illuminate\Testing\TestResponse
    {
        $body = json_encode($payload);
        $signature = base64_encode(hash_hmac('sha256', $body, config('line.channel_secret'), true));
        return $this->postJson('/messages', $payload, ['X-Line-Signature' => $signature]);
    }
}
```

## 重要テストケース例

### 1. Webhook 署名検証 (Critical)

```php
it('rejects webhook without valid signature', function () {
    $response = $this->postJson('/messages', ['destination' => 'U123', 'events' => []], [
        'X-Line-Signature' => 'invalid_signature',
    ]);
    $response->assertStatus(400);
});

it('accepts webhook with valid HMAC-SHA256 signature', function () {
    $this->fakeLineApi();
    $response = $this->signedWebhookRequest($this->buildWebhookPayload('message'));
    $response->assertStatus(200);
});
```

### 2. 抽選確率統計テスト (Critical)

```php
it('win rate of 50% produces wins within statistical tolerance', function () {
    $coupon = Coupon::factory()->create([
        'is_lottery' => true,
        'win_rate'   => 50,
    ]);

    $wins = 0;
    $trials = 10000;
    $action = app(\App\Actions\Coupon\RedeemRewardAction::class);
    $method = new \ReflectionMethod($action, 'isWinner');

    for ($i = 0; $i < $trials; $i++) {
        if ($method->invoke($action, $coupon)) $wins++;
    }

    $winRate = ($wins / $trials) * 100;
    expect($winRate)->toBeGreaterThan(47)->toBeLessThan(53);
});
```

### 3. Referral 報酬トランザクション (High)

```php
it('awards points to both referrer and new user atomically', function () {
    $referral = Referral::factory()->create([
        'referrer_awarded_points'     => 100,
        'referral_acceptance_points'  => 50,
    ]);
    $referrer = Friend::factory()->create(['user_id' => 'U_referrer', 'points' => 0]);

    app(ReferralAction::class)->execute(ReferralData::fromArray([
        'referral_name' => $referral->name,
        'user_id'       => 'U_newuser',
        'referred_by'   => 'U_referrer',
        'name'          => 'New User',
        'profile_url'   => 'https://example.com/pic.jpg',
    ]));

    expect($referrer->fresh()->points)->toBe(100);
    expect(Friend::whereUserId('U_newuser')->first()->points)->toBe(50);
});

it('throws exception when user tries to refer themselves', function () {
    expect(fn () => app(ReferralAction::class)->execute(
        ReferralData::fromArray(['user_id' => 'U123', 'referred_by' => 'U123'])
    ))->toThrow(\Exception::class, "You can't refer yourself");
});
```

## 90日テスト戦略ロードマップ

### Week 1-2: 土台整備
- `phpunit.xml` に `<coverage>` タグ追加
- 全 Factory の `definition()` を実装
- `tests/Concerns/FakesLineApi.php` トレイト作成
- GitHub Actions パイプライン初期構築
- Larastan `phpstan.neon` の level を 5 に設定

### Week 3-6: Core Action テスト
- `RedeemRewardAction` — 7つの分岐全テスト
- `ReferralAction` — 自己紹介エラー・ポイント付与・二重登録防止
- `BroadcastMessageAction` — 'all' フィルタ vs マーク別フィルタ
- `StoreFriendAction` — 新規登録 / 既存更新

### Week 7-10: Webhook & Job テスト、Feature テスト
- Webhook 署名検証テスト
- `BroadcastCommand` / `ScenarioDeliveriesCommand` の全スケジューリング分岐
- `BroadcastingJob` / `ScenarioDeliveriesJob` のディスパッチ検証
- Filament Resource CRUD テスト

### Week 11-12: E2E・負荷テスト・Mutation
- Laravel Dusk による管理者オペレーション E2E
- k6 による大量配信シミュレーション
- Infection (Mutation testing)
- `composer audit` + GitHub Actions の依存脆弱性スキャン自動化

## CI/CD パイプライン (GitHub Actions)

```yaml
# .github/workflows/ci.yml
name: CI
on:
  push: { branches: [main, develop] }
  pull_request:

jobs:
  test:
    runs-on: ubuntu-latest
    services:
      mysql:
        image: mysql:8.0
        env: { MYSQL_DATABASE: testing, MYSQL_ROOT_PASSWORD: password }
        options: --health-cmd="mysqladmin ping"

    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          extensions: mbstring, pdo_mysql, pcov
          coverage: pcov
      - run: composer install --no-interaction --prefer-dist
      - run: cp .env.example .env.testing && php artisan key:generate --env=testing
      - run: php artisan migrate --env=testing --force
      - run: composer analyse
      - run: composer format-dry-run
      - run: composer test-coverage -- --min=60
      - run: composer audit
```

## Larastan 設定

```neon
# phpstan.neon
includes:
    - vendor/larastan/larastan/extension.neon

parameters:
    paths: [app]
    level: 5
    excludePaths: [app/Http/Middleware/]
```

Level 5 からスタートし、ベースライン (`phpstan-baseline.neon`) を生成してゼロエラー状態を維持しながら段階的に level 7 まで引き上げる。

## ブラウザテスト選定

**Laravel Dusk を推奨**。Filament の Livewire コンポーネントとの親和性が高く、`actingAs()` などの Laravel ヘルパーがそのまま使える。Playwright/Cypress は JSON API テストには優秀だが、Filament の Alpine.js + Livewire スタックでの安定性が Dusk より低い。

## 最優先アクション

1. `database/factories/CouponFactory.php` ほか全Factoryの `definition()` 実装 — これがないと他テストが全て書けない
2. `tests/Concerns/FakesLineApi.php` の作成
3. `phpunit.xml` に `<coverage>` ブロック追加と、`phpstan.neon` のレベル設定

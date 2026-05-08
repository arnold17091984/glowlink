<?php

namespace App\Domains\LineIntegration;

use App\Domains\LineIntegration\Gateway\LineGateway;
use App\Domains\LineIntegration\Gateway\LineGatewayManager;
use App\Domains\LineIntegration\Gateway\LineMessagingApiGateway;
use Illuminate\Support\ServiceProvider;

/**
 * LineGatewayManager と LineGateway (デフォルト) を DI コンテナに登録する。
 *
 *   app(LineGatewayManager::class)  → 複数チャネル対応 (推奨)
 *   app(LineGateway::class)         → デフォルトチャネル (legacy互換)
 *
 * config/app.php providers 配列に登録済。
 */
class LineIntegrationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(LineGatewayManager::class, function ($app) {
            return new LineGatewayManager($app);
        });

        $this->app->bind(LineGateway::class, function ($app) {
            return $app->make(LineGatewayManager::class)->default();
        });
    }

    public function boot(): void
    {
        // 将来的に Sentry breadcrumb 等の横断観測を追加。
    }
}

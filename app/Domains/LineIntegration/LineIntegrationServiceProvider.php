<?php

namespace App\Domains\LineIntegration;

use App\Domains\LineIntegration\Gateway\LineGateway;
use App\Domains\LineIntegration\Gateway\LineMessagingApiGateway;
use Illuminate\Support\ServiceProvider;
use LINE\Clients\MessagingApi\Api\MessagingApiApi;

/**
 * LineGateway を DI コンテナに登録する。
 *
 * config/app.php の providers 配列にこのクラスを追加することで、
 * `app(LineGateway::class)` からアプリ全体で統一インターフェース経由で LINE API を叩ける。
 *
 * テスト時は AppServiceProvider::boot() やテストケース内で
 *   $this->app->instance(LineGateway::class, new FakeLineGateway());
 * に差し替える。
 */
class LineIntegrationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(LineGateway::class, function ($app) {
            return new LineMessagingApiGateway(
                $app->make(MessagingApiApi::class)
            );
        });
    }

    public function boot(): void
    {
        // 将来的にここで LINE SDK の observer を張ったり Sentry breadcrumb 追加等を行う。
    }
}

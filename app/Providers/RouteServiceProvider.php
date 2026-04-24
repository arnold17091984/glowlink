<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // LINE Webhook 用レートリミット。
        // LINE は1秒あたり数百イベントをバーストする仕様なので分単位で広めに取る。
        RateLimiter::for('line-webhook', function (Request $request) {
            return Limit::perMinute(600)->by($request->ip());
        });

        // LIFF API 用 (1 友達あたり約 30 req/分 = 数秒に 1 リクエスト相当)。
        RateLimiter::for('liff-api', function (Request $request) {
            return Limit::perMinute(30)->by($request->bearerToken() ?: $request->ip());
        });

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}

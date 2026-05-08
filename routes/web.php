<?php

use App\Http\Controllers\MessagesController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::redirect('/', '/admin');

/*
| LINE Webhook
| - /messages          : default channel (legacy)
| - /messages/{channel}: per-channel multi-tenant routing
|
| Spatie route-attributes は config/route-attributes.php の prefix=api 設定で
| /api/messages/... に登録してしまうため、ここで明示的に root に登録する。
| LINE Developers Console の Webhook 検証が想定する URL は /messages/{slug}。
*/
Route::post('/messages', MessagesController::class)
    ->middleware(['line.signature', 'throttle:line-webhook'])
    ->withoutMiddleware([
        \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
        \Illuminate\Cookie\Middleware\EncryptCookies::class,
        \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
        \Illuminate\Session\Middleware\StartSession::class,
        \Illuminate\View\Middleware\ShareErrorsFromSession::class,
    ])
    ->name('line.webhook.default');

Route::post('/messages/{channel}', MessagesController::class)
    ->middleware(['line.signature', 'throttle:line-webhook'])
    ->withoutMiddleware([
        \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
        \Illuminate\Cookie\Middleware\EncryptCookies::class,
        \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
        \Illuminate\Session\Middleware\StartSession::class,
        \Illuminate\View\Middleware\ShareErrorsFromSession::class,
    ])
    ->where('channel', '[a-z0-9\-_]+')
    ->name('line.webhook.channel');

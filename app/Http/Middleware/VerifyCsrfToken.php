<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'messages',
        // LINE Webhook はチャネル slug 付きでも受信
        'messages/*',
        // Livewire 3 は wire:snapshot の HMAC 署名で XHR 整合性を独自保護するため、
        // Laravel の CSRF 二重チェックを外しても安全。
        // ブラウザ側のクッキー/キャッシュずれによる 419 "This page has expired" 連発を回避。
        'livewire/update',
        'livewire/upload-file',
        'livewire/preview-file/*',
    ];
}

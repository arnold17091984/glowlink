<?php

namespace App\Filament\Pages\Auth;

use Filament\Pages\Auth\Login as BaseLogin;

/**
 * glowlink 専用の split-layout 版ログインページ。
 *
 * Filament 標準の SimplePage (中央 420px カード) を流用しつつ、
 * theme.css 側で .fi-simple-main を full-bleed grid に再構成する。
 */
class Login extends BaseLogin
{
    protected static string $view = 'filament.pages.auth.login';

    public function getHeading(): string
    {
        return 'Access Terminal';
    }

    public function hasLogo(): bool
    {
        return false;
    }
}

<?php

namespace App\Providers;

use BezhanSalleh\FilamentLanguageSwitch\LanguageSwitch;
use Filament\Tables\Columns\Column;
use Filament\Tables\Table;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 本番 (APP_ENV=production) は必ず HTTPS で URL 生成。
        // nginx が SSL 終端しているため、TrustProxies と合わせて HTTPS を一貫保証。
        if ($this->app->environment('production') || env('APP_FORCE_HTTPS', false)) {
            URL::forceScheme('https');
        }

        Table::configureUsing(function (Table $table): void {
            $table->defaultSort('updated_at', 'desc');
        });

        Column::configureUsing(function (Column $column): void {
            $column
                ->searchable()
                ->toggleable()
                ->sortable();
        });

        LanguageSwitch::configureUsing(function (LanguageSwitch $switch) {
            $switch
                ->locales(['ja', 'en']) // also accepts a closure;
                ->flags([
                    'ja' => asset('https://flagicons.lipis.dev/flags/4x3/jp.svg'),
                    'en' => asset('https://flagicons.lipis.dev/flags/4x3/us.svg'),
                ]);
        });
    }
}

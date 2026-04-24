<?php

namespace App\Providers;

use BezhanSalleh\FilamentLanguageSwitch\LanguageSwitch;
use Filament\Tables\Columns\Column;
use Filament\Tables\Table;
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

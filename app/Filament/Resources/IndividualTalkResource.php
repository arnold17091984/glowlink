<?php

namespace App\Filament\Resources;

use App\Filament\Resources\IndividualTalkResource\Pages;
use Filament\Resources\Resource;

class IndividualTalkResource extends Resource
{
    protected static ?string $navigationGroup = '友だち管理';

    protected static ?string $navigationLabel = '個別トーク';

    protected static ?string  = '個別トーク';

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\Messages::route('/'),
        ];
    }
}

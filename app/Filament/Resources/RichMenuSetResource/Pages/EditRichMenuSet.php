<?php

namespace App\Filament\Resources\RichMenuSetResource\Pages;

use App\Actions\RichMenu\EditRichMenuAction;
use App\DataTransferObjects\RichMenuData;
use App\Filament\Resources\RichMenuSetResource;
use App\Jobs\DeleteRichMenuLineJob;
use App\Models\RichMenuSet;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EditRichMenuSet extends EditRecord
{
    protected static string $resource = RichMenuSetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()->using(function (RichMenuSet $richMenuSet) {
                foreach ($richMenuSet->richMenus as $richMenu) {
                    DeleteRichMenuLineJob::dispatch($richMenu);
                    $richMenu->delete();
                }

                return $richMenuSet->delete();
            }),
        ];
    }

    // protected function handleRecordUpdate(Model $record, array $data): Model
    // {
    //     $richMenu = DB::transaction(
    //         fn () => app(EditRichMenuAction::class)->execute(RichMenuData::fromArray($this->data), $record));

    //     return $richMenu;
    // }
}

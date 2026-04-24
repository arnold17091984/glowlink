<?php

namespace App\Filament\Resources\LineChannelResource\Pages;

use App\Filament\Resources\LineChannelResource;
use App\Models\LineChannel;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Http;

class EditLineChannel extends EditRecord
{
    protected static string $resource = LineChannelResource::class;

    /**
     * 暗号化カラムはフォーム表示時に復号しない (空欄のまま保存しても既存値を維持する dehydrated=false 相当)。
     * Channel Secret / Access Token は「未入力なら旧値を維持」という挙動にする。
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        // 表示時は空欄にしておき、ユーザーが編集時に入力した場合のみ更新
        $data['channel_secret'] = '';
        $data['channel_access_token'] = '';

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // 空欄で送られてきたら既存値を維持する
        $record = $this->getRecord();

        if (empty($data['channel_secret'] ?? null) && $record instanceof LineChannel) {
            $data['channel_secret'] = $record->channel_secret;
        }
        if (empty($data['channel_access_token'] ?? null) && $record instanceof LineChannel) {
            $data['channel_access_token'] = $record->channel_access_token;
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('testConnection')
                ->label('接続テスト')
                ->icon('heroicon-o-signal')
                ->color('success')
                ->action(function () {
                    $record = $this->getRecord();
                    try {
                        $response = Http::withToken($record->channel_access_token)
                            ->timeout(10)
                            ->get('https://api.line.me/v2/bot/info');
                        if ($response->successful()) {
                            $record->update(['last_connected_at' => now()]);
                            Notification::make()
                                ->title('接続成功')
                                ->body($response->json()['displayName'] ?? 'Connected')
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('接続失敗')
                                ->body('HTTP '.$response->status())
                                ->danger()
                                ->send();
                        }
                    } catch (\Throwable $e) {
                        Notification::make()->title('エラー')->body($e->getMessage())->danger()->send();
                    }
                }),
            Actions\DeleteAction::make(),
        ];
    }
}

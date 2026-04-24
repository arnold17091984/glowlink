<?php

namespace App\Actions\Friend;

use App\Enums\FlagEnum;
use App\Models\Friend;
use App\Models\User;
use Filament\Notifications\Notification;
use LINE\Laravel\Facades\LINEMessagingApi;

class StoreFriendAction
{
    public function execute($event)
    {
        $userId = $event['source']['userId'];
        $contentType = 'application/json';
        $response = LINEMessagingApi::getProfile($userId, $contentType);
        $friend = Friend::whereUserId($userId)->first();

        if (! $friend) {
            $friend = Friend::create([
                'user_id' => $response['userId'],
                'name' => $response['displayName'],
                'profile_url' => $response['pictureUrl'],
                'mark' => FlagEnum::UNRESOLVED,
            ]);

            Notification::make()
                ->icon('heroicon-o-envelope')
                ->iconColor('success')
                ->title('New Friend Notification')
                ->body($friend->name.' is added to your friendlist.')
                ->sendToDatabase(User::all());

        } else {
            $friend->update([
                'name' => $response['displayName'],
                'profile_url' => $response['pictureUrl'],
            ]);
        }
    }
}

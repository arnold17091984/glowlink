<?php

namespace App\Actions\Friend;

use App\Domains\LineIntegration\Gateway\LineGatewayManager;
use App\Enums\FlagEnum;
use App\Models\Friend;
use App\Models\LineChannel;
use App\Models\User;
use Filament\Notifications\Notification;

class StoreFriendAction
{
    public function __construct(protected LineGatewayManager $gateways)
    {
    }

    public function execute($event, ?LineChannel $channel = null)
    {
        $userId = $event['source']['userId'];

        // Webhook を受けたチャネルを使ってプロフィールを取得 (per-channel)
        $gateway = $channel
            ? $this->gateways->forChannel($channel)
            : $this->gateways->default();

        $response = $gateway->getProfile($userId);
        $friend = Friend::whereUserId($userId)->first();

        $payload = [
            'user_id' => $response['userId'] ?? $userId,
            'name' => $response['displayName'] ?? '(unknown)',
            'profile_url' => $response['pictureUrl'] ?? null,
            'mark' => FlagEnum::UNRESOLVED,
            'line_channel_id' => $channel?->id,
        ];

        if (! $friend) {
            $friend = Friend::create($payload);

            Notification::make()
                ->icon('heroicon-o-envelope')
                ->iconColor('success')
                ->title('新しい友だち')
                ->body($friend->name.' が友だち追加されました')
                ->sendToDatabase(User::all());
        } else {
            $update = [
                'name' => $payload['name'],
                'profile_url' => $payload['profile_url'],
            ];
            if ($channel && empty($friend->line_channel_id)) {
                $update['line_channel_id'] = $channel->id;
            }
            $friend->update($update);
        }

        return $friend;
    }
}

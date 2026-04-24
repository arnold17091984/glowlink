<?php

namespace App\Http\Controllers;

use App\Actions\AutoResponse\AutoResponseAction;
use App\Actions\Friend\StoreFriendAction;
use App\Actions\LineMessage\GetMessageContentAction;
use App\Actions\Talk\StoreChatAction;
use App\Enums\MessagingTypeEnum;
use App\Models\Friend;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;

class MessagesController extends Controller
{
    public function __construct(
        protected StoreChatAction $storeChatAction,
        protected StoreFriendAction $storeFriendAction,
        protected AutoResponseAction $autoResponseAction,
        protected GetMessageContentAction $getMessageContentAction
    ) {
    }

    #[Post('messages', middleware: ['line.signature', 'throttle:line-webhook'])]
    public function __invoke(Request $request)
    {
        $data = $request->json()->all();

        if (! isset($data['events']) || ! is_array($data['events'])) {
            return response()->json(['status' => 'ok']);
        }

        foreach ($data['events'] as $event) {
            try {
                match ($event['type'] ?? null) {
                    'message' => $this->handleMessage($event),
                    'follow' => $this->handleFollow($event),
                    'unfollow' => $this->handleUnfollow($event),
                    default => null,
                };
            } catch (\Throwable $e) {
                // 個別イベントの失敗でWebhook全体を失敗させない (LINE側の再送ポリシーで二重配信されないように)
                Log::warning('LINE webhook event handling failed', [
                    'event_type' => $event['type'] ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);
                report($e);
            }
        }

        return response()->json(['status' => 'ok']);
    }

    private function handleMessage(array $event): void
    {
        $this->storeFriendAction->execute($event);
        $this->storeChatAction->execute($event);
        $this->autoResponseAction->execute($event);
    }

    private function handleFollow(array $event): void
    {
        $this->storeFriendAction->execute($event);
        Friend::whereUserId($event['source']['userId'] ?? '')->update(['mark' => 'unresolved']);
    }

    private function handleUnfollow(array $event): void
    {
        // ブロック済みユーザーへの Push を止めるためマークを更新 (ENUM 変更は別マイグで扱う)
        $userId = $event['source']['userId'] ?? null;
        if (! $userId) {
            return;
        }
        Log::info('LINE friend unfollowed/blocked', ['user_id' => $userId]);
        Friend::whereUserId($userId)->update(['mark' => 'already_resolved']);
    }
}

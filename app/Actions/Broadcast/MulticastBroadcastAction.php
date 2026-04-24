<?php

namespace App\Actions\Broadcast;

use App\Actions\LineMessagingRequest\BuildPushMessageRequestAction;
use App\Actions\LineMessagingRequest\RichCard\BuildPushRichCardRequestAction;
use App\Actions\LineMessagingRequest\RichMessage\BuildPushRichMessageRequestAction;
use App\Actions\LineMessagingRequest\RichVideo\BuildPushRichVideoRequestAction;
use App\Enums\MessagingTypeEnum;
use App\Jobs\SendMulticastChunkJob;
use App\Models\Friend;
use App\Models\Message;
use App\Models\RichCard;
use App\Models\RichMessage;
use App\Models\RichVideo;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;
use Throwable;

/**
 * 友だちへのブロードキャスト配信。
 *
 * 旧実装 (`BroadcastMessageAction`) は `Friend::all()` + Push per Friend で、
 * 友達 N 人に対して N 回 API を叩いていた。本 Action は以下の改善を行う:
 *
 * 1. `Friend::chunkById(500)` でメモリ一定
 * 2. LINE Multicast API (500 userId / req) で API 呼び出しを 1/500 に削減
 * 3. `Bus::batch()` で進捗可視化 / 失敗リカバリ
 * 4. 各 chunk に UUID の Retry Key を付与 (LINE 側で冪等化)
 *
 * 既存の旧 Action も残してあるため、段階的に切り替え可能。
 */
class MulticastBroadcastAction
{
    /** @var int LINE Multicast は 1 リクエストあたり最大 500 userId */
    public const CHUNK_SIZE = 500;

    public function __construct(
        protected BuildPushMessageRequestAction $buildPushMessageRequestAction,
        protected BuildPushRichMessageRequestAction $buildPushRichMessageRequestAction,
        protected BuildPushRichVideoRequestAction $buildPushRichVideoRequestAction,
        protected BuildPushRichCardRequestAction $buildPushRichCardRequestAction,
    ) {
    }

    public function execute(RichCard|RichVideo|RichMessage|Message $message, string $sendTo): Batch
    {
        $messagesPayload = $this->buildMessagesPayload($message);

        $query = Friend::query()->select(['id', 'user_id', 'mark']);
        if ($sendTo !== 'all') {
            $query->where('mark', $sendTo);
        }

        $jobs = [];
        $query->orderBy('id')->chunkById(self::CHUNK_SIZE, function ($friends) use (&$jobs, $messagesPayload, $message) {
            $userIds = $friends->pluck('user_id')->filter()->values()->all();
            if (empty($userIds)) {
                return;
            }

            $jobs[] = new SendMulticastChunkJob(
                userIds: $userIds,
                messages: $messagesPayload,
                retryKey: (string) Str::uuid(),
                originalMessageType: get_class($message),
                originalMessageId: $message->id,
            );
        });

        return Bus::batch($jobs)
            ->name('broadcast:'.get_class($message).':'.$message->id)
            ->onQueue('broadcasts')
            ->allowFailures()
            ->catch(function (Batch $batch, Throwable $e) {
                // バッチ全体レベルの失敗。個別 Job の失敗は各 Job の failed() で扱う。
                report($e);
            })
            ->dispatch();
    }

    /**
     * Message モデルから LINE Messaging API の messages 配列 ($push->getMessages()) を抽出し、
     * Multicast でそのまま流用する。
     *
     * 仮の userId (`U0_multicast_template`) をビルダーに渡して body を取り、
     * 後段で SendMulticastChunkJob が MulticastRequest の to=[...] に差し替える。
     */
    private function buildMessagesPayload(RichCard|RichVideo|RichMessage|Message $message): array
    {
        $templateUserId = 'U0_multicast_template';

        $push = match (true) {
            $message instanceof Message => $this->buildPushMessageRequestAction->execute(
                $templateUserId,
                $message->type === MessagingTypeEnum::TEXT
                    ? ($message->message ?? '')
                    : $message->getFirstMediaUrl('messages'),
                $message->type->value
            ),
            $message instanceof RichMessage => $this->buildPushRichMessageRequestAction->execute(
                $message,
                $templateUserId,
                $message->layouts,
                $message->getFirstMediaUrl('messages'),
            ),
            $message instanceof RichVideo => $this->buildPushRichVideoRequestAction->execute(
                $templateUserId,
                $message,
                // 疑似的な Friend オブジェクトは不要。Builder 側で user_id 以外を参照しない前提。
                new Friend(['id' => 0, 'user_id' => $templateUserId]),
            ),
            $message instanceof RichCard => $this->buildPushRichCardRequestAction->execute(
                $templateUserId,
                $message,
                new Friend(['id' => 0, 'user_id' => $templateUserId]),
            ),
        };

        // PushMessageRequest::getMessages() は SDK v9 で array of message object を返す。
        // Multicast は JSON 構造が共通なので、そのまま配列化して渡す。
        $messages = $push->getMessages() ?? [];

        return array_map(fn ($m) => method_exists($m, 'jsonSerialize')
            ? $m->jsonSerialize()
            : (array) $m, is_array($messages) ? $messages : [$messages]);
    }
}

<?php

namespace App\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use LINE\Clients\MessagingApi\Model\MulticastRequest;
use LINE\Laravel\Facades\LINEMessagingApi;
use Sentry\Laravel\Integration;
use Throwable;

/**
 * 最大 500 件の userId へまとめて 1 回の Multicast API 呼び出しで配信するワーカー。
 *
 * - retryKey (UUID) を付与することで LINE 側が重複配信を抑止
 * - 429 / 5xx に対する指数バックオフ (Job 再試行)
 * - バッチ失敗時は Batch::catch() へ伝搬
 */
class SendMulticastChunkJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 300;

    public function __construct(
        public array $userIds,
        public array $messages,
        public string $retryKey,
        public string $originalMessageType,
        public int $originalMessageId,
    ) {
        $this->onQueue('broadcasts');
    }

    public function handle(): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        if (empty($this->userIds)) {
            return;
        }

        $request = new MulticastRequest([
            'to' => $this->userIds,
            'messages' => $this->messages,
            'notificationDisabled' => false,
        ]);

        // LINE SDK v9: multicastWithHttpInfo($request, $retryKey) で [response, status, headers] 取得可
        // retryKey の付与で一時障害時の再試行でも LINE 側が重複配信しない
        try {
            if (method_exists(LINEMessagingApi::getFacadeRoot(), 'multicastWithHttpInfo')) {
                [$body, $status, $headers] = LINEMessagingApi::multicastWithHttpInfo($request, $this->retryKey);
                if ($status >= 500 || $status === 429) {
                    throw new \RuntimeException("LINE multicast returned HTTP {$status}");
                }
            } else {
                LINEMessagingApi::multicast($request, $this->retryKey);
            }

            Log::info('LINE multicast sent', [
                'recipients' => count($this->userIds),
                'message_type' => $this->originalMessageType,
                'message_id' => $this->originalMessageId,
                'retry_key' => $this->retryKey,
            ]);
        } catch (Throwable $e) {
            Log::warning('LINE multicast failed, will retry', [
                'error' => $e->getMessage(),
                'recipients' => count($this->userIds),
                'retry_key' => $this->retryKey,
            ]);
            throw $e;
        }
    }

    public function backoff(): array
    {
        return [10, 60, 300];
    }

    public function failed(Throwable $exception): void
    {
        Log::error('SendMulticastChunkJob permanently failed', [
            'recipients' => count($this->userIds),
            'message_type' => $this->originalMessageType,
            'message_id' => $this->originalMessageId,
            'retry_key' => $this->retryKey,
            'error' => $exception->getMessage(),
        ]);

        Integration::captureUnhandledException($exception);
    }
}

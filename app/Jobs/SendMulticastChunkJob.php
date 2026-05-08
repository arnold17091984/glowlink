<?php

namespace App\Jobs;

use App\Domains\LineIntegration\Gateway\LineGatewayManager;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use LINE\Clients\MessagingApi\Model\MulticastRequest;
use Sentry\Laravel\Integration;
use Throwable;

/**
 * 最大 500 件の userId へまとめて 1 回の Multicast API 呼び出しで配信するワーカー。
 *
 * - retryKey (UUID) を付与することで LINE 側が重複配信を抑止
 * - 429 / 5xx に対する指数バックオフ (Job 再試行)
 * - LineGatewayManager 経由で channel-aware 配信に対応
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
        public ?int $lineChannelId = null,
    ) {
        $this->onQueue('broadcasts');
    }

    public function handle(LineGatewayManager $gateways): void
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

        $gateway = $gateways->forChannelId($this->lineChannelId);

        try {
            $result = $gateway->multicast($request, $this->retryKey);
            $status = (int) ($result['status'] ?? 0);
            if ($status >= 500 || $status === 429) {
                throw new \RuntimeException("LINE multicast returned HTTP {$status}");
            }

            Log::info('LINE multicast sent', [
                'recipients' => count($this->userIds),
                'message_type' => $this->originalMessageType,
                'message_id' => $this->originalMessageId,
                'channel_id' => $this->lineChannelId,
                'retry_key' => $this->retryKey,
                'status' => $status,
            ]);
        } catch (Throwable $e) {
            Log::warning('LINE multicast failed, will retry', [
                'error' => $e->getMessage(),
                'recipients' => count($this->userIds),
                'channel_id' => $this->lineChannelId,
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
            'channel_id' => $this->lineChannelId,
            'retry_key' => $this->retryKey,
            'error' => $exception->getMessage(),
        ]);

        Integration::captureUnhandledException($exception);
    }
}

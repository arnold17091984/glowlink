<?php

namespace App\Jobs;

use App\Actions\Broadcast\BroadcastMessageAction;
use App\Models\Message;
use App\Models\RichCard;
use App\Models\RichMessage;
use App\Models\RichVideo;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Sentry\Laravel\Integration;
use Throwable;

class BroadcastingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 900;

    protected RichCard|RichVideo|RichMessage|Message $message;

    protected string $sendTo;

    public function __construct(RichCard|RichVideo|RichMessage|Message $message, string $sendTo)
    {
        $this->message = $message;
        $this->sendTo = $sendTo;
        $this->onQueue('broadcasts');
    }

    public function handle(): void
    {
        app(BroadcastMessageAction::class)->execute($this->message, $this->sendTo);
    }

    /**
     * LINE API 429 / 5xx に対する指数バックオフ (秒)。
     */
    public function backoff(): array
    {
        return [10, 60, 300];
    }

    public function failed(Throwable $exception): void
    {
        Log::error('BroadcastingJob failed permanently', [
            'message_id' => $this->message->id ?? null,
            'message_class' => get_class($this->message),
            'send_to' => $this->sendTo,
            'exception' => $exception->getMessage(),
        ]);

        Integration::captureUnhandledException($exception);
    }
}

<?php

namespace App\Domains\LineIntegration\Gateway;

use LINE\Clients\MessagingApi\Model\BroadcastRequest;
use LINE\Clients\MessagingApi\Model\MulticastRequest;
use LINE\Clients\MessagingApi\Model\PushMessageRequest;
use LINE\Clients\MessagingApi\Model\ReplyMessageRequest;

/**
 * テスト時に LineGateway を差し替えるためのフェイク実装。
 *
 * 送信内容を配列に蓄積し、テストから assertSent() 等で検証できる。
 * 本格的な Consumer-Driven Contract テストをするなら PactPHP に移行可能。
 */
class FakeLineGateway implements LineGateway
{
    public array $pushed = [];

    public array $replied = [];

    public array $multicasted = [];

    public array $broadcasted = [];

    public int $quotaConsumption = 0;

    public function push(PushMessageRequest $request, ?string $retryKey = null): array
    {
        $this->pushed[] = ['request' => $request, 'retry_key' => $retryKey];

        return ['status' => 200, 'headers' => [], 'body' => (object) ['sentMessages' => []]];
    }

    public function reply(ReplyMessageRequest $request): array
    {
        $this->replied[] = ['request' => $request];

        return ['status' => 200, 'headers' => [], 'body' => (object) ['sentMessages' => []]];
    }

    public function multicast(MulticastRequest $request, ?string $retryKey = null): array
    {
        $this->multicasted[] = ['request' => $request, 'retry_key' => $retryKey];

        return ['status' => 200, 'headers' => [], 'body' => (object) ['sentMessages' => []]];
    }

    public function broadcast(BroadcastRequest $request, ?string $retryKey = null): array
    {
        $this->broadcasted[] = ['request' => $request, 'retry_key' => $retryKey];

        return ['status' => 200, 'headers' => [], 'body' => (object) ['sentMessages' => []]];
    }

    public function getProfile(string $userId): array
    {
        return [
            'userId' => $userId,
            'displayName' => 'Fake User',
            'pictureUrl' => 'https://example.com/fake.png',
        ];
    }

    public function getMessageQuotaConsumption(): int
    {
        return $this->quotaConsumption;
    }

    // ---- 検証ヘルパー ----
    public function assertPushed(callable $cb, int $expectedCount = 1): void
    {
        $matches = array_filter($this->pushed, fn ($p) => $cb($p['request'], $p['retry_key']));
        if (count($matches) !== $expectedCount) {
            throw new \RuntimeException(sprintf(
                'Expected %d pushed messages, got %d.',
                $expectedCount,
                count($matches)
            ));
        }
    }

    public function assertMulticasted(callable $cb, int $expectedCount = 1): void
    {
        $matches = array_filter($this->multicasted, fn ($p) => $cb($p['request'], $p['retry_key']));
        if (count($matches) !== $expectedCount) {
            throw new \RuntimeException(sprintf(
                'Expected %d multicasts, got %d.',
                $expectedCount,
                count($matches)
            ));
        }
    }
}

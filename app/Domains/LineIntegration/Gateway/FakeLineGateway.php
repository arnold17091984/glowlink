<?php

namespace App\Domains\LineIntegration\Gateway;

use LINE\Clients\MessagingApi\Model\BroadcastRequest;
use LINE\Clients\MessagingApi\Model\MulticastRequest;
use LINE\Clients\MessagingApi\Model\PushMessageRequest;
use LINE\Clients\MessagingApi\Model\ReplyMessageRequest;
use LINE\Clients\MessagingApi\Model\RichMenuAliasCreateRequest;
use LINE\Clients\MessagingApi\Model\RichMenuRequest;

/**
 * LineGateway インターフェイスを満たすフェイク実装。
 * テスト時に Manager の forChannel() の返り値を差し替えるか、
 * `app()->instance(LineGateway::class, new FakeLineGateway())` で利用。
 */
class FakeLineGateway implements LineGateway
{
    public array $pushed = [];

    public array $replied = [];

    public array $multicasted = [];

    public array $broadcasted = [];

    public array $createdRichMenus = [];

    public array $deletedRichMenus = [];

    public array $createdAliases = [];

    public array $deletedAliases = [];

    public array $linkedRichMenus = [];

    public int $quotaConsumption = 0;

    public string $nextRichMenuId = 'richmenu-fake-id';

    // ---- Messaging ----------------------------------------------------------
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

    // ---- Profile ------------------------------------------------------------
    public function getProfile(string $userId): array
    {
        return [
            'userId' => $userId,
            'displayName' => 'Fake User',
            'pictureUrl' => 'https://example.com/fake.png',
        ];
    }

    public function getMessageContent(string $messageId): string
    {
        return '/tmp/fake-line-content-'.$messageId;
    }

    // ---- Rich Menu ----------------------------------------------------------
    public function createRichMenu(RichMenuRequest $request): string
    {
        $id = $this->nextRichMenuId;
        $this->createdRichMenus[] = ['id' => $id, 'request' => $request];

        return $id;
    }

    public function setRichMenuImage(string $richMenuId, string $imagePath, string $contentType = 'image/png'): void
    {
        // no-op
    }

    public function deleteRichMenu(string $richMenuId): void
    {
        $this->deletedRichMenus[] = $richMenuId;
    }

    public function setDefaultRichMenu(string $richMenuId): void
    {
        // no-op
    }

    public function getRichMenuList(): array
    {
        return [];
    }

    public function createRichMenuAlias(RichMenuAliasCreateRequest $request): void
    {
        $this->createdAliases[] = $request;
    }

    public function deleteRichMenuAlias(string $aliasId): void
    {
        $this->deletedAliases[] = $aliasId;
    }

    public function getRichMenuAliasList(): array
    {
        return [];
    }

    public function linkRichMenuToUser(string $userId, string $richMenuId): void
    {
        $this->linkedRichMenus[] = ['user' => $userId, 'menu' => $richMenuId];
    }

    public function linkRichMenuToUsers(array $userIds, string $richMenuId): void
    {
        foreach ($userIds as $u) {
            $this->linkedRichMenus[] = ['user' => $u, 'menu' => $richMenuId];
        }
    }

    public function unlinkRichMenuFromUser(string $userId): void
    {
        $this->linkedRichMenus[] = ['user' => $userId, 'menu' => null];
    }

    // ---- Quota --------------------------------------------------------------
    public function getMessageQuotaConsumption(): int
    {
        return $this->quotaConsumption;
    }

    // ---- 検証ヘルパー -------------------------------------------------------
    public function assertPushed(callable $cb, int $expectedCount = 1): void
    {
        $matches = array_filter($this->pushed, fn ($p) => $cb($p['request'], $p['retry_key']));
        if (count($matches) !== $expectedCount) {
            throw new \RuntimeException(sprintf('Expected %d pushed, got %d.', $expectedCount, count($matches)));
        }
    }

    public function assertMulticasted(callable $cb, int $expectedCount = 1): void
    {
        $matches = array_filter($this->multicasted, fn ($p) => $cb($p['request'], $p['retry_key']));
        if (count($matches) !== $expectedCount) {
            throw new \RuntimeException(sprintf('Expected %d multicasts, got %d.', $expectedCount, count($matches)));
        }
    }
}

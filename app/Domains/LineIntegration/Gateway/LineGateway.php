<?php

namespace App\Domains\LineIntegration\Gateway;

use LINE\Clients\MessagingApi\Model\BroadcastRequest;
use LINE\Clients\MessagingApi\Model\MulticastRequest;
use LINE\Clients\MessagingApi\Model\PushMessageRequest;
use LINE\Clients\MessagingApi\Model\ReplyMessageRequest;
use LINE\Clients\MessagingApi\Model\RichMenuAliasCreateRequest;
use LINE\Clients\MessagingApi\Model\RichMenuRequest;

/**
 * 1 LINE 公式アカウント (チャネル) に対する Messaging API 呼び出しの境界。
 *
 * 各実装は固有の channel_access_token / channel_secret を保持する想定。
 * テスト時は FakeLineGateway に差し替え可能。
 */
interface LineGateway
{
    // ============ Messaging =================================================
    public function push(PushMessageRequest $request, ?string $retryKey = null): array;

    public function reply(ReplyMessageRequest $request): array;

    public function multicast(MulticastRequest $request, ?string $retryKey = null): array;

    public function broadcast(BroadcastRequest $request, ?string $retryKey = null): array;

    // ============ Profile ===================================================
    public function getProfile(string $userId): array;

    public function getMessageContent(string $messageId): string;

    // ============ Rich Menu =================================================
    public function createRichMenu(RichMenuRequest $request): string;

    public function setRichMenuImage(string $richMenuId, string $imagePath, string $contentType = 'image/png'): void;

    public function deleteRichMenu(string $richMenuId): void;

    public function setDefaultRichMenu(string $richMenuId): void;

    public function getRichMenuList(): array;

    public function createRichMenuAlias(RichMenuAliasCreateRequest $request): void;

    public function deleteRichMenuAlias(string $aliasId): void;

    public function getRichMenuAliasList(): array;

    public function linkRichMenuToUser(string $userId, string $richMenuId): void;

    public function linkRichMenuToUsers(array $userIds, string $richMenuId): void;

    public function unlinkRichMenuFromUser(string $userId): void;

    // ============ Quota / Insight ===========================================
    public function getMessageQuotaConsumption(): int;
}

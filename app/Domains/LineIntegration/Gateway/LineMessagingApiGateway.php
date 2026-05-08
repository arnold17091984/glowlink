<?php

namespace App\Domains\LineIntegration\Gateway;

use App\Models\LineChannel;
use GuzzleHttp\Client as GuzzleClient;
use LINE\Clients\MessagingApi\Api\MessagingApiApi;
use LINE\Clients\MessagingApi\Api\MessagingApiBlobApi;
use LINE\Clients\MessagingApi\Configuration;
use LINE\Clients\MessagingApi\Model\BroadcastRequest;
use LINE\Clients\MessagingApi\Model\MulticastRequest;
use LINE\Clients\MessagingApi\Model\PushMessageRequest;
use LINE\Clients\MessagingApi\Model\ReplyMessageRequest;
use LINE\Clients\MessagingApi\Model\RichMenuAliasCreateRequest;
use LINE\Clients\MessagingApi\Model\RichMenuRequest;

/**
 * LINE SDK v9 ベースのプロダクション実装。
 *
 * チャネル ($accessToken) ごとに専用 SDK クライアントを生成するため、
 * LineGatewayManager::forChannel(LineChannel) から組み立てられる。
 * .env 既定チャネル向けのインスタンスは LineIntegrationServiceProvider で
 * `app(LineGateway::class)` として bind される (後方互換)。
 */
class LineMessagingApiGateway implements LineGateway
{
    private MessagingApiApi $api;

    private MessagingApiBlobApi $blobApi;

    public function __construct(string $accessToken)
    {
        $config = new Configuration();
        $config->setAccessToken($accessToken);

        $client = new GuzzleClient(['timeout' => 30]);

        $this->api = new MessagingApiApi(client: $client, config: $config);
        $this->blobApi = new MessagingApiBlobApi(client: $client, config: $config);
    }

    public static function fromChannel(LineChannel $channel): self
    {
        return new self((string) $channel->channel_access_token);
    }

    // ============ Messaging =================================================

    public function push(PushMessageRequest $request, ?string $retryKey = null): array
    {
        [$body, $status, $headers] = $this->api->pushMessageWithHttpInfo($request, $retryKey);

        return ['status' => $status, 'headers' => $headers, 'body' => $body];
    }

    public function reply(ReplyMessageRequest $request): array
    {
        [$body, $status, $headers] = $this->api->replyMessageWithHttpInfo($request);

        return ['status' => $status, 'headers' => $headers, 'body' => $body];
    }

    public function multicast(MulticastRequest $request, ?string $retryKey = null): array
    {
        [$body, $status, $headers] = $this->api->multicastWithHttpInfo($request, $retryKey);

        return ['status' => $status, 'headers' => $headers, 'body' => $body];
    }

    public function broadcast(BroadcastRequest $request, ?string $retryKey = null): array
    {
        [$body, $status, $headers] = $this->api->broadcastWithHttpInfo($request, $retryKey);

        return ['status' => $status, 'headers' => $headers, 'body' => $body];
    }

    // ============ Profile ===================================================

    public function getProfile(string $userId): array
    {
        $profile = $this->api->getProfile($userId);

        return method_exists($profile, 'jsonSerialize')
            ? (array) $profile->jsonSerialize()
            : (array) $profile;
    }

    public function getMessageContent(string $messageId): string
    {
        $file = $this->blobApi->getMessageContent($messageId);

        return method_exists($file, 'getRealPath') ? $file->getRealPath() : (string) $file;
    }

    // ============ Rich Menu =================================================

    public function createRichMenu(RichMenuRequest $request): string
    {
        $response = $this->api->createRichMenu($request);

        return method_exists($response, 'getRichMenuId')
            ? (string) $response->getRichMenuId()
            : (string) ($response['richMenuId'] ?? '');
    }

    public function setRichMenuImage(string $richMenuId, string $imagePath, ?string $contentType = null): void
    {
        // LINE SDK v9 のデフォルト Content-Type は 'application/json' で、画像アップロード時に
        // 415 Unsupported Media Type を返す。第 5 引数で必ず image/png か image/jpeg を渡す。
        $contentType ??= $this->detectContentType($imagePath);

        $body = new \SplFileObject($imagePath, 'r');
        $this->blobApi->setRichMenuImage($richMenuId, $body, null, [], $contentType);
    }

    private function detectContentType(string $path): string
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return match ($ext) {
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            default => function_exists('mime_content_type')
                ? (mime_content_type($path) ?: 'image/png')
                : 'image/png',
        };
    }

    public function deleteRichMenu(string $richMenuId): void
    {
        try {
            $this->api->deleteRichMenu($richMenuId);
        } catch (\LINE\Clients\MessagingApi\ApiException $e) {
            // 404 は idempotent に成功扱い (Edit chain での「既に消えている」)
            if ($e->getCode() === 404) {
                return;
            }
            throw $e;
        }
    }

    public function setDefaultRichMenu(string $richMenuId): void
    {
        $this->api->setDefaultRichMenu($richMenuId);
    }

    public function getRichMenuList(): array
    {
        $response = $this->api->getRichMenuList();
        $list = method_exists($response, 'getRichmenus') ? $response->getRichmenus() : [];

        return is_array($list) ? $list : [];
    }

    public function createRichMenuAlias(RichMenuAliasCreateRequest $request): void
    {
        try {
            $this->api->createRichMenuAlias($request);
        } catch (\LINE\Clients\MessagingApi\ApiException $e) {
            // 409 (alias 既存) は idempotent 扱い: 上書きしたいケースが多い
            if ($e->getCode() === 409 && method_exists($request, 'getRichMenuAliasId')) {
                $this->api->updateRichMenuAlias($request->getRichMenuAliasId(), [
                    'richMenuId' => method_exists($request, 'getRichMenuId') ? $request->getRichMenuId() : null,
                ]);

                return;
            }
            throw $e;
        }
    }

    public function deleteRichMenuAlias(string $aliasId): void
    {
        try {
            $this->api->deleteRichMenuAlias($aliasId);
        } catch (\LINE\Clients\MessagingApi\ApiException $e) {
            if ($e->getCode() === 404) {
                return;
            }
            throw $e;
        }
    }

    public function getRichMenuAliasList(): array
    {
        $response = $this->api->getRichMenuAliasList();
        $list = method_exists($response, 'getAliases') ? $response->getAliases() : [];

        return is_array($list) ? $list : [];
    }

    public function linkRichMenuToUser(string $userId, string $richMenuId): void
    {
        $this->api->linkRichMenuIdToUser($userId, $richMenuId);
    }

    public function linkRichMenuToUsers(array $userIds, string $richMenuId): void
    {
        $this->api->linkRichMenuIdToUsers([
            'richMenuId' => $richMenuId,
            'userIds' => array_values($userIds),
        ]);
    }

    public function unlinkRichMenuFromUser(string $userId): void
    {
        $this->api->unlinkRichMenuIdFromUser($userId);
    }

    // ============ Quota =====================================================

    public function getMessageQuotaConsumption(): int
    {
        $consumption = $this->api->getMessageQuotaConsumption();

        return (int) (is_object($consumption) ? ($consumption->totalUsage ?? 0) : 0);
    }
}

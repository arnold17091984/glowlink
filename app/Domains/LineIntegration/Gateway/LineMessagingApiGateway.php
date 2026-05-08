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

    /** LINE が受け付ける Rich Menu 画像サイズ (width => height) 候補 */
    private const VALID_RICHMENU_SIZES = [
        2500 => 1686,  // フル
        1200 => 810,
        // コンパクト
        // 2500x843 や 1200x405 は別途 height を判定して採用
    ];

    public function setRichMenuImage(string $richMenuId, string $imagePath, ?string $contentType = null): void
    {
        // LINE SDK v9 のデフォルト Content-Type は 'application/json' で、画像アップロード時に
        // 415 Unsupported Media Type を返す。第 5 引数で必ず image/png か image/jpeg を渡す。
        $contentType ??= $this->detectContentType($imagePath);

        // LINE の許容寸法に強制リサイズ。元画像のアスペクト比から
        // フル (2500x1686) かコンパクト (2500x843) を選ぶ。
        $resizedPath = $this->ensureValidRichMenuSize($imagePath, $contentType);

        try {
            $body = new \SplFileObject($resizedPath, 'r');
            $this->blobApi->setRichMenuImage($richMenuId, $body, null, [], $contentType);
        } finally {
            if ($resizedPath !== $imagePath && file_exists($resizedPath)) {
                @unlink($resizedPath);
            }
        }
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

    private function ensureValidRichMenuSize(string $imagePath, string $contentType): string
    {
        if (! function_exists('imagecreatefromstring')) {
            // GD 未導入なら諦めてそのまま送信 (LINE が 400 を返す可能性あり)
            return $imagePath;
        }

        $info = @getimagesize($imagePath);
        if (! $info) {
            return $imagePath;
        }
        [$srcW, $srcH] = $info;

        // アスペクト比から full / compact を判定
        $ratio = $srcW > 0 ? $srcH / $srcW : 1;
        $isCompact = $ratio < 0.55;  // 1686/2500 = 0.674, 843/2500 = 0.337 → 中間 0.55 で分岐

        $targetW = 2500;
        $targetH = $isCompact ? 843 : 1686;

        if ($srcW === $targetW && $srcH === $targetH) {
            return $imagePath;  // 既に LINE 仕様
        }

        $src = @imagecreatefromstring(file_get_contents($imagePath) ?: '');
        if (! $src) {
            return $imagePath;
        }

        $dst = imagecreatetruecolor($targetW, $targetH);

        // PNG / JPEG どちらでも背景を白で塗ってからリサイズ (透過 PNG の黒背景化を防ぐ)
        $white = imagecolorallocate($dst, 255, 255, 255);
        imagefilledrectangle($dst, 0, 0, $targetW, $targetH, $white);

        imagecopyresampled($dst, $src, 0, 0, 0, 0, $targetW, $targetH, $srcW, $srcH);

        $tmp = tempnam(sys_get_temp_dir(), 'richmenu_').'.'.($contentType === 'image/jpeg' ? 'jpg' : 'png');
        if ($contentType === 'image/jpeg') {
            imagejpeg($dst, $tmp, 90);
        } else {
            imagepng($dst, $tmp, 6);
        }

        imagedestroy($src);
        imagedestroy($dst);

        return $tmp;
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

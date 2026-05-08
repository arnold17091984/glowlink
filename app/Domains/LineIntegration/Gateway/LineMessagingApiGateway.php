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
use LINE\Clients\MessagingApi\Model\CreateRichMenuAliasRequest;
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
        $sourceContentType = $contentType ?? $this->detectContentType($imagePath);

        // ensureValidRichMenuSize() が常に JPEG を返すため、Content-Type は image/jpeg に上書き。
        // ※ オリジナルが PNG でも、リサイズ後は JPEG なので必ず一致させる。
        $resizedPath = $this->ensureValidRichMenuSize($imagePath, $sourceContentType);
        $uploadContentType = ($resizedPath !== $imagePath || str_ends_with(strtolower($resizedPath), '.jpg'))
            ? 'image/jpeg'
            : $sourceContentType;

        try {
            $body = new \SplFileObject($resizedPath, 'r');
            $this->blobApi->setRichMenuImage($richMenuId, $body, null, [], $uploadContentType);
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

    /**
     * LINE が認める 4 サイズに合わせる。
     *   フル:    2500x1686 / 1200x810
     *   コンパクト: 2500x843 / 1200x405
     * 入力アスペクト比に最も近い LINE サイズを選び、
     * **アスペクト比を保ったまま center-crop COVER** で生成する (歪ませない)。
     *
     * LINE のファイル上限 1MB を満たすため、最終出力は常に JPEG q=82 とし
     * 白背景でフラット化する (透過 PNG の見た目を保ちつつサイズ削減)。
     * 上位の setRichMenuImage は contentType を上書きで image/jpeg にする。
     */
    private function ensureValidRichMenuSize(string $imagePath, string $contentType): string
    {
        if (! function_exists('imagecreatefromstring')) {
            return $imagePath;
        }

        $info = @getimagesize($imagePath);
        if (! $info) {
            return $imagePath;
        }
        [$srcW, $srcH] = $info;
        if ($srcW <= 0 || $srcH <= 0) {
            return $imagePath;
        }

        $srcRatio = $srcW / $srcH;

        // 候補サイズ: アスペクト比だけが一致すれば LINE は OK
        $candidates = [
            [2500, 1686, 2500 / 1686],
            [2500, 843,  2500 / 843],
            [1200, 810,  1200 / 810],
            [1200, 405,  1200 / 405],
        ];

        // 最も近いアスペクト比を選ぶ
        $best = $candidates[0];
        $bestDiff = abs($srcRatio - $best[2]);
        foreach ($candidates as $c) {
            $d = abs($srcRatio - $c[2]);
            if ($d < $bestDiff) {
                $best = $c;
                $bestDiff = $d;
            }
        }
        [$targetW, $targetH] = [$best[0], $best[1]];
        $targetRatio = $best[2];

        // 既に正解サイズなら何もしない
        if ($srcW === $targetW && $srcH === $targetH) {
            return $imagePath;
        }

        // COVER 計算: source を target アスペクト比に揃えるため source 側を crop する
        if ($srcRatio > $targetRatio) {
            // source が target より横長 → 左右を切る
            $cropH = $srcH;
            $cropW = (int) round($srcH * $targetRatio);
            $cropX = (int) round(($srcW - $cropW) / 2);
            $cropY = 0;
        } else {
            // source が target より縦長 → 上下を切る
            $cropW = $srcW;
            $cropH = (int) round($srcW / $targetRatio);
            $cropX = 0;
            $cropY = (int) round(($srcH - $cropH) / 2);
        }

        $src = @imagecreatefromstring(file_get_contents($imagePath) ?: '');
        if (! $src) {
            return $imagePath;
        }

        $dst = imagecreatetruecolor($targetW, $targetH);

        // 透過は白背景でフラット化 (JPEG 出力で 1MB 以下を保証するため)
        $white = imagecolorallocate($dst, 255, 255, 255);
        imagefilledrectangle($dst, 0, 0, $targetW, $targetH, $white);

        imagecopyresampled(
            $dst, $src,
            0, 0,                    // dst x, y
            $cropX, $cropY,           // src x, y
            $targetW, $targetH,       // dst w, h
            $cropW, $cropH            // src w, h
        );

        // 常に JPEG で出力 (LINE の 1MB 制限を確実に満たす)
        $tmp = tempnam(sys_get_temp_dir(), 'richmenu_').'.jpg';
        $quality = 85;
        imagejpeg($dst, $tmp, $quality);

        // 万一 1MB を超えていたら品質を落として再保存 (1MB = 1048576 bytes)
        while (filesize($tmp) > 1048000 && $quality > 50) {
            $quality -= 10;
            imagejpeg($dst, $tmp, $quality);
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

    public function createRichMenuAlias(CreateRichMenuAliasRequest $request): void
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

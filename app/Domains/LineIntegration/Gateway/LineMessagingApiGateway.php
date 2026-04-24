<?php

namespace App\Domains\LineIntegration\Gateway;

use LINE\Clients\MessagingApi\Api\MessagingApiApi;
use LINE\Clients\MessagingApi\Model\BroadcastRequest;
use LINE\Clients\MessagingApi\Model\MulticastRequest;
use LINE\Clients\MessagingApi\Model\PushMessageRequest;
use LINE\Clients\MessagingApi\Model\ReplyMessageRequest;

/**
 * LINE SDK v9 の MessagingApiApi を実際に呼び出す本番実装。
 *
 * このクラスに例外ハンドリング・リトライ・観測を集約する。
 * 旧コードは `LINEMessagingApi::pushMessage($req)` のように Facade 直呼びだが、
 * 将来的にここに一元化することでテスト容易性と運用可視性を確保。
 */
class LineMessagingApiGateway implements LineGateway
{
    public function __construct(
        private MessagingApiApi $client,
    ) {
    }

    public function push(PushMessageRequest $request, ?string $retryKey = null): array
    {
        [$body, $status, $headers] = $this->client->pushMessageWithHttpInfo(
            $request,
            $retryKey,
        );

        return ['status' => $status, 'headers' => $headers, 'body' => $body];
    }

    public function reply(ReplyMessageRequest $request): array
    {
        [$body, $status, $headers] = $this->client->replyMessageWithHttpInfo($request);

        return ['status' => $status, 'headers' => $headers, 'body' => $body];
    }

    public function multicast(MulticastRequest $request, ?string $retryKey = null): array
    {
        [$body, $status, $headers] = $this->client->multicastWithHttpInfo($request, $retryKey);

        return ['status' => $status, 'headers' => $headers, 'body' => $body];
    }

    public function broadcast(BroadcastRequest $request, ?string $retryKey = null): array
    {
        [$body, $status, $headers] = $this->client->broadcastWithHttpInfo($request, $retryKey);

        return ['status' => $status, 'headers' => $headers, 'body' => $body];
    }

    public function getProfile(string $userId): array
    {
        $profile = $this->client->getProfile($userId);

        return method_exists($profile, 'jsonSerialize')
            ? (array) $profile->jsonSerialize()
            : (array) $profile;
    }

    public function getMessageQuotaConsumption(): int
    {
        $consumption = $this->client->getMessageQuotaConsumption();

        return (int) (is_object($consumption) ? ($consumption->totalUsage ?? 0) : 0);
    }
}

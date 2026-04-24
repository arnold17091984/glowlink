<?php

namespace App\Domains\LineIntegration\Gateway;

use LINE\Clients\MessagingApi\Model\BroadcastRequest;
use LINE\Clients\MessagingApi\Model\MulticastRequest;
use LINE\Clients\MessagingApi\Model\PushMessageRequest;
use LINE\Clients\MessagingApi\Model\ReplyMessageRequest;

/**
 * アプリ側から LINE Messaging API を叩くための境界インターフェース。
 *
 * 直接 LINEMessagingApi Facade を呼ぶのではなく、本インターフェースを介すことで:
 *   - テスト時に `FakeLineGateway` に差し替えられる
 *   - リトライ / レート制御 / 観測を共通化できる
 *   - LINE SDK の破壊的変更 (v9 → v10 等) を隠蔽できる
 *
 * 実体は `LineMessagingApiGateway` (プロダクション) と `FakeLineGateway` (テスト)。
 */
interface LineGateway
{
    /**
     * 単一ユーザーへ Push 配信。retryKey で重複配信を防止。
     *
     * @return array{status:int, headers:array, body:mixed}
     */
    public function push(PushMessageRequest $request, ?string $retryKey = null): array;

    /**
     * 返信トークンに対する Reply 配信。Reply は発行から 30 秒以内・1 回限り。
     */
    public function reply(ReplyMessageRequest $request): array;

    /**
     * 複数ユーザーへ Multicast。to は 500 件まで。
     */
    public function multicast(MulticastRequest $request, ?string $retryKey = null): array;

    /**
     * 友達登録している全員へ Broadcast。LINE 公式 API で最もコスト効率が良い。
     */
    public function broadcast(BroadcastRequest $request, ?string $retryKey = null): array;

    /**
     * 友達プロフィール取得。
     *
     * @return array{userId:string, displayName:string, pictureUrl?:string, statusMessage?:string}
     */
    public function getProfile(string $userId): array;

    /**
     * LINE Messaging API の送信可能数残を取得。
     */
    public function getMessageQuotaConsumption(): int;
}

<?php

namespace Tests\Concerns;

use LINE\Clients\MessagingApi\Api\MessagingApiApi;
use Mockery;
use Mockery\MockInterface;

/**
 * LINE Messaging API をフェイクし、テスト中は外部通信を一切行わない。
 *
 * 使い方:
 *   class MyTest extends TestCase {
 *       use FakesLineApi;
 *
 *       it('does something', function () {
 *           $line = $this->fakeLineApi();
 *           $line->shouldReceive('multicast')->once();
 *           // ...
 *       });
 *   }
 */
trait FakesLineApi
{
    protected ?MockInterface $lineApiMock = null;

    protected function fakeLineApi(): MockInterface
    {
        $mock = Mockery::mock(MessagingApiApi::class);
        $mock->shouldReceive('pushMessage')->andReturn((object) ['sentMessages' => []])->byDefault();
        $mock->shouldReceive('replyMessage')->andReturn((object) ['sentMessages' => []])->byDefault();
        $mock->shouldReceive('multicast')->andReturn((object) ['sentMessages' => []])->byDefault();
        $mock->shouldReceive('multicastWithHttpInfo')->andReturn([(object) [], 200, []])->byDefault();
        $mock->shouldReceive('broadcast')->andReturn((object) ['sentMessages' => []])->byDefault();
        $mock->shouldReceive('getProfile')->andReturn((object) [
            'userId' => 'Utest123',
            'displayName' => 'Test User',
            'pictureUrl' => 'https://example.com/pic.jpg',
        ])->byDefault();

        $this->app->instance(MessagingApiApi::class, $mock);
        $this->lineApiMock = $mock;

        return $mock;
    }

    /**
     * 署名付きの LINE Webhook リクエストを送信する。
     */
    protected function postSignedWebhook(array $payload, ?string $secretOverride = null): \Illuminate\Testing\TestResponse
    {
        $body = json_encode($payload, JSON_UNESCAPED_UNICODE);
        $secret = $secretOverride ?? (string) config('line-bot.channel_secret');
        $signature = base64_encode(hash_hmac('sha256', $body, $secret, true));

        return $this->call(
            'POST',
            '/messages',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X-Line-Signature' => $signature,
            ],
            $body
        );
    }

    protected function buildWebhookEvent(string $type, array $overrides = []): array
    {
        return array_merge([
            'type' => $type,
            'replyToken' => 'dummyReplyToken',
            'source' => ['type' => 'user', 'userId' => 'Utest123'],
            'timestamp' => (int) (microtime(true) * 1000),
            'message' => ['id' => '111', 'type' => 'text', 'text' => 'hello'],
        ], $overrides);
    }
}

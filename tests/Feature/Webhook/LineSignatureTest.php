<?php

use Tests\Concerns\FakesLineApi;

uses(FakesLineApi::class);

beforeEach(function () {
    config()->set('line-bot.channel_secret', 'test-secret');
});

it('rejects webhook without signature header', function () {
    $response = $this->postJson('/messages', ['events' => []]);
    $response->assertStatus(401);
});

it('rejects webhook with invalid signature', function () {
    $response = $this->postJson('/messages', ['events' => []], [
        'X-Line-Signature' => base64_encode('bogus-signature'),
    ]);
    $response->assertStatus(401);
});

it('accepts webhook with valid HMAC-SHA256 signature', function () {
    $this->fakeLineApi();

    $response = $this->postSignedWebhook([
        'destination' => 'Uxxxxxxxxxx',
        'events' => [],
    ]);

    $response->assertOk();
    $response->assertJson(['status' => 'ok']);
});

it('handles follow event without crashing the whole webhook on per-event failure', function () {
    $this->fakeLineApi();

    $payload = [
        'destination' => 'Uxxxxxxxxxx',
        'events' => [
            $this->buildWebhookEvent('follow', [
                'source' => ['type' => 'user', 'userId' => 'Unewfriend'],
            ]),
        ],
    ];

    $response = $this->postSignedWebhook($payload);
    $response->assertOk();
});

it('responds 500 when channel secret is not configured', function () {
    config()->set('line-bot.channel_secret', '');

    $response = $this->call(
        'POST', '/messages', [], [], [],
        ['CONTENT_TYPE' => 'application/json', 'HTTP_X-Line-Signature' => 'any'],
        json_encode(['events' => []])
    );

    $response->assertStatus(500);
});

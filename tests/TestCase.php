<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        // テスト中は LINE 設定を必ず埋める。個別テストで config() で上書き可能。
        config()->set('line-bot.channel_secret', config('line-bot.channel_secret') ?: 'test-secret');
        config()->set('line-bot.channel_access_token', config('line-bot.channel_access_token') ?: 'test-access-token');
    }
}

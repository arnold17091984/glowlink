<?php

use App\Actions\Broadcast\MulticastBroadcastAction;
use App\Enums\FlagEnum;
use App\Enums\MessagingTypeEnum;
use App\Jobs\SendMulticastChunkJob;
use App\Models\Friend;
use App\Models\Message;
use Illuminate\Support\Facades\Bus;
use Tests\Concerns\FakesLineApi;

uses(FakesLineApi::class, \Illuminate\Foundation\Testing\RefreshDatabase::class);

it('dispatches one SendMulticastChunkJob per 500 friends when sending to all', function () {
    Bus::fake();
    $this->fakeLineApi();

    $message = Message::factory()->text('hello world')->create();
    Friend::factory()->count(1200)->create();

    app(MulticastBroadcastAction::class)->execute($message, 'all');

    // 1200 友達 → chunk(500) なので 3 ジョブに分割される
    Bus::assertBatched(fn ($batch) => $batch->jobs->count() === 3);
});

it('segments by mark when sendTo is not "all"', function () {
    Bus::fake();
    $this->fakeLineApi();

    $message = Message::factory()->text('segmented')->create();
    Friend::factory()->count(300)->flagged(FlagEnum::REQUIRES_ACTION)->create();
    Friend::factory()->count(200)->flagged(FlagEnum::UNRESOLVED)->create();

    app(MulticastBroadcastAction::class)->execute($message, FlagEnum::REQUIRES_ACTION->value);

    Bus::assertBatched(function ($batch) {
        $job = $batch->jobs->first();
        if (! $job instanceof SendMulticastChunkJob) {
            return false;
        }

        return count($job->userIds) === 300;
    });
});

it('each chunk carries its own retry key for idempotency', function () {
    Bus::fake();
    $this->fakeLineApi();

    $message = Message::factory()->text('hello')->create();
    Friend::factory()->count(750)->create();

    app(MulticastBroadcastAction::class)->execute($message, 'all');

    Bus::assertBatched(function ($batch) {
        $keys = $batch->jobs->map(fn ($j) => $j->retryKey)->all();

        return count($keys) === count(array_unique($keys));
    });
});

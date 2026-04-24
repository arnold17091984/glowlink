<?php

use App\Actions\Coupon\RedeemRewardAction;
use App\Models\Coupon;

it('win rate 50% stays within statistical tolerance over 10k trials', function () {
    $coupon = Coupon::factory()->lottery(50)->make();

    $action = app(RedeemRewardAction::class);
    $method = new ReflectionMethod($action, 'isWinner');
    $method->setAccessible(true);

    $trials = 10000;
    $wins = 0;
    for ($i = 0; $i < $trials; $i++) {
        if ($method->invoke($action, $coupon)) {
            $wins++;
        }
    }

    // ±3% 以内に収まることを期待 (50% → [47%, 53%])
    $rate = ($wins / $trials) * 100;
    expect($rate)->toBeGreaterThan(47);
    expect($rate)->toBeLessThan(53);
});

it('win rate 0% never wins', function () {
    $coupon = Coupon::factory()->lottery(0)->make();

    $action = app(RedeemRewardAction::class);
    $method = new ReflectionMethod($action, 'isWinner');
    $method->setAccessible(true);

    for ($i = 0; $i < 200; $i++) {
        expect($method->invoke($action, $coupon))->toBeFalse();
    }
});

it('win rate 100% always wins', function () {
    $coupon = Coupon::factory()->lottery(100)->make();

    $action = app(RedeemRewardAction::class);
    $method = new ReflectionMethod($action, 'isWinner');
    $method->setAccessible(true);

    for ($i = 0; $i < 200; $i++) {
        expect($method->invoke($action, $coupon))->toBeTrue();
    }
});

it('non-lottery coupon never enters winner check', function () {
    $coupon = Coupon::factory()->make(['is_lottery' => false, 'win_rate' => 100]);

    $action = app(RedeemRewardAction::class);
    $method = new ReflectionMethod($action, 'isWinner');
    $method->setAccessible(true);

    expect($method->invoke($action, $coupon))->toBeFalse();
});

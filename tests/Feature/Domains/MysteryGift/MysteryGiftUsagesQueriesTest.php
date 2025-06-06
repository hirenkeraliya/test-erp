<?php

declare(strict_types=1);

use App\Domains\MysteryGift\MysteryGiftUsagesQueries;
use App\Models\Member;
use App\Models\MysteryGiftUsage;
use App\Models\Product;

beforeEach(function (): void {
    $this->member = Member::factory()->create();

    $this->product = Product::factory()->create();

    $this->mysteryGiftUsage = MysteryGiftUsage::factory()->create([
        'coupon_code' => 'TEST123',
        'product_id' => $this->product->id,
        'member_id' => $this->member->id,
        'used_at' => null,
    ]);

    $this->mysteryGiftUsagesQueries = new MysteryGiftUsagesQueries();
});

test('addNew creates a new MysteryGiftUsage record', function (): void {
    $data = [
        'coupon_code' => 'TEST1',
        'product_id' => $this->product->id,
        'member_id' => $this->member->id,
        'used_at' => null,
    ];

    $this->mysteryGiftUsagesQueries->addNew($data);

    $this->assertDatabaseHas('mystery_gift_usages', $data);
});

test('existsByCouponCode returns true if coupon code exists', function (): void {
    $result = $this->mysteryGiftUsagesQueries->existsByCouponCode('TEST123');

    expect($result)->toBeTrue();
});

test('existsByCouponCode returns false if coupon code does not exist', function (): void {
    $result = $this->mysteryGiftUsagesQueries->existsByCouponCode('NON_EXISTENT');

    expect($result)->toBeFalse();
});

test('getDetailsByCouponCode retrieves correct details', function (): void {
    $result = $this->mysteryGiftUsagesQueries->getDetailsByCouponCode('TEST123');

    expect($result)->not->toBeNull();
    expect($result->id)->toBe($this->mysteryGiftUsage->id);
    expect($result->product_id)->toBe($this->mysteryGiftUsage->product_id);
    expect($result->member_id)->toBe($this->mysteryGiftUsage->member_id);
});

test('getDetailsByCouponCodeOnlyNotUsedAt retrieves correct details when used_at is null', function (): void {
    $result = $this->mysteryGiftUsagesQueries->getDetailsByCouponCodeOnlyNotUsedAt('TEST123');

    expect($result)->not->toBeNull();
    expect($result->id)->toBe($this->mysteryGiftUsage->id);
});

test('getDetailsByCouponCodeOnlyNotUsedAt returns null if used_at is not null', function (): void {
    MysteryGiftUsage::factory()->create([
        'coupon_code' => 'TEST2',
        'product_id' => $this->member->id,
        'member_id' => $this->product->id,
        'used_at' => now(),
    ]);

    $result = $this->mysteryGiftUsagesQueries->getDetailsByCouponCodeOnlyNotUsedAt('TEST2');

    expect($result)->toBeNull();
});

test('updateUsedAt updates the used_at field of a MysteryGiftUsage record', function (): void {
    $usedAt = now()->toDateTimeString();
    $this->mysteryGiftUsagesQueries->updateUsedAt($this->mysteryGiftUsage, $usedAt);

    $this->assertDatabaseHas('mystery_gift_usages', [
        'id' => $this->mysteryGiftUsage->id,
        'used_at' => $usedAt,
    ]);
});

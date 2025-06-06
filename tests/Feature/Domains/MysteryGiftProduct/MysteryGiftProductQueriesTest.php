<?php

declare(strict_types=1);

use App\Domains\MysteryGiftProduct\MysteryGiftProductQueries;
use App\Models\Member;
use App\Models\MysteryGift;
use App\Models\MysteryGiftProduct;
use App\Models\MysteryGiftUsage;
use App\Models\Product;

beforeEach(function (): void {
    $this->mysteryGift = MysteryGift::factory()->create();
    $this->product = Product::factory()->create();
    $this->member = Member::factory()->create();
    $this->mysteryGiftProductQueries = new MysteryGiftProductQueries();
});

it('can add a new mystery gift product', function (): void {
    $data = [
        'mystery_gift_id' => $this->mysteryGift->id,
        'product_id' => $this->product->id,
        'quantity' => 10,
    ];

    $this->mysteryGiftProductQueries->addNew($data);

    $this->assertDatabaseHas('mystery_gift_products', $data);
});

it('returns the correct basic column names', function (): void {
    $columns = $this->mysteryGiftProductQueries->getBasicColumnNames();

    expect($columns)->toBe('id,mystery_gift_id,product_id,quantity');
});

it('returns a random product ID when usage count is less than quantity', function (): void {
    MysteryGiftProduct::factory()->create([
        'mystery_gift_id' => $this->mysteryGift->id,
        'product_id' => $this->product->id,
        'quantity' => 5,
    ]);

    MysteryGiftUsage::factory()->create([
        'mystery_gift_id' => $this->mysteryGift->id,
        'product_id' => $this->product->id,
        'member_id' => $this->member->id,
        'used_at' => now(),
    ]);

    $randomProduct = $this->mysteryGiftProductQueries->getRandomProductId($this->mysteryGift->id);

    expect($randomProduct->product_id)->toBe($this->product->id);
});

it('returns null when all products are fully used', function (): void {
    MysteryGiftProduct::factory()->create([
        'mystery_gift_id' => $this->mysteryGift->id,
        'product_id' => $this->product->id,
        'quantity' => 1,
    ]);

    MysteryGiftUsage::factory()->create([
        'mystery_gift_id' => $this->mysteryGift->id,
        'product_id' => $this->product->id,
        'member_id' => $this->member->id,
        'used_at' => now(),
    ]);

    $randomProduct = $this->mysteryGiftProductQueries->getRandomProductId($this->mysteryGift->id);

    expect($randomProduct)->toBeNull();
});

it('returns a product when quantity is unlimited (0)', function (): void {
    MysteryGiftProduct::factory()->create([
        'mystery_gift_id' => $this->mysteryGift->id,
        'product_id' => $this->product->id,
        'quantity' => 0,
    ]);

    $randomProduct = $this->mysteryGiftProductQueries->getRandomProductId($this->mysteryGift->id);

    expect($randomProduct->product_id)->toBe($this->product->id);
});

it('handles multiple products and returns one with available quantity', function (): void {
    $product1 = Product::factory()->create();
    $product2 = Product::factory()->create();

    MysteryGiftProduct::factory()->create([
        'mystery_gift_id' => $this->mysteryGift->id,
        'product_id' => $product1->id,
        'quantity' => 1,
    ]);

    MysteryGiftProduct::factory()->create([
        'mystery_gift_id' => $this->mysteryGift->id,
        'product_id' => $product2->id,
        'quantity' => 5,
    ]);

    MysteryGiftUsage::factory()->create([
        'mystery_gift_id' => $this->mysteryGift->id,
        'product_id' => $product1->id,
        'member_id' => $this->member->id,
        'used_at' => now(),
    ]);

    $randomProduct = $this->mysteryGiftProductQueries->getRandomProductId($this->mysteryGift->id);

    expect($randomProduct->product_id)->toBe($product2->id);
});

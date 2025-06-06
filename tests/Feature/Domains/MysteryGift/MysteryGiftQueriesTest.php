<?php

declare(strict_types=1);

use App\Domains\MysteryGift\DataObjects\MysteryGiftData;
use App\Domains\MysteryGift\Enums\Statuses;
use App\Domains\MysteryGift\MysteryGiftQueries;
use App\Models\Company;
use App\Models\MysteryGift;
use App\Models\Product;
use Illuminate\Pagination\LengthAwarePaginator;

beforeEach(function (): void {
    $this->company = Company::factory()->create();

    $this->mysteryGiftA = MysteryGift::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Gift A',
    ]);

    $this->mysteryGiftB = MysteryGift::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Gift B',
    ]);

    $this->mysteryGiftQueries = new MysteryGiftQueries();
});

test('listQuery returns paginated mystery gifts', function (): void {
    $response = $this->mysteryGiftQueries->listQuery([
        'per_page' => 10,
        'search_text' => 'Gift',
        'sort_by' => 'name',
        'sort_direction' => 'asc',
    ], $this->company->id);

    expect($response)->toBeInstanceOf(LengthAwarePaginator::class);
    expect($response->total())->toBe(2);
    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('name', $this->mysteryGiftA->name);
});

test('addNew creates a new mystery gift', function (): void {
    $mysteryGiftData = new MysteryGiftData(
        'New Gift',
        now()->toDateTimeString(),
        now()->addDays(10)->toDateTimeString(),
        0,
        0,
        [],
        0,
        0,
        0,
        0,
        false,
        false,
        false,
    );

    $response = $this->mysteryGiftQueries->addNew($mysteryGiftData, $this->company->id);

    expect($response)->toBeInstanceOf(MysteryGift::class);
    expect($response->name)->toBe('New Gift');
    expect($response->company_id)->toBe($this->company->id);

    $this->assertDatabaseHas('mystery_gifts', [
        'name' => 'New Gift',
        'company_id' => $this->company->id,
    ]);
});

test('addNew creates a new mystery gift with products', function (): void {
    $uploadedProducts[] = [
        'id' => Product::factory()->create()->id,
        'quantity' => 2,
    ];
    $mysteryGiftData = new MysteryGiftData(
        'New Gift',
        now()->toDateTimeString(),
        now()->addDays(10)->toDateTimeString(),
        0,
        0,
        $uploadedProducts,
        0,
        0,
        0,
        0,
        false,
        false,
        false,
    );

    $response = $this->mysteryGiftQueries->addNew($mysteryGiftData, $this->company->id);

    expect($response)->toBeInstanceOf(MysteryGift::class);
    expect($response->name)->toBe('New Gift');
    expect($response->company_id)->toBe($this->company->id);

    $this->assertDatabaseHas('mystery_gifts', [
        'name' => 'New Gift',
        'company_id' => $this->company->id,
    ]);
});

test('update modifies an existing mystery gift', function (): void {
    $mysteryGiftData = new MysteryGiftData(
        'Updated Gift',
        now()->toDateTimeString(),
        now()->addDays(10)->toDateTimeString(),
        0,
        0,
        [],
        0,
        0,
        0,
        0,
        false,
        false,
        false,
    );

    $response = $this->mysteryGiftQueries->update($mysteryGiftData, $this->mysteryGiftA);

    expect($response)->toBeInstanceOf(MysteryGift::class);
    expect($response->name)->toBe('Updated Gift');

    $this->assertDatabaseHas('mystery_gifts', [
        'id' => $this->mysteryGiftA->id,
        'name' => 'Updated Gift',
    ]);
});

test('update modifies an existing mystery gift with products', function (): void {
    $uploadedProducts[] = [
        'id' => Product::factory()->create()->id,
        'quantity' => 5,
    ];

    $mysteryGiftData = new MysteryGiftData(
        'Updated Gift',
        now()->toDateTimeString(),
        now()->addDays(10)->toDateTimeString(),
        0,
        0,
        [],
        0,
        0,
        0,
        0,
        false,
        false,
        false,
    );

    $response = $this->mysteryGiftQueries->update($mysteryGiftData, $this->mysteryGiftA);

    expect($response)->toBeInstanceOf(MysteryGift::class);
    expect($response->name)->toBe('Updated Gift');

    $this->assertDatabaseHas('mystery_gifts', [
        'id' => $this->mysteryGiftA->id,
        'name' => 'Updated Gift',
    ]);
});

test('filterByCompany filters mystery gifts by company ID', function (): void {
    $query = MysteryGift::query();
    $filter = $this->mysteryGiftQueries->filterByCompany($this->company->id);
    $filter($query);

    expect($query->count())->toBe(2);
});

test('getById retrieves a mystery gift by ID', function (): void {
    $response = $this->mysteryGiftQueries->getById($this->mysteryGiftA->id, $this->company->id);

    expect($response)->toBeInstanceOf(MysteryGift::class);
    expect($response->id)->toBe($this->mysteryGiftA->id);
});

test('setStatus updates the status of a mystery gift', function (): void {
    $this->mysteryGiftQueries->setStatus($this->mysteryGiftA->id, $this->company->id, true);

    $this->assertDatabaseHas('mystery_gifts', [
        'id' => $this->mysteryGiftA->id,
        'status' => true,
    ]);
});

test('getMysteryGiftConfigurations retrieves active configurations', function (): void {
    $this->mysteryGiftC = MysteryGift::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Gift A',
        'status' => true,
        'start_date' => now()->format('Y-m-d'),
        'end_date' => now()->format('Y-m-d'),
    ]);

    $response = $this->mysteryGiftQueries->getMysteryGiftConfigurations($this->company->id);

    expect($response)->toBeInstanceOf(MysteryGift::class);
    expect($response->id)->toBe($this->mysteryGiftC->id);
    expect($response->company_id)->toBe($this->company->id);
    expect($response->status)->toBeTrue();
    expect($response->start_date)->toBeLessThanOrEqual(now()->format('Y-m-d'));
    expect($response->end_date)->toBeGreaterThanOrEqual(now()->format('Y-m-d'));
});

test('removeSelectedProducts removes products from a mystery gift', function (): void {
    $product = Product::factory()->create();

    $this->mysteryGiftA->mysteryGiftProducts()->create([
        'product_id' => $product->id,
    ]);

    $this->mysteryGiftQueries->removeSelectedProducts([
        'id' => $this->mysteryGiftA->id,
    ]);

    $this->assertDatabaseMissing('mystery_gift_products', [
        'mystery_gift_id' => $this->mysteryGiftA->id,
        'product_id' => $product->id,
    ]);
});

test('fetchPromotionProducts retrieves promotion products', function (): void {
    $product = Product::factory()->create();
    $this->mysteryGiftA->mysteryGiftProducts()->create([
        'product_id' => $product->id,
    ]);

    $response = $this->mysteryGiftQueries->fetchPromotionProducts($this->mysteryGiftA->id);

    expect($response)->toBeInstanceOf(MysteryGift::class);
    expect($response->id)->toBe($this->mysteryGiftA->id);
    expect($response->mysteryGiftProducts)->toHaveCount(1);
    expect($response->mysteryGiftProducts->first()->product->id)->toBe($product->id);
});

test('getActiveConfigurations retrieves active configurations for a specific company', function (): void {
    $this->mysteryGiftB = MysteryGift::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Active Gift',
        'status' => Statuses::ACTIVE->value,
        'start_date' => now()->subDay()->format('Y-m-d'),
        'end_date' => now()->addDay()->format('Y-m-d'),
    ]);

    $response = $this->mysteryGiftQueries->getActiveConfigurations($this->company->id);

    expect($response)->toBeInstanceOf(MysteryGift::class);
    expect($response->id)->toBe($this->mysteryGiftB->id);
    expect($response->name)->toBe('Active Gift');
    expect($response->start_date)->toBeLessThanOrEqual(now()->format('Y-m-d'));
    expect($response->end_date)->toBeGreaterThanOrEqual(now()->format('Y-m-d'));
});

test('getActiveConfigurations retrieves active configurations without company filter', function (): void {
    $this->mysteryGiftC = MysteryGift::factory()->create([
        'name' => 'Global Active Gift',
        'status' => Statuses::ACTIVE->value,
        'start_date' => now()->subDay()->format('Y-m-d'),
        'end_date' => now()->addDay()->format('Y-m-d'),
    ]);

    $response = $this->mysteryGiftQueries->getActiveConfigurations();

    expect($response)->toBeInstanceOf(MysteryGift::class);
    expect($response->id)->toBe($this->mysteryGiftC->id);
    expect($response->name)->toBe('Global Active Gift');
    expect($response->start_date)->toBeLessThanOrEqual(now()->format('Y-m-d'));
    expect($response->end_date)->toBeGreaterThanOrEqual(now()->format('Y-m-d'));
});

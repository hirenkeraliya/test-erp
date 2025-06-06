<?php

declare(strict_types=1);

use App\Domains\Reward\DataObjects\RewardData;
use App\Domains\Reward\Enums\RewardTargetTypes;
use App\Domains\Reward\Enums\RewardTypes;
use App\Domains\Reward\RewardQueries;
use App\Models\Admin;
use App\Models\Brand;
use App\Models\Company;
use App\Models\Reward;

beforeEach(function (): void {
    $this->companyId = Company::factory()->create()->id;

    $this->rewardA = Reward::factory()->create([
        'company_id' => $this->companyId,
        'title' => 'ABCD',
        'type' => RewardTypes::DISCOUNT_ON_ENTIRE_SALE->value,
        'minimum_point' => 5,
        'maximum_point' => 10,
    ]);

    $this->rewardB = Reward::factory()->create([
        'company_id' => $this->companyId,
        'title' => 'ASDF',
        'type' => RewardTypes::FREE_ITEM->value,
        'target_type' => RewardTargetTypes::CATEGORIES->value,
        'loyalty_point' => 50,
    ]);

    $this->rewardQueries = new RewardQueries();
});

test('rewards can be searched', function (): void {
    $response = $this->rewardQueries->listQuery([
        'search_text' => 'ABCD',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ], $this->companyId);

    $this->assertEquals(1, $response->total());

    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('title', $this->rewardA->title)
        ->toHaveKey('minimum_point', $this->rewardA->minimum_point)
        ->toHaveKey('maximum_point', $this->rewardA->maximum_point);
});

test('A reward can be fetched', function (): void {
    $response = $this->rewardQueries->getById($this->rewardA->id, $this->companyId);
    expect($response->toArray())
        ->toHaveKey('id', $this->rewardA->id)
        ->toHaveKey('title', $this->rewardA->title)
        ->toHaveKey('minimum_point', $this->rewardA->minimum_point)
        ->toHaveKey('maximum_point', $this->rewardA->maximum_point);
});

test('New reward can be added', function (): void {
    $brand = Brand::factory()->create();
    $brandIds = [$brand->id];
    $admin = Admin::factory()->create();

    $newRewardRecord['title'] = 'EFGH';
    $newRewardRecord['loyalty_point'] = 11.11;
    $newRewardRecord['type'] = RewardTypes::FREE_ITEM->value;
    $newRewardRecord['target_type'] = RewardTargetTypes::BRANDS->value;
    $newRewardRecord['status'] = true;
    $newRewardRecord['brand_ids'] = $brandIds;

    $this->rewardQueries->addNew(new RewardData(...$newRewardRecord), $this->companyId, $admin);

    $this->assertDatabaseHas('rewards', [
        'company_id' => $this->companyId,
        'title' => 'EFGH',
        'loyalty_point' => 11.11,
    ]);

    $this->assertDatabaseHas('brand_reward', [
        'brand_id' => $brand->id,
    ]);
});

test('A reward can be updated', function (): void {
    $brand = Brand::factory()->create();
    $brandIds = [$brand->id];

    $newRewardRecord['title'] = 'IJKL';
    $newRewardRecord['loyalty_point'] = 11.11;
    $newRewardRecord['type'] = RewardTypes::FREE_ITEM->value;
    $newRewardRecord['target_type'] = RewardTargetTypes::BRANDS->value;
    $newRewardRecord['status'] = true;
    $newRewardRecord['brand_ids'] = $brandIds;

    $this->rewardQueries->update(new RewardData(...$newRewardRecord), $this->rewardA->id, $this->companyId);

    $this->assertDatabaseHas('rewards', [
        'company_id' => $this->companyId,
        'title' => 'IJKL',
        'loyalty_point' => 11.11,
    ]);

    $this->assertDatabaseHas('brand_reward', [
        'brand_id' => $brand->id,
    ]);
});

test('getExport method returns proper response', function (): void {
    $response = $this->rewardQueries->getExport([
        'search_text' => 'ABCD',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ], $this->companyId);

    expect($response->first()->toArray())
        ->toHaveKey('title', $this->rewardA->title)
        ->toHaveKey('minimum_point', $this->rewardA->minimum_point)
        ->toHaveKey('maximum_point', $this->rewardA->maximum_point);
});

test('setStatus method returns proper response', function (): void {
    $rewardC = Reward::factory()->create([
        'company_id' => $this->companyId,
        'status' => false,
    ]);

    $this->assertDatabaseHas(Reward::class, [
        'id' => $rewardC->getKey(),
        'status' => false,
    ]);

    $this->rewardQueries->setStatus($rewardC->getKey(), $this->companyId, true);

    $this->assertDatabaseHas(Reward::class, [
        'id' => $rewardC->getKey(),
        'status' => true,
    ]);
});

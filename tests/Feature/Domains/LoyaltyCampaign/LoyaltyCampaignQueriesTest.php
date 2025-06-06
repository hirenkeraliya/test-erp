<?php

declare(strict_types=1);

use App\Domains\LoyaltyCampaign\DataObjects\LoyaltyCampaignData;
use App\Domains\LoyaltyCampaign\LoyaltyCampaignQueries;
use App\Models\Admin;
use App\Models\Brand;
use App\Models\Company;
use App\Models\LoyaltyCampaign;
use Carbon\Carbon;

beforeEach(function (): void {
    $this->companyId = Company::factory()->create()->id;

    $this->loyaltyCampaignA = LoyaltyCampaign::factory()->create([
        'company_id' => $this->companyId,
        'name' => 'ABCD',
        'minimum_spend_amount' => 10.10,
        'loyalty_points' => 10,
        'start_date' => '2022-05-10',
        'end_date' => '2022-05-11',
    ]);

    $this->loyaltyCampaignB = LoyaltyCampaign::factory()->create([
        'company_id' => $this->companyId,
        'name' => 'XYZW',
        'minimum_spend_amount' => 20.20,
        'loyalty_points' => 20,
        'start_date' => '2022-09-11',
        'end_date' => Carbon::now()->addYear()->format('Y-m-d'),
    ]);

    $this->loyaltyCampaignQueries = new LoyaltyCampaignQueries();
});

test('loyalty campaigns can be searched', function (): void {
    $response = $this->loyaltyCampaignQueries->listQuery([
        'search_text' => 'ABCD',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
        'start_date' => null,
        'end_date' => null,
    ], $this->companyId);

    $this->assertEquals(1, $response->total());

    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('name', $this->loyaltyCampaignA->name)
        ->toHaveKey('minimum_spend_amount', $this->loyaltyCampaignA->minimum_spend_amount)
        ->toHaveKey('loyalty_point_expiration_days', $this->loyaltyCampaignA->loyalty_point_expiration_days);
});

test('A loyalty campaign can be fetched', function (): void {
    $response = $this->loyaltyCampaignQueries->getById($this->loyaltyCampaignA->id, $this->companyId);
    expect($response->toArray())
        ->toHaveKey('name', $this->loyaltyCampaignA->name)
        ->toHaveKey('minimum_spend_amount', $this->loyaltyCampaignA->minimum_spend_amount)
        ->toHaveKey('loyalty_point_expiration_days', $this->loyaltyCampaignA->loyalty_point_expiration_days);
});

test('New loyalty campaign can be added', function (): void {
    $brand = Brand::factory()->create();
    $brandIds = [$brand->id];
    $admin = Admin::factory()->create();
    $this->loyaltyCampaignQueries->addNew(
        new LoyaltyCampaignData('EFGH', 11.11, 11, '2022-09-11', '2022-10-15', 10, $brandIds),
        $this->companyId,
        $admin
    );

    $this->assertDatabaseHas('loyalty_campaigns', [
        'company_id' => $this->companyId,
        'name' => 'EFGH',
        'minimum_spend_amount' => 11.11,
    ]);

    $this->assertDatabaseHas('brand_loyalty_campaign', [
        'brand_id' => $brand->id,
    ]);
});

test('A loyalty campaign can be updated', function (): void {
    $brand = Brand::factory()->create();
    $brandIds = [$brand->id];

    $this->loyaltyCampaignQueries->update(
        new LoyaltyCampaignData('IJKL', 30.30, 30, '2022-05-10', '2022-05-12', 10, $brandIds),
        $this->loyaltyCampaignA->id,
        $this->companyId
    );

    $this->assertDatabaseHas('loyalty_campaigns', [
        'company_id' => $this->companyId,
        'name' => 'IJKL',
        'minimum_spend_amount' => 30.30,
    ]);

    $this->assertDatabaseHas('brand_loyalty_campaign', [
        'brand_id' => $brand->id,
    ]);
});

test(
    'get active loyalty campaigns by company method returns the loyalty campaign list',
    function (): void {
        $brand = Brand::factory()->create([
            'name' => 'Test Brand',
            'code' => '12345674',
        ]);

        $this->loyaltyCampaignB->excludedBrands()->sync([$brand->id]);

        $response = $this->loyaltyCampaignQueries->getActiveLoyaltyCampaignsByCompanyId($this->companyId);

        expect($response->first()->toArray())
            ->toHaveKey('name', $this->loyaltyCampaignB->name)
            ->toHaveKey('minimum_spend_amount', $this->loyaltyCampaignB->minimum_spend_amount)
            ->toHaveKey('loyalty_points', $this->loyaltyCampaignB->loyalty_points)
            ->toHaveKey('excluded_brands.0.id', $brand->id)
            ->toHaveKey('excluded_brands.0.name', $brand->name)
            ->toHaveKey('excluded_brands.0.code', $brand->code)
            ->toHaveKey('loyalty_point_expiration_days', $this->loyaltyCampaignB->loyalty_point_expiration_days);
    }
);

test('getLoyaltyCampaignsExport method returns cashback as expected', function (): void {
    $response = $this->loyaltyCampaignQueries->getLoyaltyCampaignsExport([
        'search_text' => $this->loyaltyCampaignA->name,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
        'start_date' => null,
        'end_date' => null,
    ], $this->companyId);

    expect($response->first()->toArray())
        ->toHaveKey('name', $this->loyaltyCampaignA->name)
        ->toHaveKey('minimum_spend_amount', $this->loyaltyCampaignA->minimum_spend_amount);
});

test(
    'getByIds method returns the loyalty campaign list',
    function (): void {
        $brand = Brand::factory()->create([
            'name' => 'Test Brand',
            'code' => '12345674',
        ]);

        $this->loyaltyCampaignB->excludedBrands()->sync([$brand->id]);

        $response = $this->loyaltyCampaignQueries->getByIds([$this->loyaltyCampaignB->id], $this->companyId);

        expect($response->first()->toArray())
            ->toHaveKey('name', $this->loyaltyCampaignB->name)
            ->toHaveKey('minimum_spend_amount', $this->loyaltyCampaignB->minimum_spend_amount)
            ->toHaveKey('excluded_brands.0.id', $brand->id)
            ->toHaveKey('excluded_brands.0.name', $brand->name)
            ->toHaveKey('excluded_brands.0.code', $brand->code);
    }
);

test('getLoyaltyCampaignsForApplication method returns paginated results as expected', function (): void {
    $loyaltyCampaign = LoyaltyCampaign::factory()->create([
        'company_id' => $this->companyId,
        'start_date' => Carbon::now()->subMonth()->startOfMonth()->format('Y-m-d'),
        'end_date' => Carbon::now()->subMonth()->endOfMonth()->format('Y-m-d'),
    ]);

    $response = $this->loyaltyCampaignQueries->getLoyaltyCampaignsForApplication([
        'sort_by' => null,
        'sort_direction' => null,
        'search_text' => null,
        'per_page' => 1,
        'selected_date' => Carbon::now()->subMonth()->startOfMonth()->format('Y-m-d'),
    ], $this->companyId);

    expect($response->toArray()['data'][0])
        ->toHaveKey('name', $loyaltyCampaign->name)
        ->toHaveKey('loyalty_points', $loyaltyCampaign->loyalty_points);
});

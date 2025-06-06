<?php

declare(strict_types=1);

use App\Domains\Company\CompanyQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\Sale\SaleQueries;
use App\Domains\SaleReturn\SaleReturnQueries;
use App\Models\Company;
use App\Models\Location;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Services\IOICityMallSalesDataService;
use Illuminate\Support\Collection;

beforeEach(function (): void {
    $this->ioiCityMallSalesDataService = new IOICityMallSalesDataService();
});

it('returns an empty collection when storeIdentifier is null', function (): void {
    $this->mock(CompanyQueries::class, function ($mock): void {
        $mock->shouldReceive('getWithIdNameAndIOICityMall')
            ->once()
            ->andReturn(collect([]));
    });

    $response = $this->ioiCityMallSalesDataService->storesList();

    expect($response)->toBeInstanceOf(Collection::class);
    expect($response)->toBeEmpty();
});

it('returns location details for a valid storeIdentifier with enabled IOI City Mall integration', function (): void {
    $company = Company::factory()->make([
        'enable_ioi_city_mall_integration' => true,
        'default_country_id' => 1,
    ]);

    $location = Location::factory()->make([
        'company_id' => $company->id,
        'ioi_city_mall_machine_id' => '123465',
        'enable_ioi_city_mall_data_sharing' => true,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $location->company = $company;

    $this->mock(LocationQueries::class, function ($mock) use ($location): void {
        $mock->shouldReceive('getDetailsByNameForIOICityMall')
            ->once()
            ->andReturn($location);
    });

    $response = $this->ioiCityMallSalesDataService->storesList($location->name);

    expect($response)->toBeInstanceOf(Collection::class);
    expect($response)->not->toBeEmpty();
    expect($response->first())
        ->toHaveKey('store_identifier', $location->name)
        ->toHaveKey('machine_id', $location->ioi_city_mall_machine_id)
        ->toHaveKeys(['sst_registered']);
});

it(
    'returns empty collection when storeIdentifier is valid but IOI City Mall integration is disabled for the company',
    function (): void {
        $company = Company::factory()->make([
            'enable_ioi_city_mall_integration' => false,
            'default_country_id' => 1,
        ]);

        $location = Location::factory()->make([
            'company_id' => $company->id,
            'ioi_city_mall_machine_id' => '123465',
            'enable_ioi_city_mall_data_sharing' => true,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $location->company = $company;

        $this->mock(LocationQueries::class, function ($mock) use ($location): void {
            $mock->shouldReceive('getDetailsByNameForIOICityMall')
            ->once()
            ->andReturn($location);
        });

        $response = $this->ioiCityMallSalesDataService->storesList($location->name);

        expect($response)->toBeInstanceOf(Collection::class);
        expect($response)->toBeEmpty();
    }
);

it('returns empty collection when location does not exist', function (): void {
    $company = Company::factory()->make([
        'enable_ioi_city_mall_integration' => false,
        'default_country_id' => 1,
    ]);

    $location = Location::factory()->make([
        'company_id' => $company->id,
        'ioi_city_mall_machine_id' => '123465',
        'enable_ioi_city_mall_data_sharing' => true,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $location->company = $company;

    $this->mock(LocationQueries::class, function ($mock): void {
        $mock->shouldReceive('getIdByNameForIOICityMall')
            ->once()
            ->andReturn(null);
    });

    $response = $this->ioiCityMallSalesDataService->salesData($location->name, now()->format('Y-m-d'));

    expect($response)->toBeInstanceOf(Collection::class);
    expect($response)->toBeEmpty();
});

it('returns sales data collection for a valid location and date', function (): void {
    $company = Company::factory()->make([
        'enable_ioi_city_mall_integration' => false,
        'default_country_id' => 1,
    ]);

    $location = Location::factory()->make([
        'company_id' => $company->id,
        'ioi_city_mall_machine_id' => '123465',
        'enable_ioi_city_mall_data_sharing' => true,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $date = now()->format('Y-m-d');

    $sale = Sale::factory(2)->make([
        'counter_update_id' => 1,
        'happened_at' => $date,
        'member_id' => 1,
        'total_amount_paid' => 10,
        'total_discount_amount' => 0,
        'total_tax_amount' => 5,
    ]);

    $saleReturn = SaleReturn::factory(2)->make([
        'counter_update_id' => 1,
        'original_sale_id' => 1,
        'happened_at' => $date,
        'member_id' => 1,
        'total_price_paid' => 10,
        'total_discount_amount' => 0,
        'total_tax_amount' => 5,
    ]);

    $location->company = $company;

    $this->mock(LocationQueries::class, function ($mock) use ($location): void {
        $mock->shouldReceive('getIdByNameForIOICityMall')
            ->once()
            ->andReturn($location);
    });

    $this->mock(SaleQueries::class, function ($mock) use ($sale): void {
        $mock->shouldReceive('getSalesDataCollectionForTheIOICityMall')
            ->once()
            ->andReturn($sale);
    });

    $this->mock(SaleReturnQueries::class, function ($mock) use ($saleReturn): void {
        $mock->shouldReceive('getSaleReturnsDataCollectionForTheIOICityMall')
            ->once()
            ->andReturn($saleReturn);
    });

    $response = $this->ioiCityMallSalesDataService->salesData($location->name, $date);

    expect($response)->toBeInstanceOf(Collection::class);
    expect($response->first())
        ->toHaveKey('happened_at', $date)
        ->toHaveKey('net_amount', $sale->first()->total_amount_paid)
        ->toHaveKey('discount', $sale->first()->total_discount_amount)
        ->toHaveKey('SST', $sale->first()->total_tax_amount)
        ->toHaveKeys(['payments']);
});

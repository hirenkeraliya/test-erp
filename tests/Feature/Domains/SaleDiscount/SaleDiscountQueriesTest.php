<?php

declare(strict_types=1);

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Sale\Enums\SaleStatus;
use App\Domains\SaleDiscount\SaleDiscountQueries;
use App\Models\Company;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\Location;
use App\Models\Sale;
use App\Models\SaleDiscount;
use App\Models\Voucher;

test('Sale Discount can be added', function (): void {
    $saleDiscount = SaleDiscount::factory()->make();

    $saleDiscountQueries = new SaleDiscountQueries();
    $saleDiscountQueries->addNew(
        $saleDiscount->sale_id,
        $saleDiscount->discountable_id,
        $saleDiscount->discountable_type,
        10.20
    );

    $this->assertDatabaseHas('sale_discounts', [
        'sale_id' => $saleDiscount->sale_id,
        'discountable_id' => $saleDiscount->discountable_id,
        'discountable_type' => $saleDiscount->discountable_type,
        'amount' => 10.20,
    ]);
});

test('getSaleDiscountByCounterUpdateId method returns the records by counter update id', function (): void {
    $counterUpdate = CounterUpdate::factory()->create();
    $sale = Sale::factory()->create([
        'counter_update_id' => $counterUpdate->id,
        'status' => SaleStatus::REGULAR_SALE->value,
    ]);
    $voucher = Voucher::factory()->create();
    SaleDiscount::factory()->create([
        'sale_id' => $sale->id,
        'discountable_type' => ModelMapping::VOUCHER->name,
        'discountable_id' => $voucher->id,
    ]);
    $saleDiscountQueries = new SaleDiscountQueries();
    $response = $saleDiscountQueries->getSaleDiscountByCounterUpdateId($counterUpdate->id);
    expect($response->first()->toArray())
        ->toHaveKeys(['discountable_id', 'discountable_type', 'sale_id', 'amount']);
});

test('getVoucherIdBySale method returns the records by counter update id', function (): void {
    $sale = Sale::factory()->create([
        'status' => SaleStatus::REGULAR_SALE->value,
    ]);

    $voucher = Voucher::factory()->create();

    SaleDiscount::factory()->create([
        'sale_id' => $sale->id,
        'discountable_type' => ModelMapping::VOUCHER->name,
        'discountable_id' => $voucher->id,
    ]);

    $saleDiscountQueries = new SaleDiscountQueries();
    $response = $saleDiscountQueries->getVoucherIdBySale($sale->id);

    $this->assertEquals($voucher->id, $response);
});

test('getSaleDiscountBasedOnFilterForSaleSeasonalSum return the summation of the amount', function (): void {
    $companyId = Company::factory()->create()->id;
    $location = Location::factory()->create([
        'company_id' => $companyId,
        'type_id' => LocationTypes::STORE->value,
    ]);
    $counter = Counter::factory()->create([
        'location_id' => $location->id,
    ]);
    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->id,
    ]);
    $sale = Sale::factory()->create([
        'counter_update_id' => $counterUpdate->id,
        'status' => SaleStatus::REGULAR_SALE->value,
    ]);
    $voucher = Voucher::factory()->create();
    $date = now()->format('Y-m-d H:i:s');
    $saleDiscount = SaleDiscount::factory(2)->create([
        'sale_id' => $sale->id,
        'discountable_type' => ModelMapping::VOUCHER->name,
        'discountable_id' => $voucher->id,
        'amount' => 10.0,
        'created_at' => $date,
    ]);
    $filterData = [
        'location_id' => 0,
        'brand_id' => 0,
        'start_date' => $date,
        'end_date' => $date,
    ];
    $saleDiscountQueries = new SaleDiscountQueries();
    $response = $saleDiscountQueries->getSaleDiscountBasedOnFilterForSaleSeasonalSum($filterData, $companyId);
    expect($response)
        ->toBe($saleDiscount->sum('amount'));
});

test(
    'getSaleDiscountBasedOnFilterForSaleSeasonal return the collection of the based on the filter given',
    function (): void {
        $companyId = Company::factory()->create()->id;
        $location = Location::factory()->create([
            'company_id' => $companyId,
            'type_id' => LocationTypes::STORE->value,
        ]);
        $counter = Counter::factory()->create([
            'location_id' => $location->id,
        ]);
        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $counter->id,
        ]);
        $sale = Sale::factory()->create([
            'counter_update_id' => $counterUpdate->id,
            'status' => SaleStatus::REGULAR_SALE->value,
        ]);
        $voucher = Voucher::factory()->create();
        $date = now()->format('Y-m-d H:i:s');
        $saleDiscount = SaleDiscount::factory(2)->create([
            'sale_id' => $sale->id,
            'discountable_type' => ModelMapping::VOUCHER->name,
            'discountable_id' => $voucher->id,
            'created_at' => $date,
        ]);
        $filterData = [
            'location_id' => 0,
            'brand_id' => 0,
            'start_date' => $date,
            'end_date' => $date,
        ];
        $saleDiscountQueries = new SaleDiscountQueries();
        $response = $saleDiscountQueries->getSaleDiscountBasedOnFilterForSaleSeasonal($filterData, $companyId);
        expect($response->first()->toArray())
            ->toHaveKey('discountable_type', $saleDiscount->first()->discountable_type)
            ->toHaveKey('discountable_id', $saleDiscount->first()->discountable_id)
            ->toHaveKey('amount', $saleDiscount->first()->amount);
    }
);

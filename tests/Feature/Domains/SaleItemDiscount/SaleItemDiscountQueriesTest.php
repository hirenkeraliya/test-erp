<?php

declare(strict_types=1);

use App\CommonFunctions;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Sale\Enums\SaleStatus;
use App\Domains\SaleItemDiscount\Enums\DiscountableTypes;
use App\Domains\SaleItemDiscount\SaleItemDiscountQueries;
use App\Models\Company;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\DreamPrice;
use App\Models\Location;
use App\Models\Promotion;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleItemDiscount;

test('Sale item discount can be added', function (): void {
    $saleItem = SaleItem::factory()->create();

    $dreamPrice = DreamPrice::factory()->create();

    $saleItemDiscountQueries = new SaleItemDiscountQueries();
    $saleItemDiscountQueries->addNew($saleItem->id, $dreamPrice->id, DiscountableTypes::DREAM_PRICE->value, 10.20);

    $this->assertDatabaseHas('sale_item_discounts', [
        'sale_item_id' => $saleItem->id,
        'discountable_id' => $dreamPrice->id,
        'discountable_type' => DiscountableTypes::DREAM_PRICE->value,
        'amount' => 10.20,
    ]);
});

test('getSaleItemDiscountByCounterUpdateId method returns the records by counter update id', function (): void {
    $counterUpdate = CounterUpdate::factory()->create();
    $sale = Sale::factory()->create([
        'counter_update_id' => $counterUpdate->id,
        'status' => SaleStatus::REGULAR_SALE->value,
    ]);
    $saleItem = SaleItem::factory()->create([
        'sale_id' => $sale->id,
    ]);
    $promotion = Promotion::factory()->create();
    SaleItemDiscount::factory()->create([
        'sale_item_id' => $saleItem->id,
        'discountable_type' => ModelMapping::PROMOTION->name,
        'discountable_id' => $promotion->id,
    ]);
    $saleItemDiscountQueries = new SaleItemDiscountQueries();
    $response = $saleItemDiscountQueries->getSaleItemDiscountByCounterUpdateId($counterUpdate->id);
    expect($response->first()->toArray())
        ->toHaveKeys(['sale_item_id', 'discountable_id', 'discountable_type', 'amount']);
});

test(
    'getSaleItemDiscountBasedOnFilterForSaleSeasonalSum return the summation of the amount',
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
        $saleItem = SaleItem::factory()->create([
            'sale_id' => $sale->id,
        ]);
        $promotion = Promotion::factory()->create();
        $date = now()->format('Y-m-d H:i:s');
        $saleItemDiscount = SaleItemDiscount::factory(2)->create([
            'sale_item_id' => $saleItem->id,
            'discountable_type' => ModelMapping::PROMOTION->name,
            'discountable_id' => $promotion->id,
            'created_at' => $date,
        ]);
        $filterData = [
            'location_id' => 0,
            'brand_id' => 0,
            'start_date' => $date,
            'end_date' => $date,
        ];
        $saleItemDiscountQueries = new SaleItemDiscountQueries();
        $response = $saleItemDiscountQueries->getSaleItemDiscountBasedOnFilterForSaleSeasonalSum(
            $filterData,
            $companyId
        );
        expect($response)
            ->toBe(CommonFunctions::numberFormat($saleItemDiscount->sum('amount')));
    }
);

test(
    'getSaleItemDiscountBasedOnFilterForSaleSeasonal return the collection of the based on the filter given',
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
        $saleItem = SaleItem::factory()->create([
            'sale_id' => $sale->id,
        ]);
        $promotion = Promotion::factory()->create();
        $date = now()->format('Y-m-d H:i:s');
        $saleItemDiscount = SaleItemDiscount::factory(2)->create([
            'sale_item_id' => $saleItem->id,
            'discountable_type' => ModelMapping::PROMOTION->name,
            'discountable_id' => $promotion->id,
            'created_at' => $date,
        ]);
        $filterData = [
            'location_id' => 0,
            'brand_id' => 0,
            'start_date' => $date,
            'end_date' => $date,
        ];
        $saleItemDiscountQueries = new SaleItemDiscountQueries();
        $response = $saleItemDiscountQueries->getSaleItemDiscountBasedOnFilterForSaleSeasonal($filterData, $companyId);
        expect($response->first()->toArray())
            ->toHaveKey('discountable_type', $saleItemDiscount->first()->discountable_type)
            ->toHaveKey('discountable_id', $saleItemDiscount->first()->discountable_id)
            ->toHaveKey('amount', $saleItemDiscount->first()->amount);
    }
);

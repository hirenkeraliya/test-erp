<?php

declare(strict_types=1);

use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Sale\Enums\SaleStatus;
use App\Domains\SaleReturnItem\SaleReturnItemQueries;
use App\Models\Company;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\Location;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

beforeEach(function (): void {
    $this->saleReturnItemQueries = new SaleReturnItemQueries();
});

test('new sale return item can be added', function (): void {
    $saleReturnItem = SaleReturnItem::factory()->make();

    $this->saleReturnItemQueries->addNew(
        $saleReturnItem->sale_return_reason_id,
        $saleReturnItem->sale_return_id,
        $saleReturnItem->original_sale_item_id,
        $saleReturnItem->product_id,
        20.20,
        30.20,
        10.10,
        10.10,
        10.10,
        10.10
    );

    $this->assertDatabaseHas('sale_return_items', [
        'sale_return_id' => $saleReturnItem->sale_return_id,
        'original_sale_item_id' => $saleReturnItem->original_sale_item_id,
        'product_id' => $saleReturnItem->product_id,
        'quantity' => 20.20,
        'total_price_paid' => 30.20,
        'sale_return_reason_id' => $saleReturnItem->sale_return_reason_id,
    ]);
});

test('getByIdWithRelation methods returns the sale return item details', function (): void {
    $saleReturnItem = SaleReturnItem::factory()->create();

    $response = $this->saleReturnItemQueries->getByIdWithRelation($saleReturnItem->id);

    expect($response->first()->toArray())
        ->toHaveKey('id', $saleReturnItem->id)
        ->toHaveKey('original_sale_item_id', $saleReturnItem->original_sale_item_id);
});

test('if product is merged then the product id is updated', function (): void {
    $companyId = Company::factory()->create()->id;
    $locationId = Location::factory()->create([
        'company_id' => $companyId,
        'type_id' => LocationTypes::STORE->value,
    ])->id;
    $counterId = Counter::factory()->create([
        'location_id' => $locationId,
    ])->id;
    $counterUpdateId = CounterUpdate::factory()->create([
        'counter_id' => $counterId,
    ])->id;
    $saleReturn = SaleReturn::factory()->create([
        'counter_update_id' => $counterUpdateId,
    ]);
    $productAId = Product::factory()->create()->id;
    $productBId = Product::factory()->create()->id;

    $saleReturnItem = SaleReturnItem::factory()->create([
        'sale_return_id' => $saleReturn->id,
        'product_id' => $productBId,
    ]);

    $this->saleReturnItemQueries->updateProductId($companyId, $productBId, $productAId);

    $this->assertDatabaseHas('sale_return_items', [
        'sale_return_id' => $saleReturn->id,
        'product_id' => $productAId,
    ]);
});

test(
    'getCachedTodaySalesForDashboard method returns sale return details',
    function (): void {
        $companyId = Company::factory()->create()->id;

        $location = Location::factory()->create([
            'company_id' => $companyId,
        ]);

        $counter = Counter::factory()->create([
            'location_id' => $location->getKey(),
        ]);

        $date = now();

        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $counter->getKey(),
            'opened_by_pos_at' => $date->format('Y-m-d H:i:s'),
        ]);

        $sale = Sale::factory()->create([
            'counter_update_id' => $counterUpdate->getKey(),
            'status' => SaleStatus::REGULAR_SALE->value,
        ]);

        $product = Product::factory()->create([
            'is_non_selling_item' => false,
        ]);

        SaleItem::factory()->create([
            'sale_id' => $sale->getKey(),
            'product_id' => $product->getKey(),
            'total_tax_amount' => 10.00,
            'total_price_paid' => 20.00,
            'cart_discount_amount' => 30.00,
        ]);

        $saleReturn = SaleReturn::factory()->create([
            'counter_update_id' => $counterUpdate->getKey(),
            'original_sale_id' => $sale->getKey(),
        ]);

        $saleReturnItem = SaleReturnItem::factory()->create([
            'sale_return_id' => $saleReturn->id,
            'total_price_paid' => 20.00,
            'quantity' => 2,
            'product_id' => $product->getKey(),
        ]);

        $newSale = Sale::factory()->create([
            'counter_update_id' => $counterUpdate->getKey(),
            'status' => SaleStatus::REGULAR_SALE->value,
            'sale_return_id' => $saleReturn->id,
        ]);

        SaleItem::factory()->create([
            'sale_id' => $newSale->getKey(),
            'sale_return_item_id' => $saleReturnItem->id,
            'product_id' => $product->getKey(),
            'total_tax_amount' => 10.00,
            'total_price_paid' => 20.00,
            'cart_discount_amount' => 30.00,
            'is_exchange' => 0,
        ]);

        $cacheKey = 'cache-today-sale-returns-dashboard-' . null . null . $date->format('Y-m-d') . $date->format(
            'Y-m-d'
        );

        $response = $this->saleReturnItemQueries->getCachedTodaySaleReturnsForDashboard(
            $companyId,
            null,
            null,
            $date->format('Y-m-d'),
            $date->format('Y-m-d'),
        );

        expect($response->toArray())
            ->toHaveKey('return_amount', 20)
            ->toHaveKey('return_units', 2);

        expect(Cache::has($cacheKey))->toBeTrue();

        $cachedResponse = $this->saleReturnItemQueries->getCachedTodaySaleReturnsForDashboard(
            $companyId,
            null,
            null,
            $date->format('Y-m-d'),
            $date->format('Y-m-d'),
        );

        expect($cachedResponse)->toEqual($response);
    }
);

test(
    'getYesterdaySaleReturnWithSaleReturnItems method returns yesterday sale return details',
    function (): void {
        $companyId = Company::factory()->create()->id;

        $location = Location::factory()->create([
            'company_id' => $companyId,
        ]);

        $counter = Counter::factory()->create([
            'location_id' => $location->getKey(),
        ]);

        $date = Carbon::yesterday()->format('Y-m-d H:i:s');

        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $counter->getKey(),
        ]);

        $sale = Sale::factory()->create([
            'counter_update_id' => $counterUpdate->getKey(),
            'status' => SaleStatus::REGULAR_SALE->value,
        ]);

        $product = Product::factory()->create([
            'is_non_selling_item' => false,
        ]);

        SaleItem::factory()->create([
            'sale_id' => $sale->getKey(),
            'product_id' => $product->getKey(),
            'total_tax_amount' => 10.00,
            'total_price_paid' => 20.00,
            'cart_discount_amount' => 30.00,
        ]);

        $saleReturn = SaleReturn::factory()->create([
            'counter_update_id' => $counterUpdate->getKey(),
            'original_sale_id' => $sale->getKey(),
            'happened_at' => $date,
        ]);

        SaleReturnItem::factory()->create([
            'sale_return_id' => $saleReturn->id,
            'total_price_paid' => 20.00,
            'quantity' => 2,
            'product_id' => $product->getKey(),
        ]);

        $response = $this->saleReturnItemQueries->getYesterdaySaleReturnWithSaleReturnItems($date);

        expect(current($response->toArray()))
            ->toHaveKeys(['location_id', 'product_id']);
    }
);

test(
    'getSaleReturnItemForTheStoreManagerApplicationDashboard method returns sale return Item details for store-manager api',
    function (): void {
        $companyId = Company::factory()->create()->id;

        $location = Location::factory()->create([
            'company_id' => $companyId,
        ]);

        $counter = Counter::factory()->create([
            'location_id' => $location->getKey(),
        ]);

        $date = now();

        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $counter->getKey(),
            'opened_by_pos_at' => $date->format('Y-m-d H:i:s'),
        ]);

        $sale = Sale::factory()->create([
            'counter_update_id' => $counterUpdate->getKey(),
            'status' => SaleStatus::REGULAR_SALE->value,
        ]);

        $product = Product::factory()->create([
            'is_non_selling_item' => false,
        ]);

        SaleItem::factory()->create([
            'sale_id' => $sale->getKey(),
            'product_id' => $product->getKey(),
            'total_tax_amount' => 10.00,
            'total_price_paid' => 20.00,
            'cart_discount_amount' => 30.00,
        ]);

        $saleReturn = SaleReturn::factory()->create([
            'counter_update_id' => $counterUpdate->getKey(),
            'original_sale_id' => $sale->getKey(),
        ]);

        $saleReturnItem = SaleReturnItem::factory()->create([
            'sale_return_id' => $saleReturn->id,
            'total_price_paid' => 20.00,
            'quantity' => 2,
            'product_id' => $product->getKey(),
        ]);

        $newSale = Sale::factory()->create([
            'counter_update_id' => $counterUpdate->getKey(),
            'status' => SaleStatus::REGULAR_SALE->value,
            'sale_return_id' => $saleReturn->id,
        ]);

        SaleItem::factory()->create([
            'sale_id' => $newSale->getKey(),
            'sale_return_item_id' => $saleReturnItem->id,
            'product_id' => $product->getKey(),
            'total_tax_amount' => 10.00,
            'total_price_paid' => 20.00,
            'cart_discount_amount' => 30.00,
            'is_exchange' => 0,
        ]);

        $dateArray = [$date->format('Y-m-d'), $date->format('Y-m-d')];

        $response = $this->saleReturnItemQueries->getSaleReturnItemForTheStoreManagerApplicationDashboard(
            $location->id,
            $dateArray,
        );

        expect($response->toArray())
            ->toHaveKey('total_sales_amount', 20)
            ->toHaveKey('total_sales', 1);
    }
);

test(
    'getSalesReturnForDashboardByDate method returns sale return details',
    function (): void {
        $companyId = Company::factory()->create()->id;

        $location = Location::factory()->create([
            'company_id' => $companyId,
        ]);

        $counter = Counter::factory()->create([
            'location_id' => $location->getKey(),
        ]);

        $date = now();

        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $counter->getKey(),
            'opened_by_pos_at' => $date->format('Y-m-d H:i:s'),
        ]);

        $sale = Sale::factory()->create([
            'counter_update_id' => $counterUpdate->getKey(),
            'status' => SaleStatus::REGULAR_SALE->value,
        ]);

        $product = Product::factory()->create([
            'is_non_selling_item' => false,
        ]);

        SaleItem::factory()->create([
            'sale_id' => $sale->getKey(),
            'product_id' => $product->getKey(),
            'total_tax_amount' => 10.00,
            'total_price_paid' => 20.00,
            'cart_discount_amount' => 30.00,
        ]);

        $saleReturn = SaleReturn::factory()->create([
            'counter_update_id' => $counterUpdate->getKey(),
            'original_sale_id' => $sale->getKey(),
        ]);

        $saleReturnItem = SaleReturnItem::factory()->create([
            'sale_return_id' => $saleReturn->id,
            'total_price_paid' => 20.00,
            'quantity' => 2,
            'product_id' => $product->getKey(),
        ]);

        $newSale = Sale::factory()->create([
            'counter_update_id' => $counterUpdate->getKey(),
            'status' => SaleStatus::REGULAR_SALE->value,
            'sale_return_id' => $saleReturn->id,
        ]);

        SaleItem::factory()->create([
            'sale_id' => $newSale->getKey(),
            'sale_return_item_id' => $saleReturnItem->id,
            'product_id' => $product->getKey(),
            'total_tax_amount' => 10.00,
            'total_price_paid' => 20.00,
            'cart_discount_amount' => 30.00,
            'is_exchange' => 0,
        ]);

        $response = $this->saleReturnItemQueries->getSalesReturnForDashboardByDate(
            $companyId,
            $date->format('Y-m-d'),
            $date->format('Y-m-d'),
        );

        expect($response[0]->toArray())
            ->toHaveKey('return_amount', 20)
            ->toHaveKey('return_units', 2);
    }
);

<?php

declare(strict_types=1);

use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Product\Enums\ProductTypes;
use App\Domains\Product\Enums\Statuses;
use App\Domains\Sale\Enums\SaleStatus;
use App\Domains\SaleDiscount\Enums\DiscountableTypes as EnumsDiscountableTypes;
use App\Domains\SaleItem\SaleItemQueries;
use App\Domains\SaleItemDiscount\Enums\DiscountableTypes;
use App\Models\BoxProduct;
use App\Models\Brand;
use App\Models\Company;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\Employee;
use App\Models\Location;
use App\Models\LoyaltyCampaign;
use App\Models\LoyaltyPoint;
use App\Models\MasterProduct;
use App\Models\Member;
use App\Models\PackageType;
use App\Models\Product;
use App\Models\Promoter;
use App\Models\Promotion;
use App\Models\Sale;
use App\Models\SaleCashback;
use App\Models\SaleDiscount;
use App\Models\SaleItem;
use App\Models\SaleItemDiscount;
use App\Models\SaleItemUnit;
use App\Models\SalePayment;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

beforeEach(function (): void {
    $this->companyId = Company::factory()->create()->id;
    $this->saleItemQueries = new SaleItemQueries();
});

test('new sale item can be added', function (): void {
    $sale = Sale::factory()->create();

    $product = Product::factory()->create();

    $item = [
        'id' => $product->id,
        'price' => '10.00',
        'quantity' => '1',
        'promoter_ids' => null,
        'vendor_commission_percentage' => null,
    ];

    $itemTax = '0.1';

    $this->saleItemQueries->addNew($sale, $item, (float) '1', (float) $itemTax, 5.50, 2.50);

    $this->assertDatabaseHas('sale_items', [
        'sale_id' => $sale->id,
        'product_id' => $item['id'],
        'total_tax_amount' => $itemTax,
        'cart_discount_amount' => '5.50',
        'item_discount_amount' => '2.50',
    ]);
});

test(
    'getPaginatedMemberSalesReportList method returns member sales report as expected when product variant',
    function (bool $productVariant): void {
        Config::set('app.product_variant', $productVariant);

        $location = Location::factory()->create([
            'company_id' => $this->companyId,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $counter = Counter::factory()->create([
            'location_id' => $location->id,
        ]);

        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $counter->id,
        ]);

        $member = Member::factory()->create();

        $sale = Sale::factory()->create([
            'member_id' => $member->id,
            'counter_update_id' => $counterUpdate->id,
            'layaway_pending_amount' => null,
            'status' => SaleStatus::COMPLETE_LAYAWAY_SALE->value,
        ]);

        SaleDiscount::factory()->create([
            'sale_id' => $sale->id,
        ]);

        if ($productVariant) {
            $masterProduct = MasterProduct::factory()->create([
                'company_id' => $this->companyId,
                'has_batch' => false,
                'is_non_inventory' => false,
            ]);
        }

        $product = Product::factory()->create([
            'company_id' => $this->companyId,
            'compound_product_name' => $productVariant ? 'ABCD' : 'DEFG',
            'code' => $productVariant ? '8898998' : '12132465465',
            'status' => Statuses::ACTIVE->value,
            'master_product_id' => $productVariant ? $masterProduct->id : null,
            'has_batch' => false,
            'is_non_inventory' => false,
        ]);

        if ($productVariant) {
            $product->masterProduct = $masterProduct;
        }

        $saleItem = SaleItem::factory()->create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
        ]);

        SaleItemDiscount::factory()->create([
            'sale_item_id' => $saleItem->id,
        ]);

        $response = $this->saleItemQueries->getPaginatedMemberSalesReportList([
            'search_text' => $product->compound_product_name,
            'sort_by' => null,
            'sort_direction' => null,
            'per_page' => 15,
            'member_id' => null,
            'product_id' => null,
            'date_range' => null,
            'location_id' => null,
            'product_collection_id' => null,
        ], $this->companyId);

        $this->assertEquals(1, $response->total());

        if ($productVariant) {
            expect($response->getCollection()->first()->toArray())
                ->toHaveKey('sale_id', $sale->id)
                ->toHaveKey('quantity', $saleItem->quantity)
                ->toHaveKey('returned_quantity', $saleItem->returned_quantity)
                ->toHaveKey('product.master_product_id', $masterProduct->id)
                ->toHaveKeys(['product', 'product.master_product_id'], $masterProduct->id);
        } else {
            expect($response->getCollection()->first()->toArray())
                ->toHaveKey('sale_id', $sale->id)
                ->toHaveKey('quantity', $saleItem->quantity)
                ->toHaveKey('returned_quantity', $saleItem->returned_quantity)
                ->toHaveKeys(['product', 'product.color', 'product.size', 'sale.member']);
        }
    }
)->with([[true], [false]]);

test(
    'getPaginatedMemberSalesReportListForStoreManager method returns member sales report as expected',
    function (): void {
        $location = Location::factory()->create([
            'company_id' => $this->companyId,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $counter = Counter::factory()->create([
            'location_id' => $location->id,
        ]);

        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $counter->id,
        ]);

        $member = Member::factory()->create();

        $sale = Sale::factory()->create([
            'member_id' => $member->id,
            'counter_update_id' => $counterUpdate->id,
            'layaway_pending_amount' => null,
            'status' => SaleStatus::COMPLETE_LAYAWAY_SALE->value,
        ]);

        SaleDiscount::factory()->create([
            'sale_id' => $sale->id,
        ]);

        $product = Product::factory()->create();

        $saleItem = SaleItem::factory()->create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
        ]);

        SaleItemDiscount::factory()->create([
            'sale_item_id' => $saleItem->id,
        ]);

        $response = $this->saleItemQueries->getPaginatedMemberSalesReportListForStoreManager([
            'search_text' => $product->compound_product_name,
            'sort_by' => null,
            'sort_direction' => null,
            'per_page' => 15,
            'member_id' => null,
            'product_id' => null,
            'date_range' => null,
            'location_id' => null,
            'product_collection_id' => null,
        ], $location->id);

        $this->assertEquals(1, $response->total());

        expect($response->getCollection()->first()->toArray())
        ->toHaveKey('sale_id', $sale->id)
        ->toHaveKey('quantity', $saleItem->quantity)
        ->toHaveKey('returned_quantity', $saleItem->returned_quantity)
        ->toHaveKeys(['product', 'product.color', 'product.size', 'sale.member']);
    }
);

test('updateTotalPricePaid method update the sale item total paid price', function (): void {
    $saleItem = SaleItem::factory()->create();

    $this->saleItemQueries->updateTotalPricePaid($saleItem, 100.00);

    $this->assertDatabaseHas('sale_items', [
        'id' => $saleItem->id,
        'total_price_paid' => 100.00,
    ]);
});

test('addNew method can add sale item promoters while adding sale item', function (): void {
    $sale = Sale::factory()->create();

    $product = Product::factory()->create();

    $promoter = Promoter::factory()->create();

    $item = [
        'id' => $product->id,
        'price' => '10.00',
        'quantity' => '1',
        'promoter_ids' => [$promoter->id],
        'group_id' => 1,
        'vendor_commission_percentage' => null,
    ];

    $itemTax = '0.1';

    $response = $this->saleItemQueries->addNew($sale, $item, (float) '1', (float) $itemTax, 5.0, 2.0);

    $saleItem = $response->toArray();

    $this->assertDatabaseHas('sale_items', [
        'sale_id' => $sale->id,
        'product_id' => $item['id'],
        'cart_discount_amount' => 5.0,
        'item_discount_amount' => 2.0,
        'total_discount_amount' => 7.0,
        'total_tax_amount' => $itemTax,
        'group_id' => 1,
    ]);

    $this->assertDatabaseHas('sale_item_promoter', [
        'sale_item_id' => $saleItem['id'],
        'promoter_id' => $promoter->id,
    ]);
});

test('addNew method can add open price value while selling non-regular products', function (): void {
    $sale = Sale::factory()->create();

    $product = Product::factory()->create();

    $promoter = Promoter::factory()->create();

    $item = [
        'id' => $product->id,
        'open_price' => '10.00',
        'quantity' => '1',
        'promoter_ids' => [$promoter->id],
        'group_id' => 1,
        'vendor_commission_percentage' => null,
    ];

    $itemTax = '0.1';

    $response = $this->saleItemQueries->addNew($sale, $item, (float) '1', (float) $itemTax, 5.0, 2.0);

    $saleItem = $response->toArray();

    $this->assertDatabaseHas('sale_items', [
        'sale_id' => $sale->id,
        'product_id' => $item['id'],
        'original_price_per_unit' => $item['open_price'],
        'cart_discount_amount' => 5.0,
        'item_discount_amount' => 2.0,
        'total_discount_amount' => 7.0,
        'total_tax_amount' => $itemTax,
        'group_id' => 1,
    ]);

    $this->assertDatabaseHas('sale_item_promoter', [
        'sale_item_id' => $saleItem['id'],
        'promoter_id' => $promoter->id,
    ]);
});

test('updateLayawayAmountOf method updates the layaway amount', function (): void {
    $sale = Sale::factory()->create([
        'total_tax_amount' => 0,
        'cart_discount_amount' => 0,
        'items_discount_amount' => 0,
        'total_discount_amount' => 0,
        'total_amount_before_round_off' => 0,
        'total_amount_paid' => 10,
        'layaway_pending_amount' => 40,
        'status' => SaleStatus::PENDING_LAYAWAY_SALE->value,
    ]);

    SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'quantity' => 5,
        'returned_quantity' => 0,
        'original_price_per_unit' => 10,
        'cart_discount_amount' => 0,
        'item_discount_amount' => 0,
        'total_discount_amount' => 0,
        'total_tax_amount' => 0,
        'price_paid_per_unit' => 10,
        'total_price_paid' => 10,
    ]);

    $this->saleItemQueries->updateLayawayAmountOf($sale, 10, false);

    $this->assertDatabaseHas('sale_items', [
        'sale_id' => $sale->id,
        'total_price_paid' => (string) 20,
    ]);
});

test(
    'getByIdsWithRelations method returns the sale items with sale and units',
    function (): void {
        $member = Member::factory()->create([
            'company_id' => $this->companyId,
        ]);

        $sale = Sale::factory()->create([
            'status' => SaleStatus::REGULAR_SALE->value,
            'member_id' => $member->id,
        ]);

        $saleCashback = SaleCashback::factory()->create([
            'sale_id' => $sale->id,
        ]);

        $loyaltyCampaign = LoyaltyCampaign::factory()->create();

        $loyaltyPoint = LoyaltyPoint::factory()->create([
            'sale_id' => $sale->id,
            'loyalty_campaign_id' => $loyaltyCampaign->id,
        ]);

        $salePayment = SalePayment::factory()->create([
            'sale_id' => $sale->id,
        ]);

        $product = Product::factory()->create([
            'company_id' => $this->companyId,
        ]);

        $boxProduct = BoxProduct::factory()->create();
        $packageType = PackageType::factory()->create();

        $saleItem = SaleItem::factory()->create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'box_product_id' => $boxProduct->id,
            'product_box_package_type_id' => $packageType->id,
            'product_box_units' => 10.10,
        ]);

        $saleItemUnit = SaleItemUnit::factory()->create([
            'sale_item_id' => $saleItem->id,
        ]);

        $promotion = Promotion::factory()->create([
            'company_id' => $this->companyId,
        ]);

        $saleItemDiscount = SaleItemDiscount::factory()->create([
            'sale_item_id' => $saleItem->id,
            'discountable_id' => $promotion->id,
            'discountable_type' => DiscountableTypes::getDiscountableClass(DiscountableTypes::PROMOTION->value),
        ]);

        $saleDiscount = SaleDiscount::factory()->create([
            'sale_id' => $sale->id,
            'discountable_id' => $promotion->id,
            'discountable_type' => EnumsDiscountableTypes::getDiscountableClass(
                EnumsDiscountableTypes::PROMOTION->value
            ),
        ]);

        $response = $this->saleItemQueries->getByIdsWithRelations([$saleItem->id]);

        expect($response->first()->toArray())
            ->toHaveKey('id', $saleItem->id)
            ->toHaveKey('box_product_id', $boxProduct->id)
            ->toHaveKey('product_box_package_type_id', $packageType->id)
            ->toHaveKey('product_box_units', 10.10)
            ->toHaveKey('sale.id', $sale->id)
            ->toHaveKey('sale.cashback.id', $saleCashback->id)
            ->toHaveKey('sale.cashback.sale_id', $saleCashback->sale_id)
            ->toHaveKey('sale.sale_discounts.0.id', $saleDiscount->id)
            ->toHaveKey('sale.sale_discounts.0.sale_id', $saleDiscount->sale_id)
            ->toHaveKey('sale.payments.0.id', $salePayment->id)
            ->toHaveKey('sale.payments.0.sale_id', $salePayment->sale_id)
            ->toHaveKey('sale.issued_loyalty_points.0.id', $loyaltyPoint->id)
            ->toHaveKey('sale.issued_loyalty_points.0.sale_id', $loyaltyPoint->sale_id)
            ->toHaveKey('sale.issued_loyalty_points.0.loyalty_campaign.id', $loyaltyCampaign->id)
            ->toHaveKey('sale.issued_loyalty_points.0.loyalty_campaign.name', $loyaltyCampaign->name)
            ->toHaveKey('sale.issued_loyalty_points.0.loyalty_campaign.excluded_brands')
            ->toHaveKey('sale_item_units.0.sale_item_id', $saleItemUnit->sale_item_id)
            ->toHaveKey('sale_item_units.0.inventory_id', $saleItemUnit->inventory_id)
            ->toHaveKey('sale_item_discounts.0.id', $saleItemDiscount->id)
            ->toHaveKey('sale_item_discounts.0.sale_item_id', $saleItemDiscount->sale_item_id)
            ->toHaveKey('sale_item_discounts.0.discountable.id', $promotion->id)
            ->toHaveKey('sale_item_discounts.0.discountable.company_id', $promotion->company_id);
    }
);

test('incrementReturnedQuantity method updates the returned quantity', function (): void {
    $saleItem = SaleItem::factory()->create([
        'returned_quantity' => 5,
    ]);

    $this->saleItemQueries->incrementReturnedQuantity($saleItem, 10.00);

    $this->assertDatabaseHas('sale_items', [
        'id' => $saleItem->id,
        'returned_quantity' => 15.00,
    ]);
});

test(
    'getPaginatedMemberSalesListForExport method returns member sales report as expected when product variant',
    function (bool $productVariant): void {
        Config::set('app.product_variant', $productVariant);

        $location = Location::factory()->create([
            'company_id' => $this->companyId,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $counter = Counter::factory()->create([
            'location_id' => $location->id,
        ]);

        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $counter->id,
        ]);

        $member = Member::factory()->create();

        $sale = Sale::factory()->create([
            'member_id' => $member->id,
            'counter_update_id' => $counterUpdate->id,
            'layaway_pending_amount' => null,
            'status' => SaleStatus::COMPLETE_LAYAWAY_SALE->value,
        ]);

        SaleDiscount::factory()->create([
            'sale_id' => $sale->id,
        ]);

        if ($productVariant) {
            $masterProduct = MasterProduct::factory()->create([
                'company_id' => $this->companyId,
                'has_batch' => false,
                'is_non_inventory' => false,
            ]);
        }

        $product = Product::factory()->create([
            'company_id' => $this->companyId,
            'compound_product_name' => $productVariant ? 'ABCD' : 'DEFG',
            'code' => $productVariant ? '8898998' : '12132465465',
            'status' => Statuses::ACTIVE->value,
            'master_product_id' => $productVariant ? $masterProduct->id : null,
            'has_batch' => false,
            'is_non_inventory' => false,
        ]);

        if ($productVariant) {
            $product->masterProduct = $masterProduct;
        }

        $saleItem = SaleItem::factory()->create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
        ]);

        SaleItemDiscount::factory()->create([
            'sale_item_id' => $saleItem->id,
        ]);

        $response = $this->saleItemQueries->getPaginatedMemberSalesListForExport([
            'search_text' => $product->compound_product_name,
            'sort_by' => null,
            'sort_direction' => null,
            'member_id' => null,
            'product_id' => null,
            'date_range' => null,
            'location_id' => null,
            'product_collection_id' => null,
        ], $this->companyId);

        $this->assertEquals(1, $response->count());

        if ($productVariant) {
            expect($response->first()->toArray())
                ->toHaveKey('sale_id', $sale->id)
                ->toHaveKey('quantity', $saleItem->quantity)
                ->toHaveKey('returned_quantity', $saleItem->returned_quantity)
                ->toHaveKey('product.master_product_id', $masterProduct->id)
                ->toHaveKeys(['product', 'product.master_product_id'], $masterProduct->id);
        } else {
            expect($response->first()->toArray())
                ->toHaveKey('sale_id', $sale->id)
                ->toHaveKey('quantity', $saleItem->quantity)
                ->toHaveKey('returned_quantity', $saleItem->returned_quantity)
                ->toHaveKeys(['product', 'product.color', 'product.size', 'sale.member']);
        }
    }
)->with([[true], [false]]);

test(
    'getPaginatedMemberSalesListForExportInStoreManagerPanel method returns member sales report as expected',
    function (): void {
        $location = Location::factory()->create([
            'company_id' => $this->companyId,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $counter = Counter::factory()->create([
            'location_id' => $location->id,
        ]);

        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $counter->id,
        ]);

        $member = Member::factory()->create([
            'company_id' => $this->companyId,
            'employee_id' => null,
        ]);

        $sale = Sale::factory()->create([
            'member_id' => $member->id,
            'counter_update_id' => $counterUpdate->id,
            'layaway_pending_amount' => null,
            'status' => SaleStatus::COMPLETE_LAYAWAY_SALE->value,
        ]);

        SaleDiscount::factory()->create([
            'sale_id' => $sale->id,
        ]);

        $product = Product::factory()->create([
            'compound_product_name' => 'product 1 color 1',
        ]);

        $saleItem = SaleItem::factory()->create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
        ]);

        SaleItemDiscount::factory()->create([
            'sale_item_id' => $saleItem->id,
        ]);

        $response = $this->saleItemQueries->getPaginatedMemberSalesListForExportInStoreManagerPanel([
            'search_text' => $product->compound_product_name,
            'sort_by' => null,
            'sort_direction' => null,
            'member_id' => null,
            'product_id' => null,
            'date_range' => null,
            'location_id' => null,
            'product_collection_id' => null,
        ], $location->id);

        expect($response->first()->toArray())
            ->toHaveKey('sale_id', $sale->id)
            ->toHaveKey('quantity', $saleItem->quantity)
            ->toHaveKey('returned_quantity', $saleItem->returned_quantity)
            ->toHaveKeys(['product', 'sale.member']);
    }
);

test(
    'update method updates the sale item details according the exchange discount',
    function (): void {
        $location = Location::factory()->create([
            'company_id' => $this->companyId,
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

        $product = Product::factory()->create();

        $oldSaleItem = SaleItem::factory()->create([
            'box_product_id' => BoxProduct::factory()->create()->id,
            'product_box_package_type_id' => PackageType::factory()->create()->id,
            'product_box_units' => 100,
        ]);

        $saleItem = SaleItem::factory()->create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'is_exchange' => 1,
            'original_price_per_unit' => 100,
            'quantity' => 1,
        ]);

        $response = $this->saleItemQueries->update($saleItem->id, 100, 10, 1, $oldSaleItem);

        $this->assertDatabaseHas('sale_items', [
            'id' => $saleItem->id,
            'item_discount_amount' => 1,
            'total_discount_amount' => 1,
            'price_paid_per_unit' => 109,
            'total_price_paid' => 109,
            'box_product_id' => $oldSaleItem->box_product_id,
            'product_box_package_type_id' => $oldSaleItem->product_box_package_type_id,
            'product_box_units' => $oldSaleItem->product_box_units,
        ]);
    }
);

test(
    'getGeneralSalesReportByProduct method returns the saleItems return data with relations for general sales report',
    function (): void {
        $location = Location::factory()->create([
            'company_id' => $this->companyId,
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
            'member_id' => null,
            'layaway_pending_amount' => null,
            'status' => SaleStatus::REGULAR_SALE->value,
            'happened_at' => Carbon::now()->format('Y-m-d'),
        ]);

        $product = Product::factory()->create([
            'is_non_selling_item' => false,
        ]);

        $saleItem = SaleItem::factory()->create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'returned_quantity' => 0,
            'is_exchange' => false,
            'sale_return_item_id' => null,
        ]);

        SalePayment::factory()->create([
            'sale_id' => $sale->id,
        ]);

        $response = $this->saleItemQueries->getGeneralSalesReportByProduct([
            'location_ids' => [$location->id],
            'date_range' => [Carbon::now()->yesterday()->format('Y-m-d'), Carbon::now()->addDay()->format('Y-m-d')],
            'department_ids' => null,
            'brand_ids' => null,
            'promoter_ids' => null,
            'counter_ids' => null,
            'e_invoice_submitted' => null,
        ], false);

        expect($response->first()->toArray())
            ->toHaveKeys(['id', 'sale_id', 'price_paid_per_unit', 'quantity', 'product_id']);
    }
);

test(
    'getByStoreForTopCategoryExport method returns products for report as expected',
    function (): void {
        $location = Location::factory()->create([
            'company_id' => $this->companyId,
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
            'layaway_pending_amount' => null,
            'status' => SaleStatus::COMPLETE_LAYAWAY_SALE->value,
            'happened_at' => now()->format('Y-m-d'),
        ]);

        $product = Product::factory()->create([
            'company_id' => $this->companyId,
            'is_non_selling_item' => false,
        ]);

        SaleItem::factory()->create([
            'sale_id' => $sale->id,
            'sale_return_item_id' => null,
            'product_id' => $product->id,
            'is_exchange' => false,
        ]);

        $response = $this->saleItemQueries->getByStoreForTopCategoryExport([
            'location_ids' => [$location->id],
            'counter_ids' => null,
            'cashier_ids' => null,
            'date_range' => [now()->subDay()->format('Y-m-d'), now()->addDay()->format('Y-m-d')],
        ]);

        expect($response->first()->toArray())
            ->toHaveKey('product_id', $product->id);
    }
);

test(
    'getForGeneralSalesReportBySalesDate method returns the saleItems return data with relations for general sales report',
    function (): void {
        $location = Location::factory()->create([
            'company_id' => $this->companyId,
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
            'member_id' => null,
            'layaway_pending_amount' => null,
            'status' => SaleStatus::REGULAR_SALE->value,
            'happened_at' => Carbon::now()->format('Y-m-d'),
        ]);

        $product = Product::factory()->create([
            'is_non_selling_item' => false,
        ]);

        SaleItem::factory()->create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'returned_quantity' => 0,
            'is_exchange' => false,
            'sale_return_item_id' => null,
        ]);

        SalePayment::factory()->create([
            'sale_id' => $sale->id,
        ]);

        $response = $this->saleItemQueries->getForGeneralSalesReportBySalesDate([
            'location_ids' => [$location->id],
            'date_range' => [now()->subDay()->format('Y-m-d'), Carbon::now()->addDay()->format('Y-m-d')],
            'department_ids' => null,
            'brand_ids' => null,
            'promoter_ids' => null,
            'counter_ids' => null,
            'e_invoice_submitted' => null,
        ], false);

        expect($response->first()->toArray())
            ->toHaveKeys(['id', 'sale_id', 'price_paid_per_unit', 'quantity', 'product_id']);
    }
);

test(
    'getForGeneralSalesReportBySalesDateColorAndSize method returns the saleItems return data with relations for general sales report',
    function (): void {
        $location = Location::factory()->create([
            'company_id' => $this->companyId,
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
            'member_id' => null,
            'layaway_pending_amount' => null,
            'status' => SaleStatus::REGULAR_SALE->value,
            'happened_at' => Carbon::now()->format('Y-m-d'),
        ]);

        $product = Product::factory()->create([
            'is_non_selling_item' => false,
        ]);

        SaleItem::factory()->create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'returned_quantity' => 0,
            'is_exchange' => false,
            'sale_return_item_id' => null,
        ]);

        SalePayment::factory()->create([
            'sale_id' => $sale->id,
        ]);

        $response = $this->saleItemQueries->getForGeneralSalesReportBySalesDateColorAndSize([
            'location_ids' => [$location->id],
            'date_range' => [now()->subDay()->format('Y-m-d'), Carbon::now()->addDay()->format('Y-m-d')],
            'department_ids' => null,
            'brand_ids' => null,
            'promoter_ids' => null,
            'counter_ids' => null,
            'e_invoice_submitted' => null,
        ], false);

        expect($response->first()->toArray())
            ->toHaveKeys(['id', 'sale_id', 'price_paid_per_unit', 'quantity', 'product_id']);
    }
);

test(
    'getByIds method returns the sale items',
    function (): void {
        $sale = Sale::factory()->create([
            'status' => SaleStatus::REGULAR_SALE->value,
        ]);

        $product = Product::factory()->create([
            'company_id' => $this->companyId,
        ]);

        $saleItem = SaleItem::factory()->create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
        ]);

        $response = $this->saleItemQueries->getByIds([$saleItem->id]);

        expect($response->first()->toArray())
            ->toHaveKey('id', $saleItem->id)
            ->toHaveKey('sale_id', $sale->id);
    }
);

test('updateCreditAmountOf method updates the credit amount', function (): void {
    $sale = Sale::factory()->create([
        'total_tax_amount' => 0,
        'cart_discount_amount' => 0,
        'items_discount_amount' => 0,
        'total_discount_amount' => 0,
        'total_amount_before_round_off' => 0,
        'total_amount_paid' => 10,
        'credit_pending_amount' => 40,
        'status' => SaleStatus::PENDING_CREDIT_SALE->value,
    ]);

    SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'quantity' => 5,
        'returned_quantity' => 0,
        'original_price_per_unit' => 10,
        'cart_discount_amount' => 0,
        'item_discount_amount' => 0,
        'total_discount_amount' => 0,
        'total_tax_amount' => 0,
        'price_paid_per_unit' => 10,
        'total_price_paid' => 10,
    ]);

    $this->saleItemQueries->updateCreditAmountOf($sale, 10, false);

    $this->assertDatabaseHas('sale_items', [
        'sale_id' => $sale->id,
        'total_price_paid' => (string) 20,
    ]);
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
    $sale = Sale::factory()->create([
        'counter_update_id' => $counterUpdateId,
    ]);
    $productAId = Product::factory()->create()->id;
    $productBId = Product::factory()->create()->id;

    $saleItem = SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'product_id' => $productBId,
    ]);

    $this->saleItemQueries->updateProductId($companyId, $productBId, $productAId);

    $this->assertDatabaseHas('sale_items', [
        'sale_id' => $sale->id,
        'product_id' => $productAId,
    ]);
});

test(
    'getGeneralSalesReportBySummary method returns the saleItems return data with relations for general sales report',
    function (): void {
        $location = Location::factory()->create([
            'company_id' => $this->companyId,
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
            'member_id' => null,
            'layaway_pending_amount' => null,
            'status' => SaleStatus::REGULAR_SALE->value,
            'happened_at' => Carbon::now()->format('Y-m-d'),
        ]);

        $product = Product::factory()->create([
            'is_non_selling_item' => false,
        ]);

        $saleItem = SaleItem::factory()->create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'returned_quantity' => 0,
            'is_exchange' => false,
            'sale_return_item_id' => null,
        ]);

        SalePayment::factory()->create([
            'sale_id' => $sale->id,
        ]);

        $response = $this->saleItemQueries->getGeneralSalesReportBySummary([
            'location_ids' => [$location->id],
            'date_range' => [Carbon::now()->yesterday()->format('Y-m-d'), Carbon::now()->addDay()->format('Y-m-d')],
            'department_ids' => null,
            'brand_ids' => null,
            'promoter_ids' => null,
            'counter_ids' => null,
            'e_invoice_submitted' => null,
        ], false);

        expect($response->first()->toArray())
            ->toHaveKeys(['id', 'sale_id', 'total_quantity', 'total_price_paid']);
    }
);

test(
    'getGeneralSalesReportByDateAndBrand method returns the saleItems return data with relations for general sales report',
    function (): void {
        $location = Location::factory()->create([
            'company_id' => $this->companyId,
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
            'member_id' => null,
            'layaway_pending_amount' => null,
            'status' => SaleStatus::REGULAR_SALE->value,
            'happened_at' => Carbon::now()->format('Y-m-d'),
        ]);

        $product = Product::factory()->create([
            'is_non_selling_item' => false,
        ]);

        SaleItem::factory()->create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'returned_quantity' => 0,
            'is_exchange' => false,
            'sale_return_item_id' => null,
        ]);

        SalePayment::factory()->create([
            'sale_id' => $sale->id,
        ]);

        $response = $this->saleItemQueries->getGeneralSalesReportByDateAndBrand([
            'location_ids' => [$location->id],
            'date_range' => [Carbon::now()->yesterday()->format('Y-m-d'), Carbon::now()->addDay()->format('Y-m-d')],
            'department_ids' => null,
            'brand_ids' => null,
            'promoter_ids' => null,
            'counter_ids' => null,
            'e_invoice_submitted' => null,
        ], false);

        expect($response->first()->toArray())
            ->toHaveKeys(['id', 'sale_id', 'total_quantity', 'total_price_paid']);
    }
);

test('getCachedTodaySalesForDashboard method return sale details', function (): void {
    $location = Location::factory()->create([
        'company_id' => $this->companyId,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $counter = Counter::factory()->create([
        'location_id' => $location->id,
    ]);

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->id,
        'opened_by_pos_at' => now()->format('Y-m-d H:i:s'),
    ]);

    $sale = Sale::factory()->create([
        'counter_update_id' => $counterUpdate->id,
        'status' => SaleStatus::REGULAR_SALE->value,
        'layaway_pending_amount' => null,
    ]);

    $product = Product::factory()->create();

    SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'product_id' => $product->id,
        'total_tax_amount' => 10.00,
        'total_price_paid' => 20.00,
        'quantity' => 2,
        'cart_discount_amount' => 30.00,
    ]);

    $cacheKey = 'cache-today-sales-dashboard-' . $location->id . null . now()->format('Y-m-d') . now()->format('Y-m-d');

    Cache::forget($cacheKey);

    $response = $this->saleItemQueries->getCachedTodaySalesForDashboard(
        $this->companyId,
        $location->id,
        null,
        now()->format('Y-m-d'),
        now()->format('Y-m-d'),
    );

    expect($response->toArray())
        ->toHaveKey('total_amount', 20)
        ->toHaveKey('total_units_sold', 2)
        ->toHaveKey('total_sales_count', 1);

    expect(Cache::has($cacheKey))->toBeTrue();

    $cachedResponse = $this->saleItemQueries->getCachedTodaySalesForDashboard(
        $this->companyId,
        $location->id,
        null,
        now()->format('Y-m-d'),
        now()->format('Y-m-d'),
    );

    expect($cachedResponse)->toEqual($response);
});

test('getCachedTodaySalesForDashboard method return sale details with brand', function (): void {
    $brand = Brand::factory()->create();

    $product = Product::factory()->create([
        'brand_id' => $brand->id,
    ]);

    $location = Location::factory()->create([
        'company_id' => $this->companyId,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $date = now();

    $counter = Counter::factory()->create([
        'location_id' => $location->id,
    ]);

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->id,
        'opened_by_pos_at' => $date->format('Y-m-d H:i:s'),
    ]);

    $location = Location::factory()->create([
        'company_id' => $this->companyId,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $sale = Sale::factory()->create([
        'counter_update_id' => $counterUpdate->id,
        'status' => SaleStatus::REGULAR_SALE->value,
        'layaway_pending_amount' => null,
    ]);

    SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'product_id' => $product->id,
        'total_tax_amount' => 10.00,
        'total_price_paid' => 30.00,
        'quantity' => 3,
        'cart_discount_amount' => 30.00,
    ]);

    $cacheKey = 'cache-today-sales-dashboard-' . $date->format('Y-m-d') . $date->format('Y-m-d') . $brand->id;

    Cache::forget($cacheKey);

    $response = $this->saleItemQueries->getCachedTodaySalesForDashboard(
        $this->companyId,
        null,
        $brand->id,
        $date->format('Y-m-d'),
        $date->format('Y-m-d'),
    );

    expect($response->toArray())
        ->toHaveKey('total_amount', 30)
        ->toHaveKey('total_units_sold', 3)
        ->toHaveKey('total_sales_count', 1);

    expect(Cache::has($cacheKey))->toBeTrue();

    $cachedResponse = $this->saleItemQueries->getCachedTodaySalesForDashboard(
        $this->companyId,
        null,
        $brand->id,
        now()->format('Y-m-d'),
        now()->format('Y-m-d'),
    );

    expect($cachedResponse)->toEqual($response);
});

test('updateBoxProductDetails method update box product details', function (): void {
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

    $sale = Sale::factory()->create([
        'counter_update_id' => $counterUpdateId,
    ]);

    $saleItem = SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'box_product_id' => null,
        'product_box_package_type_id' => null,
        'product_box_units' => null,
    ]);

    $boxProduct = BoxProduct::factory()->create();

    $this->assertDatabaseHas('sale_items', [
        'id' => $saleItem->id,
        'box_product_id' => null,
        'product_box_package_type_id' => null,
        'product_box_units' => null,
    ]);

    $this->saleItemQueries->updateBoxProductDetails($saleItem, $boxProduct);

    $this->assertDatabaseHas('sale_items', [
        'id' => $saleItem->id,
        'box_product_id' => $boxProduct->id,
        'product_box_package_type_id' => $boxProduct->package_type_id,
        'product_box_units' => $boxProduct->units,
    ]);
});
test(
    'getPaginatedEmployeeSalesReportList method returns employee sales report as expected',
    function (bool $productVariant): void {
        Config::set('app.product_variant', $productVariant);

        $location = Location::factory()->create([
            'company_id' => $this->companyId,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $counter = Counter::factory()->create([
            'location_id' => $location->id,
        ]);

        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $counter->id,
        ]);

        $employee = Employee::factory()->create([
            'company_id' => $this->companyId,
        ]);

        $member = Member::factory()->create([
            'company_id' => $this->companyId,
            'employee_id' => $employee->id,
        ]);

        $sale = Sale::factory()->create([
            'member_id' => $member->id,
            'counter_update_id' => $counterUpdate->id,
            'layaway_pending_amount' => null,
            'status' => SaleStatus::COMPLETE_LAYAWAY_SALE->value,
        ]);

        SaleDiscount::factory()->create([
            'sale_id' => $sale->id,
        ]);

        if ($productVariant) {
            $masterProduct = MasterProduct::factory()->create([
                'company_id' => $this->companyId,
                'has_batch' => false,
                'is_non_inventory' => false,
            ]);
        }

        $product = Product::factory()->create([
            'company_id' => $this->companyId,
            'compound_product_name' => $productVariant ? 'ABCD' : 'DEFG',
            'code' => $productVariant ? '8898998' : '12132465465',
            'status' => Statuses::ACTIVE->value,
            'master_product_id' => $productVariant ? $masterProduct->id : null,
            'has_batch' => false,
            'is_non_inventory' => false,
        ]);

        $saleItem = SaleItem::factory()->create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
        ]);

        SaleItemDiscount::factory()->create([
            'sale_item_id' => $saleItem->id,
        ]);

        $response = $this->saleItemQueries->getPaginatedEmployeeSalesReportList([
            'search_text' => null,
            'sort_by' => null,
            'sort_direction' => null,
            'per_page' => 15,
            'employee_id' => null,
            'product_id' => null,
            'date_range' => null,
            'product_collection_id' => null,
        ], $this->companyId);

        $this->assertEquals(1, $response->total());

        if ($productVariant) {
            expect($response->first()->toArray())
                ->toHaveKey('sale_id', $sale->id)
                ->toHaveKey('quantity', $saleItem->quantity)
                ->toHaveKey('returned_quantity', $saleItem->returned_quantity)
                ->toHaveKey('product.master_product_id', $masterProduct->id)
                ->toHaveKeys(['product', 'product.master_product_id'], $masterProduct->id);
        } else {
            expect($response->first()->toArray())
                ->toHaveKey('sale_id', $sale->id)
                ->toHaveKey('quantity', $saleItem->quantity)
                ->toHaveKey('returned_quantity', $saleItem->returned_quantity)
                ->toHaveKeys(['product', 'product.color', 'product.size', 'sale.member']);
        }
    }
)->with([[true], [false]]);

test(
    'getPaginatedEmployeeSalesListForExport method returns member sales report as expected',
    function (bool $productVariant): void {
        Config::set('app.product_variant', $productVariant);
        $location = Location::factory()->create([
            'company_id' => $this->companyId,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $counter = Counter::factory()->create([
            'location_id' => $location->id,
        ]);

        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $counter->id,
        ]);

        $employee = Employee::factory()->create([
            'company_id' => $this->companyId,
        ]);

        $member = Member::factory()->create([
            'company_id' => $this->companyId,
            'employee_id' => $employee->id,
        ]);

        $sale = Sale::factory()->create([
            'member_id' => $member->id,
            'counter_update_id' => $counterUpdate->id,
            'layaway_pending_amount' => null,
            'status' => SaleStatus::COMPLETE_LAYAWAY_SALE->value,
        ]);

        SaleDiscount::factory()->create([
            'sale_id' => $sale->id,
        ]);

        if ($productVariant) {
            $masterProduct = MasterProduct::factory()->create([
                'company_id' => $this->companyId,
                'has_batch' => false,
                'is_non_inventory' => false,
            ]);
        }

        $product = Product::factory()->create([
            'company_id' => $this->companyId,
            'compound_product_name' => $productVariant ? 'ABCD' : 'DEFG',
            'code' => $productVariant ? '8898998' : '12132465465',
            'status' => Statuses::ACTIVE->value,
            'master_product_id' => $productVariant ? $masterProduct->id : null,
            'has_batch' => false,
            'is_non_inventory' => false,
        ]);

        $saleItem = SaleItem::factory()->create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
        ]);

        SaleItemDiscount::factory()->create([
            'sale_item_id' => $saleItem->id,
        ]);

        $response = $this->saleItemQueries->getPaginatedEmployeeSalesListForExport([
            'search_text' => null,
            'sort_by' => null,
            'sort_direction' => null,
            'employee_id' => null,
            'product_id' => null,
            'date_range' => null,
            'product_collection_id' => null,
        ], $this->companyId);

        if ($productVariant) {
            expect($response->first()->toArray())
                ->toHaveKey('sale_id', $sale->id)
                ->toHaveKey('quantity', $saleItem->quantity)
                ->toHaveKey('returned_quantity', $saleItem->returned_quantity)
                ->toHaveKey('product.master_product_id', $masterProduct->id)
                ->toHaveKeys(['product', 'product.master_product_id'], $masterProduct->id);
        } else {
            expect($response->first()->toArray())
                ->toHaveKey('sale_id', $sale->id)
                ->toHaveKey('quantity', $saleItem->quantity)
                ->toHaveKey('returned_quantity', $saleItem->returned_quantity)
                ->toHaveKeys(['product', 'sale.member']);
        }
    }
)->with([[true], [false]]);

test(
    'getPaginatedEmployeeSalesReportListForStoreManager method returns employee sales report as expected',
    function (): void {
        $location = Location::factory()->create([
            'company_id' => $this->companyId,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $counter = Counter::factory()->create([
            'location_id' => $location->id,
        ]);

        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $counter->id,
        ]);

        $employee = Employee::factory()->create([
            'company_id' => $this->companyId,
        ]);

        $member = Member::factory()->create([
            'company_id' => $this->companyId,
            'employee_id' => $employee->id,
        ]);

        $member->employee();

        $sale = Sale::factory()->create([
            'member_id' => $member->id,
            'counter_update_id' => $counterUpdate->id,
            'layaway_pending_amount' => null,
            'status' => SaleStatus::COMPLETE_LAYAWAY_SALE->value,
        ]);

        SaleDiscount::factory()->create([
            'sale_id' => $sale->id,
        ]);

        $product = Product::factory()->create();

        $saleItem = SaleItem::factory()->create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
        ]);

        SaleItemDiscount::factory()->create([
            'sale_item_id' => $saleItem->id,
        ]);

        $response = $this->saleItemQueries->getPaginatedEmployeeSalesReportListForStoreManager([
            'search_text' => $product->compound_product_name,
            'sort_by' => null,
            'sort_direction' => null,
            'per_page' => 15,
            'employee_id' => null,
            'product_id' => null,
            'date_range' => null,
            'product_collection_id' => null,
        ], $location->id, $this->companyId);

        $this->assertEquals(1, $response->total());

        expect($response->getCollection()->first()->toArray())
        ->toHaveKey('sale_id', $sale->id)
        ->toHaveKey('quantity', $saleItem->quantity)
        ->toHaveKey('returned_quantity', $saleItem->returned_quantity)
        ->toHaveKeys(['product', 'product.color', 'product.size', 'sale.member']);
    }
);

test(
    'getPaginatedEmployeeSalesListForExportInStoreManagerPanel method returns member sales report as expected',
    function (): void {
        $location = Location::factory()->create([
            'company_id' => $this->companyId,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $counter = Counter::factory()->create([
            'location_id' => $location->id,
        ]);

        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $counter->id,
        ]);

        $employee = Employee::factory()->create([
            'company_id' => $this->companyId,
        ]);

        $member = Member::factory()->create([
            'company_id' => $this->companyId,
            'employee_id' => $employee->id,
        ]);

        $sale = Sale::factory()->create([
            'member_id' => $member->id,
            'counter_update_id' => $counterUpdate->id,
            'layaway_pending_amount' => null,
            'status' => SaleStatus::COMPLETE_LAYAWAY_SALE->value,
        ]);

        SaleDiscount::factory()->create([
            'sale_id' => $sale->id,
        ]);

        $product = Product::factory()->create([
            'compound_product_name' => 'product 1 color 1',
        ]);

        $saleItem = SaleItem::factory()->create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
        ]);

        SaleItemDiscount::factory()->create([
            'sale_item_id' => $saleItem->id,
        ]);

        $response = $this->saleItemQueries->getPaginatedEmployeeSalesListForExportInStoreManagerPanel([
            'search_text' => $product->compound_product_name,
            'sort_by' => null,
            'sort_direction' => null,
            'employee_id' => null,
            'product_id' => null,
            'date_range' => null,
            'product_collection_id' => null,
        ], $location->id, $this->companyId);

        expect($response->first()->toArray())
            ->toHaveKey('sale_id', $sale->id)
            ->toHaveKey('quantity', $saleItem->quantity)
            ->toHaveKey('returned_quantity', $saleItem->returned_quantity)
            ->toHaveKeys(['product', 'sale.member']);
    }
);

test('getSaleDetailsById method returns member sales details as expected', function (): void {
    $location = Location::factory()->create([
        'company_id' => $this->companyId,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $counter = Counter::factory()->create([
        'location_id' => $location->id,
    ]);

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->id,
    ]);

    $member = Member::factory()->create();

    $sale = Sale::factory()->create([
        'member_id' => $member->id,
        'counter_update_id' => $counterUpdate->id,
        'layaway_pending_amount' => null,
        'status' => SaleStatus::COMPLETE_LAYAWAY_SALE->value,
    ]);

    SaleDiscount::factory()->create([
        'sale_id' => $sale->id,
    ]);

    $product = Product::factory()->create();

    $saleItem = SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'product_id' => $product->id,
    ]);

    SaleItemDiscount::factory()->create([
        'sale_item_id' => $saleItem->id,
    ]);

    $response = $this->saleItemQueries->getSaleDetailsById($saleItem->id);

    expect($response->first()->toArray())
        ->toHaveKey('sale_id', $sale->id)
        ->toHaveKey('quantity', $saleItem->quantity);
});

test('updateLayawayAmountOf method updates the complete layaway amount', function (): void {
    $sale = Sale::factory()->create([
        'total_tax_amount' => 0,
        'cart_discount_amount' => 0,
        'items_discount_amount' => 0,
        'total_discount_amount' => 0,
        'total_amount_before_round_off' => 0,
        'total_amount_paid' => 10,
        'layaway_pending_amount' => 40,
        'status' => SaleStatus::PENDING_LAYAWAY_SALE->value,
    ]);

    SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'quantity' => 5,
        'returned_quantity' => 0,
        'original_price_per_unit' => 10,
        'cart_discount_amount' => 0,
        'item_discount_amount' => 0,
        'total_discount_amount' => 0,
        'total_tax_amount' => 0,
        'price_paid_per_unit' => 10,
        'total_price_paid' => 10,
    ]);

    $this->saleItemQueries->updateLayawayAmountOf($sale, 10, true);

    $this->assertDatabaseHas('sale_items', [
        'sale_id' => $sale->id,
        'total_price_paid' => (string) 50,
    ]);
});

test('updateCreditAmountOf method updates the complete credit amount', function (): void {
    $sale = Sale::factory()->create([
        'total_tax_amount' => 0,
        'cart_discount_amount' => 0,
        'items_discount_amount' => 0,
        'total_discount_amount' => 0,
        'total_amount_before_round_off' => 0,
        'total_amount_paid' => 10,
        'credit_pending_amount' => 40,
        'status' => SaleStatus::PENDING_CREDIT_SALE->value,
    ]);

    SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'quantity' => 5,
        'returned_quantity' => 0,
        'original_price_per_unit' => 10,
        'cart_discount_amount' => 0,
        'item_discount_amount' => 0,
        'total_discount_amount' => 0,
        'total_tax_amount' => 0,
        'price_paid_per_unit' => 10,
        'total_price_paid' => 10,
    ]);

    $this->saleItemQueries->updateCreditAmountOf($sale, 10, true);

    $this->assertDatabaseHas('sale_items', [
        'sale_id' => $sale->id,
        'total_price_paid' => (string) 50,
    ]);
});

test('getSaleItemsForTheProductAgeingReport method returns the quantity of the product', function (): void {
    $location = Location::factory()->create([
        'company_id' => $this->companyId,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $counter = Counter::factory()->create([
        'location_id' => $location->id,
    ]);

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->id,
    ]);

    $sale = Sale::factory()->create([
        'counter_update_id' => $counterUpdate->getKey(),
        'total_tax_amount' => 0,
        'cart_discount_amount' => 0,
        'items_discount_amount' => 0,
        'total_discount_amount' => 0,
        'total_amount_before_round_off' => 0,
        'total_amount_paid' => 10,
        'credit_pending_amount' => 40,
        'status' => SaleStatus::REGULAR_SALE->value,
    ]);

    $product = Product::factory()->create();

    SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'product_id' => $product->id,
        'quantity' => 5,
        'returned_quantity' => 0,
        'original_price_per_unit' => 10,
        'cart_discount_amount' => 0,
        'item_discount_amount' => 0,
        'total_discount_amount' => 0,
        'total_tax_amount' => 0,
        'price_paid_per_unit' => 10,
        'total_price_paid' => 10,
    ]);

    $response = $this->saleItemQueries->getSaleItemsForTheProductAgeingReport($product->getKey(), $location->getKey());

    expect($response->first())
        ->toHaveKeys([
            'first_month_quantity_sold',
            'second_month_quantity_sold',
            'third_month_quantity_sold',
            'fourth_month_quantity_sold',
            'fifth_month_quantity_sold',
            'sixth_month_quantity_sold',
            'seventh_month_quantity_sold',
            'eighth_month_quantity_sold',
            'ninth_month_quantity_sold',
            'tenth_month_quantity_sold',
            'eleventh_month_quantity_sold',
            'twelfth_month_quantity_sold',
        ]);
});

test('getYesterdaySaleWithSaleItems method returns the collection of the yesterday sale items', function (): void {
    $location = Location::factory()->create([
        'company_id' => $this->companyId,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $counter = Counter::factory()->create([
        'location_id' => $location->id,
    ]);

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->id,
    ]);

    $yesterdayDate = Carbon::yesterday()->format('Y-m-d H:i:s');

    $sale = Sale::factory()->create([
        'counter_update_id' => $counterUpdate->getKey(),
        'total_tax_amount' => 0,
        'cart_discount_amount' => 0,
        'items_discount_amount' => 0,
        'total_discount_amount' => 0,
        'total_amount_before_round_off' => 0,
        'total_amount_paid' => 10,
        'credit_pending_amount' => 40,
        'status' => SaleStatus::REGULAR_SALE->value,
        'happened_at' => $yesterdayDate,
    ]);

    $product = Product::factory()->create();

    SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'product_id' => $product->id,
        'quantity' => 5,
        'returned_quantity' => 0,
        'original_price_per_unit' => 10,
        'cart_discount_amount' => 0,
        'item_discount_amount' => 0,
        'total_discount_amount' => 0,
        'total_tax_amount' => 0,
        'price_paid_per_unit' => 10,
        'total_price_paid' => 10,
    ]);

    $response = $this->saleItemQueries->getYesterdaySaleWithSaleItems($yesterdayDate);

    expect($response->first()->toArray())
        ->toHaveKeys(['product_id', 'location_id']);
});

test(
    'getSaleItemsForTheStoreManagerApplicationDashboard method return sale details for store manager api',
    function (): void {
        $location = Location::factory()->create([
            'company_id' => $this->companyId,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $counter = Counter::factory()->create([
            'location_id' => $location->id,
        ]);

        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $counter->id,
            'opened_by_pos_at' => now()->format('Y-m-d H:i:s'),
        ]);

        $sale = Sale::factory()->create([
            'counter_update_id' => $counterUpdate->id,
            'status' => SaleStatus::REGULAR_SALE->value,
            'layaway_pending_amount' => null,
        ]);

        $product = Product::factory()->create();

        SaleItem::factory()->create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'total_tax_amount' => 10.00,
            'total_price_paid' => 20.00,
            'quantity' => 2,
            'cart_discount_amount' => 30.00,
        ]);

        $response = $this->saleItemQueries->getSaleItemsForTheStoreManagerApplicationDashboard(
            $location->id,
            [now()->format('Y-m-d'), now()->format('Y-m-d')],
            $this->companyId,
        );

        expect($response->toArray())
            ->toHaveKey('total_sales_amount', 20)
            ->toHaveKey('total_sales', 1);
    }
);

test('getPreferredItems method call return proper response', function (): void {
    $member = Member::factory()->create([
        'company_id' => $this->companyId,
    ]);

    $location = Location::factory()->create([
        'company_id' => $this->companyId,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $counter = Counter::factory()->create([
        'location_id' => $location->id,
    ]);

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->id,
    ]);

    $sale = Sale::factory()->create([
        'counter_update_id' => $counterUpdate->getKey(),
        'status' => SaleStatus::REGULAR_SALE->value,
        'member_id' => $member->id,
    ]);

    $product = Product::factory()->create([
        'company_id' => $this->companyId,
    ]);

    SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'product_id' => $product->id,
        'is_exchange' => false,
        'sale_return_item_id' => null,
    ]);

    $response = $this->saleItemQueries->getPreferredItems($member->id, $location->company_id, $location->id);

    expect($response->first()->toArray())
        ->toHaveKeys(
            [
                'quantity',
                'product_id',
                'sale_id',
                'product',
                'sale',
                'product.color',
                'product.size',
                'product.categories',
            ]
        );
});

test('getSalesForDashboardByDate method return sale details', function (): void {
    $location = Location::factory()->create([
        'company_id' => $this->companyId,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $counter = Counter::factory()->create([
        'location_id' => $location->id,
    ]);

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->id,
        'opened_by_pos_at' => now()->format('Y-m-d H:i:s'),
    ]);

    $sale = Sale::factory()->create([
        'counter_update_id' => $counterUpdate->id,
        'status' => SaleStatus::REGULAR_SALE->value,
        'layaway_pending_amount' => null,
    ]);

    $product = Product::factory()->create();

    SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'product_id' => $product->id,
        'total_tax_amount' => 10.00,
        'total_price_paid' => 20.00,
        'quantity' => 2,
        'cart_discount_amount' => 30.00,
    ]);

    $response = $this->saleItemQueries->getSalesForDashboardByDate(
        $this->companyId,
        now()->format('Y-m-d'),
        now()->format('Y-m-d'),
    );

    expect($response[0]->toArray())
        ->toHaveKey('total_amount', 20)
        ->toHaveKey('total_units_sold', 2)
        ->toHaveKey('total_sales_count', 1);
});

test('getRegularProductAggregateSales method return sale details', function (): void {
    $location = Location::factory()->create([
        'company_id' => $this->companyId,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $counter = Counter::factory()->create([
        'location_id' => $location->id,
    ]);

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->id,
        'opened_by_pos_at' => now()->format('Y-m-d H:i:s'),
    ]);

    $sale = Sale::factory()->create([
        'counter_update_id' => $counterUpdate->id,
        'status' => SaleStatus::REGULAR_SALE->value,
        'layaway_pending_amount' => null,
    ]);

    $product = Product::factory()->create([
        'type_id' => ProductTypes::REGULAR_PRODUCT->value,
    ]);

    SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'product_id' => $product->id,
        'total_price_paid' => 20.00,
        'quantity' => 2,
    ]);

    $response = $this->saleItemQueries->getRegularProductAggregateSales($this->companyId);

    expect($response->toArray(null)['data'][0])
        ->toHaveKey('product_id', $product->id)
        ->toHaveKey('location_id', $location->id)
        ->toHaveKey('quantity', 2)
        ->toHaveKey('amount', 20);
});

test('getRegularProductSalesAggregateForClosedCounter method return sale details', function (): void {
    $location = Location::factory()->create([
        'company_id' => $this->companyId,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $counter = Counter::factory()->create([
        'location_id' => $location->id,
    ]);

    $date = Carbon::now();

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->id,
        'opened_by_pos_at' => $date->format('Y-m-d H:i:s'),
        'closed_by_pos_at' => $date->format('Y-m-d H:i:s'),
    ]);

    $sale = Sale::factory()->create([
        'counter_update_id' => $counterUpdate->id,
        'status' => SaleStatus::REGULAR_SALE->value,
        'layaway_pending_amount' => null,
        'happened_at' => $date->format('Y-m-d H:i:s'),
    ]);

    $saleReturn = SaleReturn::factory()->create([
        'counter_update_id' => $counterUpdate->id,
        'happened_at' => $date->format('Y-m-d H:i:s'),
    ]);

    $product = Product::factory()->create([
        'type_id' => ProductTypes::REGULAR_PRODUCT->value,
    ]);

    SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'product_id' => $product->id,
        'total_price_paid' => 30.00,
        'quantity' => 3,
    ]);

    SaleReturnItem::factory()->create([
        'sale_return_id' => $saleReturn->id,
        'product_id' => $product->id,
        'total_price_paid' => 10.00,
        'quantity' => 1,
    ]);

    $response = $this->saleItemQueries->getRegularProductSalesAggregateForClosedCounter(
        $this->companyId,
        [
            'start_date' => $date->format('Y-m-d'),
            'end_date' => $date->format('Y-m-d'),
        ]
    );

    expect($response->toArray(null)['data'][0])
        ->toHaveKey('product_id', $product->id)
        ->toHaveKey('location_id', $location->id)
        ->toHaveKey('quantity', 2)
        ->toHaveKey('amount', 20);
});

test(
    'getRegularProductCompleteCreditAndLayawaySalesAggregateForClosedCounter method return sale details',
    function (): void {
        $location = Location::factory()->create([
            'company_id' => $this->companyId,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $counter = Counter::factory()->create([
            'location_id' => $location->id,
        ]);

        $date = Carbon::now();

        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $counter->id,
            'opened_by_pos_at' => $date->format('Y-m-d H:i:s'),
            'closed_by_pos_at' => $date->format('Y-m-d H:i:s'),
        ]);

        $sale = Sale::factory()->create([
            'counter_update_id' => $counterUpdate->id,
            'status' => SaleStatus::COMPLETE_LAYAWAY_SALE->value,
            'layaway_pending_amount' => null,
            'happened_at' => $date->format('Y-m-d H:i:s'),
            'layaway_completed_at' => $date->format('Y-m-d H:i:s'),
        ]);

        $product = Product::factory()->create([
            'type_id' => ProductTypes::REGULAR_PRODUCT->value,
        ]);

        SaleItem::factory()->create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'total_price_paid' => 30.00,
            'quantity' => 3,
        ]);

        $response = $this->saleItemQueries->getRegularProductCompleteCreditAndLayawaySalesAggregateForClosedCounter(
            $this->companyId,
            [
                'start_date' => $date->format('Y-m-d'),
                'end_date' => $date->format('Y-m-d'),
            ]
        );

        expect($response->first()->date)->toBe($date->format('Y-m-d'));
        expect($response->first()->product_id)->toBe($product->id);
        expect($response->first()->location_id)->toBe($location->id);
        expect($response->first()->quantity)->toBe('3.00');
        expect($response->first()->amount)->toBe('30.00');
    }
);

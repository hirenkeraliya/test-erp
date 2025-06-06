<?php

declare(strict_types=1);

use App\CommonFunctions;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Member\Enums\Status;
use App\Domains\Product\ProductQueries;
use App\Domains\Sale\DataObjects\SaleData;
use App\Domains\Sale\Enums\CreditAndLayawaySaleStatuses;
use App\Domains\Sale\Enums\SaleStatus;
use App\Domains\Sale\SaleQueries;
use App\Domains\SaleTarget\Enums\TargetType;
use App\Models\Brand;
use App\Models\Cashier;
use App\Models\CashierGroup;
use App\Models\Company;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\Employee;
use App\Models\Location;
use App\Models\MasterProduct;
use App\Models\Member;
use App\Models\PosMismatch;
use App\Models\Product;
use App\Models\Promoter;
use App\Models\Region;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleItemUnit;
use App\Models\SalePayment;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\StoreManager;
use App\Models\VoidSale;
use App\Models\VoidSaleReason;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

beforeEach(function (): void {
    $this->companyId = Company::factory()->create([
        'name' => 'Test Company',
        'email' => 'abc@company.test',
        'code' => 'ABC',
    ])->id;

    $this->location = Location::factory()->create([
        'company_id' => $this->companyId,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $this->saleQueries = new SaleQueries();
});

test('Regular sale can be searched', function (): void {
    $counter = Counter::factory()->create([
        'location_id' => $this->location->id,
    ]);

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->id,
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
        'cart_discount_amount' => 30.00,
    ]);

    $response = $this->saleQueries->getPaginatedRegularAndCompleteSalesWithRelations([
        'search_text' => $sale->offline_sale_id,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
        'date_range' => null,
        'location_ids' => null,
        'counter_ids' => null,
        'cashier_id' => null,
        'member_id' => null,
        'employee_id' => null,
        'offline_sale_id' => null,
        'e_invoice_submitted' => null,
    ], $this->companyId);

    $this->assertEquals(1, $response->total());

    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('id', $sale->id)
        ->toHaveKey('counter_update_id', $sale->counter_update_id)
        ->toHaveKey('total_tax_amount', $sale->total_tax_amount)
        ->toHaveKey('total_discount_amount', $sale->total_discount_amount)
        ->toHaveKey('total_amount_paid', $sale->total_amount_paid)
        ->toHaveKeys(['counter_update', 'counter_update.counter', 'counter_update.counter.location']);
});

test('Exchanges can be fetched', function (): void {
    $counter = Counter::factory()->create([
        'location_id' => $this->location->id,
    ]);

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->id,
    ]);

    $saleReturn = SaleReturn::factory()->create([
        'counter_update_id' => $counterUpdate->id,
    ]);

    $saleReturnItem = SaleReturnItem::factory()->create([
        'sale_return_id' => $saleReturn->id,
    ]);

    $sale = Sale::factory()->create([
        'counter_update_id' => $counterUpdate->id,
        'sale_return_id' => $saleReturn->id,
        'status' => SaleStatus::REGULAR_SALE->value,
    ]);

    $product = Product::factory()->create();

    SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'sale_return_item_id' => $saleReturnItem->id,
        'product_id' => $product->id,
        'total_tax_amount' => 10.00,
        'total_price_paid' => 20.00,
        'cart_discount_amount' => 30.00,
        'is_exchange' => true,
    ]);

    $response = $this->saleQueries->getPaginatedSaleExchangesWithRelations([
        'search_text' => $sale->offline_sale_id,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
        'date_range' => null,
        'location_ids' => null,
        'counter_ids' => null,
        'cashier_id' => null,
        'member_id' => null,
        'employee_id' => null,
    ], $this->companyId);

    $this->assertEquals(1, $response->total());

    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('id', $sale->id)
        ->toHaveKey('counter_update_id', $sale->counter_update_id)
        ->toHaveKey('total_tax_amount', $sale->total_tax_amount)
        ->toHaveKey('total_discount_amount', $sale->total_discount_amount)
        ->toHaveKey('total_amount_paid', $sale->total_amount_paid)
        ->toHaveKeys(
            [
                'counter_update',
                'counter_update.counter',
                'counter_update.counter.location',
                'counter_update.counter.location.company',
                'sale_items.0.quantity',
            ]
        );
});

test('getSaleExchangesWithRelationsForExport method call and returns the records as expected.', function (): void {
    $counter = Counter::factory()->create([
        'location_id' => $this->location->id,
    ]);

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->id,
    ]);

    $saleReturn = SaleReturn::factory()->create([
        'counter_update_id' => $counterUpdate->id,
    ]);

    $sale = Sale::factory()->create([
        'counter_update_id' => $counterUpdate->id,
        'sale_return_id' => $saleReturn->id,
        'status' => SaleStatus::REGULAR_SALE->value,
    ]);

    $product = Product::factory()->create();

    SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'product_id' => $product->id,
        'total_tax_amount' => 10.00,
        'total_price_paid' => 20.00,
        'cart_discount_amount' => 30.00,
    ]);

    $response = $this->saleQueries->getSaleExchangesWithRelationsForExport([
        'search_text' => $sale->offline_sale_id,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
        'date_range' => null,
        'location_ids' => null,
        'counter_ids' => null,
        'cashier_id' => null,
        'member_id' => null,
        'employee_id' => null,
    ], $this->companyId);

    expect($response->first()->toArray())
        ->toHaveKey('id', $sale->id)
        ->toHaveKey('counter_update_id', $sale->counter_update_id)
        ->toHaveKey('total_tax_amount', $sale->total_tax_amount)
        ->toHaveKey('total_discount_amount', $sale->total_discount_amount)
        ->toHaveKey('total_amount_paid', $sale->total_amount_paid)
        ->toHaveKeys(
            [
                'counter_update',
                'counter_update.counter',
                'counter_update.counter.location',
                'counter_update.counter.location.company',
                'sale_items',
                'payments',
            ]
        );
});

test('Store manager regular sale can be searched', function (): void {
    $counter = Counter::factory()->create([
        'location_id' => $this->location->id,
    ]);

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->id,
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
        'cart_discount_amount' => 30.00,
    ]);

    $response = $this->saleQueries->getPaginatedRegularSalesAndCompleteWithRelationsForStoreManager([
        'search_text' => $sale->offline_sale_id,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
        'date_range' => null,
        'counter_ids' => null,
        'cashier_id' => null,
        'member_id' => null,
        'employee_id' => null,
        'offline_sale_id' => null,
    ], [$this->location->id], $this->companyId);

    $this->assertEquals(1, $response->total());

    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('id', $sale->id)
        ->toHaveKey('counter_update_id', $sale->counter_update_id)
        ->toHaveKey('total_tax_amount', $sale->total_tax_amount)
        ->toHaveKey('total_discount_amount', $sale->total_discount_amount)
        ->toHaveKey('total_amount_paid', $sale->total_amount_paid)
        ->toHaveKeys(['counter_update', 'counter_update.counter', 'counter_update.counter.location']);
});

test('Void sale can be searched', function (): void {
    $counter = Counter::factory()->create([
        'location_id' => $this->location->id,
    ]);

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->id,
    ]);

    $sale = Sale::factory()->create([
        'member_id' => null,
        'counter_update_id' => $counterUpdate->id,
        'layaway_pending_amount' => null,
        'status' => SaleStatus::VOID_SALE->value,
        'happened_at' => Carbon::now(),
    ]);

    $product = Product::factory()->create();

    SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'product_id' => $product->id,
        'returned_quantity' => 0,
    ]);

    SalePayment::factory()->create([
        'sale_id' => $sale->id,
        'counter_update_id' => $counterUpdate->id,
    ]);

    $voidSaleReason = VoidSaleReason::factory()->create([
        'company_id' => $counter->location->company_id,
    ]);

    VoidSale::factory()->create([
        'sale_id' => $sale->id,
        'void_sale_reason_id' => $voidSaleReason->id,
    ]);

    $response = $this->saleQueries->getPaginatedVoidSalesWithRelations([
        'search_text' => $counter->getName(),
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
        'date_range' => null,
        'location_ids' => null,
        'counter_ids' => null,
        'cashier_id' => null,
        'member_id' => null,
        'employee_id' => null,
        'void_sale_number' => null,
        'e_invoice_submitted' => null,
    ], $this->companyId);

    $this->assertEquals(1, $response->count());

    expect($response->first()->toArray())
        ->toHaveKey('id', $sale->id)
        ->toHaveKey('offline_sale_id', $sale->offline_sale_id)
        ->toHaveKeys(
            [
                'counter_update',
                'counter_update.cashier',
                'counter_update.counter',
                'counter_update.cashier.employee',
                'mismatches',
            ]
        );
});
test('Store Manager void sale can be searched', function (): void {
    $counter = Counter::factory()->create([
        'location_id' => $this->location->id,
    ]);

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->id,
    ]);

    $sale = Sale::factory()->create([
        'member_id' => null,
        'counter_update_id' => $counterUpdate->id,
        'layaway_pending_amount' => null,
        'status' => SaleStatus::VOID_SALE->value,
        'happened_at' => Carbon::now(),
    ]);

    $product = Product::factory()->create();

    SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'product_id' => $product->id,
        'returned_quantity' => 0,
    ]);

    SalePayment::factory()->create([
        'sale_id' => $sale->id,
        'counter_update_id' => $counterUpdate->id,
    ]);

    $voidSaleReason = VoidSaleReason::factory()->create([
        'company_id' => $counter->location->company_id,
    ]);

    VoidSale::factory()->create([
        'sale_id' => $sale->id,
        'void_sale_reason_id' => $voidSaleReason->id,
    ]);

    $response = $this->saleQueries->getPaginatedVoidSalesWithRelationsForStoreManager([
        'search_text' => $counter->getName(),
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
        'date_range' => null,
        'counter_ids' => null,
        'cashier_id' => null,
        'member_id' => null,
        'employee_id' => null,
        'void_sale_number' => null,
    ], $this->location->id, $this->companyId);

    $this->assertEquals(1, $response->count());

    expect($response->first()->toArray())
        ->toHaveKey('id', $sale->id)
        ->toHaveKey('offline_sale_id', $sale->offline_sale_id)
        ->toHaveKeys(
            [
                'counter_update',
                'counter_update.cashier',
                'counter_update.counter',
                'counter_update.cashier.employee',
                'mismatches',
            ]
        );
});

test('Layaway sale can be searched', function (): void {
    $counter = Counter::factory()->create([
        'location_id' => $this->location->getKey(),
    ]);

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->getKey(),
    ]);

    $sale = Sale::factory()->create([
        'counter_update_id' => $counterUpdate->getKey(),
        'status' => SaleStatus::PENDING_LAYAWAY_SALE->value,
        'layaway_pending_amount' => 100.00,
    ]);

    $product = Product::factory()->create();

    SaleItem::factory()->create([
        'sale_id' => $sale->getKey(),
        'product_id' => $product->getKey(),
        'total_tax_amount' => 10.00,
        'total_price_paid' => 20.00,
        'cart_discount_amount' => 30.00,
    ]);

    $response = $this->saleQueries->getPaginatedPendingLayawaySalesWithRelations([
        'search_text' => $counter->getName(),
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
        'date_range' => null,
        'location_ids' => null,
        'counter_ids' => null,
        'cashier_id' => null,
        'member_id' => null,
        'employee_id' => null,
        'status_id' => CreditAndLayawaySaleStatuses::PENDING->value,
        'e_invoice_submitted' => null,
    ], $this->companyId);

    $this->assertEquals(1, $response->total());

    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('id', $sale->getKey())
        ->toHaveKey('counter_update_id', $sale->getCounterUpdateId())
        ->toHaveKey('total_amount_paid', $sale->getTotalAmountPaid())
        ->toHaveKey('layaway_pending_amount', $sale->getLayawayPendingAmount())
        ->toHaveKeys(
            [
                'layaway_authorizer_id',
                'layaway_authorizer_type',
                'counter_update',
                'counter_update.counter',
                'counter_update.counter.location',
            ]
        );
});

test('credit sale can be searched', function (): void {
    $counter = Counter::factory()->create([
        'location_id' => $this->location->getKey(),
    ]);

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->getKey(),
    ]);

    $sale = Sale::factory()->create([
        'counter_update_id' => $counterUpdate->getKey(),
        'status' => SaleStatus::PENDING_CREDIT_SALE->value,
        'credit_pending_amount' => 100.00,
    ]);

    $product = Product::factory()->create();

    SaleItem::factory()->create([
        'sale_id' => $sale->getKey(),
        'product_id' => $product->getKey(),
        'total_tax_amount' => 10.00,
        'total_price_paid' => 20.00,
        'cart_discount_amount' => 30.00,
    ]);

    $response = $this->saleQueries->getPaginatedPendingCreditSalesWithRelations([
        'search_text' => $counter->getName(),
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
        'date_range' => null,
        'location_ids' => null,
        'counter_ids' => null,
        'cashier_id' => null,
        'member_id' => null,
        'employee_id' => null,
        'status_id' => CreditAndLayawaySaleStatuses::PENDING->value,
        'e_invoice_submitted' => null,
    ], $this->companyId);

    $this->assertEquals(1, $response->total());

    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('id', $sale->getKey())
        ->toHaveKey('counter_update_id', $sale->getCounterUpdateId())
        ->toHaveKey('total_amount_paid', $sale->getTotalAmountPaid())
        ->toHaveKey('credit_pending_amount', $sale->getCreditPendingAmount())
        ->toHaveKeys(
            [
                'credit_authorizer_id',
                'credit_authorizer_type',
                'counter_update',
                'counter_update.counter',
                'counter_update.counter.location',
            ]
        );
});

test('Store Manager layaway sale can be searched', function (): void {
    $counter = Counter::factory()->create([
        'location_id' => $this->location->getKey(),
    ]);

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->getKey(),
    ]);

    $sale = Sale::factory()->create([
        'counter_update_id' => $counterUpdate->getKey(),
        'status' => SaleStatus::PENDING_LAYAWAY_SALE->value,
        'layaway_pending_amount' => 100.00,
    ]);

    $product = Product::factory()->create();

    SaleItem::factory()->create([
        'sale_id' => $sale->getKey(),
        'product_id' => $product->getKey(),
        'total_tax_amount' => 10.00,
        'total_price_paid' => 20.00,
        'cart_discount_amount' => 30.00,
    ]);

    $response = $this->saleQueries->getPaginatedPendingLayawaySalesWithRelationsForStoreManager([
        'search_text' => $counter->getName(),
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
        'date_range' => null,
        'location_id' => null,
        'counter_ids' => null,
        'cashier_id' => null,
        'member_id' => null,
        'employee_id' => null,
        'status_id' => CreditAndLayawaySaleStatuses::PENDING->value,
    ], $this->location->id, $this->companyId);

    $this->assertEquals(1, $response->total());

    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('id', $sale->getKey())
        ->toHaveKey('counter_update_id', $sale->getCounterUpdateId())
        ->toHaveKey('total_amount_paid', $sale->getTotalAmountPaid())
        ->toHaveKey('layaway_pending_amount', $sale->getLayawayPendingAmount())
        ->toHaveKeys(['counter_update', 'counter_update.counter', 'counter_update.counter.location', 'mismatches']);
});

test('Store Manager credit sale can be searched', function (): void {
    $counter = Counter::factory()->create([
        'location_id' => $this->location->getKey(),
    ]);

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->getKey(),
    ]);

    $sale = Sale::factory()->create([
        'counter_update_id' => $counterUpdate->getKey(),
        'status' => SaleStatus::PENDING_CREDIT_SALE->value,
        'credit_pending_amount' => 100.00,
    ]);

    $product = Product::factory()->create();

    SaleItem::factory()->create([
        'sale_id' => $sale->getKey(),
        'product_id' => $product->getKey(),
        'total_tax_amount' => 10.00,
        'total_price_paid' => 20.00,
        'cart_discount_amount' => 30.00,
    ]);

    $response = $this->saleQueries->getPaginatedPendingCreditSalesWithRelationsForStoreManager([
        'search_text' => $counter->getName(),
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
        'date_range' => null,
        'location_id' => null,
        'counter_ids' => null,
        'cashier_id' => null,
        'member_id' => null,
        'employee_id' => null,
        'status_id' => CreditAndLayawaySaleStatuses::PENDING->value,
    ], $this->location->id, $this->companyId);

    $this->assertEquals(1, $response->total());

    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('id', $sale->getKey())
        ->toHaveKey('counter_update_id', $sale->getCounterUpdateId())
        ->toHaveKey('total_amount_paid', $sale->getTotalAmountPaid())
        ->toHaveKey('credit_pending_amount', $sale->getCreditPendingAmount())
        ->toHaveKeys(['counter_update', 'counter_update.counter', 'counter_update.counter.location', 'mismatches']);
});

test('new sales can be added', function (): void {
    $member = Member::factory()->create();
    $product = Product::factory()->create();

    $saleDetails = [
        'offline_sale_id' => '1',
        'employee_id' => null,
        'return_items' => null,
        'vouchers' => null,
        'cashback_id' => null,
        'cashback_amount' => null,
        'items' => [
            '0' => [
                'id' => $product->id,
                'price' => '00.00',
                'quantity' => '0',
            ],
        ],
        'payments' => [
            0 => [
                'type_id' => '1',
                'amount' => '300',
            ],
        ],
        'sale_notes' => 'Notes goes here',
        'happened_at' => '2022-01-04 04:20:50',
        'member_id' => $member->id,
        'is_layaway' => false,
        'cart_promotion_id' => 1,
        'sale_round_off_amount' => 0.01,
    ];

    $saleData = new SaleData(...$saleDetails);

    $counterUpdate = CounterUpdate::factory()->create();

    $this->saleQueries->addNew($member->id, $counterUpdate->id, $saleData, '00000001', true);

    $this->assertDatabaseHas('sales', [
        'offline_sale_id' => '1',
        'member_id' => $member->id,
        'counter_update_id' => $counterUpdate->id,
        'has_mismatch' => true,
    ]);
});

test('updateTotals method update the sale details as expected', function (): void {
    $sale = Sale::factory()->create([
        'round_off' => 0.00,
        'status' => SaleStatus::REGULAR_SALE->value,
    ]);

    $saleItem = SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'total_tax_amount' => 10.00,
        'total_price_paid' => 20.00,
        'cart_discount_amount' => 30.00,
    ]);

    $this->saleQueries->updateTotals($sale, 0.00);

    $this->assertDatabaseHas('sales', [
        'id' => $sale->id,
        'total_tax_amount' => $saleItem->total_tax_amount,
        'total_amount_paid' => $saleItem->total_price_paid,
        'cart_discount_amount' => $saleItem->cart_discount_amount,
        'total_amount_before_round_off' => $saleItem->total_price_paid,
        'round_off' => 0.00,
    ]);
});

test('updateTotals method update the sale details as expected when sale has round off amount', function (): void {
    $sale = Sale::factory()->create([
        'status' => SaleStatus::REGULAR_SALE->value,
    ]);

    $saleItem = SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'total_tax_amount' => 10.00,
        'total_price_paid' => 20.00,
    ]);

    $this->saleQueries->updateTotals($sale, 0.01);

    $this->assertDatabaseHas('sales', [
        'id' => $sale->id,
        'total_tax_amount' => $saleItem->total_tax_amount,
        'total_amount_paid' => $saleItem->total_price_paid + 0.01,
        'total_amount_before_round_off' => $saleItem->total_price_paid,
        'round_off' => 0.01,
    ]);
});

test('updateTotals method update the sale details as expected when layaway sale', function (): void {
    $sale = Sale::factory()->create([
        'status' => SaleStatus::PENDING_LAYAWAY_SALE->value,
        'layaway_pending_amount' => 20,
        'round_off' => -0.01,
    ]);

    $saleItem = SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'total_tax_amount' => 10.00,
        'price_paid_per_unit' => 20.00,
        'total_price_paid' => null,
    ]);

    $this->saleQueries->updateTotals($sale, 0.01);

    $this->assertDatabaseHas('sales', [
        'id' => $sale->id,
        'total_tax_amount' => $saleItem->total_tax_amount,
        'total_amount_paid' => 0.00,
        'total_amount_before_round_off' => 0.00,
        'round_off' => $sale->round_off,
    ]);
});

test('updateLayawayPendingAmountAndStatus method works as expected', function (): void {
    $sale = Sale::factory()->create();
    $storeManager = StoreManager::factory()->create();

    $this->saleQueries->updateLayawayPendingAmountAndStatus($sale, 100.00, $storeManager->id);

    $this->assertDatabaseHas('sales', [
        'id' => $sale->id,
        'layaway_pending_amount' => 100.00,
        'status' => SaleStatus::getValueByCaseName('PENDING_LAYAWAY_SALE'),
        'layaway_authorizer_id' => $storeManager->id,
        'layaway_authorizer_type' => ModelMapping::STORE_MANAGER->name,
    ]);
});

test('loadRelations method loads the sale details as expected', function (): void {
    $sale = Sale::factory()->create();

    SaleItem::factory()->create([
        'sale_id' => $sale->id,
    ]);

    SalePayment::factory()->create([
        'sale_id' => $sale->id,
    ]);

    PosMismatch::factory()->create([
        'module_id' => $sale->id,
        'module_type' => ModelMapping::SALE->name,
    ]);

    $response = $this->saleQueries->loadRelations($sale);
    expect($response->toArray())
        ->toHaveKey('member')
        ->toHaveKey('sale_items')
        ->toHaveKey('sale_items.0.sale_item_units')
        ->toHaveKey('payments')
        ->toHaveKey('mismatches')
        ->toHaveKeys(
            [
                'counter_update',
                'counter_update.cashier',
                'counter_update.counter',
                'counter_update.cashier.employee',
            ]
        );
});

test('loadSaleItemsProductAndBrand method loads the sale details as expected', function (): void {
    $sale = Sale::factory()->create();

    SaleItem::factory()->create([
        'sale_id' => $sale->id,
    ]);

    $response = $this->saleQueries->loadSaleItemsProductAndBrand($sale);

    expect($response->toArray())
        ->toHaveKey('sale_items')
        ->toHaveKey('sale_items.0.product')
        ->toHaveKey('sale_items.0.product.brand');
});

test('it can return sale with items product and units', function (): void {
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

    $saleItemUnit = SaleItemUnit::factory()->create([
        'sale_item_id' => $saleItem->id,
    ]);

    $response = $this->saleQueries->getRegularOrLayawayOrCreditSaleByIdWithItemsAndItemUnits($sale->id);

    expect($response->toArray())
        ->toHaveKey('id', $sale->id)
        ->toHaveKey('offline_sale_id', $sale->offline_sale_id)
        ->toHaveKey('sale_items.0.sale_id', $sale->id)
        ->toHaveKey('sale_items.0.product_id', $product->id)
        ->toHaveKey('sale_items.0.sale_item_units.0.sale_item_id', $saleItemUnit->sale_item_id)
        ->toHaveKey('sale_items.0.sale_item_units.0.inventory_id', $saleItemUnit->inventory_id);
});

test('it can mark sale as void', function (): void {
    $sale = Sale::factory()->create([
        'status' => SaleStatus::REGULAR_SALE->value,
    ]);

    $this->saleQueries->markAsVoid($sale);

    $sale->refresh();
    expect($sale->toArray())
        ->toHaveKey('id', $sale->id)
        ->toHaveKey('status', SaleStatus::VOID_SALE->value);
});

test('it does not return voided sale', function (): void {
    $sale = Sale::factory()->create([
        'status' => SaleStatus::VOID_SALE->value,
    ]);

    $this->saleQueries->getRegularOrLayawayOrCreditSaleByIdWithItemsAndItemUnits($sale->id);
})->throws(ModelNotFoundException::class);

test('the getPaginatedVoidedSales method return voided sales', function (): void {
    $cashier = Cashier::factory()->create();
    $counter = Counter::factory()->create([
        'location_id' => $this->location->id,
    ]);

    $counter->location = $this->location;

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->id,
        'cashier_id' => $cashier->id,
    ]);

    $cashier->counter_update_id = $counterUpdate->id;
    $cashier->save();

    $sale = Sale::factory()->create([
        'counter_update_id' => $counterUpdate->id,
        'layaway_pending_amount' => null,
        'status' => SaleStatus::VOID_SALE->value,
        'happened_at' => Carbon::now(),
    ]);

    SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'returned_quantity' => 0,
    ]);

    SalePayment::factory()->create([
        'sale_id' => $sale->id,
        'counter_update_id' => $counterUpdate->id,
    ]);

    $voidSaleReason = VoidSaleReason::factory()->create([
        'company_id' => $counter->location->company_id,
    ]);

    VoidSale::factory()->create([
        'sale_id' => $sale->id,
        'void_sale_reason_id' => $voidSaleReason->id,
    ]);

    $filterData = [
        'member_id' => null,
        'employee_id' => null,
        'is_user' => false,
        'from_date' => Carbon::now()->format('Y-m-d'),
        'to_date' => null,
        'search_text' => null,
        'after_updated_at' => null,
    ];

    $request = new Request($filterData);

    $request->setUserResolver(fn (): Cashier => $cashier);

    $saleQueries = new SaleQueries();

    $response = $saleQueries->getPaginatedVoidedSales($filterData, $this->location->id);

    $this->assertEquals(1, $response->count());

    expect($response->first()->toArray())
        ->toHaveKey('id', $sale->id)
        ->toHaveKey('offline_sale_id', $sale->offline_sale_id)
        ->toHaveKeys(
            [
                'sale_items',
                'sale_items.0.product',
                'sale_items.0.promoters',
                'payments',
                'counter_update',
                'counter_update.cashier',
                'counter_update.counter',
                'counter_update.cashier.employee',
                'mismatches',
                'cashback',
                'generated_vouchers',
            ]
        );
});

test(
    'the getPaginatedRegularAndCompletedLayawaySalesWithItemsPaymentsAndMismatches method returns the regular sales list',
    function (): void {
        $employeeId = Employee::factory()->create([
            'company_id' => $this->companyId,
            'email' => 'employee@company.test',
        ])->id;

        $cashierGroupId = CashierGroup::factory()->create([
            'company_id' => $this->companyId,
            'name' => 'Test Cashier Group',
        ])->id;

        $cashier = Cashier::factory()->create([
            'employee_id' => $employeeId,
            'cashier_group_id' => $cashierGroupId,
            'username' => 'Cashier',
        ]);

        $counter = Counter::factory()->create([
            'location_id' => $this->location->id,
            'name' => 'Counter 1',
        ]);

        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $counter->id,
            'cashier_id' => $cashier->id,
        ]);

        $cashier->counter_update_id = $counterUpdate->id;
        $cashier->save();

        $sale = Sale::factory()->create([
            'counter_update_id' => $counterUpdate->id,
            'layaway_pending_amount' => null,
            'status' => SaleStatus::REGULAR_SALE->value,
            'happened_at' => Carbon::now(),
        ]);

        $product = Product::factory()->create([
            'company_id' => $this->companyId,
        ]);

        SaleItem::factory()->create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
        ]);

        SalePayment::factory()->create([
            'sale_id' => $sale->id,
            'counter_update_id' => $counterUpdate->id,
        ]);

        $filterData = [
            'per_page' => 1,
            'member_id' => null,
            'employee_id' => null,
            'counter_id' => null,
            'from_date' => Carbon::now()->format('Y-m-d'),
            'to_date' => null,
            'sort_by' => null,
            'sort_direction' => null,
            'search_text' => null,
            'after_updated_at' => null,
            'status_id' => null,
        ];

        $request = new Request($filterData);
        $request->setUserResolver(fn (): Cashier => $cashier);

        $saleQueries = new SaleQueries();
        $response = $saleQueries->getPaginatedRegularAndCompletedLayawaySalesWithItemsPaymentsAndMismatches(
            $filterData,
            $this->location->id
        );

        $this->assertEquals(1, $response->count());

        expect($response->first()->toArray())
            ->toHaveKey('id', $sale->id)
            ->toHaveKey('offline_sale_id', $sale->offline_sale_id)
            ->toHaveKeys(
                [
                    'sale_items',
                    'sale_items.0.product',
                    'sale_items.0.product.color',
                    'sale_items.0.product.size',
                    'sale_items.0.promoters',
                    'payments',
                    'mismatches',
                    'counter_update',
                    'counter_update.cashier',
                    'counter_update.counter',
                    'counter_update.cashier.employee',
                    'cashback',
                    'generated_vouchers',
                    'round_off',
                ]
            );
    }
);

test(
    'the getSaleWithRelations method returns the sale details of given offline sale id or sale id',
    function (): void {
        $employeeId = Employee::factory()->create([
            'company_id' => $this->companyId,
            'email' => 'employee@company.test',
        ])->id;

        $cashierGroupId = CashierGroup::factory()->create([
            'company_id' => $this->companyId,
            'name' => 'Test Cashier Group',
        ])->id;

        $cashier = Cashier::factory()->create([
            'employee_id' => $employeeId,
            'cashier_group_id' => $cashierGroupId,
            'username' => 'Cashier',
        ]);

        $counter = Counter::factory()->create([
            'location_id' => $this->location->id,
            'name' => 'Counter 1',
        ]);

        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $counter->id,
            'cashier_id' => $cashier->id,
        ]);

        $cashier->counter_update_id = $counterUpdate->id;
        $cashier->save();

        $sale = Sale::factory()->create([
            'offline_sale_id' => '123456',
            'counter_update_id' => $counterUpdate->id,
            'layaway_pending_amount' => null,
            'status' => SaleStatus::VOID_SALE->value,
            'happened_at' => Carbon::now(),
        ]);

        $companyTwo = Company::factory()->create([
            'name' => 'Test Company1',
            'email' => 'abc1@company.test',
            'code' => 'ABCDE',
        ]);

        $employeeIdTwo = Employee::factory()->create([
            'company_id' => $companyTwo->id,
            'email' => 'employee1@company.test',
        ])->id;

        $cashierGroupIdTwo = CashierGroup::factory()->create([
            'company_id' => $companyTwo->id,
            'name' => 'Test1 Cashier Group',
        ])->id;

        $cashierTwo = Cashier::factory()->create([
            'employee_id' => $employeeIdTwo,
            'cashier_group_id' => $cashierGroupIdTwo,
            'username' => 'Cashier1',
        ]);

        $locationTwo = Location::factory()->create([
            'company_id' => $companyTwo->id,
            'email' => 'store1@company.test',
        ]);

        $counterTwo = Counter::factory()->create([
            'location_id' => $locationTwo->id,
            'name' => 'Counter 2',
        ]);

        $counterUpdateTwo = CounterUpdate::factory()->create([
            'counter_id' => $counterTwo->id,
            'cashier_id' => $cashierTwo->id,
        ]);

        $cashierTwo->counter_update_id = $counterUpdateTwo->id;
        $cashierTwo->save();

        $saleTwo = Sale::factory()->create([
            'offline_sale_id' => '1234567',
            'counter_update_id' => $counterUpdateTwo->id,
            'layaway_pending_amount' => null,
            'status' => SaleStatus::VOID_SALE->value,
            'happened_at' => Carbon::now(),
        ]);

        $request = new Request();
        $request->setUserResolver(fn (): Cashier => $cashier);

        $saleQueries = new SaleQueries();

        $response = $saleQueries->getSaleWithRelations($this->companyId, $sale->offline_sale_id);

        expect($response->toArray())
            ->toHaveKey('id', $sale->id)
            ->toHaveKey('offline_sale_id', $sale->offline_sale_id)
            ->toHaveKeys(
                [
                    'sale_items',
                    'payments',
                    'mismatches',
                    'counter_update',
                    'counter_update.cashier',
                    'counter_update.counter',
                    'counter_update.cashier.employee',
                    'round_off',
                    'used_voucher',
                ]
            );

        expect($response->toArray())
            ->not()
            ->toHaveKey('id', $saleTwo->id);
    }
);

test(
    'the getPendingLayawaySalesWithItemsPaymentsAndMismatches method returns the pending layaway sales list only',
    function (): void {
        [$sale, $filterData, $counter, $location] = commonSaleQueriesSeedRecords(
            $this->companyId,
            SaleStatus::PENDING_LAYAWAY_SALE->value
        );

        $saleQueries = new SaleQueries();
        $response = $saleQueries->getPendingLayawaySalesWithItemsPaymentsAndMismatches($filterData, $location->id);

        $this->assertEquals(1, $response->count());

        expect($response->first()->toArray())
            ->toHaveKey('id', $sale->id)
            ->toHaveKey('offline_sale_id', $sale->offline_sale_id)
            ->toHaveKeys(
                [
                    'sale_items',
                    'sale_items.0.product',
                    'sale_items.0.promoters',
                    'payments',
                    'mismatches',
                    'counter_update',
                    'counter_update.cashier',
                    'counter_update.cashier.employee',
                    'counter_update.counter',
                ]
            );
    }
);

test(
    'the getPendingLayawaySaleByIdWithItemsPaymentsAndMismatches method returns the pending layaway sales list only',
    function (): void {
        [$sale, $filterData, $counter, $location] = commonSaleQueriesSeedRecords(
            $this->companyId,
            SaleStatus::PENDING_LAYAWAY_SALE->value
        );

        $saleQueries = new SaleQueries();
        $response = $saleQueries->getPendingLayawaySaleByIdWithItemsPaymentsAndMismatches($sale->id, $location->id);

        $this->assertEquals(1, $response->count());

        expect($response->toArray())
            ->toHaveKey('id', $sale->id)
            ->toHaveKey('offline_sale_id', $sale->offline_sale_id)
            ->toHaveKeys(
                [
                    'sale_items',
                    'sale_items.0.product',
                    'sale_items.0.promoters',
                    'payments',
                    'mismatches',
                    'counter_update',
                    'counter_update.cashier',
                    'counter_update.cashier.employee',
                    'counter_update.counter',
                ]
            );
    }
);

test(
    'the getPendingCreditSalesWithRelations method returns the pending layaway sales list only',
    function (): void {
        [$sale, $filterData, $counter, $location] = commonSaleQueriesSeedRecords(
            $this->companyId,
            SaleStatus::PENDING_CREDIT_SALE->value
        );

        $saleQueries = new SaleQueries();
        $response = $saleQueries->getPendingCreditSalesWithRelations($filterData, $location->id);

        $this->assertEquals(1, $response->count());

        expect($response->first()->toArray())
            ->toHaveKey('id', $sale->id)
            ->toHaveKey('offline_sale_id', $sale->offline_sale_id)
            ->toHaveKeys(
                [
                    'sale_items',
                    'sale_items.0.product',
                    'sale_items.0.promoters',
                    'payments',
                    'mismatches',
                    'counter_update',
                    'counter_update.cashier',
                    'counter_update.cashier.employee',
                    'counter_update.counter',
                ]
            );
    }
);

test(
    'the getPendingCreditSaleByIdWithRelations method returns the pending layaway sales list only',
    function (): void {
        [$sale, $filterData, $counter, $location] = commonSaleQueriesSeedRecords(
            $this->companyId,
            SaleStatus::PENDING_CREDIT_SALE->value
        );

        $saleQueries = new SaleQueries();
        $response = $saleQueries->getPendingCreditSaleByIdWithRelations($sale->id, $location->id);

        $this->assertEquals(1, $response->count());

        expect($response->toArray())
            ->toHaveKey('id', $sale->id)
            ->toHaveKey('offline_sale_id', $sale->offline_sale_id)
            ->toHaveKeys(
                [
                    'sale_items',
                    'sale_items.0.product',
                    'sale_items.0.promoters',
                    'payments',
                    'mismatches',
                    'counter_update',
                    'counter_update.cashier',
                    'counter_update.cashier.employee',
                    'counter_update.counter',
                ]
            );
    }
);

function commonSaleQueriesSeedRecords(int $companyId, int $status): array
{
    $employeeId = Employee::factory()->create([
        'company_id' => $companyId,
        'email' => 'employee@company.test',
    ])->id;

    $cashierGroupId = CashierGroup::factory()->create([
        'company_id' => $companyId,
        'name' => 'Test Cashier Group',
    ])->id;

    $cashier = Cashier::factory()->create([
        'employee_id' => $employeeId,
        'cashier_group_id' => $cashierGroupId,
        'username' => 'Cashier',
    ]);

    $location = Location::factory()->create([
        'company_id' => $companyId,
        'email' => 'store@company.test',
    ]);

    $counter = Counter::factory()->create([
        'location_id' => $location->id,
        'name' => 'Counter 1',
    ]);

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->id,
        'cashier_id' => $cashier->id,
    ]);

    $cashier->counter_update_id = $counterUpdate->id;
    $cashier->save();

    $sale = Sale::factory()->create([
        'member_id' => null,
        'counter_update_id' => $counterUpdate->id,
        'layaway_pending_amount' => 1,
        'status' => $status,
        'happened_at' => Carbon::now(),
    ]);

    $product = Product::factory()->create([
        'company_id' => $companyId,
    ]);

    SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'product_id' => $product->id,
    ]);

    SalePayment::factory()->create([
        'sale_id' => $sale->id,
        'counter_update_id' => $counterUpdate->id,
    ]);

    $filterData = [
        'per_page' => 1,
        'member_id' => null,
        'employee_id' => null,
        'from_date' => Carbon::now()->format('Y-m-d'),
        'to_date' => null,
        'search_text' => null,
        'after_updated_at' => null,
    ];

    $request = new Request($filterData);
    $request->setUserResolver(fn (): Cashier => $cashier);

    return [$sale, $filterData, $counter, $location];
}

test('getSaleByIdWithSaleItems method returns the specified sale details', function (): void {
    $sale = Sale::factory()->create([
        'layaway_pending_amount' => 1,
        'status' => SaleStatus::PENDING_LAYAWAY_SALE->value,
        'happened_at' => Carbon::now(),
    ]);
    $saleQueries = new SaleQueries();
    $response = $saleQueries->getSaleByIdWithSaleItems($sale->id);
    expect($response->toArray())
        ->toHaveKeys(
            [
                'id',
                'offline_sale_id',
                'total_tax_amount',
                'total_amount_paid',
                'layaway_pending_amount',
                'status',
                'notes',
                'happened_at',
                'has_mismatch',
                'sale_items',
                'round_off',
            ]
        );
});

test('updateLayawayAmountOf method updates the specified sale layaway amounts', function (): void {
    $layawayPendingAmount = 100;
    $totalAmountPaid = 10;

    $sale = Sale::factory()->create([
        'member_id' => null,
        'layaway_pending_amount' => $layawayPendingAmount,
        'total_amount_paid' => $totalAmountPaid,
        'status' => SaleStatus::PENDING_LAYAWAY_SALE->value,
        'happened_at' => Carbon::now(),
    ]);

    $payments = collect([
        [
            'type_id' => 1,
            'amount' => 10,
        ],
    ]);

    $saleQueries = new SaleQueries();
    $saleQueries->updateLayawayAmountOf($sale, $payments, now()->toDateTimeString());

    $this->assertDatabaseHas('sales', [
        'id' => $sale->id,
        'layaway_pending_amount' => (string) $layawayPendingAmount - $payments->sum('amount'),
        'total_amount_paid' => (string) $totalAmountPaid + $payments->sum('amount'),
    ]);
});

test(
    'the getRegularSalesByCounterUpdateId method returns the sales with sale items by counter update id',
    function (): void {
        $counterUpdate = CounterUpdate::factory()->create();
        $sale = seedSale(SaleStatus::REGULAR_SALE->value, $counterUpdate->id);
        SaleItem::factory()->create([
            'sale_id' => $sale->id,
            'is_exchange' => false,
        ]);
        $saleQueries = new SaleQueries();
        $response = $saleQueries->getRegularSalesByCounterUpdateId($counterUpdate->id);
        expect($response->first()->toArray())
            ->toHaveKey('id', $sale->getKey())
            ->toHaveKey('total_tax_amount', $sale->total_tax_amount)
            ->toHaveKey('cart_discount_amount', $sale->cart_discount_amount)
            ->toHaveKey('items_discount_amount', $sale->items_discount_amount)
            ->toHaveKey('total_amount_paid', $sale->total_amount_paid)
            ->toHaveKey('round_off', $sale->round_off);
    }
);

test(
    'the getLayawaySalesByCounterUpdateId method returns the layaway sales by counter update id',
    function (): void {
        $counterUpdate = CounterUpdate::factory()->create();
        $sale = seedSale(SaleStatus::PENDING_LAYAWAY_SALE->value, $counterUpdate->id);
        $saleQueries = new SaleQueries();
        $response = $saleQueries->getLayawaySalesByCounterUpdateId($counterUpdate->id);
        expect($response->first()->toArray())
            ->toHaveKey('id', $sale->getKey())
            ->toHaveKey('total_tax_amount', $sale->total_tax_amount)
            ->toHaveKey('cart_discount_amount', $sale->cart_discount_amount)
            ->toHaveKey('items_discount_amount', $sale->items_discount_amount)
            ->toHaveKey('total_amount_paid', $sale->total_amount_paid)
            ->toHaveKey('round_off', $sale->round_off)
            ->toHaveKey('layaway_pending_amount', $sale->layaway_pending_amount);
    }
);

test(
    'the getCancelLayawaySalesByCounterUpdateId method returns the layaway sales by counter update id',
    function (): void {
        $counterUpdate = CounterUpdate::factory()->create();
        $sale = seedSale(SaleStatus::CANCEL_LAYAWAY_SALE->value, $counterUpdate->id);
        $saleQueries = new SaleQueries();
        $response = $saleQueries->getCancelLayawaySalesByCounterUpdateId($counterUpdate->id);
        expect($response->first()->toArray())
            ->toHaveKey('id', $sale->getKey())
            ->toHaveKey('total_tax_amount', $sale->total_tax_amount)
            ->toHaveKey('cart_discount_amount', $sale->cart_discount_amount)
            ->toHaveKey('items_discount_amount', $sale->items_discount_amount)
            ->toHaveKey('total_amount_paid', $sale->total_amount_paid)
            ->toHaveKey('round_off', $sale->round_off)
            ->toHaveKey('layaway_pending_amount', $sale->layaway_pending_amount);
    }
);

test(
    'the getCreditSalesByCounterUpdateId method returns the credit sales by counter update id',
    function (): void {
        $counterUpdate = CounterUpdate::factory()->create();
        $sale = seedSale(SaleStatus::PENDING_CREDIT_SALE->value, $counterUpdate->id);
        $saleQueries = new SaleQueries();
        $response = $saleQueries->getCreditSalesByCounterUpdateId($counterUpdate->id);
        expect($response->first()->toArray())
            ->toHaveKey('id', $sale->getKey())
            ->toHaveKey('total_tax_amount', $sale->total_tax_amount)
            ->toHaveKey('cart_discount_amount', $sale->cart_discount_amount)
            ->toHaveKey('items_discount_amount', $sale->items_discount_amount)
            ->toHaveKey('total_amount_paid', $sale->total_amount_paid)
            ->toHaveKey('round_off', $sale->round_off)
            ->toHaveKey('credit_pending_amount', $sale->credit_pending_amount);
    }
);

test(
    'the getVoidedSalesByCounterUpdateId method returns the voided sales by counter update id',
    function (): void {
        $counterUpdate = CounterUpdate::factory()->create();
        $sale = seedSale(SaleStatus::VOID_SALE->value, $counterUpdate->id);
        $saleQueries = new SaleQueries();
        $response = $saleQueries->getVoidedSalesByCounterUpdateId($counterUpdate->id);
        expect($response->first()->toArray())
            ->toHaveKey('id', $sale->getKey())
            ->toHaveKey('total_amount_paid', $sale->total_amount_paid);
    }
);

test('checkOfflineSaleId method returns as expected', function (): void {
    $employeeId = Employee::factory()->create([
        'company_id' => $this->companyId,
        'email' => 'employee@company.test',
    ])->id;

    $cashierGroupId = CashierGroup::factory()->create([
        'company_id' => $this->companyId,
        'name' => 'Test Cashier Group',
    ])->id;

    $cashier = Cashier::factory()->create([
        'employee_id' => $employeeId,
        'cashier_group_id' => $cashierGroupId,
        'username' => 'Cashier',
    ]);

    $counter = Counter::factory()->create([
        'location_id' => $this->location->id,
        'name' => 'Counter 1',
    ]);

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->id,
        'cashier_id' => $cashier->id,
    ]);

    Sale::factory()->create([
        'offline_sale_id' => '1',
        'status' => SaleStatus::REGULAR_SALE->value,
        'counter_update_id' => $counterUpdate->id,
        'happened_at' => Carbon::now()->subMonthNoOverflow(),
    ]);

    $response = $this->saleQueries->doesOfflineSaleIdExist('1', $this->companyId);
    $this->assertTrue($response);

    $response = $this->saleQueries->doesOfflineSaleIdExist('2', $this->companyId);
    $this->assertFalse($response);
});

test('loadSaleItems method loads the sale item', function (): void {
    $sale = Sale::factory()->create();

    $saleItem = SaleItem::factory()->create([
        'sale_id' => $sale->id,
    ]);

    $response = $this->saleQueries->loadSaleItems($sale);

    expect($response->toArray())
        ->toHaveKey('id', $sale->id)
        ->toHaveKey('counter_update_id', $sale->counter_update_id)
        ->toHaveKey('sale_items.0.quantity', $saleItem->quantity)
        ->toHaveKey('sale_items.0.returned_quantity', $saleItem->returned_quantity)
        ->toHaveKey('sale_items.0.price_paid_per_unit', $saleItem->price_paid_per_unit);
});

test(
    'the getSalesByPromoter method returns the sales records by promoter id',
    function (): void {
        $employeeId = Employee::factory()->create([
            'company_id' => $this->companyId,
            'email' => 'employee@company.test',
        ])->id;

        $cashierGroupId = CashierGroup::factory()->create([
            'company_id' => $this->companyId,
            'name' => 'Test Cashier Group',
        ])->id;

        $cashier = Cashier::factory()->create([
            'employee_id' => $employeeId,
            'cashier_group_id' => $cashierGroupId,
            'username' => 'Cashier',
        ]);

        $counter = Counter::factory()->create([
            'location_id' => $this->location->id,
            'name' => 'Counter 1',
        ]);

        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $counter->id,
            'cashier_id' => $cashier->id,
        ]);

        $cashier->counter_update_id = $counterUpdate->id;
        $cashier->save();

        $sale = Sale::factory()->create([
            'offline_sale_id' => '123456',
            'counter_update_id' => $counterUpdate->id,
            'layaway_pending_amount' => null,
            'status' => SaleStatus::VOID_SALE->value,
            'happened_at' => Carbon::now(),
        ]);

        $product = Product::factory()->create([
            'company_id' => $this->companyId,
        ]);

        $saleItem = SaleItem::factory()->create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
        ]);

        $promoter = Promoter::factory()->create();

        $saleItem->promoters()->sync($promoter);

        SalePayment::factory()->create([
            'sale_id' => $sale->id,
            'counter_update_id' => $counterUpdate->id,
        ]);

        $request = new Request();
        $request->setUserResolver(fn (): Cashier => $cashier);

        $saleQueries = new SaleQueries();
        $response = $saleQueries->getSalesByPromoter($counter->location_id, $promoter->id);

        $this->assertEquals(1, $response->count());

        expect($response->first()->toArray())
            ->toHaveKey('id', $sale->id)
            ->toHaveKey('offline_sale_id', $sale->offline_sale_id)
            ->toHaveKeys(
                [
                    'sale_items',
                    'sale_items.0.product',
                    'sale_items.0.promoters',
                    'payments',
                    'mismatches',
                    'counter_update',
                    'counter_update.cashier',
                    'counter_update.counter',
                    'counter_update.cashier.employee',
                    'round_off',
                ]
            );
    }
);

test('the getSalesByCounterUpdateId method returns the sales by counter update id', function (): void {
    $counterUpdate = CounterUpdate::factory()->create();
    $sale = seedSale(SaleStatus::REGULAR_SALE->value, $counterUpdate->id);
    $saleQueries = new SaleQueries();
    $response = $saleQueries->getSalesByCounterUpdateId($counterUpdate->id);
    expect($response->first()->toArray())
        ->toHaveKey('id', $sale->getKey())
        ->toHaveKey('total_tax_amount', $sale->total_tax_amount)
        ->toHaveKey('cart_discount_amount', $sale->cart_discount_amount)
        ->toHaveKey('items_discount_amount', $sale->items_discount_amount)
        ->toHaveKey('total_amount_paid', $sale->total_amount_paid)
        ->toHaveKey('status', $sale->status)
        ->toHaveKey('layaway_pending_amount', $sale->layaway_pending_amount)
        ->toHaveKeys(['void_sale', 'sale_items', 'payments']);
});

test('loadVoidSaleRelations method loads the void sale details as expected', function (): void {
    $sale = Sale::factory()->create();

    SaleItem::factory()->create([
        'sale_id' => $sale->id,
    ]);

    SalePayment::factory()->create([
        'sale_id' => $sale->id,
    ]);

    PosMismatch::factory()->create([
        'module_id' => $sale->id,
        'module_type' => ModelMapping::SALE->name,
    ]);

    $response = $this->saleQueries->loadVoidSaleRelations($sale);
    expect($response->toArray())
        ->toHaveKey('sale_items')
        ->toHaveKey('payments')
        ->toHaveKey('mismatches')
        ->toHaveKeys(
            [
                'counter_update',
                'counter_update.cashier',
                'counter_update.counter',
                'counter_update.cashier.employee',
            ]
        );
});

test(
    'getRegularAndLayawaySalesWithRelationsForExport method returns the sale data with relations for export',
    function (): void {
        $counter = Counter::factory()->create([
            'location_id' => $this->location->id,
        ]);

        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $counter->id,
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
            'cart_discount_amount' => 30.00,
        ]);

        $response = $this->saleQueries->getRegularAndLayawaySalesWithRelationsForExport([
            'search_text' => $sale->offline_sale_id,
            'sort_by' => null,
            'sort_direction' => null,
            'date_range' => null,
            'location_ids' => null,
            'counter_ids' => null,
            'cashier_id' => null,
            'member_id' => null,
            'employee_id' => null,
            'offline_sale_id' => null,
            'e_invoice_submitted' => null,
        ], $this->companyId);

        expect($response->first()->toArray())
            ->toHaveKey('id', $sale->id)
            ->toHaveKey('counter_update_id', $sale->counter_update_id)
            ->toHaveKey('total_tax_amount', $sale->total_tax_amount)
            ->toHaveKey('total_discount_amount', $sale->total_discount_amount)
            ->toHaveKey('total_amount_paid', $sale->total_amount_paid)
            ->toHaveKeys(['notes', 'bill_reference_number'])
            ->toHaveKeys(
                [
                    'counter_update',
                    'counter_update.counter',
                    'counter_update.counter.location',
                    'counter_update.counter.location.company',
                    'sale_items',
                    'payments',
                ]
            );
    }
);

test(
    'getVoidSalesWithRelationForExport method returns the sale return data with relations for export',
    function (): void {
        $counter = Counter::factory()->create([
            'location_id' => $this->location->id,
        ]);

        $counter->location = $this->location;

        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $counter->id,
        ]);

        $sale = Sale::factory()->create([
            'member_id' => null,
            'counter_update_id' => $counterUpdate->id,
            'layaway_pending_amount' => null,
            'status' => SaleStatus::VOID_SALE->value,
            'happened_at' => Carbon::now(),
        ]);

        $product = Product::factory()->create();

        SaleItem::factory()->create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'returned_quantity' => 0,
        ]);

        SalePayment::factory()->create([
            'sale_id' => $sale->id,
            'counter_update_id' => $counterUpdate->id,
        ]);

        $voidSaleReason = VoidSaleReason::factory()->create([
            'company_id' => $counter->location->company_id,
        ]);

        VoidSale::factory()->create([
            'sale_id' => $sale->id,
            'void_sale_reason_id' => $voidSaleReason->id,
        ]);

        $response = $this->saleQueries->getVoidSalesWithRelationForExport([
            'search_text' => $counter->getName(),
            'sort_by' => null,
            'sort_direction' => null,
            'date_range' => null,
            'location_ids' => null,
            'counter_ids' => null,
            'cashier_id' => null,
            'member_id' => null,
            'employee_id' => null,
            'e_invoice_submitted' => null,
        ], $this->companyId);

        expect($response->first()->toArray())
            ->toHaveKey('id', $sale->id)
            ->toHaveKey('offline_sale_id', $sale->offline_sale_id)
            ->toHaveKeys(
                [
                    'sale_items',
                    'counter_update',
                    'counter_update.cashier',
                    'counter_update.counter',
                    'counter_update.cashier.employee',
                ]
            );
    }
);

test(
    'getPendingLayawaySalesWithRelationsForExport method returns the sale data with relations for export',
    function (): void {
        $counter = Counter::factory()->create([
            'location_id' => $this->location->getKey(),
        ]);

        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $counter->getKey(),
        ]);

        $sale = Sale::factory()->create([
            'counter_update_id' => $counterUpdate->getKey(),
            'status' => SaleStatus::PENDING_LAYAWAY_SALE->value,
            'layaway_pending_amount' => 100.00,
        ]);

        $product = Product::factory()->create();

        SaleItem::factory()->create([
            'sale_id' => $sale->getKey(),
            'product_id' => $product->getKey(),
            'total_tax_amount' => 10.00,
            'total_price_paid' => 20.00,
            'cart_discount_amount' => 30.00,
        ]);

        $response = $this->saleQueries->getPendingLayawaySalesWithRelationsForExport([
            'search_text' => $counter->getName(),
            'sort_by' => null,
            'sort_direction' => null,
            'date_range' => null,
            'location_ids' => null,
            'counter_ids' => null,
            'cashier_id' => null,
            'member_id' => null,
            'employee_id' => null,
            'status_id' => CreditAndLayawaySaleStatuses::PENDING->value,
            'e_invoice_submitted' => null,
        ], $this->companyId);

        expect($response->first()->toArray())
            ->toHaveKey('id', $sale->getKey())
            ->toHaveKey('counter_update_id', $sale->getCounterUpdateId())
            ->toHaveKey('total_amount_paid', $sale->getTotalAmountPaid())
            ->toHaveKey('layaway_pending_amount', $sale->getLayawayPendingAmount())
            ->toHaveKeys(
                [
                    'layaway_authorizer_id',
                    'layaway_authorizer_type',
                    'counter_update',
                    'counter_update.counter',
                    'counter_update.counter.location',
                ]
            );
    }
);

test(
    'getPendingCreditSalesWithRelationsForExport method returns the sale data with relations for export',
    function (): void {
        $counter = Counter::factory()->create([
            'location_id' => $this->location->getKey(),
        ]);

        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $counter->getKey(),
        ]);

        $sale = Sale::factory()->create([
            'counter_update_id' => $counterUpdate->getKey(),
            'status' => SaleStatus::PENDING_CREDIT_SALE->value,
            'layaway_pending_amount' => 100.00,
        ]);

        $product = Product::factory()->create();

        SaleItem::factory()->create([
            'sale_id' => $sale->getKey(),
            'product_id' => $product->getKey(),
            'total_tax_amount' => 10.00,
            'total_price_paid' => 20.00,
            'cart_discount_amount' => 30.00,
        ]);

        $response = $this->saleQueries->getPendingCreditSalesWithRelationsForExport([
            'search_text' => $counter->getName(),
            'sort_by' => null,
            'sort_direction' => null,
            'date_range' => null,
            'location_ids' => null,
            'counter_ids' => null,
            'cashier_id' => null,
            'member_id' => null,
            'employee_id' => null,
            'status_id' => CreditAndLayawaySaleStatuses::PENDING->value,
            'e_invoice_submitted' => null,
        ], $this->companyId);

        expect($response->first()->toArray())
            ->toHaveKey('id', $sale->getKey())
            ->toHaveKey('counter_update_id', $sale->getCounterUpdateId())
            ->toHaveKey('total_amount_paid', $sale->getTotalAmountPaid())
            ->toHaveKey('credit_pending_amount', $sale->getCreditPendingAmount())
            ->toHaveKeys(
                [
                    'credit_authorizer_id',
                    'credit_authorizer_type',
                    'counter_update',
                    'counter_update.counter',
                    'counter_update.counter.location',
                ]
            );
    }
);

test(
    'getRegularAndLayawaySalesWithRelationsForExportInStoreManagerPanel method returns the sale data with relations for export',
    function (): void {
        $counter = Counter::factory()->create([
            'location_id' => $this->location->id,
        ]);

        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $counter->id,
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
            'cart_discount_amount' => 30.00,
        ]);

        $response = $this->saleQueries->getRegularAndLayawaySalesWithRelationsForExportInStoreManagerPanel([
            'search_text' => $sale->offline_sale_id,
            'sort_by' => null,
            'sort_direction' => null,
            'date_range' => null,
            'store_ids' => null,
            'counter_ids' => null,
            'cashier_id' => null,
            'member_id' => null,
            'employee_id' => null,
            'offline_sale_id' => null,
        ], [$this->location->id]); // test

        expect($response->first()->toArray())
            ->toHaveKey('id', $sale->id)
            ->toHaveKey('counter_update_id', $sale->counter_update_id)
            ->toHaveKey('total_tax_amount', $sale->total_tax_amount)
            ->toHaveKey('total_discount_amount', $sale->total_discount_amount)
            ->toHaveKey('total_amount_paid', $sale->total_amount_paid)
            ->toHaveKeys(['notes', 'bill_reference_number'])
            ->toHaveKeys(
                [
                    'counter_update',
                    'counter_update.counter',
                    'counter_update.counter.location',
                    'counter_update.counter.location.company',
                    'sale_items',
                ]
            );
    }
);

test(
    'getVoidSalesWithRelationsForExportInStoreManagerPanel method returns the sale return data with relations for export',
    function (): void {
        $counter = Counter::factory()->create([
            'location_id' => $this->location->id,
        ]);

        $counter->location = $this->location;

        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $counter->id,
        ]);

        $sale = Sale::factory()->create([
            'member_id' => null,
            'counter_update_id' => $counterUpdate->id,
            'layaway_pending_amount' => null,
            'status' => SaleStatus::VOID_SALE->value,
            'happened_at' => Carbon::now(),
        ]);

        $product = Product::factory()->create();

        SaleItem::factory()->create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'returned_quantity' => 0,
        ]);

        SalePayment::factory()->create([
            'sale_id' => $sale->id,
            'counter_update_id' => $counterUpdate->id,
        ]);

        $voidSaleReason = VoidSaleReason::factory()->create([
            'company_id' => $counter->location->company_id,
        ]);

        VoidSale::factory()->create([
            'sale_id' => $sale->id,
            'void_sale_reason_id' => $voidSaleReason->id,
        ]);

        $response = $this->saleQueries->getVoidSalesWithRelationsForExportInStoreManagerPanel([
            'search_text' => $counter->getName(),
            'sort_by' => null,
            'sort_direction' => null,
            'date_range' => null,
            'counter_ids' => null,
            'cashier_id' => null,
            'member_id' => null,
            'employee_id' => null,
            'void_sale_number' => null,
        ], $this->location->id, $this->companyId);

        expect($response->first()->toArray())
            ->toHaveKey('id', $sale->id)
            ->toHaveKey('offline_sale_id', $sale->offline_sale_id)
            ->toHaveKeys(
                [
                    'counter_update',
                    'counter_update.cashier',
                    'counter_update.counter',
                    'counter_update.cashier.employee',
                ]
            );
    }
);

test(
    'getByStoreIdForSalesCollectionExport method returns the sale return data with relations for sales collection report',
    function (): void {
        $counter = Counter::factory()->create([
            'location_id' => $this->location->id,
        ]);

        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $counter->id,
            'opened_by_pos_at' => Carbon::now()->format('Y-m-d'),
        ]);

        $sale = Sale::factory()->create([
            'member_id' => null,
            'counter_update_id' => $counterUpdate->id,
            'layaway_pending_amount' => null,
            'status' => SaleStatus::REGULAR_SALE->value,
            'happened_at' => Carbon::now(),
        ]);

        $product = Product::factory()->create();

        SaleItem::factory()->create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'returned_quantity' => 0,
        ]);

        SalePayment::factory()->create([
            'sale_id' => $sale->id,
            'counter_update_id' => $counterUpdate->id,
        ]);

        $response = $this->saleQueries->getByStoreIdForSalesCollectionExport([
            'location_ids' => [$this->location->id],
            'counter_ids' => null,
            'cashier_ids' => null,
            'date_range' => [Carbon::now()->yesterday()->format('Y-m-d'), Carbon::now()->addDay()->format('Y-m-d')],
        ]);

        expect($response->first()->toArray())
            ->toHaveKeys(['id', 'offline_sale_id', 'notes', 'total_amount_paid', 'payments']);
    }
);

test(
    'getPendingLayawaySalesWithRelationsForExportInStoreManagerPanel method returns the sale data with relations for export',
    function (): void {
        $counter = Counter::factory()->create([
            'location_id' => $this->location->getKey(),
        ]);

        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $counter->getKey(),
        ]);

        $sale = Sale::factory()->create([
            'counter_update_id' => $counterUpdate->getKey(),
            'status' => SaleStatus::PENDING_LAYAWAY_SALE->value,
            'layaway_pending_amount' => 100.00,
        ]);

        $product = Product::factory()->create();

        SaleItem::factory()->create([
            'sale_id' => $sale->getKey(),
            'product_id' => $product->getKey(),
            'total_tax_amount' => 10.00,
            'total_price_paid' => 20.00,
            'cart_discount_amount' => 30.00,
        ]);

        $response = $this->saleQueries->getPendingLayawaySalesWithRelationsForExportInStoreManagerPanel([
            'search_text' => $counter->getName(),
            'sort_by' => null,
            'sort_direction' => null,
            'date_range' => null,
            'store_ids' => null,
            'counter_ids' => null,
            'cashier_id' => null,
            'member_id' => null,
            'employee_id' => null,
            'status_id' => CreditAndLayawaySaleStatuses::PENDING->value,
        ], $this->location->id, $this->companyId); // test

        expect($response->first()->toArray())
            ->toHaveKey('id', $sale->getKey())
            ->toHaveKey('counter_update_id', $sale->getCounterUpdateId())
            ->toHaveKey('total_amount_paid', $sale->getTotalAmountPaid())
            ->toHaveKey('layaway_pending_amount', $sale->getLayawayPendingAmount())
            ->toHaveKeys(
                [
                    'layaway_authorizer_id',
                    'layaway_authorizer_type',
                    'counter_update',
                    'counter_update.counter',
                    'counter_update.counter.location',
                ]
            );
    }
);

test(
    'getPendingCreditSalesWithRelationsForExportInStoreManagerPanel method returns the sale data with relations for export',
    function (): void {
        $counter = Counter::factory()->create([
            'location_id' => $this->location->getKey(),
        ]);

        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $counter->getKey(),
        ]);

        $sale = Sale::factory()->create([
            'counter_update_id' => $counterUpdate->getKey(),
            'status' => SaleStatus::PENDING_CREDIT_SALE->value,
            'credit_pending_amount' => 100.00,
        ]);

        $product = Product::factory()->create();

        SaleItem::factory()->create([
            'sale_id' => $sale->getKey(),
            'product_id' => $product->getKey(),
            'total_tax_amount' => 10.00,
            'total_price_paid' => 20.00,
            'cart_discount_amount' => 30.00,
        ]);

        $response = $this->saleQueries->getPendingCreditSalesWithRelationsForExportInStoreManagerPanel([
            'search_text' => $counter->getName(),
            'sort_by' => null,
            'sort_direction' => null,
            'date_range' => null,
            'store_ids' => null,
            'counter_ids' => null,
            'cashier_id' => null,
            'member_id' => null,
            'employee_id' => null,
            'status_id' => CreditAndLayawaySaleStatuses::PENDING->value,
        ], $this->location->id, $this->companyId); // test

        expect($response->first()->toArray())
            ->toHaveKey('id', $sale->getKey())
            ->toHaveKey('counter_update_id', $sale->getCounterUpdateId())
            ->toHaveKey('total_amount_paid', $sale->getTotalAmountPaid())
            ->toHaveKey('credit_pending_amount', $sale->getCreditPendingAmount())
            ->toHaveKeys(
                [
                    'credit_authorizer_id',
                    'credit_authorizer_type',
                    'counter_update',
                    'counter_update.counter',
                    'counter_update.counter.location',
                ]
            );
    }
);

test('can get cached hourly based sales for chart', function (): void {
    $data = now();

    $companyId = $this->companyId;

    $brandId = Brand::factory()->create()->id;

    $counter = Counter::factory()->create([
        'location_id' => $this->location->getKey(),
    ]);

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->getKey(),
    ]);

    Sale::factory()->create([
        'counter_update_id' => $counterUpdate->id,
        'status' => SaleStatus::REGULAR_SALE->value,
        'happened_at' => now(),
    ]);

    Cache::forget(
        'cache-hourly-sales-' . $companyId . '-' . $this->location->id . '-' . $brandId . '-' . $data->format(
            'Y-m-d'
        ) . $brandId
    );

    $result = $this->saleQueries->getHourlyBasedData(
        $companyId,
        $this->location->id,
        $brandId,
        $data->format('Y-m-d'),
        false
    ); // test

    expect($result)->toBeInstanceOf(Collection::class);

    expect(
        Cache::has(
            'cache-hourly-sales-' . $companyId . '-' . $this->location->id . '-' . $brandId . '-' . $data->format(
                'Y-m-d'
            ) . $brandId
        )
    )->toBeTrue();

    $cachedResult = $this->saleQueries->getHourlyBasedData(
        $companyId,
        $this->location->id,
        $brandId,
        $data->format('Y-m-d'),
        false
    );

    expect($cachedResult)->toEqual($result);
});

test('can get daily stores wise sales data', function (): void {
    $companyId = $this->companyId;
    $product = Product::factory()->create([
        'company_id' => $companyId,
    ]);
    $counter = Counter::factory()->create([
        'location_id' => $this->location->getKey(),
    ]);
    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->getKey(),
    ]);
    $sale = Sale::factory()->create([
        'counter_update_id' => $counterUpdate->id,
        'status' => SaleStatus::REGULAR_SALE->value,
        'happened_at' => CommonFunctions::addEndTime(now()->format('Y-m-d')),
        'layaway_pending_amount' => null,
    ]);
    SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'product_id' => $product->id,
        'is_exchange' => 0,
        'sale_return_item_id' => null,
    ]);

    $result = $this->saleQueries->getDailyStoreWiseData(
        now()->startOfDay()->format('Y-m-d H:i:s'),
        now()->endOfDay()->format('Y-m-d H:i:s')
    );

    expect($result)->toBeInstanceOf(Collection::class);

    expect($result->first())->toHaveKey('location_id', $this->location->id)
        ->toHaveKey('company_id', $this->companyId);
});

test(
    'getTotalSalesAmountAndTotalSale method returns the sale data with relations',
    function (): void {
        $counter = Counter::factory()->create([
            'location_id' => $this->location->getKey(),
        ]);

        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $counter->getKey(),
        ]);
        $date = now()->format('Y-m-d H:i:s');
        $sale = Sale::factory()->create([
            'counter_update_id' => $counterUpdate->getKey(),
            'status' => SaleStatus::REGULAR_SALE->value,
            'layaway_pending_amount' => 100.00,
            'happened_at' => $date,
        ]);

        $product = Product::factory()->create();

        SaleItem::factory()->create([
            'sale_id' => $sale->getKey(),
            'product_id' => $product->getKey(),
            'total_tax_amount' => 10.00,
            'total_price_paid' => 20.00,
            'cart_discount_amount' => 30.00,
            'is_exchange' => 0,
            'quantity' => 1,
        ]);

        Cache::forget('cache-hourly-sales-today-' . $this->location->id);

        $response = $this->saleQueries->getTotalSalesAmountAndTotalSale(
            'today',
            now()->startOfDay()->format('Y-m-d H:i:s'),
            $date,
            $this->companyId,
            $this->location->id,
        );

        expect($response->toArray())
            ->toHaveKey('total_amount', 20)
            ->toHaveKey('total_units_sold', 1)
            ->toHaveKey('total_sales_count', 1);

        expect(Cache::has('cache-hourly-sales-today-' . $this->location->id))->toBeTrue();

        $cachedResponse = $this->saleQueries->getTotalSalesAmountAndTotalSale(
            'today',
            now()->startOfDay()->format('Y-m-d H:i:s'),
            $date,
            $this->companyId,
            null,
        );

        expect($cachedResponse)->toEqual($response);
    }
);

test(
    'getFilteredTotalsForReport method returns the sale total',
    function (): void {
        $counter = Counter::factory()->create([
            'location_id' => $this->location->getKey(),
        ]);

        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $counter->getKey(),
        ]);
        $date = now();
        $sale = Sale::factory()->create([
            'counter_update_id' => $counterUpdate->getKey(),
            'status' => SaleStatus::REGULAR_SALE->value,
            'layaway_pending_amount' => null,
            'happened_at' => $date->format('Y-m-d H:i:s'),
        ]);

        $product = Product::factory()->create();

        SaleItem::factory()->create([
            'sale_id' => $sale->getKey(),
            'product_id' => $product->getKey(),
            'total_tax_amount' => 10.00,
            'total_price_paid' => 20.00,
            'cart_discount_amount' => 30.00,
            'is_exchange' => 0,
            'quantity' => 1,
        ]);

        $filterData = [
            'search_text' => null,
            'sort_by' => null,
            'sort_direction' => null,
            'per_page' => null,
            'date_range' => null,
            'location_ids' => [$this->location->getKey()],
            'counter_ids' => null,
            'cashier_id' => null,
            'member_id' => null,
            'employee_id' => null,
            'offline_sale_id' => null,
            'e_invoice_submitted' => null,
        ];

        $response = $this->saleQueries->getFilteredTotalsForReport($filterData, $this->companyId);

        expect($response->toArray())
            ->toHaveKey('total_sales_amount', $sale->getTotalAmountPaid())
            ->toHaveKey('total_sales', 1)
            ->toHaveKey('total_units_sold', 1);
    }
);

test('the getByOfflineId method returns the sale returns', function (): void {
    $counter = Counter::factory()->create([
        'location_id' => $this->location->id,
    ]);

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->id,
    ]);

    $sale = Sale::factory()->create([
        'counter_update_id' => $counterUpdate->id,
    ]);

    $response = $this->saleQueries->getByOfflineId($sale->offline_sale_id, $this->companyId);

    expect($response->toArray())
        ->toHaveKey('id', $sale->id);
});

function seedSale(int $status, $counterUpdateId): Sale
{
    return Sale::factory()->create([
        'counter_update_id' => $counterUpdateId,
        'status' => $status,
    ]);
}

test('the getSaleItemsBy method returns the sale returns when product variant is false', function (): void {
    Config::set('app.product_variant', false);

    $counter = Counter::factory()->create([
        'location_id' => $this->location->id,
    ]);

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->id,
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
        'cart_discount_amount' => 30.00,
    ]);

    $response = $this->saleQueries->getSaleItemsBy($sale->id, $this->companyId);

    expect($response->first()->toArray())
        ->toHaveKey('id', $sale->id)
        ->toHaveKey('counter_update_id', $sale->counter_update_id)
        ->toHaveKey('total_tax_amount', $sale->total_tax_amount)
        ->toHaveKey('total_discount_amount', $sale->total_discount_amount)
        ->toHaveKey('total_amount_paid', $sale->total_amount_paid);
});

test('the getSaleItemsBy method returns the sale returns when product variant is true', function (): void {
    Config::set('app.product_variant', true);

    $counter = Counter::factory()->create([
        'location_id' => $this->location->id,
    ]);

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->id,
    ]);

    $sale = Sale::factory()->create([
        'counter_update_id' => $counterUpdate->id,
        'status' => SaleStatus::REGULAR_SALE->value,
        'layaway_pending_amount' => null,
    ]);

    $masterProduct = MasterProduct::factory()->create([
        'company_id' => $this->companyId,
        'has_batch' => false,
        'is_non_inventory' => false,
    ]);

    $product = Product::factory()->create([
        'master_product_id' => $masterProduct->id,
    ]);

    SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'product_id' => $product->id,
        'total_tax_amount' => 10.00,
        'total_price_paid' => 20.00,
        'cart_discount_amount' => 30.00,
    ]);

    $response = $this->saleQueries->getSaleItemsBy($sale->id, $this->companyId);

    expect($response->first()->toArray())
        ->toHaveKey('id', $sale->id)
        ->toHaveKey('counter_update_id', $sale->counter_update_id)
        ->toHaveKey('total_tax_amount', $sale->total_tax_amount)
        ->toHaveKey('total_discount_amount', $sale->total_discount_amount)
        ->toHaveKey('total_amount_paid', $sale->total_amount_paid);
});

test(
    'the getSaleItemsForStoreManager method returns the sale returns when product variant',
    function (bool $productVariant): void {
        Config::set('app.product_variant', $productVariant);

        $counter = Counter::factory()->create([
            'location_id' => $this->location->id,
        ]);

        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $counter->id,
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
            'cart_discount_amount' => 30.00,
        ]);

        $response = $this->saleQueries->getSaleItemsForStoreManager($sale->id, $this->location->id, $this->companyId);

        expect($response->first()->toArray())
            ->toHaveKey('id', $sale->id)
            ->toHaveKey('counter_update_id', $sale->counter_update_id)
            ->toHaveKey('total_tax_amount', $sale->total_tax_amount)
            ->toHaveKey('total_discount_amount', $sale->total_discount_amount)
            ->toHaveKey('total_amount_paid', $sale->total_amount_paid);
    }
)->with([[true], [false]]);

test('the getLayawaySaleItemsBy method returns the sale returns when product variant is false', function (): void {
    Config::set('app.product_variant', false);

    $counter = Counter::factory()->create([
        'location_id' => $this->location->getKey(),
    ]);

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->getKey(),
    ]);

    $sale = Sale::factory()->create([
        'counter_update_id' => $counterUpdate->getKey(),
        'status' => SaleStatus::PENDING_LAYAWAY_SALE->value,
        'layaway_pending_amount' => 100.00,
    ]);

    $product = Product::factory()->create();

    SaleItem::factory()->create([
        'sale_id' => $sale->getKey(),
        'product_id' => $product->getKey(),
        'total_tax_amount' => 10.00,
        'total_price_paid' => 20.00,
        'cart_discount_amount' => 30.00,
    ]);

    $response = $this->saleQueries->getLayawaySaleItemsBy($sale->id, $this->companyId);

    expect($response->first()->toArray())
        ->toHaveKey('id', $sale->getKey())
        ->toHaveKey('counter_update_id', $sale->getCounterUpdateId())
        ->toHaveKey('total_amount_paid', $sale->getTotalAmountPaid())
        ->toHaveKey('layaway_pending_amount', $sale->getLayawayPendingAmount());
});

test('the getLayawaySaleItemsBy method returns the sale returns when product variant is true', function (): void {
    Config::set('app.product_variant', true);

    $counter = Counter::factory()->create([
        'location_id' => $this->location->getKey(),
    ]);

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->getKey(),
    ]);

    $sale = Sale::factory()->create([
        'counter_update_id' => $counterUpdate->getKey(),
        'status' => SaleStatus::PENDING_LAYAWAY_SALE->value,
        'layaway_pending_amount' => 100.00,
    ]);

    $masterProduct = MasterProduct::factory()->create([
        'company_id' => $this->companyId,
        'has_batch' => false,
        'is_non_inventory' => false,
    ]);

    $product = Product::factory()->create([
        'master_product_id' => $masterProduct->id,
    ]);

    SaleItem::factory()->create([
        'sale_id' => $sale->getKey(),
        'product_id' => $product->getKey(),
        'total_tax_amount' => 10.00,
        'total_price_paid' => 20.00,
        'cart_discount_amount' => 30.00,
    ]);

    $response = $this->saleQueries->getLayawaySaleItemsBy($sale->id, $this->companyId);

    expect($response->first()->toArray())
        ->toHaveKey('id', $sale->getKey())
        ->toHaveKey('counter_update_id', $sale->getCounterUpdateId())
        ->toHaveKey('total_amount_paid', $sale->getTotalAmountPaid())
        ->toHaveKey('layaway_pending_amount', $sale->getLayawayPendingAmount());
});

test('the getCreditSaleItemsBy method returns the sale returns when product variant is false', function (): void {
    Config::set('app.product_variant', false);

    $counter = Counter::factory()->create([
        'location_id' => $this->location->getKey(),
    ]);

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->getKey(),
    ]);

    $sale = Sale::factory()->create([
        'counter_update_id' => $counterUpdate->getKey(),
        'status' => SaleStatus::PENDING_CREDIT_SALE->value,
        'credit_pending_amount' => 100.00,
    ]);

    $product = Product::factory()->create();

    SaleItem::factory()->create([
        'sale_id' => $sale->getKey(),
        'product_id' => $product->getKey(),
        'total_tax_amount' => 10.00,
        'total_price_paid' => 20.00,
        'cart_discount_amount' => 30.00,
    ]);

    $response = $this->saleQueries->getCreditSaleItemsBy($sale->id, $this->companyId);

    expect($response->first()->toArray())
        ->toHaveKey('id', $sale->getKey())
        ->toHaveKey('counter_update_id', $sale->getCounterUpdateId())
        ->toHaveKey('total_amount_paid', $sale->getTotalAmountPaid())
        ->toHaveKey('credit_pending_amount', $sale->getCreditPendingAmount());
});

test('the getCreditSaleItemsBy method returns the sale returns when product variant is true', function (): void {
    Config::set('app.product_variant', true);

    $counter = Counter::factory()->create([
        'location_id' => $this->location->getKey(),
    ]);

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->getKey(),
    ]);

    $sale = Sale::factory()->create([
        'counter_update_id' => $counterUpdate->getKey(),
        'status' => SaleStatus::PENDING_CREDIT_SALE->value,
        'credit_pending_amount' => 100.00,
    ]);

    $masterProduct = MasterProduct::factory()->create([
        'company_id' => $this->companyId,
        'has_batch' => false,
        'is_non_inventory' => false,
    ]);

    $product = Product::factory()->create([
        'master_product_id' => $masterProduct->id,
    ]);

    SaleItem::factory()->create([
        'sale_id' => $sale->getKey(),
        'product_id' => $product->getKey(),
        'total_tax_amount' => 10.00,
        'total_price_paid' => 20.00,
        'cart_discount_amount' => 30.00,
    ]);

    $response = $this->saleQueries->getCreditSaleItemsBy($sale->id, $this->companyId);

    expect($response->first()->toArray())
        ->toHaveKey('id', $sale->getKey())
        ->toHaveKey('counter_update_id', $sale->getCounterUpdateId())
        ->toHaveKey('total_amount_paid', $sale->getTotalAmountPaid())
        ->toHaveKey('credit_pending_amount', $sale->getCreditPendingAmount());
});

test('the getVoidSaleItemsBy method returns the void sale when product variant is false', function (): void {
    Config::set('app.product_variant', false);

    $counter = Counter::factory()->create([
        'location_id' => $this->location->id,
    ]);

    $counter->location = $this->location;

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->id,
    ]);

    $sale = Sale::factory()->create([
        'member_id' => null,
        'counter_update_id' => $counterUpdate->id,
        'layaway_pending_amount' => null,
        'status' => SaleStatus::VOID_SALE->value,
        'happened_at' => Carbon::now(),
    ]);

    $product = Product::factory()->create();

    SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'product_id' => $product->id,
        'returned_quantity' => 0,
    ]);

    SalePayment::factory()->create([
        'sale_id' => $sale->id,
        'counter_update_id' => $counterUpdate->id,
    ]);

    $voidSaleReason = VoidSaleReason::factory()->create([
        'company_id' => $counter->location->company_id,
    ]);

    VoidSale::factory()->create([
        'sale_id' => $sale->id,
        'void_sale_reason_id' => $voidSaleReason->id,
    ]);

    $response = $this->saleQueries->getVoidSaleItemsBy($sale->id, $this->companyId);

    expect($response->first()->toArray())
        ->toHaveKey('id', $sale->id)
        ->toHaveKey('counter_update_id', $sale->getCounterUpdateId())
        ->toHaveKey('offline_sale_id', $sale->offline_sale_id);
});

test('the getVoidSaleItemsBy method returns the void sale when product variant is true', function (): void {
    Config::set('app.product_variant', true);

    $counter = Counter::factory()->create([
        'location_id' => $this->location->id,
    ]);

    $counter->location = $this->location;

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->id,
    ]);

    $sale = Sale::factory()->create([
        'member_id' => null,
        'counter_update_id' => $counterUpdate->id,
        'layaway_pending_amount' => null,
        'status' => SaleStatus::VOID_SALE->value,
        'happened_at' => Carbon::now(),
    ]);

    $masterProduct = MasterProduct::factory()->create([
        'company_id' => $this->companyId,
        'has_batch' => false,
        'is_non_inventory' => false,
    ]);

    $product = Product::factory()->create([
        'company_id' => $this->companyId,
        'master_product_id' => $masterProduct->id,
    ]);

    SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'product_id' => $product->id,
        'returned_quantity' => 0,
    ]);

    SalePayment::factory()->create([
        'sale_id' => $sale->id,
        'counter_update_id' => $counterUpdate->id,
    ]);

    $voidSaleReason = VoidSaleReason::factory()->create([
        'company_id' => $counter->location->company_id,
    ]);

    VoidSale::factory()->create([
        'sale_id' => $sale->id,
        'void_sale_reason_id' => $voidSaleReason->id,
    ]);

    $response = $this->saleQueries->getVoidSaleItemsBy($sale->id, $this->companyId);

    expect($response->first()->toArray())
        ->toHaveKey('id', $sale->id)
        ->toHaveKey('counter_update_id', $sale->getCounterUpdateId())
        ->toHaveKey('offline_sale_id', $sale->offline_sale_id);
});

test(
    'the getLayawaySaleItemsForStoreManager method returns the layaway sale when product variant',
    function (bool $productVariant): void {
        Config::set('app.product_variant', $productVariant);

        $counter = Counter::factory()->create([
            'location_id' => $this->location->getKey(),
        ]);

        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $counter->getKey(),
        ]);

        $sale = Sale::factory()->create([
            'counter_update_id' => $counterUpdate->getKey(),
            'status' => SaleStatus::PENDING_LAYAWAY_SALE->value,
            'layaway_pending_amount' => 100.00,
        ]);

        $product = Product::factory()->create();

        SaleItem::factory()->create([
            'sale_id' => $sale->getKey(),
            'product_id' => $product->getKey(),
            'total_tax_amount' => 10.00,
            'total_price_paid' => 20.00,
            'cart_discount_amount' => 30.00,
        ]);

        $response = $this->saleQueries->getLayawaySaleItemsForStoreManager(
            $sale->id,
            $this->location->id,
            $this->companyId
        );

        expect($response->first()->toArray())
            ->toHaveKey('id', $sale->getKey())
            ->toHaveKey('counter_update_id', $sale->getCounterUpdateId())
            ->toHaveKey('total_amount_paid', $sale->getTotalAmountPaid())
            ->toHaveKey('layaway_pending_amount', $sale->getLayawayPendingAmount())
            ->toHaveKey('total_amount_before_round_off', $sale->getTotalAmountBeforeRoundOff());
    }
)->with([[true], [false]]);

test(
    'the getCreditSaleItemsForStoreManager method returns the layaway sale when product variant',
    function (bool $productVariant): void {
        Config::set('app.product_variant', $productVariant);

        $counter = Counter::factory()->create([
            'location_id' => $this->location->getKey(),
        ]);

        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $counter->getKey(),
        ]);

        $sale = Sale::factory()->create([
            'counter_update_id' => $counterUpdate->getKey(),
            'status' => SaleStatus::PENDING_CREDIT_SALE->value,
            'credit_pending_amount' => 100.00,
        ]);

        $product = Product::factory()->create();

        SaleItem::factory()->create([
            'sale_id' => $sale->getKey(),
            'product_id' => $product->getKey(),
            'total_tax_amount' => 10.00,
            'total_price_paid' => 20.00,
            'cart_discount_amount' => 30.00,
        ]);

        $response = $this->saleQueries->getCreditSaleItemsForStoreManager(
            $sale->id,
            $this->location->id,
            $this->companyId
        );

        expect($response->first()->toArray())
            ->toHaveKey('id', $sale->getKey())
            ->toHaveKey('counter_update_id', $sale->getCounterUpdateId())
            ->toHaveKey('total_amount_paid', $sale->getTotalAmountPaid())
            ->toHaveKey('credit_pending_amount', $sale->getCreditPendingAmount());
    }
)->with([[true], [false]]);

test(
    'the getVoidSaleItemsForStoreManager method returns the void sale when product variant',
    function (bool $productVariant): void {
        Config::set('app.product_variant', $productVariant);

        $counter = Counter::factory()->create([
            'location_id' => $this->location->id,
        ]);

        $counter->location = $this->location;

        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $counter->id,
        ]);

        $sale = Sale::factory()->create([
            'member_id' => null,
            'counter_update_id' => $counterUpdate->id,
            'layaway_pending_amount' => null,
            'status' => SaleStatus::VOID_SALE->value,
            'happened_at' => Carbon::now(),
        ]);

        $product = Product::factory()->create();

        SaleItem::factory()->create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'returned_quantity' => 0,
        ]);

        SalePayment::factory()->create([
            'sale_id' => $sale->id,
            'counter_update_id' => $counterUpdate->id,
        ]);

        $voidSaleReason = VoidSaleReason::factory()->create([
            'company_id' => $counter->location->company_id,
        ]);

        VoidSale::factory()->create([
            'sale_id' => $sale->id,
            'void_sale_reason_id' => $voidSaleReason->id,
        ]);

        $response = $this->saleQueries->getVoidSaleItemsForStoreManager(
            $sale->id,
            $this->location->id,
            $this->companyId
        );

        expect($response->first()->toArray())
            ->toHaveKey('id', $sale->id)
            ->toHaveKey('counter_update_id', $sale->getCounterUpdateId())
            ->toHaveKey('offline_sale_id', $sale->offline_sale_id);
    }
)->with([[true], [false]]);

test('the getSalesByEmployeeWithDateRange method returns the sales by employee with date range', function (): void {
    $counter = Counter::factory()->create([
        'location_id' => $this->location->id,
    ]);

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->id,
    ]);
    $employee = Employee::factory()->create([
        'company_id' => $this->companyId,
    ]);

    $date = Carbon::now();
    $previousDate = $date->subDays(1)->format('Y-m-d');
    $currentDate = $date->format('Y-m-d');

    $member = Member::factory()->create([
        'company_id' => $this->companyId,
        'employee_id' => $employee->id,
    ]);

    $sale = Sale::factory()->create([
        'member_id' => $member->id,
        'counter_update_id' => $counterUpdate->id,
        'status' => SaleStatus::REGULAR_SALE->value,
        'happened_at' => $date,
    ]);

    $response = $this->saleQueries->getSalesByEmployeeWithDateRange($previousDate, $currentDate, $employee->id);

    expect($response->first()->toArray())
        ->toHaveKey('id', $sale->id)
        ->toHaveKey('total_amount_paid', $sale->total_amount_paid);
});

test('loadCancelLayawaySaleRelations method loads the void sale details as expected', function (): void {
    $sale = Sale::factory()->create();

    SaleItem::factory()->create([
        'sale_id' => $sale->id,
    ]);

    SalePayment::factory()->create([
        'sale_id' => $sale->id,
    ]);

    PosMismatch::factory()->create([
        'module_id' => $sale->id,
        'module_type' => ModelMapping::SALE->name,
    ]);

    $response = $this->saleQueries->loadCancelLayawaySaleRelations($sale);
    expect($response->toArray())
        ->toHaveKey('sale_items')
        ->toHaveKey('payments')
        ->toHaveKey('mismatches')
        ->toHaveKeys(
            [
                'counter_update',
                'counter_update.cashier',
                'counter_update.counter',
                'counter_update.cashier.employee',
            ]
        );
});

test('loadCancelCreditSaleRelations method loads the void sale details as expected', function (): void {
    $sale = Sale::factory()->create();

    SaleItem::factory()->create([
        'sale_id' => $sale->id,
    ]);

    SalePayment::factory()->create([
        'sale_id' => $sale->id,
    ]);

    PosMismatch::factory()->create([
        'module_id' => $sale->id,
        'module_type' => ModelMapping::SALE->name,
    ]);

    $response = $this->saleQueries->loadCancelCreditSaleRelations($sale);
    expect($response->toArray())
        ->toHaveKey('sale_items')
        ->toHaveKey('payments')
        ->toHaveKey('mismatches')
        ->toHaveKeys(
            [
                'counter_update',
                'counter_update.cashier',
                'counter_update.counter',
                'counter_update.cashier.employee',
            ]
        );
});

test('getPendingLayawaySaleByIdWithRelations can return sale with items product and units', function (): void {
    $sale = Sale::factory()->create([
        'status' => SaleStatus::PENDING_LAYAWAY_SALE->value,
    ]);

    $product = Product::factory()->create([
        'company_id' => $this->companyId,
    ]);

    $saleItem = SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'product_id' => $product->id,
    ]);

    $saleItemUnit = SaleItemUnit::factory()->create([
        'sale_item_id' => $saleItem->id,
    ]);

    $response = $this->saleQueries->getPendingLayawaySaleByIdWithRelations($sale->id);

    expect($response->toArray())
        ->toHaveKey('id', $sale->id)
        ->toHaveKey('offline_sale_id', $sale->offline_sale_id)
        ->toHaveKey('sale_items.0.sale_id', $sale->id)
        ->toHaveKey('sale_items.0.product_id', $product->id)
        ->toHaveKey('sale_items.0.sale_item_units.0.sale_item_id', $saleItemUnit->sale_item_id)
        ->toHaveKey('sale_items.0.sale_item_units.0.inventory_id', $saleItemUnit->inventory_id);
});

test('getPendingCreditSaleByIdWithRelations can return sale with items product and units', function (): void {
    $counter = Counter::factory()->create([
        'location_id' => $this->location->id,
    ]);

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->id,
    ]);

    $sale = Sale::factory()->create([
        'status' => SaleStatus::PENDING_CREDIT_SALE->value,
        'counter_update_id' => $counterUpdate->id,
    ]);

    $product = Product::factory()->create([
        'company_id' => $this->companyId,
    ]);

    $saleItem = SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'product_id' => $product->id,
    ]);

    $saleItemUnit = SaleItemUnit::factory()->create([
        'sale_item_id' => $saleItem->id,
    ]);

    $response = $this->saleQueries->getPendingCreditSaleByIdAndRelations($sale->id, $this->location->id);

    expect($response->toArray())
        ->toHaveKey('id', $sale->id)
        ->toHaveKey('offline_sale_id', $sale->offline_sale_id)
        ->toHaveKey('sale_items.0.sale_id', $sale->id)
        ->toHaveKey('sale_items.0.product_id', $product->id)
        ->toHaveKey('sale_items.0.sale_item_units.0.sale_item_id', $saleItemUnit->sale_item_id)
        ->toHaveKey('sale_items.0.sale_item_units.0.inventory_id', $saleItemUnit->inventory_id);
});

test('it can mark as cancel layaway', function (): void {
    $sale = Sale::factory()->create([
        'status' => SaleStatus::PENDING_LAYAWAY_SALE->value,
    ]);

    $this->saleQueries->markAsCancelLayaway($sale);

    $sale->refresh();
    expect($sale->toArray())
        ->toHaveKey('id', $sale->id)
        ->toHaveKey('status', SaleStatus::CANCEL_LAYAWAY_SALE->value);
});

test('it can mark as cancel credit', function (): void {
    $sale = Sale::factory()->create([
        'status' => SaleStatus::CANCEL_CREDIT_SALE->value,
    ]);

    $this->saleQueries->markAsCancelCredit($sale);

    $sale->refresh();
    expect($sale->toArray())
        ->toHaveKey('id', $sale->id)
        ->toHaveKey('status', SaleStatus::CANCEL_CREDIT_SALE->value);
});

test('the getSaleItemsForStoreManagerApi method returns the sale returns', function (): void {
    $counter = Counter::factory()->create([
        'location_id' => $this->location->id,
    ]);

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->id,
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
        'cart_discount_amount' => 30.00,
    ]);

    $response = $this->saleQueries->getSaleItemsForStoreManagerApi(
        $sale->id,
        $this->location->id,
        $this->companyId
    ); // test

    expect($response->toArray())
        ->toHaveKey('id', $sale->id)
        ->toHaveKey('total_tax_amount', $sale->total_tax_amount)
        ->toHaveKey('total_discount_amount', $sale->total_discount_amount)
        ->toHaveKey('total_amount_paid', $sale->total_amount_paid)
        ->toHaveKeys(['sale_items', 'sale_items.0.product']);
});

test('the getPaginatedSaleListForMemberApi method returns the sales', function (): void {
    $counter = Counter::factory()->create([
        'location_id' => $this->location->id,
    ]);

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->id,
    ]);

    $member = Member::factory()->create([
        'company_id' => $this->companyId,
    ]);

    $sale = Sale::factory()->create([
        'counter_update_id' => $counterUpdate->id,
        'status' => SaleStatus::REGULAR_SALE->value,
        'layaway_pending_amount' => null,
        'member_id' => $member->id,
    ]);

    $response = $this->saleQueries->getPaginatedSaleListForMemberApi([
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ], $member->id);

    $this->assertEquals(1, $response->total());

    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('id', $sale->id)
        ->toHaveKey('counter_update_id', $sale->counter_update_id)
        ->toHaveKey('total_tax_amount', $sale->total_tax_amount)
        ->toHaveKey('total_discount_amount', $sale->total_discount_amount)
        ->toHaveKey('total_amount_paid', $sale->total_amount_paid)
        ->toHaveKeys(['counter_update', 'counter_update.counter', 'counter_update.counter.location']);
});

test('updateCreditPendingAmountAndStatus method works as expected', function (): void {
    $sale = Sale::factory()->create();
    $storeManager = StoreManager::factory()->create();

    $this->saleQueries->updateCreditPendingAmountAndStatus($sale, 100.00, $storeManager->id);

    $this->assertDatabaseHas('sales', [
        'id' => $sale->id,
        'credit_pending_amount' => 100.00,
        'status' => SaleStatus::getValueByCaseName('PENDING_CREDIT_SALE'),
        'credit_authorizer_id' => $storeManager->id,
        'credit_authorizer_type' => ModelMapping::STORE_MANAGER->name,
    ]);
});

test('Cancel Layaway sale can be searched', function (): void {
    $counter = Counter::factory()->create([
        'location_id' => $this->location->getKey(),
    ]);

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->getKey(),
    ]);

    $sale = Sale::factory()->create([
        'counter_update_id' => $counterUpdate->getKey(),
        'status' => SaleStatus::CANCEL_LAYAWAY_SALE->value,
        'layaway_pending_amount' => 100.00,
    ]);

    $product = Product::factory()->create();

    SaleItem::factory()->create([
        'sale_id' => $sale->getKey(),
        'product_id' => $product->getKey(),
        'total_tax_amount' => 10.00,
        'total_price_paid' => 20.00,
        'cart_discount_amount' => 30.00,
    ]);

    $response = $this->saleQueries->getPaginatedCancelLayawaySalesWithRelations([
        'search_text' => $counter->getName(),
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
        'date_range' => null,
        'location_ids' => null,
        'counter_ids' => null,
        'cashier_id' => null,
        'member_id' => null,
        'employee_id' => null,
        'e_invoice_submitted' => null,
    ], $this->companyId);

    $this->assertEquals(1, $response->total());

    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('id', $sale->getKey())
        ->toHaveKey('counter_update_id', $sale->getCounterUpdateId())
        ->toHaveKey('total_amount_paid', $sale->getTotalAmountPaid())
        ->toHaveKey('layaway_pending_amount', $sale->getLayawayPendingAmount())
        ->toHaveKeys(
            [
                'layaway_authorizer_id',
                'layaway_authorizer_type',
                'counter_update',
                'counter_update.counter',
                'counter_update.counter.location',
            ]
        );
});

test(
    'getCancelLayawaySalesWithRelationsForExport method returns the sale data with relations for export',
    function (): void {
        $counter = Counter::factory()->create([
            'location_id' => $this->location->getKey(),
        ]);

        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $counter->getKey(),
        ]);

        $sale = Sale::factory()->create([
            'counter_update_id' => $counterUpdate->getKey(),
            'status' => SaleStatus::CANCEL_LAYAWAY_SALE->value,
            'layaway_pending_amount' => 100.00,
        ]);

        $product = Product::factory()->create();

        SaleItem::factory()->create([
            'sale_id' => $sale->getKey(),
            'product_id' => $product->getKey(),
            'total_tax_amount' => 10.00,
            'total_price_paid' => 20.00,
            'cart_discount_amount' => 30.00,
        ]);

        $response = $this->saleQueries->getCancelLayawaySalesWithRelationsForExport([
            'search_text' => $counter->getName(),
            'sort_by' => null,
            'sort_direction' => null,
            'date_range' => null,
            'location_ids' => null,
            'counter_ids' => null,
            'cashier_id' => null,
            'member_id' => null,
            'employee_id' => null,
            'e_invoice_submitted' => null,
        ], $this->companyId);

        expect($response->first()->toArray())
            ->toHaveKey('id', $sale->getKey())
            ->toHaveKey('counter_update_id', $sale->getCounterUpdateId())
            ->toHaveKey('total_amount_paid', $sale->getTotalAmountPaid())
            ->toHaveKey('layaway_pending_amount', $sale->getLayawayPendingAmount())
            ->toHaveKeys(
                [
                    'layaway_authorizer_id',
                    'layaway_authorizer_type',
                    'counter_update',
                    'counter_update.counter',
                    'counter_update.counter.location',
                ]
            );
    }
);

test(
    'the getCancelLayawaySaleItemsBy method returns the sale returns when product variant is false',
    function (): void {
        Config::set('app.product_variant', false);

        $counter = Counter::factory()->create([
            'location_id' => $this->location->getKey(),
        ]);

        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $counter->getKey(),
        ]);

        $sale = Sale::factory()->create([
            'counter_update_id' => $counterUpdate->getKey(),
            'status' => SaleStatus::CANCEL_LAYAWAY_SALE->value,
            'layaway_pending_amount' => 100.00,
        ]);

        $product = Product::factory()->create();

        SaleItem::factory()->create([
            'sale_id' => $sale->getKey(),
            'product_id' => $product->getKey(),
            'total_tax_amount' => 10.00,
            'total_price_paid' => 20.00,
            'cart_discount_amount' => 30.00,
        ]);

        $response = $this->saleQueries->getCancelLayawaySaleItemsBy($sale->id, $this->companyId);

        expect($response->first()->toArray())
            ->toHaveKey('id', $sale->getKey())
            ->toHaveKey('counter_update_id', $sale->getCounterUpdateId())
            ->toHaveKey('total_amount_paid', $sale->getTotalAmountPaid())
            ->toHaveKey('layaway_pending_amount', $sale->getLayawayPendingAmount());
    }
);

test('the getCancelLayawaySaleItemsBy method returns the sale returns when product variant is true', function (): void {
    Config::set('app.product_variant', true);

    $counter = Counter::factory()->create([
        'location_id' => $this->location->getKey(),
    ]);

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->getKey(),
    ]);

    $sale = Sale::factory()->create([
        'counter_update_id' => $counterUpdate->getKey(),
        'status' => SaleStatus::CANCEL_LAYAWAY_SALE->value,
        'layaway_pending_amount' => 100.00,
    ]);

    $masterProduct = MasterProduct::factory()->create([
        'company_id' => $this->companyId,
        'has_batch' => false,
        'is_non_inventory' => false,
    ]);

    $product = Product::factory()->create([
        'company_id' => $this->companyId,
        'master_product_id' => $masterProduct->id,
    ]);

    SaleItem::factory()->create([
        'sale_id' => $sale->getKey(),
        'product_id' => $product->getKey(),
        'total_tax_amount' => 10.00,
        'total_price_paid' => 20.00,
        'cart_discount_amount' => 30.00,
    ]);

    $response = $this->saleQueries->getCancelLayawaySaleItemsBy($sale->id, $this->companyId);

    expect($response->first()->toArray())
        ->toHaveKey('id', $sale->getKey())
        ->toHaveKey('counter_update_id', $sale->getCounterUpdateId())
        ->toHaveKey('total_amount_paid', $sale->getTotalAmountPaid())
        ->toHaveKey('layaway_pending_amount', $sale->getLayawayPendingAmount());
});

test('Cancel Layaway sale for store manager  can be searched', function (): void {
    $counter = Counter::factory()->create([
        'location_id' => $this->location->getKey(),
    ]);

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->getKey(),
    ]);

    $sale = Sale::factory()->create([
        'counter_update_id' => $counterUpdate->getKey(),
        'status' => SaleStatus::CANCEL_LAYAWAY_SALE->value,
        'layaway_pending_amount' => 100.00,
    ]);

    $product = Product::factory()->create();

    SaleItem::factory()->create([
        'sale_id' => $sale->getKey(),
        'product_id' => $product->getKey(),
        'total_tax_amount' => 10.00,
        'total_price_paid' => 20.00,
        'cart_discount_amount' => 30.00,
    ]);

    $response = $this->saleQueries->getPaginatedCancelLayawaySalesForStoreManager([
        'search_text' => $counter->getName(),
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
        'date_range' => null,
        'counter_ids' => null,
        'cashier_id' => null,
        'member_id' => null,
        'employee_id' => null,
    ], $this->location->id, $this->companyId); // test

    $this->assertEquals(1, $response->total());

    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('id', $sale->getKey())
        ->toHaveKey('counter_update_id', $sale->getCounterUpdateId())
        ->toHaveKey('total_amount_paid', $sale->getTotalAmountPaid());
});

test(
    'getCancelLayawaySalesExportForStoreManager method returns the sale data with relations for export',
    function (): void {
        $counter = Counter::factory()->create([
            'location_id' => $this->location->getKey(),
        ]);

        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $counter->getKey(),
        ]);

        $sale = Sale::factory()->create([
            'counter_update_id' => $counterUpdate->getKey(),
            'status' => SaleStatus::CANCEL_LAYAWAY_SALE->value,
            'layaway_pending_amount' => 100.00,
        ]);

        $product = Product::factory()->create();

        SaleItem::factory()->create([
            'sale_id' => $sale->getKey(),
            'product_id' => $product->getKey(),
            'total_tax_amount' => 10.00,
            'total_price_paid' => 20.00,
            'cart_discount_amount' => 30.00,
        ]);

        $response = $this->saleQueries->getCancelLayawaySalesExportForStoreManager([
            'search_text' => $counter->getName(),
            'sort_by' => null,
            'sort_direction' => null,
            'date_range' => null,
            'counter_ids' => null,
            'cashier_id' => null,
            'member_id' => null,
            'employee_id' => null,
        ], $this->location->id, $this->companyId); // test

        expect($response->first()->toArray())
            ->toHaveKey('id', $sale->getKey())
            ->toHaveKey('counter_update_id', $sale->getCounterUpdateId())
            ->toHaveKey('total_amount_paid', $sale->getTotalAmountPaid());
    }
);

test(
    'the getCancelLayawaySaleItemsByForStoreManager method returns the sale returns when product variant',
    function (bool $productVariant): void {
        $counter = Counter::factory()->create([
            'location_id' => $this->location->getKey(),
        ]);

        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $counter->getKey(),
        ]);

        $sale = Sale::factory()->create([
            'counter_update_id' => $counterUpdate->getKey(),
            'status' => SaleStatus::CANCEL_LAYAWAY_SALE->value,
            'layaway_pending_amount' => 100.00,
        ]);

        $product = Product::factory()->create();

        SaleItem::factory()->create([
            'sale_id' => $sale->getKey(),
            'product_id' => $product->getKey(),
            'total_tax_amount' => 10.00,
            'total_price_paid' => 20.00,
            'cart_discount_amount' => 30.00,
        ]);

        $response = $this->saleQueries->getCancelLayawaySaleItemsByForStoreManager(
            $sale->id,
            $this->location->id,
            $this->companyId
        );

        expect($response->first()->toArray())
            ->toHaveKey('id', $sale->getKey())
            ->toHaveKey('counter_update_id', $sale->getCounterUpdateId())
            ->toHaveKey('total_amount_paid', $sale->getTotalAmountPaid());
    }
)->with([[true], [false]]);

test('the getSaleDetailsById method returns the sales', function (): void {
    $counter = Counter::factory()->create([
        'location_id' => $this->location->id,
    ]);

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->id,
    ]);

    $member = Member::factory()->create([
        'company_id' => $this->companyId,
    ]);

    $sale = Sale::factory()->create([
        'counter_update_id' => $counterUpdate->id,
        'status' => SaleStatus::REGULAR_SALE->value,
        'layaway_pending_amount' => null,
        'member_id' => $member->id,
    ]);

    $response = $this->saleQueries->getSaleDetailsById($sale->id, $member->id);

    expect($response->first()->toArray())
        ->toHaveKey('id', $sale->id)
        ->toHaveKey('counter_update_id', $sale->counter_update_id)
        ->toHaveKey('total_tax_amount', $sale->total_tax_amount)
        ->toHaveKey('total_discount_amount', $sale->total_discount_amount)
        ->toHaveKey('total_amount_paid', $sale->total_amount_paid)
        ->toHaveKeys(['counter_update', 'counter_update.counter', 'counter_update.counter.location']);
});

test('the getFirstSaleHappenedAt method returns the first sale happened at', function (): void {
    $date = now()->format('Y-m-d H:i:s');
    Sale::factory()->create([
        'status' => SaleStatus::REGULAR_SALE->value,
        'layaway_pending_amount' => null,
        'happened_at' => $date,
    ]);

    $response = $this->saleQueries->getFirstSaleHappenedAt();

    expect($response)->toBe($date);
});

test(
    'the getSalesDataCollectionForTheIOICityMall method returns the sales details of the specified location and happened_at',
    function (): void {
        $date = now()->yesterday()->format('Y-m-d H:i:s');

        $counter = Counter::factory()->create([
            'location_id' => $this->location->id,
        ]);

        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $counter->id,
        ]);

        $sale = Sale::factory()->create([
            'counter_update_id' => $counterUpdate->id,
            'sale_return_id' => null,
            'status' => SaleStatus::REGULAR_SALE->value,
            'layaway_pending_amount' => null,
            'happened_at' => $date,
        ]);

        SaleItem::factory()->create([
            'sale_id' => $sale->getKey(),
            'sale_return_item_id' => null,
            'total_tax_amount' => 10.00,
            'total_price_paid' => 20.00,
            'cart_discount_amount' => 30.00,
        ]);

        $response = $this->saleQueries->getSalesDataCollectionForTheIOICityMall($this->location->id, $date); // test

        expect($response->first()->toArray())->toHaveKeys([
            'id',
            'total_tax_amount',
            'total_discount_amount',
            'happened_at',
        ]);
    }
);

test('The getSaleTotalByUserId method return total sale', function (): void {
    $sale = Sale::factory()->create([
        'sale_return_id' => null,
        'status' => SaleStatus::REGULAR_SALE->value,
        'layaway_pending_amount' => null,
    ]);

    $response = $this->saleQueries->getSaleTotalByMemberId($sale->member_id);

    expect($response)->toBe(1);
});

test(
    'getTotalAmountForSaleCompanyTarget method returns the sale total',
    function (): void {
        $counter = Counter::factory()->create([
            'location_id' => $this->location->getKey(),
        ]);

        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $counter->getKey(),
        ]);
        $date = now();

        Sale::factory()->create([
            'counter_update_id' => $counterUpdate->getKey(),
            'status' => SaleStatus::REGULAR_SALE->value,
            'layaway_pending_amount' => null,
            'total_amount_paid' => 400.65,
            'happened_at' => $date->format('Y-m-d H:i:s'),
        ]);

        $response = $this->saleQueries->getTotalAmountForSaleCompanyTarget(
            $date->format('Y-m-d'),
            $date->format('Y-m-d'),
            $this->companyId
        );

        expect($response->toArray())
            ->toHaveKey('total_sales_amount', 400.65);
    }
);

test(
    'getTotalAmountForSaleStoreTarget method returns the sale total',
    function (): void {
        $counter = Counter::factory()->create([
            'location_id' => $this->location->getKey(),
        ]);

        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $counter->getKey(),
        ]);
        $date = now();

        Sale::factory()->create([
            'counter_update_id' => $counterUpdate->getKey(),
            'status' => SaleStatus::REGULAR_SALE->value,
            'layaway_pending_amount' => null,
            'total_amount_paid' => 400.65,
            'happened_at' => $date->format('Y-m-d H:i:s'),
        ]);

        $response = $this->saleQueries->getTotalAmountForSaleStoreTarget(
            $date->format('Y-m-d'),
            $date->format('Y-m-d'),
            [$this->location->getKey()]
        );

        expect($response->first()->toArray())
            ->toHaveKey('location_id', $this->location->getKey())
            ->toHaveKey('total_sales_amount', 400.65);
    }
);

test(
    'the getSalesDataCollectionForTheTRXMall method returns the sales details of the specified location and happened_at',
    function (): void {
        $date = now()->yesterday()->format('Y-m-d H:i:s');

        $counter = Counter::factory()->create([
            'location_id' => $this->location->id,
        ]);

        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $counter->id,
        ]);

        $sale = Sale::factory()->create([
            'counter_update_id' => $counterUpdate->id,
            'sale_return_id' => null,
            'status' => SaleStatus::REGULAR_SALE->value,
            'layaway_pending_amount' => null,
            'happened_at' => $date,
        ]);

        SaleItem::factory()->create([
            'sale_id' => $sale->getKey(),
            'sale_return_item_id' => null,
            'total_tax_amount' => 10.00,
            'total_price_paid' => 20.00,
            'cart_discount_amount' => 30.00,
        ]);

        $response = $this->saleQueries->getSalesDataCollectionForTheTRXMall($this->location->id, $date); // test

        expect($response->first()->toArray())->toHaveKeys([
            'id',
            'total_tax_amount',
            'total_discount_amount',
            'happened_at',
        ]);
    }
);

test(
    'the getCreditSaleItemsByForPrint method returns the sale returns when product variant is false',
    function (): void {
        Config::set('app.product_variant', false);

        $counter = Counter::factory()->create([
            'location_id' => $this->location->getKey(),
        ]);

        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $counter->getKey(),
        ]);

        $sale = Sale::factory()->create([
            'counter_update_id' => $counterUpdate->getKey(),
            'status' => SaleStatus::PENDING_CREDIT_SALE->value,
            'credit_pending_amount' => 100.00,
        ]);

        $product = Product::factory()->create();

        SaleItem::factory()->create([
            'sale_id' => $sale->getKey(),
            'product_id' => $product->getKey(),
            'total_tax_amount' => 10.00,
            'total_price_paid' => 20.00,
            'cart_discount_amount' => 30.00,
        ]);

        $response = $this->saleQueries->getCreditSaleItemsByForPrint($sale->id, $this->companyId, null);

        expect($response->first()->toArray())
            ->toHaveKey('id', $sale->getKey())
            ->toHaveKey('counter_update_id', $sale->getCounterUpdateId())
            ->toHaveKey('total_amount_paid', $sale->getTotalAmountPaid())
            ->toHaveKey('credit_pending_amount', $sale->getCreditPendingAmount());
    }
);

test(
    'the getCreditSaleItemsByForPrint method returns the sale returns when product variant is true',
    function (): void {
        Config::set('app.product_variant', true);

        $counter = Counter::factory()->create([
            'location_id' => $this->location->getKey(),
        ]);

        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $counter->getKey(),
        ]);

        $sale = Sale::factory()->create([
            'counter_update_id' => $counterUpdate->getKey(),
            'status' => SaleStatus::PENDING_CREDIT_SALE->value,
            'credit_pending_amount' => 100.00,
        ]);

        $masterProduct = MasterProduct::factory()->create([
            'company_id' => $this->companyId,
            'has_batch' => false,
            'is_non_inventory' => false,
        ]);

        $product = Product::factory()->create([
            'company_id' => $this->companyId,
            'master_product_id' => $masterProduct->id,
        ]);

        SaleItem::factory()->create([
            'sale_id' => $sale->getKey(),
            'product_id' => $product->getKey(),
            'total_tax_amount' => 10.00,
            'total_price_paid' => 20.00,
            'cart_discount_amount' => 30.00,
        ]);

        $response = $this->saleQueries->getCreditSaleItemsByForPrint($sale->id, $this->companyId, null);

        expect($response->first()->toArray())
            ->toHaveKey('id', $sale->getKey())
            ->toHaveKey('counter_update_id', $sale->getCounterUpdateId())
            ->toHaveKey('total_amount_paid', $sale->getTotalAmountPaid())
            ->toHaveKey('credit_pending_amount', $sale->getCreditPendingAmount());
    }
);

test('the totalCreditSalePendingAmount method returns the total credit sale pending amount', function (): void {
    $counter = Counter::factory()->create([
        'location_id' => $this->location->getKey(),
    ]);

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->getKey(),
    ]);

    Sale::factory()->create([
        'counter_update_id' => $counterUpdate->getKey(),
        'status' => SaleStatus::PENDING_CREDIT_SALE->value,
        'credit_pending_amount' => 100.00,
    ]);

    $response = $this->saleQueries->totalCreditSalePendingAmount($this->companyId, $this->location->id); // test
    expect($response)->toBe(100.0);
});

test('the getPaginatedMemberSaleDetails method returns the sale returns', function (): void {
    $counter = Counter::factory()->create([
        'location_id' => $this->location->id,
    ]);

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->id,
    ]);

    $member = Member::factory()->create([
        'company_id' => $this->companyId,
    ]);

    $sale = Sale::factory()->create([
        'member_id' => $member->id,
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
        'cart_discount_amount' => 30.00,
    ]);

    $response = $this->saleQueries->getPaginatedMemberSaleDetails([
        'search_text' => $sale->offline_sale_id,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
        'location_id' => null,
    ], $member->id);

    $this->assertEquals(1, $response->total());

    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('id', $sale->id)
        ->toHaveKey('counter_update_id', $sale->counter_update_id)
        ->toHaveKey('total_tax_amount', $sale->total_tax_amount)
        ->toHaveKey('total_discount_amount', $sale->total_discount_amount)
        ->toHaveKey('total_amount_paid', $sale->total_amount_paid)
        ->toHaveKeys(['counter_update', 'counter_update.counter', 'counter_update.counter.location']);
});

test(
    'loadSaleItemAndOtherRelation method loads the saleItem,product,counterUpdate,counter,location,storeManagers as expected',
    function (): void {
        $sale = Sale::factory()->create();
        SaleItem::factory()->create([
            'sale_id' => $sale->id,
        ]);

        $response = $this->saleQueries->loadSaleItemAndOtherRelation($sale);

        expect($response->toArray())
            ->toHaveKeys(
                [
                    'counter_update',
                    'counter_update.counter',
                    'counter_update.counter.location',
                    'counter_update.counter.location.store_managers',
                    'sale_items',
                    'sale_items.0.product',
                ]
            );
    }
);

test('getSaleHourForPrint method call and returns the records as expected.', function (): void {
    $counter = Counter::factory()->create([
        'location_id' => $this->location->id,
    ]);

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->id,
    ]);

    $sale = Sale::factory()->create([
        'counter_update_id' => $counterUpdate->id,
        'happened_at' => Carbon::now(),
        'status' => SaleStatus::REGULAR_SALE->value,
    ]);

    $product = Product::factory()->create();

    $response = $this->saleQueries->getSaleHourForPrint([
        'location_id' => null,
        'date_range' => [Carbon::now()->yesterday()->format('Y-m-d'), Carbon::now()->addDay()->format('Y-m-d')],
    ], $this->companyId);

    expect($response->first()->toArray())
        ->toHaveKey('id', $sale->id)
        ->toHaveKey('counter_update_id', $sale->counter_update_id)
        ->toHaveKey('happened_at', $sale->happened_at)
        ->toHaveKeys(['sale_items', 'counter_update', 'counter_update.counter', 'counter_update.counter.location']);
});

test('getDailyStoreWiseDataForCounterUpdate method return daily stores wise sales data', function (): void {
    $companyId = $this->companyId;
    $product = Product::factory()->create([
        'company_id' => $companyId,
    ]);
    $counter = Counter::factory()->create([
        'location_id' => $this->location->getKey(),
    ]);
    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->getKey(),
    ]);
    $sale = Sale::factory()->create([
        'counter_update_id' => $counterUpdate->id,
        'status' => SaleStatus::REGULAR_SALE->value,
        'happened_at' => CommonFunctions::addEndTime(now()->format('Y-m-d')),
        'layaway_pending_amount' => null,
    ]);

    SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'product_id' => $product->id,
        'is_exchange' => 0,
        'sale_return_item_id' => null,
    ]);

    $result = $this->saleQueries->getDailyStoreWiseDataForCounterUpdate($counterUpdate->id);

    expect($result)->toBeInstanceOf(Collection::class);

    expect($result->first())->toHaveKey('location_id', $this->location->id)
        ->toHaveKey('company_id', $this->companyId);
});

test('A getSalesByRegionId method call and return sales by regionId with date as expected', function (): void {
    $region = Region::factory()->create([
        'company_id' => $this->companyId,
    ]);

    $brand = Brand::factory()->create();

    $location = Location::factory()->create([
        'company_id' => $this->companyId,
        'region_id' => $region->id,
    ]);

    $location->brands()->sync($brand->id);

    $counter = Counter::factory()->create([
        'location_id' => $location->id,
    ]);

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->id,
        'closed_at' => now()->subDay()->format('Y-m-d H:i:s'),
    ]);

    $sale = Sale::factory()->create([
        'counter_update_id' => $counterUpdate->id,
        'status' => SaleStatus::REGULAR_SALE->value,
        'layaway_pending_amount' => null,
        'happened_at' => now()->subDay()->format('Y-m-d H:i:s'),
    ]);

    $product = Product::factory()->create();

    SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'product_id' => $product->id,
        'total_tax_amount' => 10.00,
        'total_price_paid' => 20.00,
        'cart_discount_amount' => 30.00,
    ]);

    $fromDate = now()->subDay()->startOfDay()->format('Y-m-d H:i:s');
    $toDate = now()->subDay()->endOfDay()->format('Y-m-d H:i:s');

    $response = $this->saleQueries->getSalesByRegionId($region->id, $fromDate, $toDate);

    expect($response->first())
        ->toHaveKeys(['counter_update_id', 'location_id', 'brand_id', 'location_name', 'brand_name']);
});

test('getLayawaySalesWithItemsData method return sales data', function (): void {
    $companyId = $this->companyId;
    $product = Product::factory()->create([
        'company_id' => $companyId,
    ]);
    $counter = Counter::factory()->create([
        'location_id' => $this->location->getKey(),
    ]);
    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->getKey(),
    ]);
    $date = now()->format('Y-m-d');
    $sale = Sale::factory()->create([
        'counter_update_id' => $counterUpdate->id,
        'status' => SaleStatus::PENDING_LAYAWAY_SALE->value,
        'happened_at' => CommonFunctions::addStartTime($date),
        'layaway_pending_amount' => null,
    ]);

    SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'product_id' => $product->id,
        'is_exchange' => 0,
        'sale_return_item_id' => null,
    ]);

    $filterData = [
        'location_ids' => [$this->location->id],
        'counter_ids' => null,
        'cashier_ids' => null,
        'date_range' => [$date, $date],
        'report_type' => 1,
    ];

    $result = $this->saleQueries->getLayawaySalesWithItemsData($filterData, $companyId);

    expect($result)->toBeInstanceOf(Collection::class);
    expect($result->first()->toArray())
        ->toHaveKey('offline_sale_id', $sale->offline_sale_id)
        ->toHaveKey('counter_update_id', $sale->counter_update_id)
        ->toHaveKey('status', $sale->status);
});

test('getSeasonalSalesData method call return proper response', function (): void {
    $dateRange = ['2023-03-23', '2023-04-21'];

    $companyId = $this->companyId;

    $brand = Brand::factory()->create();

    $product = Product::factory()->create([
        'company_id' => $companyId,
        'brand_id' => $brand->id,
    ]);

    $counter = Counter::factory()->create([
        'location_id' => $this->location->getKey(),
    ]);
    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->getKey(),
    ]);

    $date = '2023-03-26';
    $sale = Sale::factory()->create([
        'counter_update_id' => $counterUpdate->id,
        'status' => SaleStatus::PENDING_LAYAWAY_SALE->value,
        'happened_at' => CommonFunctions::addStartTime($date),
        'layaway_pending_amount' => null,
    ]);

    $saleItem = SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'product_id' => $product->id,
        'is_exchange' => 0,
        'sale_return_item_id' => null,
        'total_price_paid' => 100,
    ]);

    $filterData = [
        'location_ids' => [$this->location->id],
        'brand_ids' => [$brand->id],
    ];

    $result = $this->saleQueries->getSeasonalSalesData($filterData, $dateRange, $companyId, 'sale');
    expect($result)->toBeInstanceOf(Collection::class);
    expect($result->first())
        ->toHaveKey('id', $sale->id)
        ->toHaveKey('brand_id', $brand->id)
        ->toHaveKey('brand_name', $brand->name)
        ->toHaveKey('location_id', $this->location->id)
        ->toHaveKey('location_name', $this->location->name)
        ->toHaveKey('sale', $saleItem->total_price_paid);
});

test('updateLayawayAmountOf method updates the specified sale complete layaway amounts', function (): void {
    $layawayPendingAmount = 10;
    $totalAmountPaid = 10;

    $sale = Sale::factory()->create([
        'member_id' => null,
        'layaway_pending_amount' => $layawayPendingAmount,
        'total_amount_paid' => $totalAmountPaid,
        'status' => SaleStatus::PENDING_LAYAWAY_SALE->value,
        'total_amount_before_round_off' => 0,
        'happened_at' => Carbon::now(),
    ]);

    SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'total_price_paid' => 10,
    ]);

    $payments = collect([
        [
            'type_id' => 1,
            'amount' => 10,
        ],
    ]);

    $saleQueries = new SaleQueries();
    $saleQueries->updateLayawayAmountOf($sale, $payments, now()->toDateTimeString());

    $this->assertDatabaseHas('sales', [
        'id' => $sale->id,
        'layaway_pending_amount' => null,
        'total_amount_before_round_off' => 10.00,
        'total_amount_paid' => (string) $totalAmountPaid + $payments->sum('amount'),
    ]);
});

test('updateCreditAmountOf method updates the specified sale credit amounts', function (): void {
    $pendingAmount = 100;
    $totalAmountPaid = 10;

    $sale = Sale::factory()->create([
        'member_id' => null,
        'credit_pending_amount' => $pendingAmount,
        'total_amount_paid' => $totalAmountPaid,
        'status' => SaleStatus::PENDING_LAYAWAY_SALE->value,
        'happened_at' => Carbon::now(),
    ]);

    $payments = collect([
        [
            'type_id' => 1,
            'amount' => 10,
        ],
    ]);

    $saleQueries = new SaleQueries();
    $saleQueries->updateCreditAmountOf($sale, $payments, now()->toDateTimeString());

    $this->assertDatabaseHas('sales', [
        'id' => $sale->id,
        'credit_pending_amount' => (string) $pendingAmount - $payments->sum('amount'),
        'total_amount_paid' => (string) $totalAmountPaid + $payments->sum('amount'),
    ]);
});

test('updateCreditAmountOf method updates the specified sale complete credit amounts', function (): void {
    $pendingAmount = 10;
    $totalAmountPaid = 10;

    $sale = Sale::factory()->create([
        'member_id' => null,
        'credit_pending_amount' => $pendingAmount,
        'total_amount_paid' => $totalAmountPaid,
        'status' => SaleStatus::PENDING_LAYAWAY_SALE->value,
        'total_amount_before_round_off' => 0,
        'happened_at' => Carbon::now(),
    ]);

    SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'total_price_paid' => 10,
    ]);

    $payments = collect([
        [
            'type_id' => 1,
            'amount' => 10,
        ],
    ]);

    $saleQueries = new SaleQueries();
    $saleQueries->updateCreditAmountOf($sale, $payments, now()->toDateTimeString());

    $this->assertDatabaseHas('sales', [
        'id' => $sale->id,
        'credit_pending_amount' => null,
        'total_amount_before_round_off' => 10.00,
        'total_amount_paid' => (string) $totalAmountPaid + $payments->sum('amount'),
    ]);
});

test(
    'getCancelLayawaySaleItemsByForPrint method returns cancel layaway sale details when product variant is false.',
    function (): void {
        Config::set('app.product_variant', false);

        $counter = Counter::factory()->create([
            'location_id' => $this->location->id,
        ]);

        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $counter->id,
        ]);

        $sale = Sale::factory()->create([
            'counter_update_id' => $counterUpdate->id,
            'status' => SaleStatus::CANCEL_LAYAWAY_SALE->value,
        ]);

        $saleQueries = new SaleQueries();
        $response = $saleQueries->getCancelLayawaySaleItemsByForPrint($sale->id, $this->companyId, null);

        expect($response->toArray())
            ->toHaveKeys(
                [
                    'id',
                    'offline_sale_id',
                    'bill_reference_number',
                    'counter_update_id',
                    'total_tax_amount',
                    'total_discount_amount',
                    'total_amount_paid',
                    'layaway_pending_amount',
                    'total_amount_before_round_off',
                    'member_id',
                ]
            );
    }
);

test(
    'getCancelLayawaySaleItemsByForPrint method returns cancel layaway sale details when product variant is true.',
    function (): void {
        Config::set('app.product_variant', true);

        $counter = Counter::factory()->create([
            'location_id' => $this->location->id,
        ]);

        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $counter->id,
        ]);

        $sale = Sale::factory()->create([
            'counter_update_id' => $counterUpdate->id,
            'status' => SaleStatus::CANCEL_LAYAWAY_SALE->value,
        ]);

        $masterProduct = MasterProduct::factory()->create([
            'company_id' => $this->companyId,
            'has_batch' => false,
            'is_non_inventory' => false,
        ]);

        $product = Product::factory()->create([
            'company_id' => $this->companyId,
            'master_product_id' => $masterProduct->id,
        ]);

        SaleItem::factory()->create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
        ]);

        $saleQueries = new SaleQueries();
        $response = $saleQueries->getCancelLayawaySaleItemsByForPrint($sale->id, $this->companyId, null);

        expect($response->toArray())
            ->toHaveKeys(
                [
                    'id',
                    'offline_sale_id',
                    'bill_reference_number',
                    'counter_update_id',
                    'total_tax_amount',
                    'total_discount_amount',
                    'total_amount_paid',
                    'layaway_pending_amount',
                    'total_amount_before_round_off',
                    'member_id',
                ]
            );
    }
);

test('digitalInvoiceUpdate method returns sale.', function (): void {
    $counter = Counter::factory()->create([
        'location_id' => $this->location->id,
    ]);

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->id,
    ]);

    $sale = Sale::factory()->create([
        'counter_update_id' => $counterUpdate->id,
        'digital_invoice_submitted' => false,
    ]);

    $saleQueries = new SaleQueries();
    $saleQueries->digitalInvoiceUpdate($sale->id);
    $this->assertTrue(true);
});

test('getSaleByStoreIdCounterId method returns sale.', function (): void {
    $counter = Counter::factory()->create([
        'location_id' => $this->location->id,
    ]);

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->id,
    ]);

    $sale = Sale::factory()->create([
        'counter_update_id' => $counterUpdate->id,
        'digital_invoice_submitted' => false,
    ]);

    $saleQueries = new SaleQueries();
    $response = $saleQueries->getSaleByStoreIdCounterId(
        $sale->offline_sale_id,
        $this->location->id,
        $counter->id
    ); // test
    expect($response->toArray())
        ->toHaveKey('id', $sale->id)
        ->toHaveKey('digital_invoice_submitted', $sale->digital_invoice_submitted);
});

test(
    'the updateMember method update the sale queries member id to new member id',
    function (): void {
        $member = Member::factory()->create();

        $sale = Sale::factory()->create();

        $this->assertDatabaseHas(Sale::class, [
            'id' => $sale->getKey(),
            'member_id' => $sale->member_id,
        ]);

        $saleQueries = new SaleQueries();
        $saleQueries->updateMember($sale->member_id, $member->getKey());

        $this->assertDatabaseHas(Sale::class, [
            'id' => $sale->getKey(),
            'member_id' => $member->getKey(),
        ]);
    }
);

it('gets yearly sales and sale returns grouped by month', function (): void {
    $dateRange = [
        Carbon::now()->startOfYear()->format('Y-m-d H:i:s'),
        Carbon::now()->endOfYear()->format('Y-m-d H:i:s'),
    ];

    $counter = Counter::factory()->create([
        'location_id' => $this->location->id,
    ]);

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->id,
    ]);

    $sale = Sale::factory()->create([
        'counter_update_id' => $counterUpdate->id,
        'happened_at' => Carbon::now(),
        'total_amount_paid' => 100,
        'status' => SaleStatus::REGULAR_SALE->value,
    ]);

    SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'total_price_paid' => 100,
    ]);

    $filterId = 0;

    $targetType = TargetType::getValueByCaseName('company wise');
    $result = $this->saleQueries->getYearlySalesAndSaleReturnsGroupByMonth(
        $dateRange,
        $this->companyId,
        [],
        [],
        $targetType,
        $filterId
    );

    expect($result)->toHaveCount(1);
    expect($result->first()['net_sales'])->toBe(100.00);
});

it('gets monthly sales and sale returns grouped by month', function (): void {
    $dateRanges = [[
        Carbon::now()->startOfMonth()->format('Y-m-d H:i:s'),
        Carbon::now()->endOfMonth()->format('Y-m-d H:i:s'),
    ]];

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
        'happened_at' => Carbon::now(),
        'total_amount_paid' => 100,
        'status' => SaleStatus::REGULAR_SALE->value,
    ]);

    SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'total_price_paid' => 100,
    ]);

    $saleReturn = SaleReturn::factory()->create([
        'counter_update_id' => $counterUpdate->id,
        'happened_at' => Carbon::now(),
        'total_price_paid' => 20,
    ]);

    SaleReturnItem::factory()->create([
        'sale_return_id' => $saleReturn->id,
        'total_price_paid' => 20,
    ]);

    $targetType = TargetType::getValueByCaseName('company wise');
    $filterId = 0;
    $result = $this->saleQueries->getMonthlySalesAndSaleReturnsGroupByMonth(
        $dateRanges,
        $companyId,
        [],
        [],
        $targetType,
        $filterId
    );

    expect($result)->toHaveCount(1);
    expect($result->first()['net_sales'])->toBe(80.0);
});

it('gets weekly sales and sale returns grouped by week', function (): void {
    $dateRanges = [[
        Carbon::now()->startOfWeek()->format('Y-m-d H:i:s'),
        Carbon::now()->endOfWeek()->format('Y-m-d H:i:s'),
    ]];

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
        'happened_at' => Carbon::now(),
        'total_amount_paid' => 100,
        'status' => SaleStatus::REGULAR_SALE->value,
    ]);

    SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'total_price_paid' => 100,
    ]);

    $saleReturn = SaleReturn::factory()->create([
        'counter_update_id' => $counterUpdate->id,
        'happened_at' => Carbon::now(),
        'total_price_paid' => 20,
    ]);

    SaleReturnItem::factory()->create([
        'sale_return_id' => $saleReturn->id,
        'total_price_paid' => 20,
    ]);

    $targetType = TargetType::getValueByCaseName('company wise');
    $filterId = 0;
    $result = $this->saleQueries->getWeeklySalesAndSaleReturnsGroupByWeek(
        $dateRanges,
        $companyId,
        [],
        [],
        $targetType,
        $filterId
    );

    expect($result)->toHaveCount(1);
    expect($result->first()['net_sales'])->toBe(80.0);
});

it('gets daily sales and sale returns grouped by week', function (): void {
    $dateRanges = [[Carbon::now()->subDay()->format('Y-m-d'), Carbon::now()->addDay()->format('Y-m-d')]];

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
        'happened_at' => Carbon::now(),
        'total_amount_paid' => 100,
        'status' => SaleStatus::REGULAR_SALE->value,
    ]);

    SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'total_price_paid' => 100,
    ]);

    $saleReturn = SaleReturn::factory()->create([
        'counter_update_id' => $counterUpdate->id,
        'happened_at' => Carbon::now(),
        'total_price_paid' => 20,
    ]);

    SaleReturnItem::factory()->create([
        'sale_return_id' => $saleReturn->id,
        'total_price_paid' => 20,
    ]);

    $targetType = TargetType::getValueByCaseName('company wise');
    $filterId = 0;
    $result = $this->saleQueries->getDailySalesAndSaleReturnsGroupByWeek(
        $dateRanges,
        $companyId,
        [],
        [],
        $targetType,
        $filterId
    );

    expect($result)->toHaveCount(1);
    expect($result->first()['net_sales'])->toBe(80.0);
});

it('gets weekly sales and sale returns', function (): void {
    $selectedMonth = Carbon::now()->month;
    $selectedYear = Carbon::now()->year;

    $counter = Counter::factory()->create([
        'location_id' => $this->location->id,
    ]);

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->id,
    ]);

    $sale = Sale::factory()->create([
        'counter_update_id' => $counterUpdate->id,
        'happened_at' => Carbon::now(),
        'total_amount_paid' => 100,
        'status' => SaleStatus::REGULAR_SALE->value,
    ]);

    SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'total_price_paid' => 100,
    ]);

    $saleReturn = SaleReturn::factory()->create([
        'counter_update_id' => $counterUpdate->id,
        'happened_at' => Carbon::now(),
        'total_price_paid' => 20,
    ]);

    SaleReturnItem::factory()->create([
        'sale_return_id' => $saleReturn->id,
        'total_price_paid' => 20,
    ]);

    $targetType = TargetType::getValueByCaseName('company wise');
    $date = Carbon::now();
    $dateRange = [$date->startOfYear()->format('Y-m-d H:i:s'), $date->endOfYear()->format('Y-m-d H:i:s')];
    $result = $this->saleQueries->getWeeklySalesAndSaleReturns(
        $this->companyId,
        $selectedMonth,
        $selectedYear,
        [],
        [],
        $targetType,
        $dateRange
    );

    expect($result)->toHaveCount(1);
    expect($result->first()['net_sales'])->toBe(80.0);
});

it('gets daily sales and sale returns', function (): void {
    $selectedWeek = Carbon::now()->week;
    $selectedYear = Carbon::now()->year;

    $date = Carbon::now()->setISODate($selectedYear, $selectedWeek);
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
        'happened_at' => $date,
        'total_amount_paid' => 100,
    ]);

    SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'total_price_paid' => 100,
    ]);

    $saleReturn = SaleReturn::factory()->create([
        'counter_update_id' => $counterUpdate->id,
        'happened_at' => $date,
        'total_price_paid' => 20,
    ]);

    SaleReturnItem::factory()->create([
        'sale_return_id' => $saleReturn->id,
        'total_price_paid' => 20,
    ]);

    $targetType = TargetType::getValueByCaseName('company wise');
    $date = Carbon::now();
    $dateRange = [$date->startOfYear()->format('Y-m-d H:i:s'), $date->endOfYear()->format('Y-m-d H:i:s')];
    $result = $this->saleQueries->getDailySalesAndSaleReturns(
        $companyId,
        $selectedWeek,
        $selectedYear,
        [],
        [],
        $targetType,
        $dateRange
    );

    expect($result)->toHaveCount(1);
    expect($result->first()['net_sales'])->toBe(80.0);
});

it('filters sales and sale returns by location', function (): void {
    $dateRange = [
        Carbon::now()->startOfYear()->format('Y-m-d H:i:s'),
        Carbon::now()->endOfYear()->format('Y-m-d H:i:s'),
    ];

    $companyId = Company::factory()->create()->id;

    $location = Location::factory()->create([
        'company_id' => $companyId,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $otherCounter = Counter::factory()->create([
        'location_id' => $location->id,
    ]);
    $otherCounterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $otherCounter->id,
    ]);

    $sale = Sale::factory()->create([
        'counter_update_id' => $otherCounterUpdate->id,
        'happened_at' => Carbon::now(),
        'total_amount_paid' => 100,
        'status' => SaleStatus::REGULAR_SALE->value,
    ]);

    SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'total_price_paid' => 100,
    ]);

    $sale1 = Sale::factory()->create([
        'counter_update_id' => $otherCounterUpdate->id,
        'happened_at' => Carbon::now(),
        'total_amount_paid' => 200,
        'status' => SaleStatus::REGULAR_SALE->value,
    ]);

    SaleItem::factory()->create([
        'sale_id' => $sale1->id,
        'total_price_paid' => 200,
    ]);

    $filterId = 0;

    $result = $this->saleQueries->getYearlySalesAndSaleReturnsGroupByMonth(
        $dateRange,
        $companyId,
        [$location->id],
        [],
        null,
        $filterId,
    );

    expect($result)->toHaveCount(1);
    expect($result->first()['net_sales'])->toBe(300.0);
});

it('filters sales and sale returns by promoter', function (): void {
    $dateRange = [Carbon::now()->startOfYear(), Carbon::now()->endOfYear()];

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

    $sale1 = Sale::factory()->create([
        'counter_update_id' => $counterUpdate->id,
        'happened_at' => Carbon::now(),
        'total_amount_paid' => 100,
        'status' => SaleStatus::REGULAR_SALE->value,
    ]);

    $sale2 = Sale::factory()->create([
        'counter_update_id' => $counterUpdate->id,
        'happened_at' => Carbon::now(),
        'total_amount_paid' => 200,
        'status' => SaleStatus::REGULAR_SALE->value,
    ]);

    $promoter = Promoter::factory()->create();

    $saleItem1 = SaleItem::factory()->create([
        'sale_id' => $sale1->id,
        'total_price_paid' => 100,
    ]);

    SaleItem::factory()->create([
        'sale_id' => $sale2->id,
    ]);

    $saleItem1->promoters()->attach($promoter->id);

    $filterId = 0;
    $result = $this->saleQueries->getYearlySalesAndSaleReturnsGroupByMonth(
        $dateRange,
        $companyId,
        null,
        [$promoter->id],
        null,
        $filterId,
    );

    expect($result)->toHaveCount(1);
    expect($result->first()['net_sales'])->toBe(100.0);
});

it('call getInactiveMembers method to get inactive members count', function (): void {
    $counter = Counter::factory()->create([
        'location_id' => $this->location->id,
    ]);

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->id,
    ]);

    Sale::factory()->create([
        'counter_update_id' => $counterUpdate->id,
        'happened_at' => Carbon::now()->subMonths(2),
        'total_amount_paid' => 100,
        'status' => SaleStatus::REGULAR_SALE->value,
    ]);

    $response = $this->saleQueries->getInactiveMembers($this->companyId, 0, 90);

    expect($response)->toBeInt();
});

test('call getMemberAgeGroupCounts method return member age groups.', function (): void {
    $member = Member::factory()->create([
        'company_id' => $this->companyId,
        'first_name' => 'member_one',
        'created_location_id' => $this->location->id,
        'status' => Status::ACTIVE->value,
    ]);

    $counter = Counter::factory()->create([
        'location_id' => $this->location->id,
    ]);

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->id,
    ]);

    $date = Carbon::now();

    Sale::factory()->create([
        'counter_update_id' => $counterUpdate->id,
        'member_id' => $member->id,
        'happened_at' => $date,
        'total_amount_paid' => 100,
        'status' => SaleStatus::REGULAR_SALE->value,
    ]);

    $response = $this->saleQueries->getMemberAgeGroupCounts((int) $date->format('Y'), $this->companyId, 0);

    expect($response->first()->toArray())
        ->toHaveKeys(['age_group', 'count']);
});

test('call getMemberGender method return member gender.', function (): void {
    $member = Member::factory()->create([
        'company_id' => $this->companyId,
        'first_name' => 'member_one',
        'created_location_id' => $this->location->id,
        'status' => Status::ACTIVE->value,
    ]);

    $counter = Counter::factory()->create([
        'location_id' => $this->location->id,
    ]);

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->id,
    ]);

    $date = Carbon::now();

    Sale::factory()->create([
        'counter_update_id' => $counterUpdate->id,
        'member_id' => $member->id,
        'happened_at' => $date,
        'total_amount_paid' => 100,
        'status' => SaleStatus::REGULAR_SALE->value,
    ]);

    $response = $this->saleQueries->getMemberGender((int) $date->format('Y'), $this->companyId, 0);

    expect($response->first()->toArray())
        ->toHaveKeys(['gender', 'count']);
});

test('Call getIdByOfflineSaleId returns sale id and offline sale id', function (): void {
    $counter = Counter::factory()->create([
        'location_id' => $this->location->id,
    ]);

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->id,
    ]);

    $member = Member::factory()->create([
        'company_id' => $this->companyId,
        'first_name' => 'member_one',
        'created_location_id' => $this->location->id,
        'status' => Status::ACTIVE->value,
    ]);

    $sale = Sale::factory()->create([
        'id' => 1,
        'counter_update_id' => $counterUpdate->id,
        'offline_sale_id' => '1',
        'member_id' => $member->id,
        'happened_at' => 1,
        'total_amount_paid' => 100,
        'status' => SaleStatus::REGULAR_SALE->value,
    ]);

    $this->mock(ProductQueries::class, function ($mock): void {
        $mock->shouldReceive('getBasicColumnNames')
            ->once()
            ->andReturn(
                'id,name,upc,verification_qr_code,has_batch,brand_id,color_id,size_id,department_id,article_number,ean,is_non_inventory,type_id,compound_product_name,retail_price,unit_of_measure_id,purchase_cost,online_price,master_product_id,is_warranty,warranty_month'
            );
    });

    $saleQueries = new SaleQueries();
    $saleQueriesResponse = $saleQueries->getIdByOfflineSaleId($sale->offline_sale_id);

    expect($saleQueriesResponse->offline_sale_id)->toBe($sale->offline_sale_id);
});

test('Call getProductIdsBySaleId returns product id array', function (): void {
    $counter = Counter::factory()->create([
        'location_id' => $this->location->id,
    ]);

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->id,
    ]);

    $member = Member::factory()->create([
        'company_id' => $this->companyId,
        'first_name' => 'member_one',
        'created_location_id' => $this->location->id,
        'status' => Status::ACTIVE->value,
    ]);

    $sale = Sale::factory()->create([
        'id' => 1,
        'counter_update_id' => $counterUpdate->id,
        'offline_sale_id' => '1',
        'member_id' => $member->id,
        'happened_at' => 1,
        'total_amount_paid' => 100,
        'status' => SaleStatus::REGULAR_SALE->value,
    ]);

    $product = Product::factory()->create();
    SaleItem::factory()->create([
        'id' => 1,
        'sale_id' => $sale->id,
        'product_id' => $product->id,
        'total_price_paid' => 100,
    ]);

    $saleQueries = new SaleQueries();
    $saleQueriesResponse = $saleQueries->getProductIdsBySaleId((string) $sale->id);

    expect($saleQueriesResponse)->toBeArray();
});

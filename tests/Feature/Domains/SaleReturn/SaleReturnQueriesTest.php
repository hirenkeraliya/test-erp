<?php

declare(strict_types=1);

use App\CommonFunctions;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Sale\DataObjects\SaleData;
use App\Domains\Sale\Enums\SaleStatus;
use App\Domains\SaleReturn\SaleReturnQueries;
use App\Models\Brand;
use App\Models\Cashier;
use App\Models\CashierGroup;
use App\Models\Company;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\CreditNote;
use App\Models\Employee;
use App\Models\Location;
use App\Models\MasterProduct;
use App\Models\Member;
use App\Models\PosMismatch;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\SaleReturnReason;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

beforeEach(function (): void {
    $this->companyId = Company::factory()->create([
        'name' => 'Test Company',
        'email' => 'abc@company.test',
        'code' => 'ABC',
    ])->id;

    $this->saleReturnQueries = new SaleReturnQueries();
});

test('Sale return can be searched', function (): void {
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
    ]);

    $product = Product::factory()->create();

    $saleItem = SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'product_id' => $product->id,
        'total_tax_amount' => 10.00,
        'total_price_paid' => 20.00,
        'cart_discount_amount' => 30.00,
    ]);

    $saleReturn = SaleReturn::factory()->create([
        'original_sale_id' => $sale->id,
        'counter_update_id' => $counterUpdate->id,
    ]);

    $saleReturnReason = SaleReturnReason::factory()->create();

    $saleReturnItem = SaleReturnItem::factory()->create([
        'product_id' => $product->id,
        'sale_return_id' => $saleReturn->id,
        'original_sale_item_id' => $saleItem->id,
        'sale_return_reason_id' => $saleReturnReason->id,
    ]);

    $saleReturn->saleReturnItems = collect($saleReturnItem);

    $response = $this->saleReturnQueries->getPaginatedSaleReturnsWithRelations([
        'search_text' => $saleReturn->offline_sale_return_id,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
        'date_range' => null,
        'location_ids' => null,
        'counter_ids' => null,
        'cashier_id' => null,
        'member_id' => null,
        'employee_id' => null,
        'offline_sale_return_id' => null,
        'e_invoice_submitted' => null,
    ], $this->companyId);

    $this->assertEquals(1, $response->total());

    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('id', $saleReturn->id)
        ->toHaveKey('counter_update_id', $saleReturn->counter_update_id)
        ->toHaveKey('total_tax_amount', $saleReturn->total_tax_amount)
        ->toHaveKey('total_discount_amount', $saleReturn->total_discount_amount)
        ->toHaveKey('total_price_paid', $saleReturn->total_price_paid)
        ->toHaveKeys(
            ['counter_update', 'counter_update.counter', 'counter_update.cashier', 'counter_update.counter.location']
        );
});
test('Store manager sale return can be searched', function (): void {
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
    ]);

    $product = Product::factory()->create();

    $saleItem = SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'product_id' => $product->id,
        'total_tax_amount' => 10.00,
        'total_price_paid' => 20.00,
        'cart_discount_amount' => 30.00,
    ]);

    $saleReturn = SaleReturn::factory()->create([
        'original_sale_id' => $sale->id,
        'counter_update_id' => $counterUpdate->id,
    ]);

    $saleReturnReason = SaleReturnReason::factory()->create();

    $saleReturnItem = SaleReturnItem::factory()->create([
        'product_id' => $product->id,
        'sale_return_id' => $saleReturn->id,
        'original_sale_item_id' => $saleItem->id,
        'sale_return_reason_id' => $saleReturnReason->id,
    ]);

    $saleReturn->saleReturnItems = collect($saleReturnItem);

    $response = $this->saleReturnQueries->getPaginatedSaleReturnsWithRelationsForStoreManager([
        'search_text' => $saleReturn->offline_sale_return_id,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
        'date_range' => null,
        'location_ids' => null,
        'counter_ids' => null,
        'cashier_id' => null,
        'member_id' => null,
        'employee_id' => null,
        'offline_sale_return_id' => null,
    ], [$location->id], $this->companyId);

    $this->assertEquals(1, $response->total());

    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('id', $saleReturn->id)
        ->toHaveKey('counter_update_id', $saleReturn->counter_update_id)
        ->toHaveKey('total_tax_amount', $saleReturn->total_tax_amount)
        ->toHaveKey('total_discount_amount', $saleReturn->total_discount_amount)
        ->toHaveKey('total_price_paid', $saleReturn->total_price_paid)
        ->toHaveKeys(
            ['counter_update', 'counter_update.counter', 'counter_update.cashier', 'counter_update.counter.location']
        );
});

test('new sale return can be added', function (): void {
    $saleDetails = [
        'offline_sale_id' => '1',
        'employee_id' => null,
        'return_items' => null,
        'vouchers' => null,
        'cashback_id' => null,
        'cashback_amount' => null,
        'items' => [
            '0' => [
                'id' => 1,
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
        'member_id' => 1,
        'is_layaway' => false,
        'cart_promotion_id' => 1,
        'sale_round_off_amount' => 0.01,
    ];

    $saleData = new SaleData(...$saleDetails);

    $counterUpdate = CounterUpdate::factory()->create();

    $sale = Sale::factory()->create([
        'counter_update_id' => $counterUpdate->id,
    ]);

    $this->saleReturnQueries->addNew($sale->member_id, $counterUpdate->id, $sale->id, $saleData, true, '0111');

    $this->assertDatabaseHas('sale_returns', [
        'offline_sale_return_id' => '1',
        'original_sale_id' => $sale->id,
        'member_id' => $sale->member_id,
        'counter_update_id' => $counterUpdate->id,
        'has_mismatch' => true,
    ]);
});

test('updateTotals method updates the sale return details as expected', function (): void {
    $sale = Sale::factory()->create();

    $saleReturn = SaleReturn::factory()->create([
        'counter_update_id' => $sale->counter_update_id,
        'original_sale_id' => $sale->id,
    ]);

    $saleItem = SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'total_tax_amount' => 10.00,
        'total_price_paid' => 20.00,
        'cart_discount_amount' => 30.00,
    ]);

    $saleReturnItem = SaleReturnItem::factory()->create([
        'sale_return_id' => $saleReturn->id,
        'original_sale_item_id' => $saleItem->id,
        'total_tax_amount' => 10.00,
        'total_price_paid' => 20.00,
        'cart_discount_amount' => 30.00,
    ]);

    $this->saleReturnQueries->updateTotals($saleReturn);

    $this->assertDatabaseHas('sale_returns', [
        'id' => $saleReturn->id,
        'total_tax_amount' => $saleReturnItem->total_tax_amount,
        'total_price_paid' => $saleReturnItem->total_price_paid,
        'cart_discount_amount' => $saleReturnItem->cart_discount_amount,
        'total_amount_before_round_off' => $saleReturnItem->total_price_paid,
        'round_off_amount' => 0.00,
    ]);
});

test(
    'updateTotals method updates the sale return details as expected when sale has round off amount',
    function (): void {
        $sale = Sale::factory()->create();

        $saleReturn = SaleReturn::factory()->create([
            'counter_update_id' => $sale->counter_update_id,
            'original_sale_id' => $sale->id,
        ]);

        $saleItem = SaleItem::factory()->create([
            'sale_id' => $sale->id,
            'total_tax_amount' => 10.00,
            'total_price_paid' => 20.00,
            'cart_discount_amount' => 30.00,
        ]);

        $saleReturnItem = SaleReturnItem::factory()->create([
            'sale_return_id' => $saleReturn->id,
            'original_sale_item_id' => $saleItem->id,
            'total_tax_amount' => 10.00,
            'total_price_paid' => 20.00,
            'cart_discount_amount' => 30.00,
        ]);

        $this->saleReturnQueries->updateTotals($saleReturn, 0.1);

        $this->assertDatabaseHas('sale_returns', [
            'id' => $saleReturn->id,
            'total_tax_amount' => $saleReturnItem->total_tax_amount,
            'total_price_paid' => $saleReturnItem->total_price_paid + 0.1,
            'cart_discount_amount' => $saleReturnItem->cart_discount_amount,
            'total_amount_before_round_off' => $saleReturnItem->total_price_paid,
            'round_off_amount' => 0.10,
        ]);
    }
);

test('the getByCounterUpdateId method returns the sale returns by counter update id', function (): void {
    $counterUpdate = CounterUpdate::factory()->create();

    $saleReturn = SaleReturn::factory()->create([
        'counter_update_id' => $counterUpdate->id,
    ]);

    $response = $this->saleReturnQueries->getByCounterUpdateId($counterUpdate->id);

    expect($response->first()->toArray())
        ->toHaveKey('id', $saleReturn->id)
        ->toHaveKey('total_price_paid', $saleReturn->total_price_paid);
});

test('doesOfflineSaleReturnIdExist method returns as expected', function (): void {
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

    $location = Location::factory()->create([
        'company_id' => $this->companyId,
        'type_id' => LocationTypes::STORE->value,
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

    $sale = Sale::factory()->create();

    SaleReturn::factory()->create([
        'counter_update_id' => $counterUpdate->id,
        'original_sale_id' => $sale->id,
        'offline_sale_return_id' => '1',
    ]);

    $response = $this->saleReturnQueries->doesOfflineSaleReturnIdExist('1', $this->companyId);
    $this->assertTrue($response);

    $response = $this->saleReturnQueries->doesOfflineSaleReturnIdExist('2', $this->companyId);
    $this->assertFalse($response);
});

test('loadRelations method loads the sale return details as expected', function (): void {
    $saleReturn = SaleReturn::factory()->create();

    SaleReturnItem::factory()->create([
        'sale_return_id' => $saleReturn->id,
    ]);

    CreditNote::factory()->create([
        'sale_return_id' => $saleReturn->id,
    ]);

    PosMismatch::factory()->create([
        'module_id' => $saleReturn->id,
        'module_type' => ModelMapping::SALE_RETURN->name,
    ]);

    $response = $this->saleReturnQueries->loadRelations($saleReturn);

    expect($response->toArray())
        ->toHaveKey('sale_return_items')
        ->toHaveKey('credit_note')
        ->toHaveKey('counter_update')
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

test('the getSaleReturnItemsForStoreManagerApi method returns the sale returns', function (): void {
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
    ]);

    $product = Product::factory()->create();

    $saleItem = SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'product_id' => $product->id,
        'total_tax_amount' => 10.00,
        'total_price_paid' => 20.00,
        'cart_discount_amount' => 30.00,
    ]);

    $saleReturn = SaleReturn::factory()->create([
        'original_sale_id' => $sale->id,
        'counter_update_id' => $counterUpdate->id,
    ]);

    $saleReturnReason = SaleReturnReason::factory()->create();

    $saleReturnItem = SaleReturnItem::factory()->create([
        'product_id' => $product->id,
        'sale_return_id' => $saleReturn->id,
        'original_sale_item_id' => $saleItem->id,
        'sale_return_reason_id' => $saleReturnReason->id,
    ]);

    $saleReturn->saleReturnItems = collect($saleReturnItem);

    $response = $this->saleReturnQueries->getSaleReturnItemsForStoreManagerApi(
        $saleReturn->id,
        $location->id,
        $this->companyId
    );

    expect($response->toArray())
        ->toHaveKey('id', $saleReturn->id)
        ->toHaveKey('total_tax_amount', $saleReturn->total_tax_amount)
        ->toHaveKey('total_discount_amount', $saleReturn->total_discount_amount)
        ->toHaveKey('total_price_paid', $saleReturn->total_price_paid)
        ->toHaveKeys(['sale_return_items', 'sale_return_items.0.sale_item', 'sale_return_items.0.product']);
});

test(
    'the getPaginatedSaleReturnsWithAllRelations method returns the sale returns list',
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

        $location = Location::factory()->create([
            'company_id' => $this->companyId,
            'type_id' => LocationTypes::STORE->value,
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

        $saleReturn = SaleReturn::factory()->create([
            'counter_update_id' => $counterUpdate->id,
            'happened_at' => Carbon::now(),
        ]);

        $product = Product::factory()->create([
            'company_id' => $this->companyId,
        ]);

        SaleReturnItem::factory()->create([
            'sale_return_id' => $saleReturn->id,
            'product_id' => $product->id,
        ]);

        $filterData = [
            'per_page' => 1,
            'member_id' => null,
            'employee_id' => null,
            'from_date' => Carbon::now()->format('Y-m-d'),
            'to_date' => null,
            'sort_by' => null,
            'sort_direction' => null,
            'search_text' => null,
            'after_updated_at' => null,
        ];

        $saleReturnQueries = new SaleReturnQueries();
        $response = $saleReturnQueries->getPaginatedSaleReturnsWithAllRelations(
            $filterData,
            $counter->location_id,
            $this->companyId
        );

        $this->assertEquals(1, $response->count());

        expect($response->first()->toArray())
            ->toHaveKey('id', $saleReturn->id)
            ->toHaveKey('offline_sale_return_id', $saleReturn->offline_sale_return_id)
            ->toHaveKeys(
                [
                    'sale_return_items',
                    'sale_return_items.0.product',
                    'sale_return_items.0.sale_return_reason',
                    'counter_update',
                    'counter_update.counter',
                    'counter_update.cashier',
                    'mismatches',
                    'credit_note',
                ]
            );
    }
);

test(
    'getSaleReturnsWithRelationsForExport method returns the sale return data with relations for export',
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
        ]);

        $product = Product::factory()->create();

        $saleItem = SaleItem::factory()->create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'total_tax_amount' => 10.00,
            'total_price_paid' => 20.00,
            'cart_discount_amount' => 30.00,
        ]);

        $saleReturn = SaleReturn::factory()->create([
            'original_sale_id' => $sale->id,
            'counter_update_id' => $counterUpdate->id,
        ]);

        $saleReturnReason = SaleReturnReason::factory()->create();

        $saleReturnItem = SaleReturnItem::factory()->create([
            'product_id' => $product->id,
            'sale_return_id' => $saleReturn->id,
            'original_sale_item_id' => $saleItem->id,
            'sale_return_reason_id' => $saleReturnReason->id,
        ]);

        $saleReturn->saleReturnItems = collect($saleReturnItem);

        $response = $this->saleReturnQueries->getSaleReturnsWithRelationsForExport([
            'search_text' => $saleReturn->offline_sale_return_id,
            'sort_by' => null,
            'sort_direction' => null,
            'date_range' => null,
            'location_ids' => null,
            'counter_ids' => null,
            'cashier_id' => null,
            'member_id' => null,
            'employee_id' => null,
            'offline_sale_return_id' => null,
            'e_invoice_submitted' => null,
        ], $this->companyId);

        expect($response->first()->toArray())
        ->toHaveKey('id', $saleReturn->id)
        ->toHaveKey('counter_update_id', $saleReturn->counter_update_id)
        ->toHaveKey('total_tax_amount', $saleReturn->total_tax_amount)
        ->toHaveKey('total_discount_amount', $saleReturn->total_discount_amount)
        ->toHaveKey('total_price_paid', $saleReturn->total_price_paid)
        ->toHaveKeys(
            [
                'counter_update',
                'counter_update.counter',
                'counter_update.cashier',
                'counter_update.counter.location',
                'sale_return_items',
            ]
        );
    }
);

test(
    'getSaleReturnsWithRelationsForStoreManagerExport method returns the sale return data with relations for export',
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
        ]);

        $product = Product::factory()->create();

        $saleItem = SaleItem::factory()->create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'total_tax_amount' => 10.00,
            'total_price_paid' => 20.00,
            'cart_discount_amount' => 30.00,
        ]);

        $saleReturn = SaleReturn::factory()->create([
            'original_sale_id' => $sale->id,
            'counter_update_id' => $counterUpdate->id,
        ]);

        $saleReturnReason = SaleReturnReason::factory()->create();

        $saleReturnItem = SaleReturnItem::factory()->create([
            'product_id' => $product->id,
            'sale_return_id' => $saleReturn->id,
            'original_sale_item_id' => $saleItem->id,
            'sale_return_reason_id' => $saleReturnReason->id,
        ]);

        $saleReturn->saleReturnItems = collect($saleReturnItem);

        $response = $this->saleReturnQueries->getSaleReturnsWithRelationsForStoreManagerExport([
            'search_text' => $saleReturn->offline_sale_return_id,
            'sort_by' => null,
            'sort_direction' => null,
            'date_range' => null,
            'counter_ids' => null,
            'cashier_id' => null,
            'member_id' => null,
            'employee_id' => null,
            'offline_sale_return_id' => null,
        ], [$location->id], $this->companyId);

        expect($response->first()->toArray())
        ->toHaveKey('id', $saleReturn->id)
        ->toHaveKey('counter_update_id', $saleReturn->counter_update_id)
        ->toHaveKey('total_tax_amount', $saleReturn->total_tax_amount)
        ->toHaveKey('total_discount_amount', $saleReturn->total_discount_amount)
        ->toHaveKey('total_price_paid', $saleReturn->total_price_paid)
        ->toHaveKeys(
            ['counter_update', 'counter_update.counter', 'counter_update.cashier', 'counter_update.counter.location']
        );
    }
);

test('the getByOfflineId method returns the sale returns', function (): void {
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

    $saleReturn = SaleReturn::factory()->create([
        'counter_update_id' => $counterUpdate->id,
    ]);

    $response = $this->saleReturnQueries->getByOfflineId($saleReturn->offline_sale_return_id, $this->companyId);

    expect($response->toArray())
        ->toHaveKey('id', $saleReturn->id);
});

test('the getFilteredTotalsForReport method returns the sale returns', function (): void {
    $location = Location::factory()->create([
        'company_id' => $this->companyId,
    ]);

    $counter = Counter::factory()->create([
        'location_id' => $location->id,
    ]);

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->id,
    ]);

    $saleReturn = SaleReturn::factory()->create([
        'counter_update_id' => $counterUpdate->id,
    ]);

    SaleReturnItem::factory()->create([
        'sale_return_id' => $saleReturn->id,
        'quantity' => 1,
        'total_price_paid' => 20,
    ]);

    $filterData = [
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => null,
        'date_range' => null,
        'location_ids' => [$location->getKey()],
        'counter_ids' => null,
        'cashier_id' => null,
        'member_id' => null,
        'employee_id' => null,
        'offline_sale_return_id' => null,
        'e_invoice_submitted' => null,
    ];

    $response = $this->saleReturnQueries->getFilteredTotalsForReport($filterData, $this->companyId);

    expect($response->toArray())
            ->toHaveKey('total_return_amount', $saleReturn->getTotalPricePaid())
            ->toHaveKey('total_return_sales', 1)
            ->toHaveKey('total_units_returned', 1);
});

test(
    'getDailyStoreWiseData method returns the sale return data with relations',
    function (): void {
        $location = Location::factory()->create([
            'company_id' => $this->companyId,
        ]);

        $counter = Counter::factory()->create([
            'location_id' => $location->id,
        ]);

        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $counter->id,
        ]);

        $sale = Sale::factory()->create([
            'counter_update_id' => $counterUpdate->id,
        ]);

        $product = Product::factory()->create();

        $saleItem = SaleItem::factory()->create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'total_tax_amount' => 10.00,
            'total_price_paid' => 20.00,
            'cart_discount_amount' => 30.00,
        ]);

        $saleReturn = SaleReturn::factory()->create([
            'original_sale_id' => $sale->id,
            'counter_update_id' => $counterUpdate->id,
            'happened_at' => now()->format('Y-m-d H:i:s'),
        ]);

        $saleReturnReason = SaleReturnReason::factory()->create();

        $saleReturnItem = SaleReturnItem::factory()->create([
            'product_id' => $product->id,
            'sale_return_id' => $saleReturn->id,
            'original_sale_item_id' => $saleItem->id,
            'sale_return_reason_id' => $saleReturnReason->id,
            'quantity' => 10,
            'total_price_paid' => 100,
        ]);

        $saleReturn->saleReturnItems = collect($saleReturnItem);

        $response = $this->saleReturnQueries->getDailyStoreWiseData(
            now()->startOfDay()->format('Y-m-d H:i:s'),
            now()->endOfDay()->format('Y-m-d H:i:s')
        );

        expect($response->first()->toArray())
            ->toHaveKey('total_sale_return_amount', 100)
            ->toHaveKey('total_units_return', 10)
            ->toHaveKey('location_id', $location->id)
            ->toHaveKey('company_id', $this->companyId);
    }
);

test(
    'getTotalSaleReturnsAmountAndCount method returns the sale return data with relations',
    function (): void {
        $location = Location::factory()->create([
            'company_id' => $this->companyId,
        ]);

        $counter = Counter::factory()->create([
            'location_id' => $location->id,
        ]);

        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $counter->id,
        ]);

        $sale = Sale::factory()->create([
            'counter_update_id' => $counterUpdate->id,
        ]);

        $product = Product::factory()->create();

        $saleItem = SaleItem::factory()->create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'total_tax_amount' => 10.00,
            'total_price_paid' => 20.00,
            'cart_discount_amount' => 30.00,
        ]);

        $date = now()->format('Y-m-d H:i:s');
        $saleReturn = SaleReturn::factory()->create([
            'original_sale_id' => $sale->id,
            'counter_update_id' => $counterUpdate->id,
            'happened_at' => $date,
        ]);

        $saleReturnReason = SaleReturnReason::factory()->create();

        $saleReturnItem = SaleReturnItem::factory()->create([
            'product_id' => $product->id,
            'sale_return_id' => $saleReturn->id,
            'original_sale_item_id' => $saleItem->id,
            'sale_return_reason_id' => $saleReturnReason->id,
            'quantity' => 10,
            'total_price_paid' => 100,
        ]);

        $saleReturn->saleReturnItems = collect($saleReturnItem);

        $response = $this->saleReturnQueries->getTotalSaleReturnsAmountAndCount(
            'sale_return_amount_with_count',
            now()->startOfDay()->format('Y-m-d H:i:s'),
            $date,
            (int) $this->companyId,
            null,
        );

        expect($response->toArray())
            ->toHaveKey('total_sale_return_amount', 100)
            ->toHaveKey('total_units_return', 10);
    }
);
test('the getSaleReturnItemsBy method returns the sale returns when product variant is false', function (): void {
    Config::set('app.product_variant', false);

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
    ]);

    $product = Product::factory()->create();

    $saleItem = SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'product_id' => $product->id,
        'total_tax_amount' => 10.00,
        'total_price_paid' => 20.00,
        'cart_discount_amount' => 30.00,
    ]);

    $saleReturn = SaleReturn::factory()->create([
        'original_sale_id' => $sale->id,
        'counter_update_id' => $counterUpdate->id,
    ]);

    $saleReturnReason = SaleReturnReason::factory()->create();

    $saleReturnItem = SaleReturnItem::factory()->create([
        'product_id' => $product->id,
        'sale_return_id' => $saleReturn->id,
        'original_sale_item_id' => $saleItem->id,
        'sale_return_reason_id' => $saleReturnReason->id,
    ]);

    $saleReturn->saleReturnItems = collect($saleReturnItem);

    $response = $this->saleReturnQueries->getSaleReturnItemsBy($saleReturn->id, $this->companyId);

    expect($response->first()->toArray())
        ->toHaveKey('id', $saleReturn->id)
        ->toHaveKey('counter_update_id', $saleReturn->counter_update_id)
        ->toHaveKey('total_tax_amount', $saleReturn->total_tax_amount)
        ->toHaveKey('total_discount_amount', $saleReturn->total_discount_amount)
        ->toHaveKey('total_price_paid', $saleReturn->total_price_paid);
});

test('the getSaleReturnItemsBy method returns the sale returns when product variant is true', function (): void {
    Config::set('app.product_variant', true);

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

    $saleItem = SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'product_id' => $product->id,
        'total_tax_amount' => 10.00,
        'total_price_paid' => 20.00,
        'cart_discount_amount' => 30.00,
    ]);

    $saleReturn = SaleReturn::factory()->create([
        'original_sale_id' => $sale->id,
        'counter_update_id' => $counterUpdate->id,
    ]);

    $saleReturnReason = SaleReturnReason::factory()->create();

    $saleReturnItem = SaleReturnItem::factory()->create([
        'product_id' => $product->id,
        'sale_return_id' => $saleReturn->id,
        'original_sale_item_id' => $saleItem->id,
        'sale_return_reason_id' => $saleReturnReason->id,
    ]);

    $saleReturn->saleReturnItems = collect($saleReturnItem);

    $response = $this->saleReturnQueries->getSaleReturnItemsBy($saleReturn->id, $this->companyId);

    expect($response->first()->toArray())
        ->toHaveKey('id', $saleReturn->id)
        ->toHaveKey('counter_update_id', $saleReturn->counter_update_id)
        ->toHaveKey('total_tax_amount', $saleReturn->total_tax_amount)
        ->toHaveKey('total_discount_amount', $saleReturn->total_discount_amount)
        ->toHaveKey('total_price_paid', $saleReturn->total_price_paid);
});

test(
    'the getSaleReturnItemsForStoreManager method returns the sale returns when product variant',
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

        $sale = Sale::factory()->create([
            'counter_update_id' => $counterUpdate->id,
        ]);

        $product = Product::factory()->create();

        $saleItem = SaleItem::factory()->create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'total_tax_amount' => 10.00,
            'total_price_paid' => 20.00,
            'cart_discount_amount' => 30.00,
        ]);

        $saleReturn = SaleReturn::factory()->create([
            'original_sale_id' => $sale->id,
            'counter_update_id' => $counterUpdate->id,
        ]);

        $saleReturnReason = SaleReturnReason::factory()->create();

        $saleReturnItem = SaleReturnItem::factory()->create([
            'product_id' => $product->id,
            'sale_return_id' => $saleReturn->id,
            'original_sale_item_id' => $saleItem->id,
            'sale_return_reason_id' => $saleReturnReason->id,
        ]);

        $saleReturn->saleReturnItems = collect($saleReturnItem);

        $response = $this->saleReturnQueries->getSaleReturnItemsForStoreManager(
            $saleReturn->id,
            $location->id,
            $this->companyId
        );

        expect($response->first()->toArray())
            ->toHaveKey('id', $saleReturn->id)
            ->toHaveKey('counter_update_id', $saleReturn->counter_update_id)
            ->toHaveKey('total_tax_amount', $saleReturn->total_tax_amount)
            ->toHaveKey('total_discount_amount', $saleReturn->total_discount_amount)
            ->toHaveKey('total_price_paid', $saleReturn->total_price_paid);
    }
)->with([[true], [false]]);

test('returns daily store-wise data for sale returns', function (): void {
    $location = Location::factory()->create([
        'company_id' => $this->companyId,
    ]);

    $counter = Counter::factory()->create([
        'location_id' => $location->id,
    ]);

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->id,
    ]);

    $saleReturn = SaleReturn::factory()->create([
        'counter_update_id' => $counterUpdate->id,
        'happened_at' => now()->format('Y-m-d'),
    ]);

    SaleReturnItem::factory()->create([
        'sale_return_id' => $saleReturn->id,
        'total_price_paid' => 100,
        'quantity' => 2,
    ]);
    $result = $this->saleReturnQueries->getDailyStoreWiseData(now()->format('Y-m-d'), now()->format('Y-m-d'));

    expect($result->first()->toArray())
        ->toHaveKey('location_id', $location->id)
        ->toHaveKey('company_id', $this->companyId)
        ->toHaveKeys(['total_units_return', 'total_sale_return_amount']);
});

test('can get total sale returns amount and count', function (): void {
    $product = Product::factory()->create();

    $saleReturn = SaleReturn::factory()->create([
        'happened_at' => now()->format('Y-m-d'),
    ]);

    SaleReturnItem::factory()->create([
        'product_id' => $product->id,
        'sale_return_id' => $saleReturn->id,
        'total_price_paid' => 10.0,
        'quantity' => 2,
    ]);

    $saleReturnQueries = resolve(SaleReturnQueries::class);
    $result = $saleReturnQueries->getTotalSaleReturnsAmountAndCount(
        'test',
        '2023-04-20',
        '2023-04-25',
        $this->companyId,
        null
    );

    expect($result->toArray())
        ->toHaveKeys(['total_sale_return_amount', 'total_units_return']);
});

test(
    'getForSaleReturnReport method returns the sale data with relations for export',
    function (): void {
        $location = Location::factory()->create([
            'company_id' => $this->companyId,
        ]);

        $counter = Counter::factory()->create([
            'location_id' => $location->getKey(),
        ]);

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
        ]);

        $saleReturnItem = SaleReturnItem::factory()->create([
            'sale_return_id' => $saleReturn->id,
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

        $response = $this->saleReturnQueries->getForSaleReturnReport([
            'sort_by' => null,
            'sort_direction' => null,
            'location_ids' => [$location->id],
            'counter_ids' => null,
            'cashier_ids' => null,
            'date_range' => null,
        ]);

        expect($response->first()->toArray())
            ->toHaveKey('offline_sale_return_id', $saleReturn->offline_sale_return_id)
            ->toHaveKeys(
                [
                    'counter_update',
                    'counter_update.counter',
                    'counter_update.counter.location',
                    'sale_return_items',
                    'sale_return_items.0.sale_return_reason',
                    'sale_return_items.0.product',
                    'original_sale',
                    'original_sale.sale_items',
                    'original_sale.sale_items.0.product',
                    'exchange_sale',
                    'exchange_sale.sale_items',
                    'exchange_sale.sale_items.0.product',
                ]
            );
    }
);

test(
    'getForSaleReturnAndSaleExchangeReport method returns the sale data with relations for export',
    function (): void {
        $location = Location::factory()->create([
            'company_id' => $this->companyId,
        ]);

        $counter = Counter::factory()->create([
            'location_id' => $location->getKey(),
        ]);

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
        ]);

        $saleReturnItem = SaleReturnItem::factory()->create([
            'sale_return_id' => $saleReturn->id,
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

        $response = $this->saleReturnQueries->getForSaleReturnAndSaleExchangeReport([
            'sort_by' => null,
            'sort_direction' => null,
            'location_ids' => [$location->id],
            'counter_ids' => null,
            'cashier_ids' => null,
            'date_range' => null,
        ]);

        expect($response->first()->toArray())
            ->toHaveKey('offline_sale_return_id', $saleReturn->offline_sale_return_id)
            ->toHaveKeys(
                [
                    'counter_update',
                    'counter_update.counter',
                    'counter_update.counter.location',
                    'sale_return_items',
                    'sale_return_items.0.sale_return_reason',
                    'sale_return_items.0.product',
                    'original_sale',
                    'original_sale.sale_items',
                    'original_sale.sale_items.0.product',
                    'exchange_sale',
                    'exchange_sale.sale_items',
                    'exchange_sale.sale_items.0.product',
                ]
            );
    }
);

test(
    'getForExchangeReport method returns the sale data with relations for export',
    function (): void {
        $location = Location::factory()->create([
            'company_id' => $this->companyId,
        ]);

        $counter = Counter::factory()->create([
            'location_id' => $location->getKey(),
        ]);

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
        ]);

        $saleReturnItem = SaleReturnItem::factory()->create([
            'sale_return_id' => $saleReturn->id,
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
            'is_exchange' => 1,
        ]);

        $response = $this->saleReturnQueries->getForExchangeReport([
            'sort_by' => null,
            'sort_direction' => null,
            'location_ids' => [$location->id],
            'counter_ids' => null,
            'cashier_ids' => null,
            'date_range' => null,
        ]);

        expect($response->first()->toArray())
            ->toHaveKey('offline_sale_return_id', $saleReturn->offline_sale_return_id)
            ->toHaveKeys(
                [
                    'counter_update',
                    'counter_update.counter',
                    'counter_update.counter.location',
                    'sale_return_items',
                    'sale_return_items.0.sale_return_reason',
                    'sale_return_items.0.product',
                    'original_sale',
                    'original_sale.sale_items',
                    'original_sale.sale_items.0.product',
                    'exchange_sale',
                    'exchange_sale.sale_items',
                    'exchange_sale.sale_items.0.product',
                ]
            );
    }
);

test('can get cached hourly based sales for chart', function (): void {
    $data = now();

    $companyId = $this->companyId;

    $location = Location::factory()->create([
        'company_id' => $companyId,
    ]);

    $counter = Counter::factory()->create([
        'location_id' => $location->getKey(),
    ]);

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->getKey(),
        'opened_by_pos_at' => now()->format('Y-m-d H:i:s'),
    ]);

    SaleReturn::factory()->create([
        'counter_update_id' => $counterUpdate->id,
        'happened_at' => now()->format('Y-m-d H:i:s'),
    ]);

    $brandId = Brand::factory()->create()->id;

    Cache::forget(
        'cache-hourly-sale-returns-' . $companyId . '-' . $location->id . '-' . $brandId . '-' . $data->format(
            'Y-m-d'
        ) . $brandId
    );

    $result = $this->saleReturnQueries->getHourlyBasedData(
        $companyId,
        $location->id,
        $brandId,
        $data->format('Y-m-d'),
        false
    );

    expect($result)->toBeInstanceOf(Collection::class);

    expect(
        Cache::has(
            'cache-hourly-sale-returns-' . $companyId . '-' . $location->id . '-' . $brandId . '-' . $data->format(
                'Y-m-d'
            ) . $brandId
        )
    )->toBeTrue();

    $cachedResult = $this->saleReturnQueries->getHourlyBasedData(
        $companyId,
        $location->id,
        $brandId,
        $data->format('Y-m-d'),
        false
    );

    expect($cachedResult)->toEqual($result);
});

test(
    'getByStoreIdForSalesCollectionExport method returns the sale return data with relations for export',
    function (): void {
        $location = Location::factory()->create([
            'company_id' => $this->companyId,
        ]);

        $counter = Counter::factory()->create([
            'location_id' => $location->id,
        ]);

        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $counter->id,
            'opened_by_pos_at' => Carbon::now()->format('Y-m-d'),
        ]);

        $sale = Sale::factory()->create([
            'counter_update_id' => $counterUpdate->id,
        ]);

        $product = Product::factory()->create();

        $saleItem = SaleItem::factory()->create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'total_tax_amount' => 10.00,
            'total_price_paid' => 20.00,
            'cart_discount_amount' => 30.00,
        ]);

        $saleReturn = SaleReturn::factory()->create([
            'original_sale_id' => $sale->id,
            'counter_update_id' => $counterUpdate->id,
            'happened_at' => now()->format('Y-m-d H:i:s'),
        ]);

        $saleReturnReason = SaleReturnReason::factory()->create();

        $saleReturnItem = SaleReturnItem::factory()->create([
            'product_id' => $product->id,
            'sale_return_id' => $saleReturn->id,
            'original_sale_item_id' => $saleItem->id,
            'sale_return_reason_id' => $saleReturnReason->id,
        ]);

        $saleReturn->saleReturnItems = collect($saleReturnItem);

        $response = $this->saleReturnQueries->getByStoreIdForSalesCollectionExport([
            'date_range' => [now()->format('Y-m-d'), now()->format('Y-m-d')],
            'location_ids' => [$location->id],
            'counter_ids' => null,
            'cashier_ids' => null,
        ]);

        expect($response->first()->toArray())
        ->toHaveKey('id', $saleReturn->id)
        ->toHaveKey('counter_update_id', $saleReturn->counter_update_id)
        ->toHaveKey('total_tax_amount', $saleReturn->total_tax_amount)
        ->toHaveKey('total_discount_amount', $saleReturn->total_discount_amount)
        ->toHaveKey('total_price_paid', $saleReturn->total_price_paid)
        ->toHaveKeys(['counter_update', 'counter_update.counter']);
    }
);

test(
    'the getSaleReturnsByEmployeeWithDateRange method returns the sales by employee with date range',
    function (): void {
        $location = Location::factory()->create([
            'company_id' => $this->companyId,
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

        $date = Carbon::now();
        $previousDate = $date->subDays(1)->format('Y-m-d');
        $currentDate = $date->format('Y-m-d');

        $saleReturn = SaleReturn::factory()->create([
            'member_id' => $member->id,
            'counter_update_id' => $counterUpdate->id,
            'happened_at' => $date,
        ]);

        $response = $this->saleReturnQueries->getSaleReturnsByEmployeeWithDateRange(
            $previousDate,
            $currentDate,
            $employee->id
        );

        expect($response->first()->toArray())
            ->toHaveKey('id', $saleReturn->id)
            ->toHaveKey('total_price_paid', $saleReturn->total_price_paid);
    }
);

test('Sale return with different stores can be searched', function (): void {
    [$counterUpdate, $counterUpdate1] = getCounterUpdateForDifferentStoreReturns($this->companyId);

    $sale = Sale::factory()->create([
        'counter_update_id' => $counterUpdate->id,
    ]);

    $product = Product::factory()->create();

    $saleItem = SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'product_id' => $product->id,
        'total_tax_amount' => 10.00,
        'total_price_paid' => 20.00,
        'cart_discount_amount' => 30.00,
    ]);

    $saleReturn = SaleReturn::factory()->create([
        'original_sale_id' => $sale->id,
        'counter_update_id' => $counterUpdate1->id,
    ]);

    $saleReturnReason = SaleReturnReason::factory()->create();

    $saleReturnItem = SaleReturnItem::factory()->create([
        'product_id' => $product->id,
        'sale_return_id' => $saleReturn->id,
        'original_sale_item_id' => $saleItem->id,
        'sale_return_reason_id' => $saleReturnReason->id,
    ]);

    $saleReturn->originalSale = $sale;

    $saleReturn->saleReturnItems = collect($saleReturnItem);

    $response = $this->saleReturnQueries->getPaginatedDifferentStoreReturnsWithRelation([
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
        'date_range' => null,
        'location_ids' => null,
        'counter_ids' => null,
        'cashier_id' => null,
        'member_id' => null,
        'employee_id' => null,
        'original_sale_location_ids' => [],
        'original_sale_counter_ids' => [],
        'original_sale_cashier_id' => null,
        'e_invoice_submitted' => null,
    ], $this->companyId);

    $this->assertEquals(1, $response->total());

    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('id', $saleReturn->id)
        ->toHaveKey('counter_update_id', $saleReturn->counter_update_id)
        ->toHaveKey('total_tax_amount', $saleReturn->total_tax_amount)
        ->toHaveKey('total_discount_amount', $saleReturn->total_discount_amount)
        ->toHaveKey('total_price_paid', $saleReturn->total_price_paid)
        ->toHaveKeys(
            ['counter_update', 'counter_update.counter', 'counter_update.cashier', 'counter_update.counter.location']
        );
});

test(
    'getDifferentStoreReturnsForExport method returns the sale return data with relations for export',
    function (): void {
        [$counterUpdate, $counterUpdate1] = getCounterUpdateForDifferentStoreReturns($this->companyId);

        $sale = Sale::factory()->create([
            'counter_update_id' => $counterUpdate->id,
        ]);

        $product = Product::factory()->create();

        $saleItem = SaleItem::factory()->create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'total_tax_amount' => 10.00,
            'total_price_paid' => 20.00,
            'cart_discount_amount' => 30.00,
        ]);

        $saleReturn = SaleReturn::factory()->create([
            'original_sale_id' => $sale->id,
            'counter_update_id' => $counterUpdate1->id,
        ]);

        $saleReturn->originalSale = $sale;

        $saleReturnReason = SaleReturnReason::factory()->create();

        $saleReturnItem = SaleReturnItem::factory()->create([
            'product_id' => $product->id,
            'sale_return_id' => $saleReturn->id,
            'original_sale_item_id' => $saleItem->id,
            'sale_return_reason_id' => $saleReturnReason->id,
        ]);

        $saleReturn->saleReturnItems = collect($saleReturnItem);

        $response = $this->saleReturnQueries->getDifferentStoreReturnWithRelationForExport([
            'search_text' => $saleReturn->offline_sale_return_id,
            'sort_by' => null,
            'sort_direction' => null,
            'date_range' => null,
            'location_ids' => null,
            'counter_ids' => null,
            'cashier_id' => null,
            'member_id' => null,
            'employee_id' => null,
            'original_sale_location_ids' => [],
            'original_sale_counter_ids' => [],
            'original_sale_cashier_id' => null,
            'e_invoice_submitted' => null,
        ], $this->companyId);

        expect($response->first()->toArray())
        ->toHaveKey('id', $saleReturn->id)
        ->toHaveKey('counter_update_id', $saleReturn->counter_update_id)
        ->toHaveKey('total_tax_amount', $saleReturn->total_tax_amount)
        ->toHaveKey('total_discount_amount', $saleReturn->total_discount_amount)
        ->toHaveKey('total_price_paid', $saleReturn->total_price_paid)
        ->toHaveKeys(
            ['counter_update', 'counter_update.counter', 'counter_update.cashier', 'counter_update.counter.location']
        );
    }
);

test(
    'getPaginatedDifferentStoreReturnsForStoreManager method returns the sale return data with relations for export',
    function (): void {
        [$counterUpdate, $counterUpdate1, $location, $store1] = getCounterUpdateForDifferentStoreReturns(
            $this->companyId
        );

        $sale = Sale::factory()->create([
            'counter_update_id' => $counterUpdate->id,
        ]);

        $product = Product::factory()->create();

        $saleItem = SaleItem::factory()->create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'total_tax_amount' => 10.00,
            'total_price_paid' => 20.00,
            'cart_discount_amount' => 30.00,
        ]);

        $saleReturn = SaleReturn::factory()->create([
            'original_sale_id' => $sale->id,
            'counter_update_id' => $counterUpdate1->id,
        ]);

        $saleReturn->originalSale = $sale;

        $saleReturnReason = SaleReturnReason::factory()->create();

        $saleReturnItem = SaleReturnItem::factory()->create([
            'product_id' => $product->id,
            'sale_return_id' => $saleReturn->id,
            'original_sale_item_id' => $saleItem->id,
            'sale_return_reason_id' => $saleReturnReason->id,
        ]);

        $saleReturn->saleReturnItems = collect($saleReturnItem);

        $response = $this->saleReturnQueries->getPaginatedDifferentStoresReturnsForStoreManager([
            'search_text' => null,
            'sort_by' => null,
            'per_page' => 10,
            'sort_direction' => null,
            'date_range' => null,
            'counter_ids' => null,
            'cashier_id' => null,
            'member_id' => null,
            'employee_id' => null,
            'original_sale_location_ids' => [],
            'original_sale_counter_ids' => [],
            'original_sale_cashier_id' => null,
        ], [$store1->id], $this->companyId);

        expect($response->first()->toArray())
        ->toHaveKey('id', $saleReturn->id)
        ->toHaveKey('counter_update_id', $saleReturn->counter_update_id)
        ->toHaveKey('total_tax_amount', $saleReturn->total_tax_amount)
        ->toHaveKey('total_discount_amount', $saleReturn->total_discount_amount)
        ->toHaveKey('total_price_paid', $saleReturn->total_price_paid)
        ->toHaveKeys(
            ['counter_update', 'counter_update.counter', 'counter_update.cashier', 'counter_update.counter.location']
        );
    }
);

test('the getFilteredTotalsDifferentStoreForReport method returns the sale returns', function (): void {
    [$counterUpdate, $counterUpdate1, $location] = getCounterUpdateForDifferentStoreReturns($this->companyId);

    $saleReturn = SaleReturn::factory()->create([
        'counter_update_id' => $counterUpdate->id,
    ]);

    SaleReturnItem::factory()->create([
        'sale_return_id' => $saleReturn->id,
        'quantity' => 1,
        'total_price_paid' => 20,
    ]);

    $sale = Sale::factory()->create([
        'counter_update_id' => $counterUpdate1->id,
    ]);

    SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'quantity' => 1,
        'total_price_paid' => 20,
    ]);

    $filterData = [
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => null,
        'date_range' => null,
        'location_ids' => [$location->getKey()],
        'counter_ids' => null,
        'cashier_id' => null,
        'member_id' => null,
        'employee_id' => null,
        'original_sale_location_ids' => null,
        'original_sale_counter_ids' => null,
        'original_sale_cashier_id' => 'null',
        'e_invoice_submitted' => null,
    ];

    $response = $this->saleReturnQueries->getFilteredTotalsDifferentStoreForReport($filterData, $this->companyId);

    expect($response->toArray())
            ->toHaveKeys(['total_return_amount', 'total_return_sales', 'total_units_returned']);
});

test('the getTotalAmountForSaleCompanyTarget method returns the sale returns', function (): void {
    [$counterUpdate, $counterUpdate1, $location] = getCounterUpdateForDifferentStoreReturns($this->companyId);
    $date = now();
    $saleReturn = SaleReturn::factory()->create([
        'counter_update_id' => $counterUpdate->id,
        'total_price_paid' => 100.34,
        'happened_at' => $date->format('Y-m-d H:i:s'),
    ]);

    SaleReturnItem::factory()->create([
        'sale_return_id' => $saleReturn->id,
        'quantity' => 1,
        'total_price_paid' => 20,
    ]);

    $sale = Sale::factory()->create([
        'counter_update_id' => $counterUpdate1->id,
    ]);

    SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'quantity' => 1,
        'total_price_paid' => 20,
    ]);

    $response = $this->saleReturnQueries->getTotalAmountForSaleCompanyTarget(
        $date->format('Y-m-d'),
        $date->format('Y-m-d'),
        $this->companyId
    );

    expect($response->toArray())
        ->toHaveKey('total_return_amount', 100.34);
});

test('the getTotalAmountForSaleStoreTarget method returns the sale returns', function (): void {
    [$counterUpdate, $counterUpdate1, $location] = getCounterUpdateForDifferentStoreReturns($this->companyId);
    $date = now();
    $saleReturn = SaleReturn::factory()->create([
        'counter_update_id' => $counterUpdate->id,
        'total_price_paid' => 100.34,
        'happened_at' => $date->format('Y-m-d H:i:s'),
    ]);

    SaleReturnItem::factory()->create([
        'sale_return_id' => $saleReturn->id,
        'quantity' => 1,
        'total_price_paid' => 20,
    ]);

    $sale = Sale::factory()->create([
        'counter_update_id' => $counterUpdate1->id,
    ]);

    SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'quantity' => 1,
        'total_price_paid' => 20,
    ]);

    $response = $this->saleReturnQueries->getTotalAmountForSaleStoreTarget(
        $date->format('Y-m-d'),
        $date->format('Y-m-d'),
        [$location->id]
    );

    expect($response->first()->toArray())
        ->toHaveKey('total_return_amount', 100.34)
        ->toHaveKey('location_id', $location->id);
});

test(
    'getDailyStoreWiseDataForCounterUpdate method returns the sale return data with relations',
    function (): void {
        $location = Location::factory()->create([
            'company_id' => $this->companyId,
        ]);

        $counter = Counter::factory()->create([
            'location_id' => $location->id,
        ]);

        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $counter->id,
        ]);

        $sale = Sale::factory()->create([
            'counter_update_id' => $counterUpdate->id,
        ]);

        $product = Product::factory()->create();

        $saleItem = SaleItem::factory()->create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'total_tax_amount' => 10.00,
            'total_price_paid' => 20.00,
            'cart_discount_amount' => 30.00,
        ]);

        $saleReturn = SaleReturn::factory()->create([
            'original_sale_id' => $sale->id,
            'counter_update_id' => $counterUpdate->id,
            'happened_at' => now()->format('Y-m-d H:i:s'),
        ]);

        $saleReturnReason = SaleReturnReason::factory()->create();

        $saleReturnItem = SaleReturnItem::factory()->create([
            'product_id' => $product->id,
            'sale_return_id' => $saleReturn->id,
            'original_sale_item_id' => $saleItem->id,
            'sale_return_reason_id' => $saleReturnReason->id,
            'quantity' => 10,
            'total_price_paid' => 100,
        ]);

        $saleReturn->saleReturnItems = collect($saleReturnItem);

        $response = $this->saleReturnQueries->getDailyStoreWiseDataForCounterUpdate($counterUpdate->id);

        expect($response->first()->toArray())
            ->toHaveKey('total_sale_return_amount', 100)
            ->toHaveKey('total_units_return', 10)
            ->toHaveKey('location_id', $location->id)
            ->toHaveKey('company_id', $this->companyId);
    }
);

test('the digitalInvoiceUpdate method returns the sale returns', function (): void {
    [$counterUpdate, $counterUpdate1, $location] = getCounterUpdateForDifferentStoreReturns($this->companyId);
    $date = now();
    $saleReturn = SaleReturn::factory()->create([
        'counter_update_id' => $counterUpdate->id,
        'total_price_paid' => 100.34,
        'happened_at' => $date->format('Y-m-d H:i:s'),
    ]);

    SaleReturnItem::factory()->create([
        'sale_return_id' => $saleReturn->id,
        'quantity' => 1,
        'total_price_paid' => 20,
    ]);

    $sale = Sale::factory()->create([
        'counter_update_id' => $counterUpdate1->id,
    ]);

    SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'quantity' => 1,
        'total_price_paid' => 20,
    ]);

    $this->saleReturnQueries->digitalInvoiceUpdate($saleReturn->id);

    $this->assertDatabaseHas('sale_returns', [
        'id' => $saleReturn->id,
        'digital_invoice_submitted' => true,
    ]);
});

function getCounterUpdateForDifferentStoreReturns(int $companyId): array
{
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

    $location1 = Location::factory()->create([
        'company_id' => $companyId,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $counter1 = Counter::factory()->create([
        'location_id' => $location1->id,
    ]);

    $counterUpdate1 = CounterUpdate::factory()->create([
        'counter_id' => $counter1->id,
    ]);

    return [$counterUpdate, $counterUpdate1, $location, $location1];
}

test('getSeasonalSaleReturnsData method call return proper response', function (): void {
    $dateRange = ['2023-03-23', '2023-04-21'];

    $companyId = $this->companyId;

    $brand = Brand::factory()->create();

    $product = Product::factory()->create([
        'company_id' => $companyId,
        'brand_id' => $brand->id,
    ]);

    $location = Location::factory()->create([
        'company_id' => $companyId,
        'type_id' => LocationTypes::STORE->value,
    ]);
    $counter = Counter::factory()->create([
        'location_id' => $location->getKey(),
    ]);
    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->getKey(),
    ]);

    $date = '2023-03-26';
    $saleReturn = SaleReturn::factory()->create([
        'counter_update_id' => $counterUpdate->id,
        'happened_at' => CommonFunctions::addStartTime($date),
    ]);

    $saleReturnItem = SaleReturnItem::factory()->create([
        'sale_return_id' => $saleReturn->id,
        'product_id' => $product->id,
        'total_price_paid' => 100,
    ]);

    $filterData = [
        'location_ids' => [$location->id],
        'brand_ids' => [$brand->id],
    ];

    $result = $this->saleReturnQueries->getSeasonalSaleReturnsData($filterData, $dateRange, $companyId, 'sale_return');
    expect($result)->toBeInstanceOf(Collection::class);
    expect($result->first())
        ->toHaveKey('id', $saleReturn->id)
        ->toHaveKey('brand_id', $brand->id)
        ->toHaveKey('brand_name', $brand->name)
        ->toHaveKey('location_id', $location->id)
        ->toHaveKey('location_name', $location->name)
        ->toHaveKey('sale_return', $saleReturnItem->total_price_paid);
});

test('getPaginatedMemberSaleReturnDetails method call and return proper response', function (): void {
    $location = Location::factory()->create([
        'company_id' => $this->companyId,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $member = Member::factory()->create([
        'company_id' => $this->companyId,
    ]);

    $counter = Counter::factory()->create([
        'location_id' => $location->id,
    ]);

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->id,
    ]);

    $sale = Sale::factory()->create([
        'counter_update_id' => $counterUpdate->id,
    ]);

    $product = Product::factory()->create();

    $saleItem = SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'product_id' => $product->id,
        'total_tax_amount' => 10.00,
        'total_price_paid' => 20.00,
        'cart_discount_amount' => 30.00,
    ]);

    $saleReturn = SaleReturn::factory()->create([
        'original_sale_id' => $sale->id,
        'counter_update_id' => $counterUpdate->id,
        'member_id' => $member->id,
    ]);

    $saleReturnReason = SaleReturnReason::factory()->create();

    $saleReturnItem = SaleReturnItem::factory()->create([
        'product_id' => $product->id,
        'sale_return_id' => $saleReturn->id,
        'original_sale_item_id' => $saleItem->id,
        'sale_return_reason_id' => $saleReturnReason->id,
    ]);

    $saleReturn->saleReturnItems = collect($saleReturnItem);

    $response = $this->saleReturnQueries->getPaginatedMemberSaleReturnDetails([
        'search_text' => $saleReturn->offline_sale_return_id,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
        'location_id' => null,
        'member_id' => $member->id,
    ], $this->companyId);

    $this->assertEquals(1, $response->total());

    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('id', $saleReturn->id)
        ->toHaveKey('counter_update_id', $saleReturn->counter_update_id)
        ->toHaveKey('total_tax_amount', $saleReturn->total_tax_amount)
        ->toHaveKey('total_discount_amount', $saleReturn->total_discount_amount)
        ->toHaveKey('total_price_paid', $saleReturn->total_price_paid)
        ->toHaveKeys(
            ['counter_update', 'counter_update.counter', 'counter_update.cashier', 'counter_update.counter.location']
        );
});

test('getSaleReturnByStoreIdCounterId method call and return proper response', function (): void {
    $location = Location::factory()->create([
        'company_id' => $this->companyId,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $member = Member::factory()->create([
        'company_id' => $this->companyId,
    ]);

    $counter = Counter::factory()->create([
        'location_id' => $location->id,
    ]);

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->id,
    ]);

    $sale = Sale::factory()->create([
        'counter_update_id' => $counterUpdate->id,
    ]);

    $product = Product::factory()->create();

    $saleItem = SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'product_id' => $product->id,
        'total_tax_amount' => 10.00,
        'total_price_paid' => 20.00,
        'cart_discount_amount' => 30.00,
    ]);

    $saleReturn = SaleReturn::factory()->create([
        'original_sale_id' => $sale->id,
        'counter_update_id' => $counterUpdate->id,
        'member_id' => $member->id,
    ]);

    $saleReturnReason = SaleReturnReason::factory()->create();

    $saleReturnItem = SaleReturnItem::factory()->create([
        'product_id' => $product->id,
        'sale_return_id' => $saleReturn->id,
        'original_sale_item_id' => $saleItem->id,
        'sale_return_reason_id' => $saleReturnReason->id,
    ]);

    $saleReturn->saleReturnItems = collect($saleReturnItem);

    $response = $this->saleReturnQueries->getSaleReturnByStoreIdCounterId(
        $saleReturn->offline_sale_return_id,
        $location->id,
        $counter->id
    );
    expect($response->toArray())
        ->toHaveKey('id', $saleReturn->id)
        ->toHaveKey('digital_invoice_submitted', $saleReturn->digital_invoice_submitted);
});

test(
    'the updateMember method update the sale return queries member id to new member id',
    function (): void {
        $member = Member::factory()->create();

        $saleReturn = SaleReturn::factory()->create();

        $this->assertDatabaseHas(SaleReturn::class, [
            'id' => $saleReturn->getKey(),
            'member_id' => $saleReturn->member_id,
        ]);

        $this->saleReturnQueries->updateMember($saleReturn->member_id, $member->getKey());

        $this->assertDatabaseHas(SaleReturn::class, [
            'id' => $saleReturn->getKey(),
            'member_id' => $member->getKey(),
        ]);
    }
);

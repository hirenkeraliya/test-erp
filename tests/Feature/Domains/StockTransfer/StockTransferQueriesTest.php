<?php

declare(strict_types=1);

use App\Domains\Common\Enums\BarcodePrintModuleTypes;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\StockTransfer\DataObjects\StockTransferShippedData;
use App\Domains\StockTransfer\Enums\ShippedTypes;
use App\Domains\StockTransfer\Enums\StatusTypes;
use App\Domains\StockTransfer\Enums\StockTransferCustomReportDateTypes;
use App\Domains\StockTransfer\Enums\StockTransferTypes;
use App\Domains\StockTransfer\Enums\TransferTypeForReport;
use App\Domains\StockTransfer\StockTransferQueries;
use App\Models\Batch;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Location;
use App\Models\MasterProduct;
use App\Models\Product;
use App\Models\StockTransfer;
use App\Models\StockTransferAverageLeadDays;
use App\Models\StockTransferItem;
use App\Models\StockTransferItemBatch;
use App\Models\StockTransferItemTransaction;
use App\Models\StockTransferItemUnit;
use App\Models\StockTransferTransaction;
use App\Models\StoreManager;
use App\Models\WarehouseManager;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

beforeEach(function (): void {
    $this->companyId = Company::factory()->create()->id;

    $this->store = Location::factory([
        'company_id' => $this->companyId,
        'type_id' => LocationTypes::STORE->value,
    ])->create();

    $this->warehouse = Location::factory([
        'company_id' => $this->companyId,
        'type_id' => LocationTypes::WAREHOUSE->value,
    ])->create();

    $this->stockTransferA = StockTransfer::factory()->create([
        'company_id' => $this->companyId,
        'source_location_id' => $this->store->id,
        'destination_location_id' => $this->warehouse->id,
        'transfer_order_number' => Str::random(5),
        'status' => StatusTypes::getValueByCaseName('DRAFT'),
    ]);

    $this->stockTransferB = StockTransfer::factory()->create([
        'company_id' => $this->companyId,
        'source_location_id' => $this->warehouse->id,
        'destination_location_id' => $this->store->id,
        'status' => StatusTypes::getValueByCaseName('CANCELLED'),
    ]);

    $this->stockTransferQueries = new StockTransferQueries();
});

test('stock transfer can be searched', function (): void {
    $response = $this->stockTransferQueries->listQuery([
        'search_text' => 'draft',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
        'stock_transfer_date' => null,
        'location_id' => null,
        'select_status' => null,
        'transfer_type' => null,
        'stock_transfer_id' => null,
    ], $this->companyId);

    $this->assertEquals(1, $response->total());

    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('source_location_id', $this->stockTransferA->source_location_id)
        ->toHaveKey('destination_location_id', $this->stockTransferA->destination_location_id)
        ->toHaveKeys([
            'id',
            'source_location_id',
            'destination_location_id',
            'created_by_location_id',
            'reference_number',
            'status',
            'opened_at',
            'approved_at',
            'shipped_at',
            'received_at',
            'discrepancy_at',
            'closed_at',
            'cancelled_at',
            'rejected_at',
            'created_at',
        ]);
});

test('stock transfer can be added', function (): void {
    $stockTransferDetails = [
        'company_id' => $this->companyId,
        'source_location_id' => $this->store->id,
        'destination_location_id' => $this->warehouse->id,
        'requested_by_type' => ModelMapping::ADMIN->name,
        'requested_by_id' => 1,
        'reference_number' => 'abcd',
        'status' => StatusTypes::DRAFT->value,
    ];

    $response = $this->stockTransferQueries->addNew($stockTransferDetails);

    expect($response)->toBeInstanceOf(StockTransfer::class);

    $this->assertDatabaseHas('stock_transfers', $stockTransferDetails);
});

test('stock transfer can be fetched when product variant is false', function (): void {
    Config::set('app.product_variant', false);

    $stockTransferItem = StockTransferItem::factory()->create([
        'stock_transfer_id' => $this->stockTransferA->id,
    ]);

    StockTransferItemTransaction::factory()->create([
        'stock_transfer_item_id' => $stockTransferItem->id,
    ]);

    $response = $this->stockTransferQueries->getByIdWithItems($this->stockTransferA->id, $this->companyId);

    expect($response->toArray())
        ->toHaveKey('source_location_id', $this->stockTransferA->source_location_id)
        ->toHaveKey('destination_location_id', $this->stockTransferA->destination_location_id)
        ->toHaveKey('reference_number', $this->stockTransferA->reference_number)
        ->toHaveKey('created_by_location_id')
        ->toHaveKey('transfer_type')
        ->toHaveKeys(['items', 'source_location', 'destination_location']);

    expect($response->toArray()['items'][0])
        ->toHaveKeys(['product', 'product.color.name', 'product.size.name', 'transaction', 'product.unit_of_measure']);
});

test('stock transfer can be fetched when product variant is true', function (): void {
    Config::set('app.product_variant', true);

    $masterProduct = MasterProduct::factory()->create([
        'company_id' => $this->companyId,
        'has_batch' => false,
        'is_non_inventory' => false,
    ]);

    $productId = Product::factory()->create([
        'company_id' => $this->companyId,
        'is_non_selling_item' => false,
        'master_product_id' => $masterProduct->id,
    ])->id;

    $stockTransferItem = StockTransferItem::factory()->create([
        'stock_transfer_id' => $this->stockTransferA->id,
        'product_id' => $productId,
    ]);

    StockTransferItemTransaction::factory()->create([
        'stock_transfer_item_id' => $stockTransferItem->id,
    ]);

    $response = $this->stockTransferQueries->getByIdWithItems($this->stockTransferA->id, $this->companyId);

    expect($response->toArray())
        ->toHaveKey('source_location_id', $this->stockTransferA->source_location_id)
        ->toHaveKey('destination_location_id', $this->stockTransferA->destination_location_id)
        ->toHaveKey('reference_number', $this->stockTransferA->reference_number)
        ->toHaveKey('created_by_location_id')
        ->toHaveKey('transfer_type')
        ->toHaveKeys(['items', 'source_location', 'destination_location']);

    expect($response->toArray()['items'][0])
        ->toHaveKeys(['product', 'transaction', 'product.master_product']);
});

test('getByIdForRequestOrder method calls and return required columns', function (): void {
    $response = $this->stockTransferQueries->getByIdForRequestOrder($this->stockTransferA->id, $this->companyId);

    expect($response->toArray())
        ->toHaveKey('source_location_id', $this->stockTransferA->source_location_id)
        ->toHaveKey('destination_location_id', $this->stockTransferA->destination_location_id)
        ->toHaveKeys(['transfer_type', 'status']);
});

test('stock transfer can be fetched for print when product variant is false', function (): void {
    Config::set('app.product_variant', false);

    $response = $this->stockTransferQueries->getByIdForPrint($this->stockTransferA->id, $this->companyId);

    expect($response)
        ->toHaveKey('source_location_id', $this->stockTransferA->source_location_id)
        ->toHaveKey('destination_location_id', $this->stockTransferA->destination_location_id)
        ->toHaveKey('reference_number', $this->stockTransferA->reference_number)
        ->toHaveKeys(
            [
                'requested_by_type',
                'requested_by_id',
                'transfer_date',
                'stock_transfer_reason_id',
                'attention',
                'remarks',
                'received_date',
                'transit_location_id',
            ]
        )
        ->toHaveKey('items');
});

test('stock transfer can be fetched for print when product variant is true', function (): void {
    Config::set('app.product_variant', true);

    $masterProduct = MasterProduct::factory()->create([
        'company_id' => $this->companyId,
        'has_batch' => false,
        'is_non_inventory' => false,
    ]);

    $productId = Product::factory()->create([
        'company_id' => $this->companyId,
        'is_non_selling_item' => false,
        'master_product_id' => $masterProduct->id,
    ])->id;

    $stockTransferItem = StockTransferItem::factory()->create([
        'stock_transfer_id' => $this->stockTransferA->id,
        'product_id' => $productId,
    ]);

    StockTransferItemTransaction::factory()->create([
        'stock_transfer_item_id' => $stockTransferItem->id,
    ]);

    $response = $this->stockTransferQueries->getByIdForPrint($this->stockTransferA->id, $this->companyId);

    expect($response)
        ->toHaveKey('source_location_id', $this->stockTransferA->source_location_id)
        ->toHaveKey('destination_location_id', $this->stockTransferA->destination_location_id)
        ->toHaveKey('reference_number', $this->stockTransferA->reference_number)
        ->toHaveKeys(
            [
                'requested_by_type',
                'requested_by_id',
                'transfer_date',
                'stock_transfer_reason_id',
                'attention',
                'remarks',
                'received_date',
                'transit_location_id',
            ]
        )
        ->toHaveKey('items')
        ->toHaveKey('items.0.product.master_product');
});

test('stock transfer can be fetched with items & batches', function (): void {
    $stockTransferItem = StockTransferItem::factory()->create([
        'stock_transfer_id' => $this->stockTransferA->id,
    ]);
    StockTransferItemBatch::factory()->create([
        'stock_transfer_item_id' => $stockTransferItem->id,
    ]);

    $response = $this->stockTransferQueries->getByIdWithItemsAndBatches($this->stockTransferA->id, $this->companyId);

    expect($response->toArray())
        ->toHaveKey('source_location_id', $this->stockTransferA->source_location_id)
        ->toHaveKey('destination_location_id', $this->stockTransferA->destination_location_id)
        ->toHaveKeys(['transfer_date', 'transfer_type', 'require_date', 'items', 'items.0.batches',
            'items.0.unit_of_measure_derivative']);
});

test(
    'stock transfer can be fetched with items for edit request order when product variant is false',
    function (): void {
        Config::set('app.product_variant', false);

        $stockTransferItem = StockTransferItem::factory()->create([
            'stock_transfer_id' => $this->stockTransferA->id,
        ]);

        StockTransferItemTransaction::factory()->create([
            'stock_transfer_item_id' => $stockTransferItem->id,
        ]);

        $response = $this->stockTransferQueries->getByIdWithItemsForEditRequestOrder(
            $this->stockTransferA->id,
            $this->companyId
        );

        expect($response)
            ->toHaveKeys([
                'id',
                'transfer_type',
                'source_location_id',
                'destination_location_id',
                'attention',
                'reference_number',
                'remarks',
                'status',
                'items',
            ]);

        expect($response->toArray()['items'][0])
            ->toHaveKeys(['product', 'product.color.name', 'product.size.name', 'transaction']);
    }
);

test('stock transfer can be fetched with items for edit request order when product variant is true', function (): void {
    Config::set('app.product_variant', true);

    $masterProduct = MasterProduct::factory()->create([
        'company_id' => $this->companyId,
        'has_batch' => false,
        'is_non_inventory' => false,
    ]);

    $productId = Product::factory()->create([
        'company_id' => $this->companyId,
        'is_non_selling_item' => false,
        'master_product_id' => $masterProduct->id,
    ])->id;

    $stockTransferItem = StockTransferItem::factory()->create([
        'stock_transfer_id' => $this->stockTransferA->id,
        'product_id' => $productId,
    ]);

    StockTransferItemTransaction::factory()->create([
        'stock_transfer_item_id' => $stockTransferItem->id,
    ]);

    $response = $this->stockTransferQueries->getByIdWithItemsForEditRequestOrder(
        $this->stockTransferA->id,
        $this->companyId
    );

    expect($response)
        ->toHaveKeys([
            'id',
            'transfer_type',
            'source_location_id',
            'destination_location_id',
            'attention',
            'reference_number',
            'remarks',
            'status',
            'items',
        ]);

    expect($response->toArray()['items'][0])
        ->toHaveKeys(['product', 'product.master_product', 'transaction']);
});

test('stock transfer can be updated', function (): void {
    $stockTransferDetails = [
        'company_id' => $this->companyId,
        'source_location_id' => $this->store->id,
        'destination_location_id' => $this->warehouse->id,
        'reference_number' => 'abcded',
        'status' => StatusTypes::DRAFT->value,
    ];

    $response = $this->stockTransferQueries->update($stockTransferDetails, $this->stockTransferA->id, $this->companyId);

    expect($response)->toBeInstanceOf(StockTransfer::class);
    $this->assertDatabaseHas('stock_transfers', $stockTransferDetails);
});

test('stock transfer`s status can be changed', function ($status, $column): void {
    $this->freezeTime();

    $stockTransfer = StockTransfer::factory()->create([
        'company_id' => $this->companyId,
        'source_location_id' => $this->store->id,
        'destination_location_id' => $this->warehouse->id,
        'status' => StatusTypes::DRAFT->value,
    ]);

    $this->stockTransferQueries->updateStatus($stockTransfer, $status);

    $this->assertDatabaseHas('stock_transfers', [
        'id' => $stockTransfer->id,
        'status' => $status,
        $column => now()->format('Y-m-d H:i:s'),
    ]);
})->with([
    [StatusTypes::OPEN->value, 'opened_at'],
    [StatusTypes::SHIPPED->value, 'shipped_at'],
    [StatusTypes::DISCREPANCY->value, 'discrepancy_at'],
    [StatusTypes::CLOSED->value, 'closed_at'],
    [StatusTypes::CANCELLED->value, 'cancelled_at'],
    [StatusTypes::REJECTED->value, 'rejected_at'],
]);

test('get status value by stock transfer and company id', function (): void {
    $response = $this->stockTransferQueries->getLocationAndStatusById($this->stockTransferA->id, $this->companyId);

    expect($response)
        ->toHaveKey('id', $this->stockTransferA->id)
        ->toHaveKey('status', $this->stockTransferA->status);
});

test('get location and status value by stock transfer and company id', function (): void {
    $response = $this->stockTransferQueries->getLocationAndStatusById($this->stockTransferA->id, $this->companyId);

    expect($response)
        ->toHaveKey('id', $this->stockTransferA->id)
        ->toHaveKey('status', $this->stockTransferA->status)
        ->toHaveKeys(['source_location_id', 'destination_location_id', 'transfer_type']);
});

test(
    'updateApproveAndTransferNumber method calls and set approved_at and transfer_in_number value',
    function (): void {
        $this->stockTransferQueries->updateApproveAndTransferNumber(
            $this->stockTransferA->id,
            $this->stockTransferA->company_id,
            'TORO0000001',
            'TORI0000001'
        );

        $this->assertDatabaseHas('stock_transfers', [
            'id' => $this->stockTransferA->id,
            'status' => StatusTypes::APPROVED->value,
            'transfer_in_number' => 'TORO0000001',
            'transfer_out_number' => 'TORI0000001',
        ]);
    }
);

test(
    'updateShippedAndTransferNumber method calls and set status shipped',
    function (): void {
        $stockTransferShippedData = new StockTransferShippedData(
            shipped_type: ShippedTypes::DIRECT->value,
            location_id: null
        );

        $this->stockTransferQueries->updateShippedAndTransferNumber(
            $this->stockTransferA->id,
            $this->stockTransferA->company_id,
            'TIO0000001',
            'TII0000001',
            $stockTransferShippedData
        );

        $this->assertDatabaseHas('stock_transfers', [
            'id' => $this->stockTransferA->id,
            'status' => StatusTypes::SHIPPED->value,
            'transfer_in_number' => 'TIO0000001',
            'transfer_out_number' => 'TII0000001',
            'transit_location_id' => null,
        ]);
    }
);

test(
    'updateShippedAndTransferNumber method calls and set status transit & location',
    function (): void {
        $location = Location::factory([
            'type_id' => LocationTypes::STORE->value,
        ])->create();
        $stockTransferShippedData = new StockTransferShippedData(
            shipped_type: ShippedTypes::TRANSIT->value,
            location_id: (string) $location->id
        );

        $this->stockTransferQueries->updateShippedAndTransferNumber(
            $this->stockTransferA->id,
            $this->stockTransferA->company_id,
            'TIO0000001',
            'TII0000001',
            $stockTransferShippedData
        );

        $this->assertDatabaseHas('stock_transfers', [
            'id' => $this->stockTransferA->id,
            'status' => StatusTypes::TRANSIT->value,
            'transit_location_id' => $location->id,
            'transfer_in_number' => 'TIO0000001',
            'transfer_out_number' => 'TII0000001',
        ]);
    }
);

test('stock transfer can be fetched with items & unit', function (): void {
    $stockTransferItem = StockTransferItem::factory()->create([
        'stock_transfer_id' => $this->stockTransferA->id,
        'received_quantity' => null,
    ]);

    StockTransferItemUnit::factory()->create([
        'stock_transfer_item_id' => $stockTransferItem->id,
    ]);

    $response = $this->stockTransferQueries->getByIdWithItemsAndUnits($this->stockTransferA->id, $this->companyId);

    expect($response->toArray())
        ->toHaveKey('received_date', $this->stockTransferA->received_date)
        ->toHaveKeys(
            [
                'source_location_id',
                'destination_location_id',
                'reference_number',
                'status',
                'request_order_number',
                'transfer_order_number',
                'items',
                'items.0.units',
            ]
        );
});

test('store manager can search the stock transfers', function (): void {
    $response = $this->stockTransferQueries->listQuery([
        'search_text' => 'draft',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
        'stock_transfer_date' => null,
        'location_id' => null,
        'select_status' => null,
        'transfer_type' => null,
        'stock_transfer_id' => null,
    ], $this->companyId, $this->store->id);

    $this->assertEquals(1, $response->total());

    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('source_location_id', $this->stockTransferA->source_location_id)
        ->toHaveKey('destination_location_id', $this->stockTransferA->destination_location_id);
});

test('stock transfer can be fetched with items, batches & units', function (): void {
    $batch = Batch::factory()->create([
        'company_id' => $this->companyId,
    ]);

    $stockTransferItem = StockTransferItem::factory()->create([
        'stock_transfer_id' => $this->stockTransferA->id,
        'product_id' => $batch->product_id,
        'received_quantity' => null,
    ]);

    StockTransferItemBatch::factory()->create([
        'stock_transfer_item_id' => $stockTransferItem->id,
        'batch_id' => $batch->id,
    ]);

    StockTransferItemUnit::factory()->create([
        'stock_transfer_item_id' => $stockTransferItem->id,
    ]);

    $response = $this->stockTransferQueries->getByIdWithItemsBatchesAndUnits(
        $this->stockTransferA->id,
        $this->companyId
    );

    expect($response->toArray())
        ->toHaveKey('source_location_id', $this->stockTransferA->source_location_id)
        ->toHaveKey('destination_location_id', $this->stockTransferA->destination_location_id)
        ->toHaveKeys(
            [
                'items',
                'items.0.unit_of_measure_derivative',
                'items.0.batches',
                'items.0.units',
                'transfer_type',
                'transfer_order_number',
                'request_order_number',
                'received_date',
            ]
        );
});

test(
    'storeManagerListQuery method calls the stock transfer queries class and return proper response',
    function (): void {
        $batch = Batch::factory()->create([
            'company_id' => $this->companyId,
        ]);

        $employee = Employee::factory()->create([
            'company_id' => $this->companyId,
        ]);
        $storeManager = StoreManager::factory()->create([
            'employee_id' => $employee->id,
        ]);

        $storeManager->locations()->sync($this->store->id);

        $this->stockTransferA->requested_by_type = ModelMapping::STORE_MANAGER->name;
        $this->stockTransferA->requested_by_id = $storeManager->id;
        $this->stockTransferA->save();

        StockTransferItem::factory()->create([
            'stock_transfer_id' => $this->stockTransferA->id,
            'product_id' => $batch->product_id,
            'received_quantity' => null,
        ]);

        $response = $this->stockTransferQueries->storeManagerListQuery(
            [
                'search_text' => null,
                'sort_by' => null,
                'sort_direction' => null,
                'per_page' => 15,
                'transfer_type' => null,
                'stock_transfer_date' => null,
                'location_id' => null,
                'select_status' => null,
                'stock_transfer_id' => null,
                'dashboard_transfer_type' => null,
            ],
            $this->companyId,
            $this->store->id,
            $storeManager
        );

        expect($response->first()->toArray())
            ->toHaveKeys([
                'id',
                'transfer_type',
                'source_location_id',
                'destination_location_id',
                'reference_number',
                'requested_by_type',
                'requested_by_id',
                'created_by_location_id',
                'status',
                'opened_at',
                'approved_at',
                'shipped_at',
                'received_at',
                'discrepancy_at',
                'closed_at',
                'cancelled_at',
                'rejected_at',
                'created_at',
            ]);
    }
);

test(
    'warehouseManagerListQuery method calls the stock transfer queries class and return proper response',
    function (): void {
        $batch = Batch::factory()->create([
            'company_id' => $this->companyId,
        ]);

        $employee = Employee::factory()->create([
            'company_id' => $this->companyId,
        ]);
        $warehouseManager = WarehouseManager::factory()->create([
            'employee_id' => $employee->id,
        ]);

        $warehouseManager->locations()->sync($this->warehouse->id);

        $this->stockTransferA->requested_by_type = ModelMapping::WAREHOUSE_MANAGER->name;
        $this->stockTransferA->requested_by_id = $warehouseManager->id;
        $this->stockTransferA->save();

        StockTransferItem::factory()->create([
            'stock_transfer_id' => $this->stockTransferA->id,
            'product_id' => $batch->product_id,
            'received_quantity' => null,
        ]);

        $response = $this->stockTransferQueries->warehouseManagerListQuery(
            [
                'search_text' => null,
                'sort_by' => null,
                'sort_direction' => null,
                'per_page' => 15,
                'transfer_type' => null,
                'stock_transfer_date' => null,
                'location_id' => null,
                'select_status' => null,
                'stock_transfer_id' => null,
                'dashboard_transfer_type' => null,
            ],
            $this->companyId,
            $this->warehouse->id,
            $warehouseManager
        );

        expect($response->first()->toArray())
            ->toHaveKeys([
                'id',
                'transfer_type',
                'source_location_id',
                'destination_location_id',
                'reference_number',
                'requested_by_type',
                'requested_by_id',
                'created_by_location_id',
                'status',
                'opened_at',
                'approved_at',
                'shipped_at',
                'received_at',
                'discrepancy_at',
                'closed_at',
                'cancelled_at',
                'rejected_at',
                'created_at',
                'updated_at',
            ]);
    }
);

test('fetch stock transfer with stock transfer items by date and location', function (): void {
    $company = Company::factory()->create();
    $productId = Product::factory()->create([
        'company_id' => $company->id,
        'is_non_selling_item' => false,
    ])->id;
    $currentDate = now();
    $stockTransfer = StockTransfer::factory()->create([
        'company_id' => $company->id,
        'created_at' => $currentDate->format('Y-m-d'),
        'status' => StatusTypes::SHIPPED->value,
    ]);

    StockTransferItem::factory()->create([
        'stock_transfer_id' => $stockTransfer->id,
        'product_id' => $productId,
    ]);

    $filterData = [
        'location_ids' => [$stockTransfer->destination_location_id],
        'additional_location_id' => $stockTransfer->source_location_id,
        'transfer_type' => TransferTypeForReport::TRANSFER_IN->value,
        'product_id' => null,
        'status_type' => null,
        'article_number' => null,
        'date_range' => [$currentDate->subDay()->format('Y-m-d'), $currentDate->addDay()->format('Y-m-d')],
        'date_type' => StockTransferCustomReportDateTypes::CREATED_AT->value,
        'display_date_type' => StockTransferCustomReportDateTypes::CREATED_AT->value,
    ];

    $response = $this->stockTransferQueries->getByDateAndLocationWithStockTransfer($filterData, $company->id);

    expect($response->first()->toArray())
        ->toHaveKey('id', $stockTransfer->id)
        ->toHaveKey('reference_number', $stockTransfer->reference_number)
        ->toHaveKeys(['items']);
});

test(
    'fetch stock transfer with stock transfer items and products and package type by date and location',
    function (): void {
        $company = Company::factory()->create();
        $productId = Product::factory()->create([
            'company_id' => $company->id,
            'is_non_selling_item' => false,
        ])->id;
        $currentDate = now();
        $stockTransfer = StockTransfer::factory()->create([
            'company_id' => $company->id,
            'status' => StatusTypes::OPEN->value,
            'transfer_type' => StockTransferTypes::TRANSFER_ORDER->value,
            'created_at' => $currentDate,
        ]);

        StockTransferItem::factory()->create([
            'stock_transfer_id' => $stockTransfer->id,
            'product_id' => $productId,
        ]);

        $filterData = [
            'location_ids' => [$stockTransfer->source_location_id],
            'transfer_type' => TransferTypeForReport::TRANSFER_ORDER->value,
            'status_type' => null,
            'product_id' => null,
            'article_number' => null,
            'date_range' => [$currentDate->subDay()->format('Y-m-d'), $currentDate->addDay()->format('Y-m-d')],
            'date_type' => StockTransferCustomReportDateTypes::CREATED_AT->value,
            'display_date_type' => StockTransferCustomReportDateTypes::CREATED_AT->value,
        ];

        $response = $this->stockTransferQueries->getByDateAndLocationWithStockTransferAndProducts(
            $filterData,
            $company->id
        );

        expect($response->first()->toArray())
            ->toHaveKey('id', $stockTransfer->id)
            ->toHaveKey('reference_number', $stockTransfer->reference_number)
            ->toHaveKeys(['items']);
    }
);

test('getStockTransfersExport method returns stock transfer as expected', function (): void {
    $response = $this->stockTransferQueries->getStockTransfersExport([
        'search_text' => 'draft',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
        'stock_transfer_date' => null,
        'location_id' => null,
        'select_status' => null,
        'transfer_type' => null,
        'stock_transfer_id' => null,
    ], $this->companyId);

    expect($response->first()->toArray())
        ->toHaveKey('source_location_id', $this->stockTransferA->source_location_id)
        ->toHaveKey('destination_location_id', $this->stockTransferA->destination_location_id);
});

test(
    'getStoreManagerStockTransfersExport method calls the stock transfer queries class and return proper response',
    function (): void {
        $batch = Batch::factory()->create([
            'company_id' => $this->companyId,
        ]);

        $employee = Employee::factory()->create([
            'company_id' => $this->companyId,
        ]);
        $storeManager = StoreManager::factory()->create([
            'employee_id' => $employee->id,
        ]);

        $storeManager->locations()->sync($this->store->id);

        $this->stockTransferA->requested_by_type = ModelMapping::STORE_MANAGER->name;
        $this->stockTransferA->requested_by_id = $storeManager->id;
        $this->stockTransferA->save();

        StockTransferItem::factory()->create([
            'stock_transfer_id' => $this->stockTransferA->id,
            'product_id' => $batch->product_id,
            'received_quantity' => null,
        ]);

        $response = $this->stockTransferQueries->getStoreManagerStockTransfersExport(
            [
                'search_text' => null,
                'sort_by' => null,
                'sort_direction' => null,
                'per_page' => 15,
                'transfer_type' => null,
                'stock_transfer_date' => null,
                'location_id' => null,
                'select_status' => null,
                'stock_transfer_id' => null,
                'dashboard_transfer_type' => null,
            ],
            $this->companyId,
            $this->store->id,
            $storeManager
        );

        expect($response->first()->toArray())
            ->toHaveKeys([
                'id',
                'transfer_type',
                'source_location_id',
                'destination_location_id',
                'reference_number',
                'status',
            ]);
    }
);

test(
    'getWarehouseManagerStockTransfersExport method calls the stock transfer queries class and return proper response',
    function (): void {
        $batch = Batch::factory()->create([
            'company_id' => $this->companyId,
        ]);

        $employee = Employee::factory()->create([
            'company_id' => $this->companyId,
        ]);
        $warehouseManager = WarehouseManager::factory()->create([
            'employee_id' => $employee->id,
        ]);

        $warehouseManager->locations()->sync($this->warehouse->id);

        $this->stockTransferA->requested_by_type = ModelMapping::WAREHOUSE_MANAGER->name;
        $this->stockTransferA->requested_by_id = $warehouseManager->id;
        $this->stockTransferA->save();

        StockTransferItem::factory()->create([
            'stock_transfer_id' => $this->stockTransferA->id,
            'product_id' => $batch->product_id,
            'received_quantity' => null,
        ]);

        $response = $this->stockTransferQueries->getWarehouseManagerStockTransfersExport(
            [
                'search_text' => null,
                'sort_by' => null,
                'sort_direction' => null,
                'per_page' => 15,
                'transfer_type' => null,
                'stock_transfer_date' => null,
                'location_id' => null,
                'select_status' => null,
                'stock_transfer_id' => null,
                'dashboard_transfer_type' => null,
            ],
            $this->companyId,
            $this->warehouse->id,
            $warehouseManager
        );

        expect($response->first()->toArray())
            ->toHaveKeys([
                'id',
                'transfer_type',
                'source_location_id',
                'destination_location_id',
                'reference_number',
                'status',
            ]);
    }
);

test('stock transfer can be updateReceivedDateAndStatus', function (): void {
    $receiveData = Carbon::now()->format('Y-m-d');

    $this->stockTransferQueries->updateReceivedDateAndStatus($this->stockTransferA, $receiveData);

    $this->assertDatabaseHas('stock_transfers', [
        'id' => $this->stockTransferA->id,
        'received_date' => $receiveData,
        'status' => StatusTypes::RECEIVED->value,
    ]);
});

test(
    'storeManagerListQueryForApi method calls the stock transfer queries class and return proper response',
    function (): void {
        $productId = Product::factory()->create([
            'company_id' => $this->companyId,
        ])->id;

        $employee = Employee::factory()->create([
            'company_id' => $this->companyId,
        ]);
        $storeManager = StoreManager::factory()->create([
            'employee_id' => $employee->id,
        ]);

        $storeManager->locations()->sync($this->store->id);

        $this->stockTransferA->requested_by_type = ModelMapping::STORE_MANAGER->name;
        $this->stockTransferA->requested_by_id = $storeManager->id;
        $this->stockTransferA->save();

        StockTransferItem::factory()->create([
            'stock_transfer_id' => $this->stockTransferA->id,
            'product_id' => $productId,
            'received_quantity' => null,
        ]);

        $response = $this->stockTransferQueries->storeManagerListQueryForApi(
            [
                'search_text' => null,
                'sort_by' => null,
                'sort_direction' => null,
                'per_page' => 15,
                'transfer_type' => null,
                'stock_transfer_date' => null,
                'location_id' => $this->store->id,
                'select_status' => null,
                'stock_transfer_id' => null,
                'dashboard_transfer_type' => null,
            ],
            $this->companyId
        );

        expect($response->first()->toArray())
            ->toHaveKeys([
                'id',
                'transfer_type',
                'source_location_id',
                'destination_location_id',
                'reference_number',
                'requested_by_type',
                'requested_by_id',
                'created_by_location_id',
                'status',
                'opened_at',
                'approved_at',
                'shipped_at',
                'received_at',
                'discrepancy_at',
                'closed_at',
                'cancelled_at',
                'rejected_at',
                'created_at',
                'updated_at',
            ]);
    }
);

test('getWithItemsAndBatchDetailsById method calls and return relation records.', function (): void {
    $stockTransferItem = StockTransferItem::factory()->create([
        'stock_transfer_id' => $this->stockTransferA->id,
    ]);

    StockTransferItemBatch::factory()->create([
        'stock_transfer_item_id' => $stockTransferItem->id,
    ]);

    $response = $this->stockTransferQueries->getWithItemsAndBatchDetailsById($this->stockTransferA->id);

    expect($response->toArray())
        ->toHaveKeys(
            [
                'id',
                'source_location_id',
                'status',
                'items',
                'items.0.batches',
                'items.0.unit_of_measure_derivative',
            ]
        );
});

test('getLocationById method calls and return source/destination location columns.', function (): void {
    $response = $this->stockTransferQueries->getLocationById(
        $this->stockTransferA->id,
        $this->stockTransferA->company_id
    );

    expect($response->toArray())
        ->toHaveKeys(['id', 'source_location_id', 'destination_location_id']);
});

test('getStatusById method calls and return status columns.', function (): void {
    $response = $this->stockTransferQueries->getStatusById(
        $this->stockTransferA->id,
        $this->stockTransferA->company_id
    );

    expect($response->toArray())
        ->toHaveKeys(['id', 'status']);
});

test(
    'warehouseManagerListQueryForApi method calls the stock transfer queries class and return proper response',
    function (): void {
        $productId = Product::factory()->create([
            'company_id' => $this->companyId,
        ])->id;

        $employee = Employee::factory()->create([
            'company_id' => $this->companyId,
        ]);

        $warehouseManager = WarehouseManager::factory()->create([
            'employee_id' => $employee->id,
        ]);

        $warehouseManager->locations()->sync($this->warehouse->id);

        $this->stockTransferA->requested_by_type = ModelMapping::WAREHOUSE_MANAGER->name;
        $this->stockTransferA->requested_by_id = $warehouseManager->id;
        $this->stockTransferA->save();

        StockTransferItem::factory()->create([
            'stock_transfer_id' => $this->stockTransferA->id,
            'product_id' => $productId,
            'received_quantity' => null,
        ]);

        $response = $this->stockTransferQueries->warehouseManagerListQueryForApi(
            [
                'search_text' => null,
                'sort_by' => null,
                'sort_direction' => null,
                'per_page' => 15,
                'transfer_type' => null,
                'stock_transfer_date' => null,
                'location_id' => $this->warehouse->id,
                'select_status' => null,
                'stock_transfer_id' => null,
                'dashboard_transfer_type' => null,
            ],
            $this->companyId
        );

        expect($response->first()->toArray())
            ->toHaveKeys([
                'id',
                'transfer_type',
                'source_location_id',
                'destination_location_id',
                'reference_number',
                'requested_by_type',
                'requested_by_id',
                'created_by_location_id',
                'status',
                'opened_at',
                'approved_at',
                'shipped_at',
                'received_at',
                'discrepancy_at',
                'closed_at',
                'cancelled_at',
                'rejected_at',
                'created_at',
                'updated_at',
            ]);
    }
);

test(
    'getGroupBySourceLocationIdAndType method calls and return group by source location id and type.',
    function (): void {
        StockTransfer::factory()->create([
            'company_id' => $this->companyId,
            'source_location_id' => $this->store->id,
            'destination_location_id' => $this->warehouse->id,
            'status' => StatusTypes::getValueByCaseName('DRAFT'),
        ]);

        $stockTransferCount = StockTransfer::query()
            ->select('id')
            ->where('source_location_id', $this->store->id)
            ->count();

        $response = $this->stockTransferQueries->getGroupBySourceLocationIdAndType();

        $afterGroupByCount = $response
            ->where('source_location_id', $this->store->id)
            ->count();

        $this->assertNotEquals($stockTransferCount, $afterGroupByCount);
    }
);

test(
    'getStockTransferListWithAverageDayBySourceLocationAndType method calls and return stock transfer list with average day',
    function (): void {
        $this->stockTransferA->received_at = now()->format('Y-m-d h:i:s');
        $this->stockTransferA->shipped_at = now()->subDay()->format('Y-m-d h:i:s');
        $this->stockTransferA->status = StatusTypes::getValueByCaseName('RECEIVED');
        $this->stockTransferA->save();

        StockTransfer::factory()->create([
            'company_id' => $this->companyId,
            'source_location_id' => $this->store->id,
            'destination_location_id' => $this->warehouse->id,
            'status' => StatusTypes::getValueByCaseName('RECEIVED'),
            'received_at' => now()->subDay()->format('Y-m-d h:i:s'),
            'shipped_at' => now()->subDay()->format('Y-m-d h:i:s'),
        ]);

        $response = $this->stockTransferQueries->getStockTransferListWithAverageDayBySourceLocationAndType(
            (int) $this->store->id,
        );

        expect($response->first()->toArray())
            ->toHaveKeys(
                ['stock_transfer_average_lead_day_id', 'source_location_id', 'destination_location_id', 'average']
            );
    }
);

test('getCountByReferenceNumber method returns the count of the stock transfer', function (): void {
    $response = $this->stockTransferQueries->getCountByReferenceNumber(
        $this->stockTransferA->transfer_order_number,
        BarcodePrintModuleTypes::TRANSFER_ORDER->value,
        $this->companyId
    );

    expect($response)->toBe(1);
});

test('updateAverageLeadyDay method returns the count of the stock transfer', function (): void {
    $stockTransferAverageLeadDay = StockTransferAverageLeadDays::factory()->create([
        'from_location_id' => $this->stockTransferA->source_location_id,
        'to_location_id' => $this->stockTransferA->destination_location_id,
    ]);

    $this->stockTransferQueries->updateAverageLeadyDay($stockTransferAverageLeadDay->id, $this->stockTransferA);

    $this->assertDatabaseHas('stock_transfers', [
        'id' => $this->stockTransferA->id,
        'stock_transfer_average_lead_day_id' => $stockTransferAverageLeadDay->id,
    ]);
});

test(
    'getStockTransferByStatusSummary method returns the stock transfer with stock transfer transaction',
    function (): void {
        $filterData = [
            'date_range' => [now()->subDay()->format('Y-m-d H:i:s'), now()->addDay()->format('Y-m-d H:i:s')],
            'location_ids' => null,
        ];
        $stockTransfer = StockTransfer::factory()->create([
            'company_id' => $this->companyId,
            'source_location_id' => $this->store->id,
            'destination_location_id' => $this->warehouse->id,
            'status' => StatusTypes::DRAFT->value,
            'created_at' => now()->subDay()->format('Y-m-d H:i:s'),
        ]);

        StockTransferTransaction::factory()->create([
            'stock_transfer_id' => $stockTransfer->id,
            'old_status' => StatusTypes::DRAFT->value,
            'new_status' => StatusTypes::OPEN->value,
        ]);

        $response = $this->stockTransferQueries->getStockTransferByStatusSummary($filterData, $this->companyId);

        expect($response->first()->toArray())
            ->toHaveKeys(['id', 'company_id', 'created_at', 'transfer_order_number', 'transactions', 'status']);
    }
);

test('it calls getSuccessRatio method and returns the ratio of success', function (): void {
    $stockTransfer = StockTransfer::factory()->create();

    $response = $this->stockTransferQueries->getSuccessRatio($stockTransfer->toArray());
    expect($response)->toBe(0.00);
});

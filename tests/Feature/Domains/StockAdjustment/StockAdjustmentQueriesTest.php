<?php

declare(strict_types=1);

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\StockAdjustment\DataObjects\StockAdjustmentData;
use App\Domains\StockAdjustment\StockAdjustmentQueries;
use App\Models\Admin;
use App\Models\Company;
use App\Models\Employee;
use App\Models\ImportRecord;
use App\Models\Location;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use Illuminate\Http\UploadedFile;

beforeEach(function (): void {
    $this->companyId = Company::factory()->create()->id;

    $this->store = Location::factory()->create([
        'company_id' => $this->companyId,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $this->warehouse = Location::factory()->create([
        'company_id' => $this->companyId,
        'type_id' => LocationTypes::WAREHOUSE->value,
    ]);

    $this->employee = Employee::factory()->create([
        'company_id' => $this->companyId,
    ]);

    $this->admin = Admin::factory()->create([
        'employee_id' => $this->employee->id,
    ]);

    $this->stockAdjustmentA = StockAdjustment::factory()->create([
        'reason' => 'stock adjustment 1',
        'company_id' => $this->companyId,
    ]);

    $importRecord = ImportRecord::factory()->create([
        'company_id' => $this->companyId,
        'created_by_id' => $this->admin->id,
        'created_by_type' => ModelMapping::ADMIN->name,
        'module_id' => $this->stockAdjustmentA->id,
        'module_type' => ModelMapping::STOCK_ADJUSTMENT->name,
    ]);

    $this->stockAdjustmentA->importRecord = $importRecord;

    $this->stockAdjustmentB = StockAdjustment::factory()->create([
        'reason' => 'stock adjustment 2',
        'company_id' => $this->companyId,
    ]);

    $this->stockAdjustmentQueries = new StockAdjustmentQueries();
});

test('stock adjustments can be searched', function (): void {
    $response = $this->stockAdjustmentQueries->listQuery([
        'search_text' => 'stock adjustment 1',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
        'stock_adjustment_id' => null,
    ], $this->companyId);

    $this->assertEquals(1, $response->total());

    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('id', $this->stockAdjustmentA->id)
        ->toHaveKey('reason', $this->stockAdjustmentA->reason)
        ->toHaveKeys(['import_record', 'import_record.media']);
});

test('new stock adjustments can be added', function (): void {
    $admin = Admin::factory()->create();

    $stockAdjustment = StockAdjustment::factory()->make([
        'company_id' => $this->companyId,
        'created_by_admin_id' => $admin->id,
    ])->toArray();

    unset($stockAdjustment['company_id'], $stockAdjustment['created_by_admin_id']);
    $stockAdjustment['uploaded_file'] = new UploadedFile(
        public_path('files/stock-adjustments-sample-file-stock-in.xlsx'),
        'example.xlsx',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        null,
        true
    );

    $this->stockAdjustmentQueries->addNew(new StockAdjustmentData(...$stockAdjustment), $this->companyId, $admin);

    $this->assertDatabaseHas('stock_adjustments', [
        'created_by_admin_id' => $admin->id,
        'approved_by_employee_id' => $stockAdjustment['approved_by_employee_id'],
        'type_id' => $stockAdjustment['type_id'],
        'reason' => $stockAdjustment['reason'],
        'company_id' => $this->companyId,
    ]);
});

test('store manager can search the stock adjustments', function (): void {
    $this->stockAdjustmentItemA = StockAdjustmentItem::factory()->create([
        'stock_adjustment_id' => $this->stockAdjustmentB->id,
        'location_id' => $this->store->id,
    ]);

    $response = $this->stockAdjustmentQueries->storeManagerListQuery([
        'search_text' => 'stock adjustment 2',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
        'stock_adjustment_id' => null,
    ], $this->companyId, $this->store->id);

    $this->assertEquals(1, $response->total());

    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('id', $this->stockAdjustmentB->id)
        ->toHaveKey('reason', $this->stockAdjustmentB->reason);
});

test('getById method returns the stock adjustment data', function (): void {
    $product = Product::factory()->create([
        'company_id' => $this->companyId,
    ]);

    $response = $this->stockAdjustmentQueries->getById($this->stockAdjustmentB->id, $this->companyId);

    expect($response->toArray())
        ->toHaveKey('id', $this->stockAdjustmentB->id)
        ->toHaveKey('reason', $this->stockAdjustmentB->reason)
        ->toHaveKeys([
            'created_at',
            'company_id',
            'created_by_admin_id',
            'approved_by_employee_id',
            'adjustment_date',
            'type_id',
        ]);
});

test('getByIdWithItems method returns the stock adjustment data with items', function (): void {
    $product = Product::factory()->create([
        'company_id' => $this->companyId,
    ]);

    $stockAdjustmentItemA = StockAdjustmentItem::factory()->create([
        'stock_adjustment_id' => $this->stockAdjustmentB->id,
        'location_id' => $this->store->id,
        'product_id' => $product->id,
    ]);

    $response = $this->stockAdjustmentQueries->getByIdWithItems($this->stockAdjustmentB->id, $this->companyId);

    expect($response->toArray())
        ->toHaveKey('id', $this->stockAdjustmentB->id)
        ->toHaveKey('reason', $this->stockAdjustmentB->reason)
        ->toHaveKey('items.0.quantity', $stockAdjustmentItemA->quantity)
        ->toHaveKey('items.0.location.name', $this->store->name)
        ->toHaveKey('items.0.product.name', $product->name);
});

test('warehouse manager can search the stock adjustments', function (): void {
    $this->stockAdjustmentItemA = StockAdjustmentItem::factory()->create([
        'stock_adjustment_id' => $this->stockAdjustmentB->id,
        'location_id' => $this->warehouse->id,
    ]);

    $response = $this->stockAdjustmentQueries->warehouseManagerListQuery([
        'search_text' => 'stock adjustment 2',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
        'stock_adjustment_id' => null,
    ], $this->companyId, $this->warehouse->id);

    $this->assertEquals(1, $response->total());

    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('id', $this->stockAdjustmentB->id)
        ->toHaveKey('reason', $this->stockAdjustmentB->reason);
});

test('getByIdWithItemsForManagerPanel method returns the stock adjustment data with items', function (): void {
    $product = Product::factory()->create([
        'company_id' => $this->companyId,
    ]);

    $stockAdjustmentItemA = StockAdjustmentItem::factory()->create([
        'stock_adjustment_id' => $this->stockAdjustmentB->id,
        'location_id' => $this->store->id,
        'product_id' => $product->id,
    ]);

    $response = $this->stockAdjustmentQueries->getByIdWithItemsForManagerPanel(
        $this->stockAdjustmentB->id,
        $this->companyId,
        $this->store->id,
    );

    expect($response->toArray())
        ->toHaveKey('id', $this->stockAdjustmentB->id)
        ->toHaveKey('reason', $this->stockAdjustmentB->reason)
        ->toHaveKey('items.0.quantity', $stockAdjustmentItemA->quantity)
        ->toHaveKey('items.0.location.name', $this->store->name)
        ->toHaveKey('items.0.product.name', $product->name);
});

test('getStockAdjustmentsExport method returns stock adjustment as expected', function (): void {
    $response = $this->stockAdjustmentQueries->getStockAdjustmentsExport([
        'search_text' => 'stock adjustment 1',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
        'stock_adjustment_id' => null,
    ], $this->companyId);

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->stockAdjustmentA->id)
        ->toHaveKey('reason', $this->stockAdjustmentA->reason);
});

test('getStoreManagerStockAdjustmentsExport method returns stock adjustment as expected', function (): void {
    $this->stockAdjustmentItemA = StockAdjustmentItem::factory()->create([
        'stock_adjustment_id' => $this->stockAdjustmentB->id,
        'location_id' => $this->store->id,
    ]);

    $response = $this->stockAdjustmentQueries->getStoreManagerStockAdjustmentsExport([
        'search_text' => 'stock adjustment 2',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
        'stock_adjustment_id' => null,
    ], $this->companyId, $this->store->id);

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->stockAdjustmentB->id)
        ->toHaveKey('reason', $this->stockAdjustmentB->reason);
});

test('getWarehouseManagerStockAdjustmentsExport method returns stock adjustment as expected', function (): void {
    $this->stockAdjustmentItemA = StockAdjustmentItem::factory()->create([
        'stock_adjustment_id' => $this->stockAdjustmentA->id,
        'location_id' => $this->warehouse->id,
    ]);

    $response = $this->stockAdjustmentQueries->getWarehouseManagerStockAdjustmentsExport([
        'search_text' => 'stock adjustment 1',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
        'stock_adjustment_id' => null,
    ], $this->companyId, $this->warehouse->id);

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->stockAdjustmentA->id)
        ->toHaveKey('reason', $this->stockAdjustmentA->reason);
});

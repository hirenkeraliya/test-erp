<?php

declare(strict_types=1);

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\GoodsReceivedNote\DataObjects\GoodsReceivedNoteData;
use App\Domains\GoodsReceivedNote\DataObjects\GoodsReceivedNoteStoreForStoreManagerAppData;
use App\Domains\GoodsReceivedNote\DataObjects\GoodsReceivedNoteStoreForWarehouseManagerAppData;
use App\Domains\GoodsReceivedNote\DataObjects\GoodsReceivedNoteUpdateStatusData;
use App\Domains\GoodsReceivedNote\GoodsReceivedNoteQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Product\Enums\ProductStatuses;
use App\Models\Admin;
use App\Models\Company;
use App\Models\Employee;
use App\Models\GoodsReceivedNote;
use App\Models\GoodsReceivedNoteProduct;
use App\Models\ImportRecord;
use App\Models\Location;
use App\Models\MasterProduct;
use App\Models\Product;
use App\Models\StoreManager;
use App\Models\Vendor;
use App\Models\WarehouseManager;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;

beforeEach(function (): void {
    $this->companyId = Company::factory()->create()->id;

    $this->employee = Employee::factory()->create([
        'company_id' => $this->companyId,
    ]);

    $this->location = Location::factory()->create([
        'company_id' => $this->companyId,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $this->goodsReceivedNoteA = GoodsReceivedNote::factory()->create([
        'company_id' => $this->companyId,
        'location_id' => $this->location->id,
        'grn_reference' => 'grn_ref_1',
    ]);
    $this->goodsReceivedNoteB = GoodsReceivedNote::factory()->create([
        'company_id' => $this->companyId,
        'location_id' => $this->location->id,
        'grn_reference' => 'grn_ref_2',
    ]);

    $this->goodsReceivedNoteQueries = new GoodsReceivedNoteQueries();

    setCompanyIdInSession($this->companyId);
});

test('Goods Received Notes can be searched', function (): void {
    $admin = Admin::factory()->create([
        'employee_id' => $this->employee->id,
    ]);

    $importRecord = ImportRecord::factory()->create([
        'company_id' => $this->companyId,
        'created_by_id' => $admin->id,
        'created_by_type' => ModelMapping::ADMIN->name,
        'module_id' => $this->goodsReceivedNoteA->id,
        'module_type' => ModelMapping::GOODS_RECEIVED_NOTE->name,
    ]);

    $response = $this->goodsReceivedNoteQueries->listQuery([
        'search_text' => 'grn_ref_1',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
        'grn_number' => null,
    ], $this->companyId);

    $this->assertEquals(1, $response->total());

    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('id', $this->goodsReceivedNoteA->id)
        ->toHaveKey('grn_reference', $this->goodsReceivedNoteA->grn_reference)
        ->toHaveKey('notes', $this->goodsReceivedNoteA->notes)
        ->toHaveKeys(['import_record', 'import_record.media', 'remarks', 'cancelled_at']);
});

test('a new grn can be added', function (): void {
    $location = Location::factory()->create([
        'company_id' => $this->companyId,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $user = Admin::factory()->make([
        'id' => 1,
        'employee_id' => 1,
    ]);

    $vendor = Vendor::factory()->create([
        'company_id' => $this->companyId,
    ]);

    $this->goodsReceivedNoteQueries->addNew(
        new GoodsReceivedNoteData('po', 'do', 'notes', new UploadedFile(public_path(
            'files/goods-received-note-products-sample-file.xlsx'
        ), 'text.xlx'), $vendor->id, $location->id),
        $this->companyId,
        'grn/1',
        $user
    );

    $this->assertDatabaseHas('goods_received_notes', [
        'company_id' => $this->companyId,
        'grn_reference' => 'grn/1',
        'purchase_order_reference' => 'po',
        'delivery_order_reference' => 'do',
        'notes' => 'notes',
        'location_id' => $location->id,
        'created_by_type' => ModelMapping::ADMIN->name,
        'created_by_id' => 1,
    ]);
});

test('generateGrnReference method returns new number as expected', function (): void {
    $company = Company::factory()->create([
        'grn_format' => 'GRN/',
    ]);
    GoodsReceivedNote::factory()->create([
        'company_id' => $company->id,
        'grn_reference' => $company->grn_format . 1,
        'location_id' => $this->location->id,
    ]);

    $response = $this->goodsReceivedNoteQueries->generateGrnReference($company->grn_format, $company->id);

    expect($response)->toBe($company->grn_format . 2);
});

test('grnReferenceExists method returns proper response', function (): void {
    $company = Company::factory()->create([
        'grn_format' => 'GRN/',
    ]);

    $generatedGrnReference = $company->grn_format . 1;

    $response = $this->goodsReceivedNoteQueries->grnReferenceExists($generatedGrnReference, $company->id);

    expect($response)->toBeFalse();
});

test('fetch grn with goods received note products by grn id when product variant is false', function (): void {
    Config::set('app.product_variant', false);

    [$company, $goodReceivedNote, $filterData] = preparedRecordsForQueriesTest();

    $response = $this->goodsReceivedNoteQueries->getByIdWithGoodsReceivedNoteProduct(
        $goodReceivedNote->id,
        $company->id
    );

    expect($response->toArray())
        ->toHaveKey('id', $goodReceivedNote->id)
        ->toHaveKey('company_id', $goodReceivedNote->company_id)
        ->toHaveKey('grn_reference', $goodReceivedNote->grn_reference)
        ->toHaveKey('company.id', $company->id)
        ->toHaveKey('company.name', $company->name)
        ->toHaveKeys(
            [
                'company.media',
                'location',
                'goods_received_note_products.0.product.color',
                'goods_received_note_products.0.product.size',
            ]
        );
});

test('fetch grn with goods received note products by grn id when product variant is true', function (): void {
    Config::set('app.product_variant', true);

    $company = Company::factory()->create();

    $masterProduct = MasterProduct::factory()->create([
        'company_id' => $company->id,
        'has_batch' => false,
        'is_non_inventory' => false,
    ]);

    $product = Product::factory()->create([
        'company_id' => $company->id,
        'compound_product_name' => 'ABCD131333',
        'code' => '131313',
        'upc' => 'wrwrwr',
        'article_number' => '12346644',
        'status' => ProductStatuses::ACTIVE->value,
        'is_non_inventory' => false,
        'is_non_selling_item' => false,
        'is_available_in_pos' => true,
        'is_available_in_ecommerce' => false,
        'master_product_id' => $masterProduct->id,
    ]);

    $location = Location::factory()->create([
        'company_id' => $company->id,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $goodReceivedNote = GoodsReceivedNote::factory()->create([
        'company_id' => $company->id,
        'location_id' => $location->id,
    ]);

    GoodsReceivedNoteProduct::factory()->create([
        'goods_received_note_id' => $goodReceivedNote->id,
        'product_id' => $product->id,
    ]);

    $response = $this->goodsReceivedNoteQueries->getByIdWithGoodsReceivedNoteProduct(
        $goodReceivedNote->id,
        $company->id
    );

    expect($response->toArray())
        ->toHaveKey('id', $goodReceivedNote->id)
        ->toHaveKey('company_id', $goodReceivedNote->company_id)
        ->toHaveKey('grn_reference', $goodReceivedNote->grn_reference)
        ->toHaveKey('company.id', $company->id)
        ->toHaveKey('company.name', $company->name)
        ->toHaveKeys(['company.media', 'location', 'goods_received_note_products.0.product.master_product']);
});

test('fetch grn by grn id', function (): void {
    [$company, $goodReceivedNote, $filterData] = preparedRecordsForQueriesTest();

    $response = $this->goodsReceivedNoteQueries->getById($goodReceivedNote->id, $company->id);

    expect($response->toArray())
        ->toHaveKey('id', $goodReceivedNote->id)
        ->toHaveKey('company_id', $goodReceivedNote->company_id)
        ->toHaveKey('grn_reference', $goodReceivedNote->grn_reference)
        ->toHaveKeys(
            [
                'vendor_id',
                'purchase_order_reference',
                'delivery_order_reference',
                'notes',
                'company_id',
                'location_id',
                'created_by_type',
                'created_by_id',
                'created_at',
            ]
        );
});

test('fetch grn with goods received note products by date and locations', function (): void {
    [$company, $goodReceivedNote, $filterData] = preparedRecordsForQueriesTest();

    $response = $this->goodsReceivedNoteQueries->getByDateAndLocationsWithGoodsReceivedNoteProduct(
        $filterData,
        $company->id
    );

    expect($response->first()->toArray())
        ->toHaveKey('id', $goodReceivedNote->id)
        ->toHaveKey('company_id', $goodReceivedNote->company_id)
        ->toHaveKey('grn_reference', $goodReceivedNote->grn_reference);
});

test('fetch grn with goods received note products, product, color and size by date and locations', function (): void {
    [$company, $goodReceivedNote, $filterData] = preparedRecordsForQueriesTest();

    $response = $this->goodsReceivedNoteQueries->getByDateAndLocationsWithGRNProductAndProduct(
        $filterData,
        $company->id
    );

    expect($response->first()->toArray())
        ->toHaveKey('id', $goodReceivedNote->id)
        ->toHaveKey('company_id', $goodReceivedNote->company_id)
        ->toHaveKey('grn_reference', $goodReceivedNote->grn_reference)
        ->toHaveKeys([
            'goods_received_note_products',
            'location',
            'goods_received_note_products.0.product',
            'goods_received_note_products.0.product.color',
            'goods_received_note_products.0.product.size',
        ]);
});

test('getGoodeReceiveNotesExport method returns good received notes as expected', function (): void {
    $response = $this->goodsReceivedNoteQueries->getGoodeReceiveNotesExport([
        'search_text' => 'grn_ref_1',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
        'grn_number' => null,
    ], $this->companyId);

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->goodsReceivedNoteA->id)
        ->toHaveKey('grn_reference', $this->goodsReceivedNoteA->grn_reference)
        ->toHaveKey('notes', $this->goodsReceivedNoteA->notes);
});

test('fetch goods received note products by date and location', function (): void {
    [$company, $goodReceivedNote, $filterData] = preparedRecordsForQueriesTest();

    $filterData = [
        'location_ids' => [$goodReceivedNote->location_id],
        'date_range' => [now()->subDay()->format('Y-m-d'), now()->addDay()->format('Y-m-d')],
        'product_id' => null,
        'filter_by' => '',
        'article_number' => null,
    ];

    $response = $this->goodsReceivedNoteQueries->getByDateAndLocationWithProduct($filterData, $company->id);

    expect($response->first()->toArray())
        ->toHaveKey('location_id', $goodReceivedNote->location_id)
        ->toHaveKeys(['goods_received_note_products', 'location']);
});

test('listQueryForStoreManagerApi method returns good received notes as expected', function (): void {
    [$company, $goodReceivedNote, $filterData, $location] = preparedRecordsForQueriesTest();

    $filterData = [
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 10,
        'date_range' => [now()->subDay()->format('Y-m-d'), now()->addDay()->format('Y-m-d')],
    ];

    $response = $this->goodsReceivedNoteQueries->listQueryForStoreManagerApi($filterData, $company->id, $location->id);

    expect($response->first()->toArray())
        ->toHaveKeys(
            [
                'grn_reference',
                'purchase_order_reference',
                'delivery_order_reference',
                'notes',
                'created_at',
                'vendor',
                'location',
            ]
        );
});

test('listQueryForWarehouseManagerApi method returns good received notes as expected', function (): void {
    [$company] = preparedRecordsForQueriesTest();

    $productId = Product::factory()->create([
        'company_id' => $company->id,
        'is_non_selling_item' => false,
        'upc' => 'test@leo',
    ])->id;

    $location = Location::factory()->create([
        'company_id' => $company->id,
        'type_id' => LocationTypes::WAREHOUSE->value,
    ]);

    $goodReceivedNote = GoodsReceivedNote::factory()->create([
        'company_id' => $company->id,
        'location_id' => $location->id,
    ]);

    GoodsReceivedNoteProduct::factory()->create([
        'goods_received_note_id' => $goodReceivedNote->id,
        'product_id' => $productId,
    ]);

    $filterData = [
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 10,
        'date_range' => [now()->subDay()->format('Y-m-d'), now()->addDay()->format('Y-m-d')],
    ];

    $response = $this->goodsReceivedNoteQueries->listQueryForWarehouseManagerApi(
        $filterData,
        $company->id,
        $location->id
    );

    expect($response->first()->toArray())
        ->toHaveKeys(
            [
                'grn_reference',
                'purchase_order_reference',
                'delivery_order_reference',
                'notes',
                'created_at',
                'vendor',
                'location',
            ]
        );
});

test('markAsCancel method update cancel data into database', function (): void {
    [$company] = preparedRecordsForQueriesTest();

    $location = Location::factory()->create([
        'company_id' => $company->id,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $goodReceivedNote = GoodsReceivedNote::factory()->create([
        'company_id' => $company->id,
        'location_id' => $location->id,
    ]);

    $user = Admin::factory()->create();

    $goodsReceivedNoteUpdateStatusData = new GoodsReceivedNoteUpdateStatusData(...[
        'remarks' => 'added',
    ]);

    $this->goodsReceivedNoteQueries->markAsCancel(
        $goodReceivedNote,
        $goodsReceivedNoteUpdateStatusData->remarks,
        $user
    );

    $this->assertDatabaseHas('goods_received_notes', [
        'id' => $goodReceivedNote->id,
        'company_id' => $company->id,
        'location_id' => $location->id,
        'remarks' => $goodsReceivedNoteUpdateStatusData->remarks,
        'cancelled_by_id' => $user->id,
        'cancelled_by_type' => ModelMapping::getCaseName($user::class),
    ]);
});

function preparedRecordsForQueriesTest(): array
{
    $company = Company::factory()->create();
    $location = Location::factory()->create([
        'company_id' => $company->id,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $productId = Product::factory()->create([
        'company_id' => $company->id,
        'is_non_selling_item' => false,
        'is_non_inventory' => false,
    ])->id;

    $goodReceivedNote = GoodsReceivedNote::factory()->create([
        'company_id' => $company->id,
        'location_id' => $location->id,
    ]);

    GoodsReceivedNoteProduct::factory()->create([
        'goods_received_note_id' => $goodReceivedNote->id,
        'product_id' => $productId,
    ]);

    $filterData = [
        'location_ids' => [$goodReceivedNote->location_id],
        'filter_by' => null,
        'date_range' => [now()->subDay()->format('Y-m-d'), now()->addDay()->format('Y-m-d')],
    ];

    return [$company, $goodReceivedNote, $filterData, $location];
}

test('a new grn can be added by application', function (): void {
    $location = Location::factory()->create([
        'company_id' => $this->companyId,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $user = StoreManager::factory()->make([
        'id' => 1,
        'employee_id' => 1,
    ]);

    $vendor = Vendor::factory()->create([
        'company_id' => $this->companyId,
    ]);

    $this->goodsReceivedNoteQueries->addNewForInternalApplication(
        new GoodsReceivedNoteStoreForStoreManagerAppData($location->id, 'po', 'do', 'notes', new UploadedFile(
            public_path('files/goods-received-note-products-sample-file.xlsx'),
            'text.xlx'
        ), $vendor->id),
        $this->companyId,
        'grn/1',
        $user,
    );

    $this->assertDatabaseHas('goods_received_notes', [
        'company_id' => $this->companyId,
        'grn_reference' => 'grn/1',
        'purchase_order_reference' => 'po',
        'delivery_order_reference' => 'do',
        'notes' => 'notes',
        'location_id' => $location->id,
        'created_by_type' => ModelMapping::STORE_MANAGER->name,
        'created_by_id' => 1,
    ]);
});

test('a new grn can be added by warehouse manager app', function (): void {
    $location = Location::factory()->create([
        'company_id' => $this->companyId,
        'type_id' => LocationTypes::WAREHOUSE->value,
    ]);

    $user = WarehouseManager::factory()->make([
        'id' => 1,
        'employee_id' => 1,
    ]);

    $vendor = Vendor::factory()->create([
        'company_id' => $this->companyId,
    ]);

    $this->goodsReceivedNoteQueries->addNewForInternalApplication(
        new GoodsReceivedNoteStoreForWarehouseManagerAppData($location->id, 'po', 'do', 'notes', new UploadedFile(
            public_path('files/goods-received-note-products-sample-file.xlsx'),
            'text.xlx'
        ), $vendor->id),
        $this->companyId,
        'grn/1',
        $user
    );

    $this->assertDatabaseHas('goods_received_notes', [
        'company_id' => $this->companyId,
        'grn_reference' => 'grn/1',
        'purchase_order_reference' => 'po',
        'delivery_order_reference' => 'do',
        'notes' => 'notes',
        'location_id' => $location->id,
        'created_by_type' => ModelMapping::WAREHOUSE_MANAGER->name,
        'created_by_id' => 1,
    ]);
});

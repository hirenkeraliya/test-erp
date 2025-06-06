<?php

declare(strict_types=1);

use App\Domains\Brand\BrandQueries;
use App\Domains\Category\CategoryQueries;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\ImportRecord\Enums\ColumnValidationIssueTypes;
use App\Domains\ImportRecord\Enums\ImportTypes;
use App\Domains\Permission\Enums\PermissionList;
use App\Domains\Product\Enums\BulkProductUpdateImportColumns;
use App\Domains\Product\Enums\ProductTypes;
use App\Domains\Product\Imports\ImportProductUpdate;
use App\Domains\Product\ProductQueries;
use App\Domains\Tag\TagQueries;
use App\Domains\UnitOfMeasure\UnitOfMeasureQueries;
use App\Models\ImportRecord;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Config;

test('validate bulk product update import columns with specific permissions', function (): void {
    $requiredHeaderColumns = array_flip(BulkProductUpdateImportColumns::getArrayValues());

    $importProductUpdate = new ImportProductUpdate();
    $response = $importProductUpdate->validateColumns(
        $requiredHeaderColumns,
        ['product_' . PermissionList::PRODUCT_PURCHASE_COST->value],
        1
    );
    $this->assertTrue(ColumnValidationIssueTypes::COLUMN_ISSUE->value === $response['type']);
    $this->assertFalse($response['status']);
});

test('validate bulk product update import columns with all permissions', function (): void {
    $requiredHeaderColumns = array_flip(BulkProductUpdateImportColumns::getArrayValues());

    $importProductUpdate = new ImportProductUpdate();
    $response = $importProductUpdate->validateColumns($requiredHeaderColumns, allProductPermission(), 1);
    $this->assertTrue(ColumnValidationIssueTypes::COLUMN_ISSUE->value === $response['type']);
    $this->assertFalse($response['status']);
});

test('invalidate bulk product update import columns with specific permissions', function (): void {
    $requiredHeaderColumns = array_flip(BulkProductUpdateImportColumns::getArrayValues());
    array_shift($requiredHeaderColumns);
    $importProductUpdate = new ImportProductUpdate();
    $response = $importProductUpdate->validateColumns(
        $requiredHeaderColumns,
        ['product_' . PermissionList::PRODUCT_PURCHASE_COST->value],
        1
    );

    $this->assertTrue(ColumnValidationIssueTypes::COLUMN_ISSUE->value === $response['type'] && $response['status']);
});

test('invalidate bulk product update import columns with all permissions', function (): void {
    $requiredHeaderColumns = array_flip(BulkProductUpdateImportColumns::getArrayValues());
    array_shift($requiredHeaderColumns);

    $importProductUpdate = new ImportProductUpdate();
    $response = $importProductUpdate->validateColumns($requiredHeaderColumns, allProductPermission(), 1);

    $this->assertTrue(ColumnValidationIssueTypes::COLUMN_ISSUE->value === $response['type'] && $response['status']);
});

test('validate bulk product update import columns but missing permissions', function (): void {
    $requiredHeaderColumns = array_flip(BulkProductUpdateImportColumns::getArrayValues());
    $importProductUpdate = new ImportProductUpdate();
    $response = $importProductUpdate->validateColumns($requiredHeaderColumns, [], 1);

    expect($response)->toHaveKey('columns');

    $this->assertTrue(ColumnValidationIssueTypes::PERMISSION_ISSUE->value === $response['type']);
});

test('validate method returns blank array', function (): void {
    $companyId = 1;
    $productData = getProductUpdateData();
    $product = commonGetProductDetails();

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'created_by_id' => 1,
    ]);

    Config::set('app.update_unit_of_measure', true);

    $this->mock(ProductQueries::class, function ($mock) use ($productData, $companyId, $product): void {
        $mock->shouldReceive('existsByCodeUsingUpc')
            ->once()
            ->with($productData['code'], $companyId, $productData['upc'])
            ->andReturn(null);

        $mock->shouldReceive('getByUpcAndCompanyId')
            ->once()
            ->with($productData['upc'], $companyId)
            ->andReturn($product);

        $mock->shouldReceive('existsByQrCode')
            ->once()
            ->with($productData['verification_qr_code'], $companyId, $productData['upc'])
            ->andReturn(false);
    });

    $this->mock(BrandQueries::class, function ($mock) use ($productData): void {
        $mock->shouldReceive('existsByName')
            ->once()
            ->with($productData['brand'], 1)
            ->andReturn(true);
    });

    $this->mock(CategoryQueries::class, function ($mock) use ($productData): void {
        $mock->shouldReceive('existsByName')
            ->once()
            ->with($productData['category_name'], 1)
            ->andReturn(true);

        $mock->shouldReceive('existsByName')
            ->once()
            ->with($productData['subcategory_name'], 1)
            ->andReturn(true);

        $mock->shouldReceive('existsByName')
            ->with($productData['subsubcategory_name'], 1)
            ->andReturn(false);
    });

    $importProductUpdate = new ImportProductUpdate();
    $redirectResponse = $importProductUpdate->validate($productData, $importRecord);
    $this->assertEquals([], $redirectResponse);
    Config::set('app.update_unit_of_measure', false);
});

test('validate method returns issues if upc is already in records', function (): void {
    $companyId = 1;
    $productData = getProductUpdateData();

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'created_by_id' => 1,
    ]);

    $this->mock(ProductQueries::class, function ($mock) use ($productData, $companyId): void {
        $mock->shouldReceive('getByUpcAndCompanyId')
            ->once()
            ->with($productData['upc'], $companyId)
            ->andReturn(null);
    });

    $this->mock(BrandQueries::class, function ($mock) use ($productData): void {
        $mock->shouldReceive('existsByName')
            ->once()
            ->with($productData['brand'], 1)
            ->andReturn(true);
    });

    $importProductUpdate = new ImportProductUpdate();
    $redirectResponse = $importProductUpdate->validate($productData, $importRecord);
    expect($redirectResponse)->toContain('The specified UPC is not available in our records.');
});

test('validate method returns issues if code is taken', function (): void {
    $companyId = 1;
    $productData = getProductUpdateData();
    $product = commonGetProductDetails();

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'created_by_id' => 1,
    ]);

    $this->mock(ProductQueries::class, function ($mock) use ($productData, $companyId, $product): void {
        $mock->shouldReceive('getByUpcAndCompanyId')
            ->once()
            ->with($productData['upc'], $companyId)
            ->andReturn($product);

        $mock->shouldReceive('existsByCodeUsingUpc')
            ->once()
            ->with($productData['code'], $companyId, $productData['upc'])
            ->andReturn($product);

        $mock->shouldReceive('existsByQrCode')
            ->once()
            ->with($productData['verification_qr_code'], $companyId, $productData['upc'])
            ->andReturn(false);
    });

    $this->mock(BrandQueries::class, function ($mock) use ($productData): void {
        $mock->shouldReceive('existsByName')
            ->once()
            ->with($productData['brand'], 1)
            ->andReturn(true);
    });

    $this->mock(CategoryQueries::class, function ($mock) use ($productData): void {
        $mock->shouldReceive('existsByName')
            ->once()
            ->with($productData['category_name'], 1)
            ->andReturn(true);

        $mock->shouldReceive('existsByName')
            ->once()
            ->with($productData['subcategory_name'], 1)
            ->andReturn(true);

        $mock->shouldReceive('existsByName')
            ->with($productData['subsubcategory_name'], 1)
            ->andReturn(false);
    });

    $importProductUpdate = new ImportProductUpdate();
    $redirectResponse = $importProductUpdate->validate($productData, $importRecord);
    expect($redirectResponse)->toContain('Specified code is already taken.');
});

test('name, brand and upc are require while import record', function (): void {
    $companyId = 1;
    $productData = [
        'name' => '',
        'code' => '',
        'unit_of_measure' => '',
        'season' => '',
        'department' => '',
        'color' => '',
        'size' => '',
        'style' => '',
        'upc' => '',
        'brand' => '',
        'category_name' => '',
        'subcategory_name' => '',
        'subsubcategory_name' => '',
        'has_batch' => '',
        'type_id' => '',
        'is_non_inventory' => '',
        'original_created_at' => null,
    ];

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'created_by_id' => 1,
    ]);

    $this->mock(ProductQueries::class, function ($mock) use ($productData, $companyId): void {
        mockExistsByNameMethodForProductUpdate($mock, $productData['name'], $companyId, false, 0);

        $mock->shouldReceive('existsByCodeUsingUpc')
            ->times(0)
            ->with($productData['code'], $companyId)
            ->andReturn(false);

        $mock->shouldReceive('getByUpcAndCompanyId')
            ->times(0)
            ->with($productData['upc'], $companyId)
            ->andReturn(false);
    });

    $this->mock(BrandQueries::class, function ($mock) use ($productData, $companyId): void {
        mockExistsByNameMethodForProductUpdate($mock, $productData['brand'], $companyId, true, 0);
    });

    $importProductUpdate = new ImportProductUpdate();
    $redirectResponse = $importProductUpdate->validate($productData, $importRecord);
    $this->assertEquals(7, count($redirectResponse));
});

test('save method works for the product details update', function (): void {
    $companyId = 1;

    $productData = [
        'name' => 'Product 1',
        'code' => '',
        'unit_of_measure' => '',
        'season' => '',
        'department' => '',
        'color' => '',
        'size' => '',
        'style' => '',
        'upc' => 'Upc 1',
        'brand' => 'Brand 1',
        'category_name' => '',
        'subcategory_name' => '',
        'subsubcategory_name' => '',
        'sub_department' => '',
        'article_number' => '',
        'verification_qr_code' => '132ABCD123',
        'ean' => '',
        'custom_sku' => '',
        'manufacturer_sku' => '',
        'type_id' => ProductTypes::getFormattedCaseName(ProductTypes::REGULAR_PRODUCT->value),
        'retail_price' => '',
        'franchise_price_1' => '',
        'franchise_price_2' => '',
        'franchise_price_3' => '',
        'wholesale_price' => '',
        'company_or_tender_price' => '',
        'branch_price' => '',
        'minimum_price' => '',
        'original_capital_price' => '',
        'capital_price' => '',
        'staff_price' => 0,
        'is_temporarily_unavailable' => 'No',
        'is_non_selling_item' => 'No',
        'has_batch' => 'No',
        'status' => 'Active',
        'is_non_inventory' => 'yes',
        'tags' => 'test,test2',
        'is_available_in_pos' => 'yes',
        'is_available_in_ecommerce' => 'yes',
        'original_created_at' => null,
    ];

    $importRecord = getProductUpdateImportRecords($companyId);

    $this->mock(BrandQueries::class, function ($mock) use ($productData): void {
        $mock->shouldReceive('getIdByName')
            ->times(1)
            ->with($productData['brand'])
            ->andReturn(1);
    });

    $this->mock(UnitOfMeasureQueries::class, function ($mock): void {
        $mock->shouldReceive('getIdByName')
            ->once();
    });

    $this->mock(ProductQueries::class, function ($mock): void {
        $mock->shouldReceive('updateByUpc')
            ->once();
    });

    $this->mock(TagQueries::class, function ($mock): void {
        $mock->shouldReceive('existsByNames')
            ->once()
            ->andReturn(new Collection());
    });

    $importProductUpdate = new ImportProductUpdate();
    $importProductUpdate->save($productData, $importRecord);
});

function mockExistsByNameMethodForProductUpdate(
    $mockClass,
    ?string $name,
    int $companyId,
    bool $returnData,
    int $times = 1
): void {
    $mockClass->shouldReceive('existsByName')
        ->times($times)
        ->with($name, $companyId)
        ->andReturn($returnData);
}

function getProductUpdateData(): array
{
    return [
        'name' => 'Product 2',
        'description' => 'product description',
        'code' => 123,
        'unit_of_measure' => 'Test Unit2',
        'season' => 'season 1',
        'department' => 'department 1',
        'color' => 'color 1',
        'size' => 'size 1',
        'style' => 'style 1',
        'upc' => '4567554634531',
        'verification_qr_code' => 'ABCD1234XYZ',
        'brand' => 'aperiam',
        'category_name' => 'eaque',
        'subcategory_name' => 'veniam',
        'subsubcategory_name' => null,
        'sub_department' => 'GDS',
        'article_number' => 'sdsd266526',
        'ean' => 'ean',
        'custom_sku' => 'custom sku',
        'manufacturer_sku' => 'manufacturer',
        'type_id' => ProductTypes::getFormattedCaseName(ProductTypes::REGULAR_PRODUCT->value),
        'retail_price' => 10.10,
        'franchise_price_1' => 10.10,
        'franchise_price_2' => 10.10,
        'franchise_price_3' => 10.10,
        'wholesale_price' => 10.10,
        'company_or_tender_price' => 10.10,
        'branch_price' => 10.10,
        'minimum_price' => 10.10,
        'original_capital_price' => 10.10,
        'capital_price' => 10.10,
        'staff_price' => 10.30,
        'is_temporarily_unavailable' => 'No',
        'has_batch' => 'No',
        'status' => 'Active',
        'is_non_inventory' => 'yes',
        'is_non_selling_item' => 'no',
        'is_available_in_pos' => 'no',
        'is_available_in_ecommerce' => 'yes',
        'tags' => [],
        'original_created_at' => null,
        'vendor_id' => null,
        'sale_channels' => [],
    ];
}

function getProductUpdateImportRecords(int $companyId): ImportRecord
{
    return ImportRecord::factory()->make([
        'company_id' => $companyId,
        'type_id' => ImportTypes::PRODUCT_BULK_UPDATE->value,
        'created_by_id' => 1,
        'created_by_type' => ModelMapping::ADMIN->name,
    ]);
}

<?php

declare(strict_types=1);

use App\Domains\Brand\BrandQueries;
use App\Domains\Category\CategoryQueries;
use App\Domains\Color\ColorQueries;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Department\DepartmentQueries;
use App\Domains\ImportRecord\Enums\ColumnValidationIssueTypes;
use App\Domains\ImportRecord\Enums\ImportTypes;
use App\Domains\Permission\Enums\PermissionList;
use App\Domains\Product\Enums\ProductImportColumns;
use App\Domains\Product\Enums\ProductTypes;
use App\Domains\Product\Enums\Statuses;
use App\Domains\Product\Imports\ImportProduct;
use App\Domains\Product\ProductQueries;
use App\Domains\Season\SeasonQueries;
use App\Domains\Size\SizeQueries;
use App\Domains\Style\StyleQueries;
use App\Domains\Tag\TagQueries;
use App\Domains\UnitOfMeasure\UnitOfMeasureQueries;
use App\Models\Admin;
use App\Models\ImportRecord;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

test('validate product import columns with specific permissions', function (): void {
    $requiredHeaderColumns = array_flip(ProductImportColumns::getArrayValues());

    $importProduct = new ImportProduct();
    $response = $importProduct->validateColumns(
        $requiredHeaderColumns,
        ['product_' . PermissionList::PRODUCT_PURCHASE_COST->value],
        1
    );
    $this->assertTrue(ColumnValidationIssueTypes::COLUMN_ISSUE->value === $response['type']);
    $this->assertFalse($response['status']);
});

test('validate product import columns with all permissions', function (): void {
    $requiredHeaderColumns = array_flip(ProductImportColumns::getArrayValues());

    $importProduct = new ImportProduct();
    $response = $importProduct->validateColumns($requiredHeaderColumns, allProductPermission(), 1);
    $this->assertTrue(ColumnValidationIssueTypes::COLUMN_ISSUE->value === $response['type']);
    $this->assertFalse($response['status']);
});

test('invalidate product import columns with specific permissions', function (): void {
    $requiredHeaderColumns = array_flip(ProductImportColumns::getArrayValues());
    array_shift($requiredHeaderColumns);
    $importProduct = new ImportProduct();
    $response = $importProduct->validateColumns(
        $requiredHeaderColumns,
        ['product_' . PermissionList::PRODUCT_PURCHASE_COST->value],
        1
    );

    $this->assertTrue(ColumnValidationIssueTypes::COLUMN_ISSUE->value === $response['type'] && $response['status']);
});

test('invalidate product import columns with all permissions', function (): void {
    $requiredHeaderColumns = array_flip(ProductImportColumns::getArrayValues());
    array_shift($requiredHeaderColumns);

    $importProduct = new ImportProduct();
    $response = $importProduct->validateColumns($requiredHeaderColumns, allProductPermission(), 1);

    $this->assertTrue(ColumnValidationIssueTypes::COLUMN_ISSUE->value === $response['type'] && $response['status']);
});

test('validate product import columns but missing permissions', function (): void {
    $requiredHeaderColumns = array_flip(ProductImportColumns::getArrayValues());
    $importProduct = new ImportProduct();
    $response = $importProduct->validateColumns($requiredHeaderColumns, [], 1);

    expect($response)->toHaveKey('columns');

    $this->assertTrue(ColumnValidationIssueTypes::PERMISSION_ISSUE->value === $response['type']);
});

test('validate method returns blank array', function (): void {
    $companyId = 1;
    $productData = getProductData();

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'created_by_id' => 1,
    ]);

    $this->mock(ProductQueries::class, function ($mock) use ($productData, $companyId): void {
        $mock->shouldReceive('getByCodeAndCompanyId')
            ->once()
            ->with($productData['code'], $companyId)
            ->andReturn(null);
        $mock->shouldReceive('getByUpcAndCompanyId')
            ->once()
            ->with($productData['upc'], $companyId)
            ->andReturn(null);
        $mock->shouldReceive('existsByQrCode')
            ->once()
            ->andReturn(false);
    });

    $this->mock(BrandQueries::class, function ($mock) use ($productData): void {
        $mock->shouldReceive('existsByName')
            ->once()
            ->with($productData['brand'], 1)
            ->andReturn(true);
    });

    $this->mock(DepartmentQueries::class, function ($mock) use ($productData): void {
        $mock->shouldReceive('existsByName')
            ->once()
            ->with($productData['department'], 1)
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

    $importProduct = new ImportProduct();
    $redirectResponse = $importProduct->validate($productData, $importRecord);
    $this->assertEquals([], $redirectResponse);
});

test('validate method returns issues if code is already in records', function (): void {
    $companyId = 1;
    $productData = getProductData();
    $product = commonGetProductDetails();

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'created_by_id' => 1,
    ]);

    $this->mock(ProductQueries::class, function ($mock) use ($productData, $companyId, $product): void {
        $mock->shouldReceive('getByCodeAndCompanyId')
            ->once()
            ->with($productData['code'], $companyId)
            ->andReturn($product);
        $mock->shouldReceive('getByUpcAndCompanyId')
            ->once()
            ->with($productData['upc'], $companyId)
            ->andReturn(null);
        $mock->shouldReceive('existsByQrCode')
            ->once()
            ->andReturn(false);
    });

    $this->mock(BrandQueries::class, function ($mock) use ($productData): void {
        $mock->shouldReceive('existsByName')
            ->once()
            ->with($productData['brand'], 1)
            ->andReturn(true);
    });

    $this->mock(DepartmentQueries::class, function ($mock) use ($productData): void {
        $mock->shouldReceive('existsByName')
            ->once()
            ->with($productData['department'], 1)
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

    $importProduct = new ImportProduct();
    $redirectResponse = $importProduct->validate($productData, $importRecord);
    expect($redirectResponse)->toContain('The specified code is already available in our records.');
});

test('validate method returns issues if product is archived by code', function (): void {
    $companyId = 1;
    $productData = getProductData();
    $product = commonGetProductDetails();

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'created_by_id' => 1,
    ]);

    $product->status = Statuses::ARCHIVED->value;

    $this->mock(ProductQueries::class, function ($mock) use ($productData, $companyId, $product): void {
        $mock->shouldReceive('getByCodeAndCompanyId')
            ->once()
            ->with($productData['code'], $companyId)
            ->andReturn($product);
        $mock->shouldReceive('getByUpcAndCompanyId')
            ->once()
            ->with($productData['upc'], $companyId)
            ->andReturn(null);
        $mock->shouldReceive('existsByQrCode')
            ->once()
            ->andReturn(false);
    });

    $this->mock(DepartmentQueries::class, function ($mock) use ($productData): void {
        $mock->shouldReceive('existsByName')
            ->once()
            ->with($productData['department'], 1)
            ->andReturn(true);
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

    $importProduct = new ImportProduct();
    $redirectResponse = $importProduct->validate($productData, $importRecord);
    expect($redirectResponse)->toContain('The specified UPC has already been archived.');
});

test('validate method returns issues if upc is already in records', function (): void {
    $companyId = 1;
    $productData = getProductData();
    $product = commonGetProductDetails();

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'created_by_id' => 1,
    ]);

    $this->mock(ProductQueries::class, function ($mock) use ($productData, $companyId, $product): void {
        $mock->shouldReceive('getByCodeAndCompanyId')
            ->once()
            ->with($productData['code'], $companyId)
            ->andReturn(null);
        $mock->shouldReceive('getByUpcAndCompanyId')
            ->once()
            ->with($productData['upc'], $companyId)
            ->andReturn($product);
        $mock->shouldReceive('existsByQrCode')
            ->once()
            ->andReturn(false);
    });

    $this->mock(BrandQueries::class, function ($mock) use ($productData): void {
        $mock->shouldReceive('existsByName')
            ->once()
            ->with($productData['brand'], 1)
            ->andReturn(true);
    });

    $this->mock(DepartmentQueries::class, function ($mock) use ($productData): void {
        $mock->shouldReceive('existsByName')
            ->once()
            ->with($productData['department'], 1)
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

    $importProduct = new ImportProduct();
    $redirectResponse = $importProduct->validate($productData, $importRecord);
    expect($redirectResponse)->toContain('The specified UPC is already available in our records.');
});

test('validate method returns issues if product is archived by upc', function (): void {
    $companyId = 1;
    $productData = getProductData();
    $product = commonGetProductDetails();

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'created_by_id' => 1,
    ]);

    $product->status = Statuses::ARCHIVED->value;

    $this->mock(ProductQueries::class, function ($mock) use ($productData, $companyId, $product): void {
        $mock->shouldReceive('getByCodeAndCompanyId')
            ->once()
            ->with($productData['code'], $companyId)
            ->andReturn(null);
        $mock->shouldReceive('getByUpcAndCompanyId')
            ->once()
            ->with($productData['upc'], $companyId)
            ->andReturn($product);
        $mock->shouldReceive('existsByQrCode')
            ->once()
            ->andReturn(false);
    });

    $this->mock(BrandQueries::class, function ($mock) use ($productData): void {
        $mock->shouldReceive('existsByName')
            ->once()
            ->with($productData['brand'], 1)
            ->andReturn(true);
    });

    $this->mock(DepartmentQueries::class, function ($mock) use ($productData): void {
        $mock->shouldReceive('existsByName')
            ->once()
            ->with($productData['department'], 1)
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

    $importProduct = new ImportProduct();
    $redirectResponse = $importProduct->validate($productData, $importRecord);
    expect($redirectResponse)->toContain('The specified UPC has already been archived.');
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
        'is_available_in_pos' => 'yes',
        'is_available_in_ecommerce' => 'No',
        'original_created_at' => null,
        'sale_channels' => null,
    ];

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'created_by_id' => 1,
    ]);

    $this->mock(ProductQueries::class, function ($mock) use ($productData, $companyId): void {
        mockExistsByNameMethod($mock, $productData['name'], $companyId, false, 0);
        $mock->shouldReceive('getByCodeAndCompanyId')
            ->times(0)
            ->with($productData['code'], $companyId)
            ->andReturn(null);
        $mock->shouldReceive('getByUpcAndCompanyId')
            ->times(0)
            ->with($productData['upc'], $companyId)
            ->andReturn(null);
    });

    $this->mock(BrandQueries::class, function ($mock) use ($productData, $companyId): void {
        mockExistsByNameMethod($mock, $productData['brand'], $companyId, true, 0);
    });

    $this->mock(UnitOfMeasureQueries::class, function ($mock) use ($productData, $companyId): void {
        mockExistsByNameMethod($mock, $productData['unit_of_measure'], $companyId, true, 0);
    });

    $this->mock(SeasonQueries::class, function ($mock) use ($productData, $companyId): void {
        mockExistsByNameMethod($mock, $productData['season'], $companyId, true, 0);
    });

    $this->mock(DepartmentQueries::class, function ($mock) use ($productData, $companyId): void {
        mockExistsByNameMethod($mock, $productData['department'], $companyId, true, 0);
    });

    $this->mock(ColorQueries::class, function ($mock) use ($productData, $companyId): void {
        mockExistsByNameMethod($mock, $productData['color'], $companyId, true, 0);
    });

    $this->mock(SizeQueries::class, function ($mock) use ($productData, $companyId): void {
        mockExistsByNameMethod($mock, $productData['size'], $companyId, true, 0);
    });

    $this->mock(StyleQueries::class, function ($mock) use ($productData, $companyId): void {
        mockExistsByNameMethod($mock, $productData['style'], $companyId, true, 0);
    });

    $this->mock(CategoryQueries::class, function ($mock) use ($productData, $companyId): void {
        mockExistsByNameMethod($mock, $productData['category_name'], $companyId, true, 0);
        mockExistsByNameMethod($mock, $productData['subcategory_name'], $companyId, true, 0);
        mockExistsByNameMethod($mock, $productData['subsubcategory_name'], $companyId, true, 0);
    });

    $importProduct = new ImportProduct();
    $redirectResponse = $importProduct->validate($productData, $importRecord);
    $this->assertEquals(5, count($redirectResponse));
});

test('save method save only required columns', function (): void {
    $companyId = 1;

    $productData = [
        'name' => 'Product 1',
        'description' => null,
        'code' => null,
        'unit_of_measure' => null,
        'season' => null,
        'department' => 'abcd',
        'color' => null,
        'size' => null,
        'style' => null,
        'upc' => 'Upc 1',
        'brand' => 'Brand 1',
        'category_name' => null,
        'subcategory_name' => null,
        'subsubcategory_name' => null,
        'sub_department' => null,
        'article_number' => null,
        'verification_qr_code' => '123ABCD123',
        'ean' => null,
        'custom_sku' => null,
        'manufacturer_sku' => null,
        'type_id' => ProductTypes::getFormattedCaseName(ProductTypes::REGULAR_PRODUCT->value),
        'retail_price' => null,
        'franchise_price_1' => null,
        'franchise_price_2' => null,
        'franchise_price_3' => null,
        'wholesale_price' => null,
        'company_or_tender_price' => null,
        'branch_price' => null,
        'minimum_price' => null,
        'original_capital_price' => null,
        'capital_price' => null,
        'staff_price' => 0,
        'purchase_cost' => null,
        'is_temporarily_unavailable' => 'No',
        'has_batch' => 'No',
        'status' => 'Active',
        'is_non_inventory' => 'yes',
        'is_non_selling_item' => 'yes',
        'is_available_in_pos' => 'yes',
        'is_available_in_ecommerce' => 'No',
        'tags' => '',
    ];

    $importRecord = getImportRecords($companyId);

    $importRecord->createdBy = Admin::factory()->make([
        'employee_id' => 1,
    ]);

    $this->mock(BrandQueries::class, function ($mock) use ($productData): void {
        $mock->shouldReceive('getIdByName')
            ->times(1)
            ->with($productData['brand'])
            ->andReturn(1);
    });

    $this->mock(UnitOfMeasureQueries::class, function ($mock) use ($productData, $companyId): void {
        mockGetIdByNameMethod($mock, $productData['unit_of_measure'], $companyId, 0);
    });

    $this->mock(SeasonQueries::class, function ($mock) use ($productData, $companyId): void {
        mockGetIdByNameMethod($mock, $productData['season'], $companyId, 0);
    });

    $this->mock(DepartmentQueries::class, function ($mock) use ($productData, $companyId): void {
        mockGetIdByNameMethod($mock, $productData['department'], $companyId, 0);
    });

    $this->mock(ColorQueries::class, function ($mock) use ($productData, $companyId): void {
        mockGetIdByNameMethod($mock, $productData['color'], $companyId, 0);
    });

    $this->mock(SizeQueries::class, function ($mock) use ($productData, $companyId): void {
        mockGetIdByNameMethod($mock, $productData['size'], $companyId, 0);
    });

    $this->mock(StyleQueries::class, function ($mock) use ($productData, $companyId): void {
        mockGetIdByNameMethod($mock, $productData['style'], $companyId, 0);
    });

    $this->mock(CategoryQueries::class, function ($mock) use ($productData, $companyId): void {
        mockGetIdByNameMethod($mock, $productData['category_name'], $companyId, 0);
    });

    $this->mock(CategoryQueries::class, function ($mock) use ($productData, $companyId): void {
        mockGetIdByNameMethod($mock, $productData['category_name'], $companyId, 0);
    });

    $this->mock(ProductQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    $this->mock(TagQueries::class, function ($mock): void {
        $mock->shouldReceive('existsByNames')
            ->once()
            ->andReturn(new EloquentCollection());
    });

    $this->mock(DepartmentQueries::class, function ($mock) use ($productData): void {
        $mock->shouldReceive('getIdByNameForImportRecord')
            ->once()
            ->with($productData['department'])
            ->andReturn(1);
    });

    $importProduct = new ImportProduct();
    $importProduct->save($productData, $importRecord);
});

test('save method store the given data in our records', function (): void {
    $companyId = 1;

    $productData = getProductData();

    $importRecord = getImportRecords($companyId);

    $tag = Tag::factory()->make([
        'id' => 1,
        'company_id' => 1,
    ]);

    $importRecord->createdBy = Admin::factory()->make([
        'employee_id' => 1,
    ]);

    $this->mock(BrandQueries::class, function ($mock) use ($productData): void {
        $mock->shouldReceive('getIdByName')
            ->times(1)
            ->with($productData['brand'])
            ->andReturn(1);
    });

    $this->mock(DepartmentQueries::class, function ($mock) use ($productData): void {
        $mock->shouldReceive('getIdByNameForImportRecord')
            ->once()
            ->with($productData['department'])
            ->andReturn(1);
    });

    $this->mock(TagQueries::class, function ($mock) use ($tag): void {
        $mock->shouldReceive('existsByNames')
            ->once()
            ->andReturn(new EloquentCollection([$tag]));
    });

    $this->mock(UnitOfMeasureQueries::class, function ($mock) use ($productData, $companyId): void {
        mockGetIdByNameMethod($mock, $productData['unit_of_measure'], $companyId);
    });

    $this->mock(SeasonQueries::class, function ($mock) use ($productData, $companyId): void {
        mockGetIdByNameMethod($mock, $productData['season'], $companyId);
    });

    $this->mock(ColorQueries::class, function ($mock) use ($productData, $companyId): void {
        mockGetIdByNameMethod($mock, $productData['color'], $companyId);
    });

    $this->mock(SizeQueries::class, function ($mock) use ($productData, $companyId): void {
        mockGetIdByNameMethod($mock, $productData['size'], $companyId);
    });

    $this->mock(StyleQueries::class, function ($mock) use ($productData, $companyId): void {
        mockGetIdByNameMethod($mock, $productData['style'], $companyId);
    });

    $this->mock(CategoryQueries::class, function ($mock) use ($productData, $companyId): void {
        mockGetIdByNameMethod($mock, $productData['category_name'], $companyId);
        mockGetIdByNameMethod($mock, $productData['subcategory_name'], $companyId, 1);
        mockGetIdByNameMethod($mock, $productData['subsubcategory_name'], $companyId, 0);
    });

    $this->mock(ProductQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    $importProduct = new ImportProduct();
    $importProduct->save($productData, $importRecord);
});

test('validate method returns issues if type is already assembly product', function (): void {
    $companyId = 1;
    $productData = getProductData();

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'created_by_id' => 1,
    ]);

    $product = commonGetProductDetails();
    $productData['type_id'] = ProductTypes::getFormattedCaseName(ProductTypes::ASSEMBLY_PRODUCT->value);

    $this->mock(ProductQueries::class, function ($mock) use ($productData, $companyId, $product): void {
        mockExistsByNameMethod($mock, $productData['name'], $companyId, false, 0);
        $mock->shouldReceive('getByCodeAndCompanyId')
            ->times(1)
            ->with($productData['code'], $companyId)
            ->andReturn($product);
        $mock->shouldReceive('getByUpcAndCompanyId')
            ->times(1)
            ->with($productData['upc'], $companyId)
            ->andReturn($product);
        $mock->shouldReceive('existsByQrCode')
            ->times(1)
            ->andReturn(false);
    });

    $this->mock(BrandQueries::class, function ($mock) use ($productData, $companyId): void {
        mockExistsByNameMethod($mock, $productData['brand'], $companyId, true, 1);
    });

    $this->mock(DepartmentQueries::class, function ($mock) use ($productData, $companyId): void {
        mockExistsByNameMethod($mock, $productData['department'], $companyId, true, 1);
    });

    $this->mock(CategoryQueries::class, function ($mock) use ($productData, $companyId): void {
        mockExistsByNameMethod($mock, $productData['category_name'], $companyId, true, 1);
        mockExistsByNameMethod($mock, $productData['subcategory_name'], $companyId, true, 1);
    });

    $importProduct = new ImportProduct();
    $redirectResponse = $importProduct->validate($productData, $importRecord);
    expect($redirectResponse)->toContain('The specified type assembly product cannot added..');
});

function mockExistsByNameMethod($mockClass, ?string $name, int $companyId, bool $returnData, int $times = 1): void
{
    $mockClass->shouldReceive('existsByName')
        ->times($times)
        ->with($name, $companyId)
        ->andReturn($returnData);
}

function mockGetIdByNameMethod($mockClass, ?string $name, int $companyId, int $times = 1): void
{
    $mockClass->shouldReceive('getIdByName')
        ->times($times)
        ->with($name, $companyId)
        ->andReturn(1);
}

function getProductData(): array
{
    return [
        'name' => 'Product 1',
        'description' => 'new description',
        'code' => 123,
        'unit_of_measure' => 'Test Unit',
        'season' => 'season 1',
        'department' => 'department1',
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
        'purchase_cost' => 10.20,
        'is_temporarily_unavailable' => 'No',
        'has_batch' => 'No',
        'status' => 'Active',
        'is_non_inventory' => 'yes',
        'is_non_selling_item' => 'yes',
        'is_available_in_pos' => 'yes',
        'is_available_in_ecommerce' => 'No',
        'tags' => '',
        'original_created_at' => null,
        'sale_channels' => null,
    ];
}

function getImportRecords(int $companyId): ImportRecord
{
    return ImportRecord::factory()->make([
        'company_id' => $companyId,
        'type_id' => ImportTypes::PRODUCTS->value,
        'created_by_id' => 1,
        'created_by_type' => ModelMapping::ADMIN->name,
    ]);
}

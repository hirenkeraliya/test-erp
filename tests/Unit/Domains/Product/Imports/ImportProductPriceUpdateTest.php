<?php

declare(strict_types=1);

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\ImportRecord\Enums\ColumnValidationIssueTypes;
use App\Domains\ImportRecord\Enums\ImportTypes;
use App\Domains\Permission\Enums\PermissionList;
use App\Domains\Product\Enums\BulkProductPriceUpdateImportColumns;
use App\Domains\Product\Enums\Statuses;
use App\Domains\Product\Imports\ImportProductPriceUpdate;
use App\Domains\Product\ProductQueries;
use App\Models\ImportRecord;

test('validate bulk product price update import columns with specific permissions', function (): void {
    $requiredHeaderColumns = array_flip(BulkProductPriceUpdateImportColumns::getArrayValues());

    $importProductPriceUpdate = new ImportProductPriceUpdate();
    $response = $importProductPriceUpdate->validateColumns(
        $requiredHeaderColumns,
        ['product_' . PermissionList::PRODUCT_PURCHASE_COST->value],
        1
    );
    $this->assertTrue(ColumnValidationIssueTypes::COLUMN_ISSUE->value === $response['type']);
    $this->assertFalse($response['status']);
});

test('validate bulk product price update import columns with all permissions', function (): void {
    $requiredHeaderColumns = array_flip(BulkProductPriceUpdateImportColumns::getArrayValues());

    $importProductPriceUpdate = new ImportProductPriceUpdate();
    $response = $importProductPriceUpdate->validateColumns($requiredHeaderColumns, allProductPermission(), 1);
    $this->assertTrue(ColumnValidationIssueTypes::COLUMN_ISSUE->value === $response['type']);
    $this->assertFalse($response['status']);
});

test('invalidate bulk product price update import columns with specific permissions', function (): void {
    $requiredHeaderColumns = array_flip(BulkProductPriceUpdateImportColumns::getArrayValues());
    array_shift($requiredHeaderColumns);
    $importProductPriceUpdate = new ImportProductPriceUpdate();
    $response = $importProductPriceUpdate->validateColumns(
        $requiredHeaderColumns,
        ['product_' . PermissionList::PRODUCT_PURCHASE_COST->value],
        1
    );

    $this->assertTrue(ColumnValidationIssueTypes::COLUMN_ISSUE->value === $response['type'] && $response['status']);
});

test('invalidate bulk product price update import columns with all permissions', function (): void {
    $requiredHeaderColumns = array_flip(BulkProductPriceUpdateImportColumns::getArrayValues());
    array_shift($requiredHeaderColumns);

    $importProductPriceUpdate = new ImportProductPriceUpdate();
    $response = $importProductPriceUpdate->validateColumns($requiredHeaderColumns, allProductPermission(), 1);

    $this->assertTrue(ColumnValidationIssueTypes::COLUMN_ISSUE->value === $response['type'] && $response['status']);
});

test('validate bulk product price update import columns but missing permissions', function (): void {
    $requiredHeaderColumns = array_flip(BulkProductPriceUpdateImportColumns::getArrayValues());
    $importProductPriceUpdate = new ImportProductPriceUpdate();
    $response = $importProductPriceUpdate->validateColumns($requiredHeaderColumns, [], 1);

    expect($response)->toHaveKey('columns');

    $this->assertTrue(ColumnValidationIssueTypes::PERMISSION_ISSUE->value === $response['type']);
});

test('validate method returns blank array when uploading using UPC', function (): void {
    $companyId = 1;
    $productData = [
        'upc' => '4567554634531',
        'retail_price' => 100,
        'franchise_price_1' => null,
        'franchise_price_2' => null,
        'franchise_price_3' => null,
        'wholesale_price' => null,
        'company_or_tender_price' => null,
        'branch_price' => null,
        'minimum_price' => null,
        'original_capital_price' => null,
        'capital_price' => null,
        'staff_price' => null,
        'purchase_cost' => null,
        'online_price' => null,
    ];
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
    });

    $importProductPriceUpdate = new ImportProductPriceUpdate();
    $redirectResponse = $importProductPriceUpdate->validate($productData, $importRecord);
    $this->assertEquals([], $redirectResponse);
});

test('validate method returns issues list of the given data if UPC does not exists in our record', function (): void {
    $companyId = 1;
    $productData = [
        'upc' => '4567554634531',
        'retail_price' => 100,
    ];

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

    $importProductPriceUpdate = new ImportProductPriceUpdate();
    $redirectResponse = $importProductPriceUpdate->validate($productData, $importRecord);
    expect($redirectResponse)->toContain('The specified UPC does not exist in our records.');
});

test('validate method returns issues list of the given data if UPC product archived', function (): void {
    $companyId = 1;
    $productData = [
        'upc' => '4567554634531',
        'retail_price' => 100,
    ];
    $product = commonGetProductDetails();

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'created_by_id' => 1,
    ]);

    $product->status = Statuses::ARCHIVED->value;

    $this->mock(ProductQueries::class, function ($mock) use ($productData, $companyId, $product): void {
        $mock->shouldReceive('getByUpcAndCompanyId')
            ->once()
            ->with($productData['upc'], $companyId)
            ->andReturn($product);
    });

    $importProductPriceUpdate = new ImportProductPriceUpdate();
    $redirectResponse = $importProductPriceUpdate->validate($productData, $importRecord);
    expect($redirectResponse)->toContain('The specified UPC has already been archived.');
});

test('save method update the retail_price according to UPC ', function (): void {
    $companyId = 1;

    $productData = [
        'upc' => 'Upc 1',
        'retail_price' => 100,
        'franchise_price_1' => 100,
        'franchise_price_3' => 100,
        'franchise_price_2' => 100,
        'wholesale_price' => 100,
        'company_or_tender_price' => 100,
        'branch_price' => 100,
        'minimum_price' => 100,
        'original_capital_price' => 100,
        'capital_price' => 100,
        'staff_price' => 100,
        'purchase_cost' => 100,
        'online_price' => 100,
    ];

    $importRecord = getImportRecordsForProductPriceUpdate($companyId);

    $this->mock(ProductQueries::class, function ($mock): void {
        $mock->shouldReceive('updateProductPrice')
            ->once();
    });

    $importProductPriceUpdate = new ImportProductPriceUpdate();
    $importProductPriceUpdate->save($productData, $importRecord);
});

function getImportRecordsForProductPriceUpdate(int $companyId): ImportRecord
{
    return ImportRecord::factory()->make([
        'company_id' => $companyId,
        'type_id' => ImportTypes::PRODUCT_PRICE_BULK_UPDATE->value,
        'created_by_id' => 1,
        'created_by_type' => ModelMapping::ADMIN->name,
    ]);
}

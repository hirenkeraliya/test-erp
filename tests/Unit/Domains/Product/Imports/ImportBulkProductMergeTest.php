<?php

declare(strict_types=1);

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\ImportRecord\Enums\ImportTypes;
use App\Domains\Product\Enums\Statuses;
use App\Domains\Product\Imports\ImportBulkProductMerge;
use App\Domains\Product\Jobs\ProductMergeJob;
use App\Domains\Product\ProductQueries;
use App\Domains\ProductCollection\Jobs\ProductCollectionUpdateByProductJob;
use App\Domains\ProductCollectionProduct\ProductCollectionProductQueries;
use App\Models\Admin;
use App\Models\ImportRecord;
use App\Models\Product;
use Illuminate\Support\Facades\Queue;

test('validate method returns an empty array when using unique old_upc and new_upc', function (): void {
    $companyId = 1;

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'created_by_id' => 1,
    ]);

    $productData = [
        'old_upc' => 123,
        'new_upc' => 456,
    ];

    $product1 = Product::factory()->make([
        'id' => 1,
        'upc' => 123,
        'company_id' => $companyId,
        'color_id' => 1,
        'size_id' => 1,
        'brand_id' => 1,
        'style_id' => 1,
        'unit_of_measure_id' => 1,
        'season_id' => 1,
        'department_id' => 1,
        'type_id' => 1,
        'article_number' => 1123,
    ]);

    $product2 = Product::factory()->make([
        'id' => 2,
        'upc' => 456,
        'company_id' => $companyId,
        'color_id' => 1,
        'size_id' => 1,
        'brand_id' => 1,
        'style_id' => 1,
        'unit_of_measure_id' => 1,
        'season_id' => 1,
        'department_id' => 1,
        'type_id' => 1,
        'article_number' => 1123,
    ]);

    $this->mock(ProductQueries::class, function ($mock) use ($productData, $companyId, $product1, $product2): void {
        $mock->shouldReceive('getByUpcAndCompanyIdForImportMerge')
            ->once()
            ->with($productData['old_upc'], $companyId)
            ->andReturn($product1);

        $mock->shouldReceive('getByUpcAndCompanyIdForImportMerge')
            ->once()
            ->with($productData['new_upc'], $companyId)
            ->andReturn($product2);
    });

    $importBulkProductMerge = new ImportBulkProductMerge();
    $redirectResponse = $importBulkProductMerge->validate($productData, $importRecord);
    $this->assertEquals([], $redirectResponse);
});

test('validate method returns an error message for non-existent UPC in product database', function (): void {
    $companyId = 1;

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'created_by_id' => 1,
    ]);

    $productData = [
        'old_upc' => 123,
        'new_upc' => 456,
    ];

    Product::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'color_id' => 1,
        'size_id' => 1,
        'brand_id' => 1,
        'style_id' => 1,
        'unit_of_measure_id' => 1,
        'season_id' => 1,
        'department_id' => 1,
        'type_id' => 1,
        'article_number' => 1123,
        'upc' => $productData['old_upc'],
    ]);

    $product2 = Product::factory()->make([
        'id' => 2,
        'company_id' => $companyId,
        'color_id' => 1,
        'size_id' => 1,
        'brand_id' => 1,
        'style_id' => 1,
        'unit_of_measure_id' => 1,
        'season_id' => 1,
        'department_id' => 1,
        'type_id' => 1,
        'article_number' => 1123,
        'upc' => $productData['new_upc'],
    ]);

    $this->mock(ProductQueries::class, function ($mock) use ($companyId, $productData, $product2): void {
        $mock->shouldReceive('getByUpcAndCompanyIdForImportMerge')
            ->once()
            ->with($productData['old_upc'], $companyId)
            ->andReturn(null);

        $mock->shouldReceive('getByUpcAndCompanyIdForImportMerge')
            ->once()
            ->with($productData['new_upc'], $companyId)
            ->andReturn($product2);
    });

    $importBulkProductMerge = new ImportBulkProductMerge();
    $redirectResponse = $importBulkProductMerge->validate($productData, $importRecord);
    expect($redirectResponse)->toContain('The old UPC is not available in our records.');
});

test('validate method returns an error message for type_id mismatch of both the product.', function (): void {
    $companyId = 1;

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'created_by_id' => 1,
    ]);

    $productData = [
        'old_upc' => 123,
        'new_upc' => 456,
    ];

    $product1 = Product::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'color_id' => 1,
        'size_id' => 1,
        'brand_id' => 1,
        'style_id' => 1,
        'unit_of_measure_id' => 1,
        'season_id' => 1,
        'department_id' => 1,
        'type_id' => 1,
        'article_number' => 1123,
        'upc' => $productData['old_upc'],
    ]);

    $product2 = Product::factory()->make([
        'id' => 2,
        'company_id' => $companyId,
        'color_id' => 1,
        'size_id' => 1,
        'brand_id' => 1,
        'style_id' => 1,
        'unit_of_measure_id' => 1,
        'season_id' => 1,
        'department_id' => 1,
        'type_id' => 2,
        'article_number' => 1123,
        'upc' => $productData['new_upc'],
    ]);

    $this->mock(ProductQueries::class, function ($mock) use ($companyId, $productData, $product2, $product1): void {
        $mock->shouldReceive('getByUpcAndCompanyIdForImportMerge')
            ->once()
            ->with($productData['old_upc'], $companyId)
            ->andReturn($product1);

        $mock->shouldReceive('getByUpcAndCompanyIdForImportMerge')
            ->once()
            ->with($productData['new_upc'], $companyId)
            ->andReturn($product2);
    });

    $importBulkProductMerge = new ImportBulkProductMerge();
    $redirectResponse = $importBulkProductMerge->validate($productData, $importRecord);
    expect($redirectResponse)->toContain('Same Product type only can be merge. Like Regular v/s Regular.');
});

test('validate method returns an error message for article number mismatch of both the product.', function (): void {
    $companyId = 1;

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'created_by_id' => 1,
    ]);

    $productData = [
        'old_upc' => 123,
        'new_upc' => 456,
    ];

    $product1 = Product::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'color_id' => 1,
        'size_id' => 1,
        'brand_id' => 1,
        'style_id' => 1,
        'unit_of_measure_id' => 1,
        'season_id' => 1,
        'department_id' => 1,
        'type_id' => 1,
        'article_number' => 111,
        'upc' => $productData['old_upc'],
    ]);

    $product2 = Product::factory()->make([
        'id' => 2,
        'company_id' => $companyId,
        'color_id' => 1,
        'size_id' => 1,
        'brand_id' => 1,
        'style_id' => 1,
        'unit_of_measure_id' => 1,
        'season_id' => 1,
        'department_id' => 1,
        'type_id' => 1,
        'article_number' => 222,
        'upc' => $productData['new_upc'],
    ]);

    $this->mock(ProductQueries::class, function ($mock) use ($companyId, $productData, $product2, $product1): void {
        $mock->shouldReceive('getByUpcAndCompanyIdForImportMerge')
            ->once()
            ->with($productData['old_upc'], $companyId)
            ->andReturn($product1);

        $mock->shouldReceive('getByUpcAndCompanyIdForImportMerge')
            ->once()
            ->with($productData['new_upc'], $companyId)
            ->andReturn($product2);
    });

    $importBulkProductMerge = new ImportBulkProductMerge();
    $redirectResponse = $importBulkProductMerge->validate($productData, $importRecord);
    expect($redirectResponse)->toContain("Same Article Number's product only can be merge.");
});

test(
    "validate method returns an error message for one product have article number & other don't have.",
    function (): void {
        $companyId = 1;

        $importRecord = ImportRecord::factory()->make([
            'company_id' => $companyId,
            'created_by_id' => 1,
        ]);

        $productData = [
            'old_upc' => 123,
            'new_upc' => 456,
        ];

        $product1 = Product::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
            'color_id' => 1,
            'size_id' => 1,
            'brand_id' => 1,
            'style_id' => 1,
            'unit_of_measure_id' => 1,
            'season_id' => 1,
            'department_id' => 1,
            'type_id' => 1,
            'article_number' => 111,
            'upc' => $productData['old_upc'],
        ]);

        $product2 = Product::factory()->make([
            'id' => 2,
            'company_id' => $companyId,
            'color_id' => 1,
            'size_id' => 1,
            'brand_id' => 1,
            'style_id' => 1,
            'unit_of_measure_id' => 1,
            'season_id' => 1,
            'department_id' => 1,
            'type_id' => 1,
            'article_number' => null,
            'upc' => $productData['new_upc'],
        ]);

        $this->mock(ProductQueries::class, function ($mock) use ($companyId, $productData, $product2, $product1): void {
            $mock->shouldReceive('getByUpcAndCompanyIdForImportMerge')
                ->once()
                ->with($productData['old_upc'], $companyId)
                ->andReturn($product1);

            $mock->shouldReceive('getByUpcAndCompanyIdForImportMerge')
                ->once()
                ->with($productData['new_upc'], $companyId)
                ->andReturn($product2);
        });

        $importBulkProductMerge = new ImportBulkProductMerge();
        $redirectResponse = $importBulkProductMerge->validate($productData, $importRecord);
        expect($redirectResponse)->toContain('Both products must have no Article Number to be merged.');
    }
);

test('validate method checks for identical old and new UPCs', function (): void {
    $companyId = 1;

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'created_by_id' => 1,
    ]);

    $productData = [
        'old_upc' => 123,
        'new_upc' => 123,
    ];

    $product1 = Product::factory()->make([
        'id' => 1,
        'upc' => 123,
        'company_id' => $companyId,
        'color_id' => 1,
        'size_id' => 1,
        'brand_id' => 1,
        'style_id' => 1,
        'unit_of_measure_id' => 1,
        'season_id' => 1,
        'department_id' => 1,
    ]);

    $product2 = Product::factory()->make([
        'id' => 2,
        'upc' => 456,
        'company_id' => $companyId,
        'color_id' => 1,
        'size_id' => 1,
        'brand_id' => 1,
        'style_id' => 1,
        'unit_of_measure_id' => 1,
        'season_id' => 1,
        'department_id' => 1,
    ]);

    $this->mock(ProductQueries::class, function ($mock) use ($productData, $companyId, $product1, $product2): void {
        $mock->shouldReceive('getByUpcAndCompanyIdForImportMerge')
            ->once()
            ->with($productData['old_upc'], $companyId)
            ->andReturn($product1);

        $mock->shouldReceive('getByUpcAndCompanyIdForImportMerge')
            ->once()
            ->with($productData['new_upc'], $companyId)
            ->andReturn($product2);
    });

    $importBulkProductMerge = new ImportBulkProductMerge();
    $redirectResponse = $importBulkProductMerge->validate($productData, $importRecord);
    expect($redirectResponse)->toContain('Both UPCs cannot be the same.');
});

test('validate method handles archived old UPC', function (): void {
    $companyId = 1;

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'created_by_id' => 1,
    ]);

    $productData = [
        'old_upc' => 123,
        'new_upc' => 456,
    ];

    $product1 = Product::factory()->make([
        'id' => 1,
        'upc' => 123,
        'company_id' => $companyId,
        'color_id' => 1,
        'size_id' => 1,
        'brand_id' => 1,
        'style_id' => 1,
        'unit_of_measure_id' => 1,
        'season_id' => 1,
        'department_id' => 1,
        'status' => Statuses::ARCHIVED->value,
    ]);

    $product2 = Product::factory()->make([
        'id' => 2,
        'upc' => 456,
        'company_id' => $companyId,
        'color_id' => 1,
        'size_id' => 1,
        'brand_id' => 1,
        'style_id' => 1,
        'unit_of_measure_id' => 1,
        'season_id' => 1,
        'department_id' => 1,
        'status' => Statuses::ARCHIVED->value,
    ]);

    $this->mock(ProductQueries::class, function ($mock) use ($productData, $companyId, $product1, $product2): void {
        $mock->shouldReceive('getByUpcAndCompanyIdForImportMerge')
            ->once()
            ->with($productData['old_upc'], $companyId)
            ->andReturn($product1);

        $mock->shouldReceive('getByUpcAndCompanyIdForImportMerge')
            ->once()
            ->with($productData['new_upc'], $companyId)
            ->andReturn($product2);
    });

    $importBulkProductMerge = new ImportBulkProductMerge();
    $redirectResponse = $importBulkProductMerge->validate($productData, $importRecord);
    expect($redirectResponse)->toContain('The new product is already in archived state. You cannot merge it.');
});

test('save method archives old and new UPCs and dispatches job', function (): void {
    Queue::fake();
    $companyId = 1;

    $productData = [
        'old_upc' => 123,
        'new_upc' => 456,
    ];

    $product1 = Product::factory()->make([
        'id' => 1,
        'upc' => 123,
        'company_id' => $companyId,
        'color_id' => 1,
        'size_id' => 1,
        'brand_id' => 1,
        'style_id' => 1,
        'unit_of_measure_id' => 1,
        'season_id' => 1,
        'department_id' => 1,
    ]);

    $product2 = Product::factory()->make([
        'id' => 2,
        'upc' => 456,
        'company_id' => $companyId,
        'color_id' => 1,
        'size_id' => 1,
        'brand_id' => 1,
        'style_id' => 1,
        'unit_of_measure_id' => 1,
        'season_id' => 1,
        'department_id' => 1,
    ]);

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'type_id' => ImportTypes::BULK_PRODUCT_MERGE->value,
        'created_by_id' => 1,
        'created_by_type' => ModelMapping::ADMIN->name,
    ]);

    $importRecord->createdBy = Admin::factory()->make([
        'employee_id' => 1,
    ]);

    $this->mock(ProductQueries::class, function ($mock) use ($productData, $companyId, $product1, $product2): void {
        $mock->shouldReceive('getByUpcAndCompanyId')
            ->once()
            ->with($productData['old_upc'], $companyId)
            ->andReturn($product1);

        $mock->shouldReceive('getByUpcAndCompanyId')
            ->once()
            ->with($productData['new_upc'], $companyId)
            ->andReturn($product2);

        $mock->shouldReceive('markAsArchived')
            ->once()
            ->with($product1->id, $companyId);

        $mock->shouldReceive('markAsArchived')
            ->once()
            ->with($product2->id, $companyId);
    });

    $this->mock(ProductCollectionProductQueries::class, function ($mock): void {
        $mock->shouldReceive('removeByProductId')
            ->once();
    });

    $importBulkProductMerge = new ImportBulkProductMerge();
    $importBulkProductMerge->save($productData, $importRecord);

    Queue::assertPushed(ProductMergeJob::class);
    Queue::assertPushed(ProductCollectionUpdateByProductJob::class);
});

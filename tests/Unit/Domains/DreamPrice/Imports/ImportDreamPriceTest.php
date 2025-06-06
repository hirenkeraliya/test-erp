<?php

declare(strict_types=1);

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\DreamPrice\Imports\ImportDreamPrice;
use App\Domains\DreamPriceProduct\DreamPriceProductQueries;
use App\Domains\ImportRecord\Enums\ImportTypes;
use App\Domains\Product\Enums\Statuses;
use App\Domains\Product\ProductQueries;
use App\Models\ImportRecord;

test('validate method returns blank array', function (): void {
    $companyId = 1;
    $dreamPriceData = getDreamPriceData();
    $product = commonGetProductDetails();
    $product->retail_price = 1000;
    $product->is_non_selling_item = false;

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'created_by_id' => 1,
    ]);

    $this->mock(ProductQueries::class, function ($mock) use ($dreamPriceData, $companyId, $product): void {
        $mock->shouldReceive('getByUpcAndCompanyId')
            ->once()
            ->with($dreamPriceData['upc'], $companyId)
            ->andReturn($product);

        $mock->shouldReceive('getProductTypeAndPrice')
            ->once()
            ->with($dreamPriceData['upc'])
            ->andReturn($product);
    });

    $importDreamPrice = new ImportDreamPrice();
    $redirectResponse = $importDreamPrice->validate($dreamPriceData, $importRecord);
    $this->assertEquals([], $redirectResponse);
});

test('validate method returns issues list of the given data', function (): void {
    $companyId = 1;
    $dreamPriceData = getDreamPriceData();
    $product = commonGetProductDetails();
    $product->retail_price = 1;
    $product->type_id = 2;
    $product->is_non_selling_item = true;

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'created_by_id' => 1,
    ]);

    $this->mock(ProductQueries::class, function ($mock) use ($dreamPriceData, $companyId, $product): void {
        $mock->shouldReceive('getProductTypeAndPrice')
            ->once()
            ->with($dreamPriceData['upc'])
            ->andReturn($product);

        $mock->shouldReceive('getByUpcAndCompanyId')
            ->once()
            ->with($dreamPriceData['upc'], $companyId)
            ->andReturn($product);
    });

    $importDreamPrice = new ImportDreamPrice();
    $redirectResponse = $importDreamPrice->validate($dreamPriceData, $importRecord);
    $this->assertEquals(3, count($redirectResponse));
});

test('validate method returns issues if specified upc not found', function (): void {
    $companyId = 1;
    $dreamPriceData = getDreamPriceData();
    $product = commonGetProductDetails();
    $product->retail_price = 1;
    $product->type_id = 2;

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'created_by_id' => 1,
    ]);

    $this->mock(ProductQueries::class, function ($mock) use ($dreamPriceData, $companyId): void {
        $mock->shouldReceive('getByUpcAndCompanyId')
            ->once()
            ->with($dreamPriceData['upc'], $companyId)
            ->andReturn(null);
    });

    $importDreamPrice = new ImportDreamPrice();
    $redirectResponse = $importDreamPrice->validate($dreamPriceData, $importRecord);

    expect($redirectResponse)->toContain('The specified UPC is not available in our records.');
});

test('validate method returns issues if specified product is non selling item', function (): void {
    $companyId = 1;
    $dreamPriceData = getDreamPriceData();
    $product = commonGetProductDetails();
    $product->retail_price = 1000;
    $product->is_non_selling_item = true;
    $product->type_id = 1;

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'created_by_id' => 1,
    ]);

    $this->mock(ProductQueries::class, function ($mock) use ($dreamPriceData, $companyId, $product): void {
        $mock->shouldReceive('getByUpcAndCompanyId')
            ->once()
            ->with($dreamPriceData['upc'], $companyId)
            ->andReturn($product);

        $mock->shouldReceive('getProductTypeAndPrice')
            ->once()
            ->with($dreamPriceData['upc'])
            ->andReturn($product);
    });

    $importDreamPrice = new ImportDreamPrice();
    $redirectResponse = $importDreamPrice->validate($dreamPriceData, $importRecord);

    expect($redirectResponse)->toContain('The specified product is non selling item.');
});

test('validate method returns issues if specified upc product archived', function (): void {
    $companyId = 1;
    $dreamPriceData = getDreamPriceData();
    $product = commonGetProductDetails();
    $product->status = Statuses::ARCHIVED->value;
    $product->retail_price = 1;
    $product->type_id = 2;

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'created_by_id' => 1,
    ]);

    $this->mock(ProductQueries::class, function ($mock) use ($dreamPriceData, $companyId, $product): void {
        $mock->shouldReceive('getProductTypeAndPrice')
            ->once()
            ->with($dreamPriceData['upc'])
            ->andReturn($product);

        $mock->shouldReceive('getByUpcAndCompanyId')
            ->once()
            ->with($dreamPriceData['upc'], $companyId)
            ->andReturn($product);
    });

    $importDreamPrice = new ImportDreamPrice();
    $redirectResponse = $importDreamPrice->validate($dreamPriceData, $importRecord);

    expect($redirectResponse)->toContain('The specified UPC has already been archived.');
});

test('save method save only required columns', function (): void {
    $companyId = 1;

    $dreamPriceData = [
        'upc' => 'Product 1',
        'price' => 100,
    ];

    $importRecord = getDreamPriceImportRecords($companyId);

    $this->mock(ProductQueries::class, function ($mock): void {
        $mock->shouldReceive('getIdByUpc')
            ->once();
    });

    $this->mock(DreamPriceProductQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    $importDreamPrice = new ImportDreamPrice();
    $importDreamPrice->save($dreamPriceData, $importRecord);
});

function getDreamPriceData(): array
{
    return [
        'upc' => 'testUpc',
        'price' => 123,
    ];
}

function getDreamPriceImportRecords(int $companyId): ImportRecord
{
    return ImportRecord::factory()->make([
        'company_id' => $companyId,
        'type_id' => ImportTypes::DREAM_PRICE->value,
        'created_by_id' => 1,
        'created_by_type' => ModelMapping::ADMIN->name,
        'module_id' => 1,
        'module_type' => ModelMapping::DREAM_PRICE->name,
    ]);
}

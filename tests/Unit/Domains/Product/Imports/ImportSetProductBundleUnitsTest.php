<?php

declare(strict_types=1);

use App\Domains\BoxProduct\BoxProductQueries;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\ImportRecord\Enums\ImportTypes;
use App\Domains\PackageType\PackageTypeQueries;
use App\Domains\Product\Imports\ImportSetProductBoxUnits;
use App\Domains\Product\ProductQueries;
use App\Models\ImportRecord;
use App\Models\PackageType;

test('Import Product bundle units validate method returns issues list of the given data', function (): void {
    $companyId = 1;
    $productData = getProductBoxUnitData();

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'created_by_id' => 1,
    ]);

    unset($productData['units']);

    $this->mock(PackageTypeQueries::class, function ($mock) use ($productData, $companyId): void {
        $mock->shouldReceive('existsByName')
            ->once()
            ->with($productData['package_type_name'], $companyId)
            ->andReturn(false);
    });

    $this->mock(ProductQueries::class, function ($mock) use ($productData, $companyId): void {
        $mock->shouldReceive('existsByUpc')
            ->once()
            ->with($productData['upc'], $companyId)
            ->andReturn(false);
    });

    $importSetProductBoxUnits = new ImportSetProductBoxUnits();
    $redirectResponse = $importSetProductBoxUnits->validate($productData, $importRecord);
    $this->assertEquals(3, count($redirectResponse));
});

test('save method saves the product bundle units data.', function (): void {
    $companyId = 1;
    $productData = getProductBoxUnitData();

    $packageTypeId = PackageType::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'name' => $productData['package_type_name'],
    ])->id;

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'type_id' => ImportTypes::SET_PRODUCT_BOX_UNITS->value,
        'created_by_id' => 1,
        'created_by_type' => ModelMapping::ADMIN->name,
    ]);

    $this->mock(PackageTypeQueries::class, function ($mock) use ($packageTypeId): void {
        $mock->shouldReceive('getIdByName')
            ->once()
            ->andReturn($packageTypeId);
    });

    $this->mock(ProductQueries::class, function ($mock): void {
        $mock->shouldReceive('getIdByUpc')
            ->once()
            ->andReturn(1);
    });

    $this->mock(BoxProductQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    $importSetProductBoxUnits = new ImportSetProductBoxUnits();
    $importSetProductBoxUnits->save($productData, $importRecord);
});

function getProductBoxUnitData(): array
{
    return [
        'upc' => '123456',
        'package_type_name' => 'Box',
        'units' => 10,
    ];
}

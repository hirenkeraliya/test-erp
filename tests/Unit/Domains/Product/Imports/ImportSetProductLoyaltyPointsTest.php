<?php

declare(strict_types=1);

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\ImportRecord\Enums\ImportTypes;
use App\Domains\Membership\MembershipQueries;
use App\Domains\Product\Imports\ImportSetProductLoyaltyPoints;
use App\Domains\Product\ProductQueries;
use App\Domains\ProductLoyaltyPoint\ProductLoyaltyPointQueries;
use App\Models\ImportRecord;

test('validate method returns issues list of the given data', function (): void {
    $companyId = 1;
    $productData = getProductLoyaltyPointData();

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'created_by_id' => 1,
    ]);

    $this->mock(MembershipQueries::class, function ($mock) use ($productData, $companyId): void {
        $mock->shouldReceive('existsByName')
            ->once()
            ->with($productData['membership'], $companyId)
            ->andReturn(false);

        $mock->shouldReceive('getIdByName')
                ->once()
                ->andReturn(1);
    });

    $this->mock(ProductQueries::class, function ($mock) use ($productData, $companyId): void {
        $mock->shouldReceive('existsByUpc')
            ->once()
            ->with($productData['upc'], $companyId)
            ->andReturn(false);

        $mock->shouldReceive('getIdByUpcForLoyaltyPoint')
            ->once()
            ->andReturn(1);
    });

    $this->mock(ProductLoyaltyPointQueries::class, function ($mock): void {
        $mock->shouldReceive('existByProductLoyaltyPoint')
            ->once();
    });

    $importSetProductLoyaltyPoints = new ImportSetProductLoyaltyPoints();
    $redirectResponse = $importSetProductLoyaltyPoints->validate($productData, $importRecord);
    $this->assertEquals(2, count($redirectResponse));
});

test('save method saves the data', function (): void {
    $companyId = 1;

    $productData = getProductLoyaltyPointData();

    $importRecord = getImportRecordsForSetProductLoyaltyPoint($companyId);

    $this->mock(MembershipQueries::class, function ($mock): void {
        $mock->shouldReceive('getIdByName')
            ->once()
            ->andReturn(1);
    });

    $this->mock(ProductQueries::class, function ($mock): void {
        $mock->shouldReceive('getIdByUpcForLoyaltyPoint')
            ->once()
            ->andReturn(1);
    });

    $this->mock(ProductLoyaltyPointQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });
    $importSetProductLoyaltyPoints = new ImportSetProductLoyaltyPoints();
    $importSetProductLoyaltyPoints->save($productData, $importRecord);
});

function getProductLoyaltyPointData(): array
{
    return [
        'upc' => '123456',
        'membership' => 'code test',
        'loyalty_points' => '123',
    ];
}

function getImportRecordsForSetProductLoyaltyPoint(int $companyId): ImportRecord
{
    return ImportRecord::factory()->make([
        'company_id' => $companyId,
        'type_id' => ImportTypes::SET_PRODUCT_LOYALTY_POINTS->value,
        'created_by_id' => 1,
        'created_by_type' => ModelMapping::ADMIN->name,
    ]);
}

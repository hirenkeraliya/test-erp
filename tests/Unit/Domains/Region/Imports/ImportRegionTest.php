<?php

declare(strict_types=1);

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\ImportRecord\Enums\ImportTypes;
use App\Domains\Region\Imports\ImportRegion;
use App\Domains\Region\RegionQueries;
use App\Models\ImportRecord;

test('the validate method returns blank array when no error in given details', function (): void {
    $companyId = 1;

    $regionData = [
        'name' => 'Abc',
        'code' => 'def',
        'manager_name' => 'manager',
        'manager_email' => 'manager@gmail.com',
    ];

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'created_by_id' => 1,
    ]);

    $this->mock(RegionQueries::class, function ($mock) use ($companyId, $regionData): void {
        $mock->shouldReceive('existsByName')
            ->once()
            ->with($regionData['name'], $companyId)
            ->andReturn(false);

        $mock->shouldReceive('existsByCode')
            ->once()
            ->with($regionData['code'], $companyId)
            ->andReturn(false);
    });

    $importRegion = new ImportRegion();
    $redirectResponse = $importRegion->validate($regionData, $importRecord);
    expect($redirectResponse)->toBeArray();
});

test(
    'name is required for import record',
    function (): void {
        $companyId = 1;

        $importRecord = ImportRecord::factory()->make([
            'company_id' => $companyId,
            'created_by_id' => 1,
        ]);

        $regionData = [
            'name' => '',
            'code' => '',
            'manager_name' => '',
            'manager_email' => '',
        ];

        $this->mock(RegionQueries::class, function ($mock) use ($companyId, $regionData): void {
            $mock->shouldReceive('existsByCode')
                ->once()
                ->with($regionData['code'], $companyId)
                ->andReturn(false);
        });

        $importRegion = new ImportRegion();
        $redirectResponse = $importRegion->validate($regionData, $importRecord);
        expect($redirectResponse)->toContain('The name is required.');
    }
);

test('It calls addNew method to store region details', function (): void {
    $companyId = 1;

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'type_id' => ImportTypes::REGIONS->value,
        'created_by_id' => 1,
        'created_by_type' => ModelMapping::ADMIN->name,
    ]);

    $regionData = [
        'name' => 'region-1',
        'code' => 'region-code',
        'manager_name' => 'manager',
        'manager_email' => 'manager@gmail.com',
    ];

    $this->mock(RegionQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    $importRegion = new ImportRegion();
    $importRegion->save($regionData, $importRecord);
    $this->assertTrue(true);
});

<?php

declare(strict_types=1);

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\ImportRecord\Enums\ImportTypes;
use App\Domains\SizeGroup\Imports\ImportSizeGroup;
use App\Domains\SizeGroup\SizeGroupQueries;
use App\Models\ImportRecord;

test('the validate method returns blank array when no error in given details', function (): void {
    $companyId = 1;

    $sizeGroupData = [
        'name' => 'Abc',
        'code' => 'def',
    ];

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'created_by_id' => 1,
    ]);

    $this->mock(SizeGroupQueries::class, function ($mock) use ($companyId, $sizeGroupData): void {
        $mock->shouldReceive('existsByName')
            ->once()
            ->with($sizeGroupData['name'], $companyId)
            ->andReturn(false);
        $mock->shouldReceive('existsByCode')
            ->once()
            ->with($sizeGroupData['code'], $companyId)
            ->andReturn(false);
    });

    $importSizeGroup = new ImportSizeGroup();
    $redirectResponse = $importSizeGroup->validate($sizeGroupData, $importRecord);
    expect($redirectResponse)->toBeArray();
});

test(
    'name is required for import record',
    function (): void {
        $companyId = 1;

        $sizeGroupData = [
            'name' => '',
            'code' => '',
        ];

        $importRecord = ImportRecord::factory()->make([
            'company_id' => $companyId,
            'created_by_id' => 1,
        ]);

        $this->mock(SizeGroupQueries::class, function ($mock) use ($companyId, $sizeGroupData): void {
            $mock->shouldReceive('existsByCode')
                ->once()
                ->with($sizeGroupData['code'], $companyId)
                ->andReturn(false);
        });

        $importSizeGroup = new ImportSizeGroup();
        $redirectResponse = $importSizeGroup->validate($sizeGroupData, $importRecord);
        expect($redirectResponse)->toContain('The name is required.');
    }
);

test('It calls addNew method to store size group details', function (): void {
    $companyId = 1;

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'type_id' => ImportTypes::SIZE_GROUPS->value,
        'created_by_id' => 1,
        'created_by_type' => ModelMapping::ADMIN->name,
    ]);

    $sizeGroupData = [
        'name' => 'size-group-1',
        'code' => 'size-group-code',
    ];

    $this->mock(SizeGroupQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    $importSizeGroup = new ImportSizeGroup();
    $importSizeGroup->save($sizeGroupData, $importRecord);
    $this->assertTrue(true);
});

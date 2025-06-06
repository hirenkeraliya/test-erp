<?php

declare(strict_types=1);

use App\Domains\ColorGroup\ColorGroupQueries;
use App\Domains\ColorGroup\Imports\ImportColorGroup;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\ImportRecord\Enums\ImportTypes;
use App\Models\ImportRecord;

test('the validate method returns blank array when no error in given details', function (): void {
    $companyId = 1;

    $colorGroupData = [
        'name' => 'Abc',
        'code' => 'def',
    ];

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'created_by_id' => 1,
    ]);

    $this->mock(ColorGroupQueries::class, function ($mock) use ($companyId, $colorGroupData): void {
        $mock->shouldReceive('existsByName')
            ->once()
            ->with($colorGroupData['name'], $companyId)
            ->andReturn(false);
    });

    $importColorGroup = new ImportColorGroup();
    $redirectResponse = $importColorGroup->validate($colorGroupData, $importRecord);
    expect($redirectResponse)->toBeArray();
});

test(
    'name is required for import record',
    function (): void {
        $companyId = 1;

        $colorGroupData = [
            'name' => '',
            'code' => '',
        ];

        $importRecord = ImportRecord::factory()->make([
            'company_id' => $companyId,
            'created_by_id' => 1,
        ]);

        $importColorGroup = new ImportColorGroup();
        $redirectResponse = $importColorGroup->validate($colorGroupData, $importRecord);
        expect($redirectResponse)->toContain('The name is required.');
    }
);

test('It calls addNew method to store color group details', function (): void {
    $companyId = 1;

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'type_id' => ImportTypes::COLOR_GROUPS->value,
        'created_by_id' => 1,
        'created_by_type' => ModelMapping::ADMIN->name,
    ]);

    $colorGroupData = [
        'name' => 'color-group-1',
        'code' => 'color-group-code',
        'color_code' => '#ABCDE',
    ];

    $this->mock(ColorGroupQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    $importColorGroup = new ImportColorGroup();
    $importColorGroup->save($colorGroupData, $importRecord);
    $this->assertTrue(true);
});

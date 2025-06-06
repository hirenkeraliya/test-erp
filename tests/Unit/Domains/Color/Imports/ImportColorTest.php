<?php

declare(strict_types=1);

use App\Domains\Color\ColorQueries;
use App\Domains\Color\Imports\ImportColor;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\ImportRecord\Enums\ImportTypes;
use App\Models\ImportRecord;

test('the validate method returns blank array when no error in given details', function (): void {
    $companyId = 1;

    $colorData = [
        'name' => 'Abc',
        'code' => 'def',
    ];

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'created_by_id' => 1,
    ]);

    $this->mock(ColorQueries::class, function ($mock) use ($companyId, $colorData): void {
        $mock->shouldReceive('existsByName')
            ->once()
            ->with($colorData['name'], $companyId)
            ->andReturn(false);
        $mock->shouldReceive('existsByCode')
            ->once()
            ->with($colorData['code'], $companyId)
            ->andReturn(false);
    });

    $importColor = new ImportColor();
    $redirectResponse = $importColor->validate($colorData, $importRecord);
    expect($redirectResponse)->toBeArray();
});

test(
    'name is required for import record',
    function (): void {
        $companyId = 1;
        $colorData = [
            'name' => '',
            'code' => null,
        ];

        $importRecord = ImportRecord::factory()->make([
            'company_id' => $companyId,
            'created_by_id' => 1,
        ]);

        $importColor = new ImportColor();
        $redirectResponse = $importColor->validate($colorData, $importRecord);
        expect($redirectResponse)->toContain('The name is required.');
    }
);

test('It calls addNew method to store color group details', function (): void {
    $companyId = 1;

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'type_id' => ImportTypes::COLORS->value,
        'created_by_id' => 1,
        'created_by_type' => ModelMapping::ADMIN->name,
    ]);

    $colorData = [
        'name' => 'color-1',
        'code' => 'color-code',
        'color_code' => '#ABCDE',
    ];

    $this->mock(ColorQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    $importColor = new ImportColor();
    $importColor->save($colorData, $importRecord);
    $this->assertTrue(true);
});

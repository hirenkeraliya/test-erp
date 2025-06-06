<?php

declare(strict_types=1);

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\ImportRecord\Enums\ImportTypes;
use App\Domains\Size\Imports\ImportSize;
use App\Domains\Size\SizeQueries;
use App\Models\ImportRecord;

test('the validate method returns blank array when no error in given details', function (): void {
    $companyId = 1;

    $sizeData = getSizeData();

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'created_by_id' => 1,
    ]);

    $this->mock(SizeQueries::class, function ($mock): void {
        $mock->shouldReceive('existsByName')
             ->times(2);
        $mock->shouldReceive('existsByCode')
             ->times(1);
    });

    $importSize = new ImportSize();
    $redirectResponse = $importSize->validate($sizeData, $importRecord);
    expect($redirectResponse)->toBeArray();
});

test('It calls addNew method to store size details', function (): void {
    $companyId = 1;

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'type_id' => ImportTypes::SIZES->value,
        'created_by_id' => 1,
        'created_by_type' => ModelMapping::ADMIN->name,
    ]);

    $sizeRecord = [
        'name' => 'size1',
        'code' => 'abc',
        'sizeGroup' => null,
        'create_after' => 'abc',
    ];

    $this->mock(SizeQueries::class, function ($mock): void {
        $mock->shouldReceive('getIdBySortName')
          ->once();
        $mock->shouldReceive('addNew')
            ->once();
    });

    $importSize = new ImportSize();
    $importSize->save($sizeRecord, $importRecord);
    $this->assertTrue(true);
});

function getSizeData(): array
{
    return [
        'name' => 'size-1',
        'code' => 'size-code',
        'sizeGroup' => null,
        'create_after' => 1,
    ];
}

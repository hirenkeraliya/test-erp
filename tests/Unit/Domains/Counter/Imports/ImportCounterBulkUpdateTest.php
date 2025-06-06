<?php

declare(strict_types=1);

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Counter\CounterQueries;
use App\Domains\Counter\Enums\CounterImportColumns;
use App\Domains\Counter\Imports\ImportCounterBulkUpdate;
use App\Domains\ImportRecord\Enums\ColumnValidationIssueTypes;
use App\Domains\ImportRecord\Enums\ImportTypes;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Models\ImportRecord;
use App\Models\Location;

test('the validate method returns blank array when no error in given details', function (): void {
    $companyId = 1;
    $getCounterBulkUpdateData = getCounterBulkUpdateData();

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'created_by_id' => 1,
    ]);

    $this->mock(CounterQueries::class, function ($mock) use ($getCounterBulkUpdateData, $companyId): void {
        $mock->shouldReceive('counterExists')
            ->once()
            ->with($getCounterBulkUpdateData['name'], $companyId)
            ->andReturn(true);
    });

    $this->mock(LocationQueries::class, function ($mock) use ($getCounterBulkUpdateData, $companyId): void {
        $mock->shouldReceive('existsByName')
            ->once()
            ->with($getCounterBulkUpdateData['location'], $companyId)
            ->andReturn(true);
    });

    $importCounterBulkUpdate = new ImportCounterBulkUpdate();
    $redirectResponse = $importCounterBulkUpdate->validate($getCounterBulkUpdateData, $importRecord);
    $this->assertEquals([], $redirectResponse);
});

test(
    'name, is_locked and store are required for import record',
    function (): void {
        $companyId = 1;

        $getCounterBulkUpdateData = [
            'name' => '',
            'is_locked' => '',
            'location' => '',
        ];

        $importRecord = ImportRecord::factory()->make([
            'company_id' => $companyId,
            'created_by_id' => 1,
        ]);

        $this->mock(CounterQueries::class, function ($mock) use ($getCounterBulkUpdateData, $companyId): void {
            $mock->shouldReceive('counterExists')
                ->once()
                ->with($getCounterBulkUpdateData['name'], $companyId)
                ->andReturn(true);
        });

        $this->mock(LocationQueries::class, function ($mock) use ($getCounterBulkUpdateData, $companyId): void {
            $mock->shouldReceive('existsByName')
                ->once()
                ->with($getCounterBulkUpdateData['location'], $companyId)
                ->andReturn(true);
        });

        $importCounterBulkUpdate = new ImportCounterBulkUpdate();
        $redirectResponse = $importCounterBulkUpdate->validate($getCounterBulkUpdateData, $importRecord);
        $this->assertEquals(2, is_countable($redirectResponse) ? count($redirectResponse) : 0);
    }
);

test('save method works for the counter details update', function (): void {
    $companyId = 1;

    $getCounterBulkUpdateData = getCounterBulkUpdateData();

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'type_id' => ImportTypes::COUNTER_BULK_UPDATE->value,
        'created_by_id' => 1,
        'created_by_type' => ModelMapping::ADMIN->name,
    ]);

    $this->mock(CounterQueries::class, function ($mock): void {
        $mock->shouldReceive('updateByName')
            ->times(1);
    });

    $this->mock(LocationQueries::class, function ($mock): void {
        $mock->shouldReceive('getIdByName')
            ->times(1);
    });

    $importCounterBulkUpdate = new ImportCounterBulkUpdate();
    $importCounterBulkUpdate->save($getCounterBulkUpdateData, $importRecord);
    $this->assertTrue(true);
});

test('validate import Counter Bulk Update Import Columns', function (): void {
    $requiredHeaderColumns = CounterImportColumns::getArrayValues();

    $importCounterBulkUpdate = new ImportCounterBulkUpdate();
    $response = $importCounterBulkUpdate->validateColumns($requiredHeaderColumns, [], 1);
    $this->assertTrue(ColumnValidationIssueTypes::COLUMN_ISSUE->value === $response['type']);
    $this->assertTrue($response['status']);
});

function getCounterBulkUpdateData(): array
{
    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'name' => 'abcd',
        'type_id' => LocationTypes::STORE->value,
    ]);

    return [
        'name' => 'counter-1',
        'is_locked' => 'Yes',
        'location' => $location->id,
    ];
}

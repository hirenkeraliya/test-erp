<?php

declare(strict_types=1);

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Counter\CounterQueries;
use App\Domains\Counter\Imports\ImportCounter;
use App\Domains\ImportRecord\Enums\ImportTypes;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Models\ImportRecord;
use App\Models\Location;

test('the validate method returns blank array when no error in given details', function (): void {
    $companyId = 1;

    $counterData = getCounterData();

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'created_by_id' => 1,
    ]);

    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $this->mock(LocationQueries::class, function ($mock) use ($companyId, $counterData, $location): void {
        $mock->shouldReceive('getIdOnlyByName')
            ->once()
            ->with($counterData['location'], $companyId)
            ->andReturn($location);
    });

    $this->mock(CounterQueries::class, function ($mock): void {
        $mock->shouldReceive('existsByName')
            ->once();
    });

    $importCounter = new ImportCounter();
    $redirectResponse = $importCounter->validate($counterData, $importRecord);
    $this->assertEquals([], $redirectResponse);
});

test(
    'name, store and is_locked are required for import record',
    function (): void {
        $companyId = 1;

        $counterData = [
            'name' => '',
            'location' => '',
            'is_locked' => '',
        ];

        $importRecord = ImportRecord::factory()->make([
            'company_id' => $companyId,
            'created_by_id' => 1,
        ]);

        $this->mock(LocationQueries::class, function ($mock): void {
            $mock->shouldReceive('getIdOnlyByName')
                ->once()
                ->andReturn(null);
        });

        $importCounter = new ImportCounter();
        $redirectResponse = $importCounter->validate($counterData, $importRecord);
        $this->assertEquals(4, is_countable($redirectResponse) ? count($redirectResponse) : 0);
    }
);

test('It calls addNew method to store counter details', function (): void {
    $companyId = 1;

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'type_id' => ImportTypes::COUNTERS->value,
        'created_by_id' => 1,
        'created_by_type' => ModelMapping::ADMIN->name,
    ]);

    $counterRecord = [
        'name' => 'counter-2',
        'location' => 'Store-1',
        'is_locked' => 'No',
    ];

    $this->mock(LocationQueries::class, function ($mock) use ($counterRecord, $companyId): void {
        $mock->shouldReceive('getIdByName')
            ->once()
            ->with($counterRecord['location'], $companyId);
    });

    $this->mock(CounterQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    $importCounter = new ImportCounter();
    $importCounter->save($counterRecord, $importRecord);
    $this->assertTrue(true);
});

function getCounterData(): array
{
    return [
        'name' => 'counter-1',
        'location' => 'Store-1',
        'is_locked' => 'No',
    ];
}

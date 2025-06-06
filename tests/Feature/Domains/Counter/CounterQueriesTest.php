<?php

declare(strict_types=1);

use App\Domains\Counter\CounterQueries;
use App\Domains\Counter\DataObjects\CounterData;
use App\Domains\Location\Enums\LocationTypes;
use App\Models\Company;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\Location;
use Illuminate\Support\Collection;

beforeEach(function (): void {
    $this->companyA = Company::factory()->create();

    $this->location = Location::factory()->create([
        'company_id' => $this->companyA->id,
        'name' => 'ABCD',
        'type_id' => LocationTypes::STORE->value,
    ]);

    $this->counterA = Counter::factory()->create([
        'location_id' => $this->location->id,
        'name' => 'EFG',
    ]);

    $this->counterQueries = new CounterQueries();
});

test('Counters can be searched', function (): void {
    $response = $this->counterQueries->listQuery([
        'search_text' => 'ABCD',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
        'location_ids' => null,
    ], $this->companyA->id);

    $this->assertEquals(1, $response->total());

    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('name', $this->counterA->name)
        ->toHaveKey('location.name', $this->location->name);
});

test('New counter can be added', function (): void {
    $newCounterRecord = Counter::factory()->make([
        'location_id' => $this->location,
    ])->toArray();

    unset($newCounterRecord['app_version']);
    unset($newCounterRecord['app_version_updated_at']);

    $this->counterQueries->addNew(new CounterData(...$newCounterRecord), $this->companyA->id);

    $this->assertDatabaseHas('counters', $newCounterRecord);
});

test('A counter can be fetched', function (): void {
    $response = $this->counterQueries->getById($this->counterA->id, $this->companyA->id);

    expect($response->toArray())
        ->toHaveKey('name', $this->counterA->name)
        ->toHaveKey('location_id', $this->counterA->location_id);
});

test('getAppVersionCounts method call return proper response', function (): void {
    $response = $this->counterQueries->getAppVersionCounts($this->companyA->id);

    expect($response->first()->toArray())
        ->toHaveKey('app_version', $this->counterA->app_version)
        ->toHaveKey('count', 1);
});

test('getCountersOfLocations method call and returns the counters by location ids', function (): void {
    $response = $this->counterQueries->getCountersOfLocations([$this->counterA->location_id], $this->companyA->id);

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->counterA->id)
        ->toHaveKey('name', $this->counterA->name);
});

test('A counter can be updated', function (): void {
    $newCounterRecord = Counter::factory()->make()->toArray();

    unset($newCounterRecord['app_version']);
    unset($newCounterRecord['app_version_updated_at']);

    $this->counterQueries->update(new CounterData(...$newCounterRecord), $this->counterA->id, $this->companyA->id);

    $this->assertDatabaseHas('counters', $newCounterRecord);
});

test('Set counter update id while open counter', function (): void {
    $counter = Counter::factory()->create();

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->id,
    ]);

    $this->counterQueries->setCounterUpdateId($counter, $counterUpdate->id);

    $this->assertDatabaseHas('counters', [
        'id' => $counter->id,
        'counter_update_id' => $counterUpdate->id,
    ]);
});

test('It returns counter with counter update by counter update id', function (): void {
    $counter = Counter::factory()->create();

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->id,
    ]);

    $counter->counter_update_id = $counterUpdate->id;
    $counter->save();

    $response = $this->counterQueries->getDetailsWithCounterUpdateByCounterUpdateId($counterUpdate->id);

    expect($response->toArray())
        ->toHaveKeys(
            ['id', 'counter_update_id', 'name', 'is_locked', 'counter_update.id', 'counter_update.created_at']
        );
});

test('unsetCounterUpdateId method unsets the counter update id', function (): void {
    $counter = Counter::factory()->create();

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->id,
    ]);
    $counter->counter_update_id = $counterUpdate->id;

    $this->counterQueries->unsetCounterUpdateId($counter);

    $this->assertDatabaseHas('counters', [
        'id' => $counter->id,
        'counter_update_id' => null,
    ]);
});

test('getCountByLocation method returns total counters count', function (): void {
    $response = $this->counterQueries->getCountByLocation($this->counterA->location_id);

    expect($response)->toEqual(1);
});

test('getCountByOpenCounterForLocation method returns total counters count', function (): void {
    $locationId = Location::factory()->create([
        'company_id' => $this->companyA->id,
        'type_id' => LocationTypes::STORE->value,
    ])->id;

    $counter = Counter::factory()->create([
        'location_id' => $locationId,
    ]);

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->id,
    ]);
    $counter->counter_update_id = $counterUpdate->id;
    $counter->save();

    $response = $this->counterQueries->getCountByOpenCounterForLocation($counter->location_id);

    expect($response)->toEqual(1);
});

test('getByCounterUpdateId method returns the counter by counter update id', function (): void {
    $counter = Counter::factory()->create();

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->id,
    ]);

    $counter->counter_update_id = $counterUpdate->id;
    $counter->save();

    $response = $this->counterQueries->getByCounterUpdateId($counterUpdate->id);

    expect($response)
        ->toHaveKey('id', $counter->id)
        ->toHaveKey('counter_update_id', $counter->counter_update_id);
});

test('getCounterListOfSelectedLocation method returns the location counters list', function (): void {
    $locationId = Location::factory()->create([
        'company_id' => $this->companyA->id,
        'type_id' => LocationTypes::STORE->value,
    ])->id;
    $counter = Counter::factory()->create([
        'location_id' => $locationId,
    ]);
    $response = $this->counterQueries->getCounterListOfSelectedLocation($locationId, $this->companyA->id);
    expect($response->first()->toArray())
        ->toHaveKey('id', $counter->id)
        ->toHaveKey('name', $counter->name);
});

test('existsByName method returns result as expected', function (): void {
    $response = $this->counterQueries->existsByName($this->counterA->name, $this->location->id);
    $this->assertTrue($response);

    $response = $this->counterQueries->existsByName('ABCDEFGH', $this->location->id);
    $this->assertFalse($response);
});

test('getCountersExport method returns counter as expected', function (): void {
    $response = $this->counterQueries->getCountersExport([
        'search_text' => 'ABCD',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
        'location_ids' => null,
    ], $this->companyA->id);

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->counterA->id)
        ->toHaveKey('name', $this->counterA->name);
});

test('it successfully retrieves a collection of counters by their IDs', function (): void {
    $counter = Counter::factory()->create();

    $response = $this->counterQueries->getByIds([$counter->id]);
    expect($response)->toBeInstanceOf(Collection::class);

    expect(collect($response)->first()->toArray())
        ->toHaveKey('name', $counter->name);
});

test('A counter can be updated by name', function (): void {
    $this->counterQueries->updateByName(
        [
            'name' => $this->counterA->name,
            'is_locked' => true,
            'location_id' => $this->location->id,
        ],
        $this->counterA->name,
    );

    $this->assertDatabaseHas('counters', [
        'location_id' => $this->location->id,
        'is_locked' => true,
    ]);
});

test('counterExists method returns boolean as expected', function (): void {
    $response = $this->counterQueries->counterExists('test', $this->companyA->id);
    $this->assertFalse($response);

    $response = $this->counterQueries->counterExists($this->counterA->name, $this->companyA->id);
    $this->assertTrue($response);
});

test('Get Counter name for export PDF headers', function (): void {
    $response = $this->counterQueries->getCounterNameForFilter([$this->counterA->id]);

    $this->assertIsString($response);
});

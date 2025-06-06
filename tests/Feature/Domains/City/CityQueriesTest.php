<?php

declare(strict_types=1);

use App\Domains\City\CityQueries;
use App\Domains\City\DataObjects\CityData;
use App\Models\City;
use App\Models\Company;
use App\Models\Country;
use App\Models\State;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    $this->company = Company::factory()->create();
    DB::table('countries')->insert([
        'iso2' => 'Ab',
        'name' => 'ABCD',
        'status' => true,
        'phone_code' => '1234',
        'iso3' => 'bc',
        'region' => 'south',
        'subregion' => 'south left',
    ]);

    $countryId = Country::first()->id;

    DB::table('states')->insert([
        'country_id' => $countryId,
        'name' => 'state',
    ]);
    $this->state = State::first();

    DB::table('cities')->insert([
        'country_id' => $countryId,
        'state_id' => $this->state->id,
        'name' => 'city',
        'country_code' => 'ABC',
    ]);
    $this->city = City::first();

    $this->cityQueries = resolve(CityQueries::class);
});

test('It calls the getByStateId method return states collection', function (): void {
    $response = $this->cityQueries->getByStateId($this->city->state_id);
    expect($response->first()->toArray())
        ->toHaveKey('id', $this->city->id)
        ->toHaveKey('name', $this->city->name);
});

test('It calls the existsByName method return city object', function (): void {
    $response = $this->cityQueries->existsByName($this->city->name);
    expect($response)->toBeTrue();
});

test('It calls the getIdByName method return city id', function (): void {
    $response = $this->cityQueries->getIdByName($this->city->name);
    expect($response)->toBe($this->city->id);
});

test('A city can be fetched', function (): void {
    $response = $this->cityQueries->getById($this->city->id);
    expect($response->toArray())
        ->toHaveKey('id', $this->city->id)
        ->toHaveKey('name', $this->city->name);
});

test('getCityExport method returns city as expected', function (): void {
    $response = $this->cityQueries->getCityExport([
        'search_text' => 'city',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ]);

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->city->id)
        ->toHaveKey('name', $this->city->name);
});

test('listQuery method returns city as expected', function (): void {
    $response = $this->cityQueries->listQuery([
        'search_text' => 'city',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ]);

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->city->id)
        ->toHaveKey('name', $this->city->name);
});

test('New city can be added', function (): void {
    $cityData = City::factory()->make([
        'country_code' => 'ab',
        'name' => 'test',
        'country_id' => 1,
        'state_id' => 1,
    ])->toArray();

    $this->cityQueries->addNew(new CityData(...$cityData));

    $this->assertDatabaseHas('cities', $cityData);
});

test('A city can be updated', function (): void {
    $this->cityQueries->update(new CityData(1, 1, 'test'), $this->city->id);

    $this->assertDatabaseHas('cities', [
        'name' => 'test',
    ]);
});

test('getAllCities method returns all cities', function (): void {
    $response = $this->cityQueries->getAllCities();

    expect($response->first())
        ->toHaveKey('id', $this->city->id)
        ->toHaveKey('name', $this->city->name);
});

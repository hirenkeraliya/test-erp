<?php

declare(strict_types=1);

use App\Domains\State\DataObjects\StateData;
use App\Domains\State\StateQueries;
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

    $this->stateQueries = resolve(StateQueries::class);
});

test('It calls the getByCountryId method return states collection', function (): void {
    $response = $this->stateQueries->getByCountryId($this->state->country_id);
    expect($response->first()->toArray())
        ->toHaveKey('id', $this->state->id)
        ->toHaveKey('name', $this->state->name);
});

test('It calls the existsByName method return state object', function (): void {
    $response = $this->stateQueries->existsByName($this->state->name);
    expect($response)->toBeTrue();
});

test('It calls the getIdByName method return state id', function (): void {
    $response = $this->stateQueries->getIdByName($this->state->name);
    expect($response)->toBe($this->state->id);
});

test('A state can be fetched', function (): void {
    $response = $this->stateQueries->getById($this->state->id);
    expect($response->toArray())
        ->toHaveKey('id', $this->state->id)
        ->toHaveKey('name', $this->state->name);
});

test('getStateExport method returns state as expected', function (): void {
    $response = $this->stateQueries->getStateExport([
        'search_text' => 'state',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ]);

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->state->id)
        ->toHaveKey('name', $this->state->name);
});

test('listQuery method returns state as expected', function (): void {
    $response = $this->stateQueries->listQuery([
        'search_text' => 'state',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ]);

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->state->id)
        ->toHaveKey('name', $this->state->name);
});

test('New state can be added', function (): void {
    $stateData = State::factory()->make([
        'country_code' => 'ab',
        'country_id' => 1,
    ])->toArray();

    $this->stateQueries->addNew(new StateData(...$stateData));

    $this->assertDatabaseHas('states', $stateData);
});

test('A state can be updated', function (): void {
    $this->stateQueries->update(new StateData(1, 'test'), $this->state->id);
    $this->assertDatabaseHas('states', [
        'name' => 'test',
    ]);
});

test('getAllStates method returns all country', function (): void {
    $state = State::factory()->make([
        'country_code' => 'ab',
        'country_id' => 1,
    ]);

    $response = $this->stateQueries->getAllStates();

    expect($response->first())
        ->toHaveKey('id', $state->id)
        ->toHaveKey('name', $state->name);
});

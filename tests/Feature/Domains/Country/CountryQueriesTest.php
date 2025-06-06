<?php

declare(strict_types=1);

use App\Domains\Country\CountryQueries;
use App\Domains\Country\DataObjects\CountryData;
use App\Models\Country;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    $this->countryQueries = new CountryQueries();
});

test(
    'getList method returns the Country list',
    function (): void {
        DB::table('countries')->insert([
            'iso2' => 'Ab',
            'name' => 'ABCD',
            'status' => true,
            'phone_code' => '1234',
            'iso3' => 'bc',
            'region' => 'south',
            'subregion' => 'south left',
        ]);

        $country = Country::first();

        $response = $this->countryQueries->getList();
        expect($response->first())
            ->toHaveKey('id', $country->id)
            ->toHaveKey('name', $country->name);
    }
);

test('getIdByName method returns the country details', function (): void {
    DB::table('countries')->insert([
        'iso2' => 'Ab',
        'name' => 'Afghanistan',
        'status' => true,
        'phone_code' => '1234',
        'iso3' => 'bc',
        'region' => 'south',
        'subregion' => 'south left',
    ]);

    $country = Country::firstWhere('name', 'Afghanistan');
    $response = $this->countryQueries->getIdByName('Afghanistan');
    $this->assertEquals($country->id, $response);
});

test('existsByName method returns result as expected', function (): void {
    DB::table('countries')->insert([
        'iso2' => 'Ab',
        'name' => 'India',
        'status' => true,
        'phone_code' => '1234',
        'iso3' => 'bc',
        'region' => 'south',
        'subregion' => 'south left',
    ]);

    $response = $this->countryQueries->existsByName('India');
    $this->assertTrue($response);

    $response = $this->countryQueries->existsByName('ABCDE');
    $this->assertFalse($response);
});

test(
    'getCountryForEcommerce method returns the Country list',
    function (): void {
        DB::table('countries')->insert([
            'iso2' => 'Ab',
            'name' => 'ABCD',
            'status' => true,
            'phone_code' => '1234',
            'iso3' => 'bc',
            'region' => 'south',
            'subregion' => 'south left',
        ]);

        $country = Country::first();

        $filterData = [
            'per_page' => 10,
            'sort_by' => 'id',
            'sort_direction' => 'asc',
        ];

        $response = $this->countryQueries->getCountryForEcommerce($filterData);
        expect($response->first())
            ->toHaveKey('id', $country->id)
            ->toHaveKey('name', $country->name);
    }
);

test('getCountryExport method returns country as expected', function (): void {
    DB::table('countries')->insert([
        'iso2' => 'Ab',
        'name' => 'ABCD',
        'status' => true,
        'phone_code' => '1234',
        'iso3' => 'bc',
        'region' => 'south',
        'subregion' => 'south left',
    ]);

    $country = Country::first();

    $response = $this->countryQueries->getCountryExport([
        'search_text' => 'ABCD',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ]);

    expect($response->first())
        ->toHaveKey('id', $country->id)
        ->toHaveKey('name', $country->name);
});

test('listQuery method returns country as expected', function (): void {
    DB::table('countries')->insert([
        'iso2' => 'Ab',
        'name' => 'ABCD',
        'status' => true,
        'phone_code' => '1234',
        'iso3' => 'bc',
        'region' => 'south',
        'subregion' => 'south left',
    ]);

    $country = Country::first();

    $response = $this->countryQueries->listQuery([
        'search_text' => 'ABCD',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ]);

    expect($response->first())
        ->toHaveKey('id', $country->id)
        ->toHaveKey('name', $country->name);
});

test('New country can be added', function (): void {
    $countryData = Country::factory()->make([
        'phone_code' => 'abc',
    ])->toArray();

    unset($countryData['status']);

    $this->countryQueries->addNew(new CountryData(...$countryData));

    $this->assertDatabaseHas('countries', $countryData);
});

test('A country can be fetched', function (): void {
    $country = Country::factory()->create();

    $response = $this->countryQueries->getById($country->id);

    expect($response->toArray())
        ->toHaveKey('id', $country->id)
        ->toHaveKey('iso2', $country->iso2)
        ->toHaveKey('name', $country->name)
        ->toHaveKey('phone_code', $country->phone_code)
        ->toHaveKey('iso3', $country->iso3)
        ->toHaveKey('region', $country->region)
        ->toHaveKey('subregion', $country->subregion);
});

test('A country can be updated', function (): void {
    $countryId = Country::factory()->create()->id;
    $countryData = Country::factory()->make([
        'phone_code' => 'abcde',
    ])->toArray();

    unset($countryData['status']);

    $this->countryQueries->update(new CountryData(...$countryData), $countryId);

    $this->assertDatabaseHas('countries', $countryData);
});

test('getAllCountries method returns all country', function (): void {
    DB::table('countries')->insert([
        'iso2' => 'Ab',
        'name' => $name = 'ABCD',
        'status' => true,
        'phone_code' => '1234',
        'iso3' => 'bc',
        'region' => 'south',
        'subregion' => 'south left',
    ]);

    $country = Country::first();

    $response = $this->countryQueries->getAllCountries();

    expect($response->first())
        ->toHaveKey('id', $country->id)
        ->toHaveKey('name', $country->name);
});

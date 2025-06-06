<?php

declare(strict_types=1);

use App\Domains\Country\CountryQueries;
use App\Domains\Country\DataObjects\CountryData;
use App\Http\Controllers\Admin\CountryController;
use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test('It calls the List query method of the country queries class and returns proper response', function (): void {
    $requestParameter = [
        'search_text' => 'abc',
        'sort_by' => 'name',
        'sort_direction' => 'desc',
        'per_page' => 1,
    ];

    $countryQueries = $this->mock(CountryQueries::class, function ($mock) use ($requestParameter): void {
        $mock->shouldReceive('listQuery')
            ->once()
            ->with($requestParameter)
            ->andReturn(new LengthAwarePaginator([], 20, 15));
    });

    $countryController = new CountryController($countryQueries);

    $response = $countryController->fetchCountries(new Request($requestParameter));

    $this->assertEquals(20, $response['total_records']);
    $this->assertEquals(collect([]), $response['data']);
});

test('It calls the addNew method of the country queries class and returns proper response', function (): void {
    $countryData = Country::factory()->make([
        'phone_code' => 'abc',
    ])->toArray();

    unset($countryData['status']);

    $countryData = new CountryData(...$countryData);

    $countryQueries = $this->mock(CountryQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    $countryController = new CountryController($countryQueries);
    $redirectResponse = $countryController->store($countryData);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Country added successfully.', $redirectResponse->getSession()->all()['success']);
});

test('It calls the update method of the country queries class and returns proper response', function (): void {
    $countryData = Country::factory()->make([
        'phone_code' => 'abcde',
    ])->toArray();

    unset($countryData['status']);

    $countryData = new CountryData(...$countryData);

    $countryQueries = $this->mock(CountryQueries::class, function ($mock): void {
        $mock->shouldReceive('update')
            ->once();
    });

    $countryController = new CountryController($countryQueries);
    $redirectResponse = $countryController->update($countryData, 1);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Country updated successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/get-countries', $redirectResponse->getTargetUrl());
});

test('It calls the exportCountries method and returns a proper response', function (): void {
    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 'test',
    ];

    $countryQueries = $this->mock(CountryQueries::class, function ($mock) use ($requestParameter): void {
        $mock->shouldReceive('getCountryExport')
            ->once()
            ->with($requestParameter)
            ->andReturn(collect(new Country()));
    });

    $countryController = new CountryController($countryQueries);

    $response = $countryController->exportCountries('filename.csv', new Request($requestParameter));

    $this->assertEquals(200, $response->getStatusCode());
    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});

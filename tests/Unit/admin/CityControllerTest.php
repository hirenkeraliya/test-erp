<?php

declare(strict_types=1);

use App\Domains\City\CityQueries;
use App\Domains\City\DataObjects\CityData;
use App\Http\Controllers\Admin\CityController;
use App\Models\City;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test(
    'It calls the getCitiesByStateId method of the cities queries class and returns proper response',
    function (): void {
        setCompanyIdInSession(1);
        $this->mock(CityQueries::class, function ($mock): void {
            $mock->shouldReceive('getByStateId')
                ->once()
                ->andReturn(collect([]));
        });
        $cityController = resolve(CityController::class);
        $response = $cityController->getCitiesByStateId(1);

        expect($response)
            ->toHaveKey('cities');
    }
);

test('It calls the List query method of the city queries class and returns proper response', function (): void {
    $requestParameter = [
        'search_text' => 'abc',
        'sort_by' => 'name',
        'sort_direction' => 'desc',
        'per_page' => 1,
    ];

    $this->mock(CityQueries::class, function ($mock) use ($requestParameter): void {
        $mock->shouldReceive('listQuery')
            ->once()
            ->with($requestParameter)
            ->andReturn(new LengthAwarePaginator([], 20, 15));
    });

    $cityController = resolve(CityController::class);
    $response = $cityController->fetchCities(new Request($requestParameter));

    $this->assertEquals(20, $response['total_records']);
    $this->assertEquals(collect([]), $response['data']);
});

test('It calls the addNew method of the state queries class and returns proper response', function (): void {
    $cityData = new CityData(1, 1, 'test');

    $this->mock(CityQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    $cityController = resolve(CityController::class);
    $redirectResponse = $cityController->store($cityData);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('City added successfully.', $redirectResponse->getSession()->all()['success']);
});

test('It calls the update method of the state queries class and returns proper response', function (): void {
    $cityData = new CityData(1, 1, 'test');

    $this->mock(CityQueries::class, function ($mock): void {
        $mock->shouldReceive('update')
            ->once();
    });

    $cityController = resolve(CityController::class);
    $redirectResponse = $cityController->update($cityData, 1);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('City updated successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/get-cities', $redirectResponse->getTargetUrl());
});

test('It calls the exportStates method and returns a proper response', function (): void {
    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 'test',
    ];

    $this->mock(CityQueries::class, function ($mock) use ($requestParameter): void {
        $mock->shouldReceive('getCityExport')
            ->once()
            ->with($requestParameter)
            ->andReturn(collect(new City()));
    });

    $cityController = resolve(CityController::class);
    $response = $cityController->exportCities('filename.csv', new Request($requestParameter));

    $this->assertEquals(200, $response->getStatusCode());
    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});

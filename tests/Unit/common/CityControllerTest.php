<?php

declare(strict_types=1);

use App\Domains\City\CityQueries;
use App\Http\Controllers\Api\Common\CityController;

test('getAllCities returns array with records', function (): void {
    $this->mock(CityQueries::class, function ($mock): void {
        $mock->shouldReceive('getAllCities')
            ->once()
            ->andReturn(collect());
    });

    $controller = new CityController();
    $response = $controller->getAllCities();

    expect($response)->toBeArray();
    expect($response)->toHaveKey('cities');
});

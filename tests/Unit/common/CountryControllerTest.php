<?php

declare(strict_types=1);

use App\Domains\Country\CountryQueries;
use App\Http\Controllers\Api\Common\CountryController;

test('getAllCountries returns array with records', function (): void {
    $this->mock(CountryQueries::class, function ($mock): void {
        $mock->shouldReceive('getAllCountries')
            ->once()
            ->andReturn(collect());
    });

    $controller = new CountryController();
    $response = $controller->getAllCountries();

    expect($response)->toBeArray();
    expect($response)->toHaveKey('countries');
});

<?php

declare(strict_types=1);

use App\Domains\Country\CountryQueries;
use App\Http\Controllers\Api\SaleChannel\Country\CountryController;
use Illuminate\Pagination\LengthAwarePaginator;

it('returns a list of countries.', function (): void {
    $filterData = [
        'per_page' => 1,
        'page' => 1,
    ];

    $countryQueries = $this->mock(CountryQueries::class, function ($mock): void {
        $mock->shouldReceive('getCountryForEcommerce')
            ->once()
            ->andReturn(new LengthAwarePaginator([], 10, 10));
    });

    [$saleChannel, $request] = setRequestUserForSaleChannel($filterData);

    $countryController = new CountryController($countryQueries);
    $response = $countryController->getCountryList($request);

    $this->assertEquals(10, $response['total_records']);
    $this->assertEquals(collect([]), $response['countries']->resource);
});

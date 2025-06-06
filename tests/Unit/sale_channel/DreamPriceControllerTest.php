<?php

declare(strict_types=1);

use App\Domains\DreamPrice\DreamPriceQueries;
use App\Domains\DreamPriceProduct\DreamPriceProductQueries;
use App\Http\Controllers\Api\SaleChannel\DreamPrice\DreamPriceController;
use Illuminate\Pagination\LengthAwarePaginator;

it('returns a list of dream price', function (): void {
    $filterData = [
        'per_page' => 1,
        'page' => 1,
        'sort_by' => 'id',
        'sort_direction' => 'desc',
    ];

    $dreamPriceQueries = $this->mock(DreamPriceQueries::class, function ($mock): void {
        $mock->shouldReceive('getListWithProductsInEcommerce')
            ->once()
            ->andReturn(new LengthAwarePaginator([], 10, 10));
    });

    [$saleChannel, $request] = setRequestUserForSaleChannel($filterData);

    $dreamPriceController = new DreamPriceController($dreamPriceQueries);
    $response = $dreamPriceController->getList($request);

    $this->assertEquals(10, $response['total_records']);
    $this->assertEquals(collect([]), $response['dream_prices']->resource);
});

it('returns a list of dream price product list id', function (): void {
    $filterData = [
        'per_page' => 1,
        'page' => 1,
        'sort_by' => 'id',
        'sort_direction' => 'desc',
        'dream_price_id' => 1,
    ];

    $this->mock(DreamPriceProductQueries::class, function ($mock): void {
        $mock->shouldReceive('getDreamPriceProduct')
            ->once()
            ->andReturn(new LengthAwarePaginator([], 10, 10));
    });

    [$saleChannel, $request] = setRequestUserForSaleChannel($filterData);

    $dreamPriceQueries = resolve(DreamPriceQueries::class);
    $dreamPriceController = new DreamPriceController($dreamPriceQueries);
    $response = $dreamPriceController->getDreamPriceProductList($request);

    $this->assertEquals(10, $response['total_records']);
    $this->assertEquals(collect([]), $response['data']);
});

<?php

declare(strict_types=1);

use App\Domains\ProductCollection\ProductCollectionQueries;
use App\Domains\ProductCollectionProduct\ProductCollectionProductQueries;
use App\Http\Controllers\Api\SaleChannel\ProductCollection\ProductCollectionController;
use Illuminate\Pagination\LengthAwarePaginator;

it('returns a list of product collection by companyId.', function (): void {
    $filterData = [
        'per_page' => 1,
        'page' => 1,
        'sort_by' => 'id',
        'sort_direction' => 'desc',
    ];

    $productCollectionQueries = $this->mock(ProductCollectionQueries::class, function ($mock): void {
        $mock->shouldReceive('getPaginatedProductCollectionsForEcommerce')
            ->once()
            ->andReturn(new LengthAwarePaginator([], 10, 10));
    });

    [$saleChannel, $request] = setRequestUserForSaleChannel($filterData);

    $productCollectionController = new ProductCollectionController($productCollectionQueries);
    $response = $productCollectionController->getPaginatedList($request);

    $this->assertEquals(10, $response['total_records']);
    $this->assertEquals(collect([]), $response['product_collections']->resource);
});

it('returns a list of product collection product list id', function (): void {
    $filterData = [
        'per_page' => 1,
        'page' => 1,
        'sort_by' => 'id',
        'sort_direction' => 'desc',
        'product_collection_id' => 1,
    ];

    $this->mock(ProductCollectionProductQueries::class, function ($mock): void {
        $mock->shouldReceive('getProductCollectionProducts')
            ->once()
            ->andReturn(new LengthAwarePaginator([], 10, 10));
    });

    [$saleChannel, $request] = setRequestUserForSaleChannel($filterData);

    $productCollectionQueries = resolve(ProductCollectionQueries::class);
    $productCollectionController = new ProductCollectionController($productCollectionQueries);
    $response = $productCollectionController->getProductIds($request);

    $this->assertEquals(10, $response['total_records']);
    $this->assertEquals(collect([]), $response['product_ids']);
});

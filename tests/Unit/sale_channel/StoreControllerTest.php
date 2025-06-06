<?php

declare(strict_types=1);

use App\Domains\Location\LocationQueries;
use App\Http\Controllers\Api\SaleChannel\Store\StoreController;
use Illuminate\Pagination\LengthAwarePaginator;

it('returns a list of stores by companyId.', function (): void {
    $filterData = [
        'per_page' => 1,
        'page' => 1,
        'after_updated_at' => null,
    ];

    $locationQueries = $this->mock(LocationQueries::class, function ($mock): void {
        $mock->shouldReceive('getStoresByCompanyIdForEcommerce')
            ->once()
            ->andReturn(new LengthAwarePaginator([], 10, 10));
    });

    [$saleChannel, $request] = setRequestUserForSaleChannel($filterData);

    $storeController = new StoreController($locationQueries);
    $response = $storeController->getStoreList($request);

    $this->assertEquals(10, $response['total_records']);
    $this->assertEquals(collect([]), $response['stores']->resource);
});

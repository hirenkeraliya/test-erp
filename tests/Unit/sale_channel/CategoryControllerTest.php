<?php

declare(strict_types=1);

use App\Domains\Category\CategoryQueries;
use App\Http\Controllers\Api\SaleChannel\Category\CategoryController;
use Illuminate\Pagination\LengthAwarePaginator;

test('it returns a list of categories by companyId.', function (): void {
    $filterData = [
        'per_page' => 1,
        'page' => 1,
        'after_updated_at' => null,
    ];

    $categoryQueries = $this->mock(CategoryQueries::class, function ($mock): void {
        $mock->shouldReceive('getCategoriesByCompanyId')
            ->once()
            ->andReturn(new LengthAwarePaginator([], 10, 10));
    });

    [$saleChannel, $request] = setRequestUserForSaleChannel($filterData);

    $categoryController = new CategoryController($categoryQueries);
    $response = $categoryController->getCategoriesList($request);

    $this->assertEquals(10, $response['total_records']);
    $this->assertEquals(collect([]), $response['categories']->resource);
});

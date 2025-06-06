<?php

declare(strict_types=1);

use App\Domains\ColorGroup\ColorGroupQueries;
use App\Http\Controllers\Api\SaleChannel\ColorGroup\ColorGroupController;
use Illuminate\Pagination\LengthAwarePaginator;

it('returns a list of color groups by companyId.', function (): void {
    $filterData = [
        'per_page' => 1,
        'page' => 1,
        'after_updated_at' => null,
        'search_text' => '',
    ];

    $colorGroupQueries = $this->mock(ColorGroupQueries::class, function ($mock): void {
        $mock->shouldReceive('getColorGroupsByCompanyId')
            ->once()
            ->andReturn(new LengthAwarePaginator([], 10, 10));
    });

    [$saleChannel, $request] = setRequestUserForSaleChannel($filterData);

    $colorGroupController = new ColorGroupController($colorGroupQueries);
    $response = $colorGroupController->getColorGroupList($request);

    $this->assertEquals(10, $response['total_records']);
    $this->assertEquals(collect([]), $response['colorGroups']->resource);
});

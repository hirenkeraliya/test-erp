<?php

declare(strict_types=1);

use App\Domains\MasterProduct\MasterProductQueries;
use App\Http\Controllers\Api\Integration\MasterProductController;
use App\Models\Integration;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

test('It calls the getAllByCompanyId method of the MasterProductQueries class', function (): void {
    $integration = Integration::factory()->make([
        'id' => 1,
        'company_id' => 1,
    ]);

    $request = new Request();
    $request->setUserResolver(fn (): Integration => $integration);

    $productData = [
        [
            'id' => 1,
            'name' => 'Test Product',
            'company_id' => 1,
            'brand_id' => 1,
        ],
    ];

    $this->mock(MasterProductQueries::class, function ($mock) use ($productData): void {
        $mock->shouldReceive('getAllByCompanyId')
            ->once()
            ->andReturn(new LengthAwarePaginator($productData, 10, 5));
    });

    $masterProductController = new MasterProductController();
    $response = $masterProductController->getAllMasterProducts($request);

    expect($response['products']->first())->toHaveKeys(['id', 'name', 'company_id', 'brand_id']);
});

test(
    'It calls the getCompanyActiveRegularMasterProductCount method of the MasterProductQueries class',
    function (): void {
        $integration = Integration::factory()->make([
            'id' => 1,
            'company_id' => 1,
        ]);

        $request = new Request();
        $request->setUserResolver(fn (): Integration => $integration);

        $this->mock(MasterProductQueries::class, function ($mock): void {
            $mock->shouldReceive('getCompanyActiveRegularMasterProductCount')
                ->once()
                ->andReturn(1);
        });

        $masterProductController = new MasterProductController();
        $response = $masterProductController->getAllProductsCount($request);

        expect($response['total_products'])->toBe(1);
    }
);

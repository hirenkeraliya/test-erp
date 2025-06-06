<?php

declare(strict_types=1);

use App\Domains\Brand\BrandQueries;
use App\Http\Controllers\Api\Integration\BrandController;
use App\Models\Integration;
use Illuminate\Http\Request;

test('It calls the getAllBrands method of the brandQueries class', function (): void {
    $integration = Integration::factory()->make([
        'id' => 1,
        'company_id' => 1,
    ]);

    $request = new Request();
    $request->setUserResolver(fn (): Integration => $integration);

    $brandsData = [
        [
            'id' => 100,
            'name' => 'Test Brand',
            'company_id' => 1,
        ],
    ];

    $this->mock(BrandQueries::class, function ($mock) use ($brandsData): void {
        $mock->shouldReceive('getAllByCompanyId')
            ->once()
            ->andReturn(collect($brandsData));
    });

    $brandController = new BrandController();
    $response = $brandController->getAllBrands($request);

    expect($response['brands']->first())->toHaveKeys(['id', 'name']);
});

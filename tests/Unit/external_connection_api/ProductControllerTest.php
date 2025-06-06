<?php

declare(strict_types=1);

use App\Domains\Product\ProductQueries;
use App\Http\Controllers\Api\ExternalConnection\ProductController;
use Illuminate\Http\Request;

test(
    'getProductsByUpc method calls the getProductsByUpcForInterCompany method of ProductQueries calls',
    function (): void {
        $filterData = [
            'token' => '1234',
            'upc' => ['2341'],
            'company_id' => 1,
        ];

        $request = new Request($filterData);

        $return = collect([]);
        $this->mock(ProductQueries::class, function ($mock) use ($return, $request): void {
            $mock->shouldReceive('getProductsByUpcForInterCompany')
            ->once()
            ->with($request->upc, 1)
            ->andReturn($return);
        });
        $productController = new ProductController();
        $response = $productController->getProductsByUpc($request);
        expect($response['products'])->toBeCollection();
    }
);

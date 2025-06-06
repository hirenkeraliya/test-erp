<?php

declare(strict_types=1);

use App\Domains\Currency\CurrencyQueries;
use App\Domains\Product\DataObjects\ProductDataForIntegration;
use App\Domains\Product\Enums\ProductTypes;
use App\Domains\Product\ProductQueries;
use App\Domains\Product\Resources\ProductDetailsForIntegrationResource;
use App\Http\Controllers\Api\Integration\ProductController;
use App\Models\Currency;
use App\Models\Integration;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

test('It calls the store method of the productQueries class', function (): void {
    $integration = Integration::factory()->make([
        'id' => 1,
        'company_id' => 1,
    ]);
    $request = new Request();
    $request->setUserResolver(fn (): Integration => $integration);

    $productData = new ProductDataForIntegration(
        'Test Product',
        1,
        'testUniqueUPC',
        ProductTypes::REGULAR_PRODUCT->value,
        [1],
        500,
        'Test Article Number',
        100,
    );

    DB::shouldReceive('beginTransaction')->once();
    DB::shouldReceive('commit')->once();
    Log::shouldReceive('error')->never();

    $this->mock(ProductQueries::class, function ($mock) use ($productData, $integration): void {
        $mock->shouldReceive('addNewProductForIntegration')
            ->once()
            ->with($productData, $integration->getCompanyId(), $integration);
    });

    $productController = new ProductController();
    $response = $productController->store($productData, $request);
    expect($response['productDetails'])->toBeInstanceOf(ProductDetailsForIntegrationResource::class);
});

test('It handles exceptions in the store method', function (): void {
    $integration = Integration::factory()->make([
        'id' => 1,
        'company_id' => 1,
    ]);
    $request = new Request();
    $request->setUserResolver(fn (): Integration => $integration);

    $productData = new ProductDataForIntegration(
        'Test Product',
        1,
        'testUniqueUPC',
        ProductTypes::REGULAR_PRODUCT->value,
        [1],
        500,
        'Test Article Number',
        100,
    );

    DB::shouldReceive('beginTransaction')->once();
    DB::shouldReceive('rollBack')->once();
    Log::shouldReceive('error')->once();

    $this->mock(ProductQueries::class, function ($mock): void {
        $mock->shouldReceive('addNewProductForIntegration')
            ->once()
            ->andThrow(new Exception('Test exception'));
    });

    $productController = new ProductController();

    $this->expectExceptionMessage('An error occurred. Please try again.');
    $productController->store($productData, $request);
});

test('It calls the getAllByCompanyId method of the ProductQueries class', function (): void {
    $integration = Integration::factory()->make([
        'id' => 1,
        'company_id' => 1,
    ]);

    $request = new Request();
    $request->setUserResolver(fn (): Integration => $integration);

    $currency = Currency::factory()->make([
        'country_id' => 1,
        'code' => 'USD',
    ]);

    $productData = collect([
        (object) [
            'id' => 1,
            'name' => 'Test Product',
            'company_id' => 1,
            'brand_id' => 1,
            'currency_code' => 'USD',
        ],
    ]);

    $this->mock(ProductQueries::class, function ($mock) use ($productData): void {
        $mock->shouldReceive('getAllByCompanyId')
            ->once()
            ->andReturn(new LengthAwarePaginator($productData, 10, 5));
    });

    $this->mock(CurrencyQueries::class, function ($mock) use ($currency): void {
        $mock->shouldReceive('getByCompanyId')
            ->once()
            ->andReturn($currency);
    });

    $productController = new ProductController();
    $response = $productController->getAllProducts($request);

    expect($response['product_variants']->first())->toHaveKeys(['id', 'name', 'company_id', 'brand_id']);
});

test('It calls the getCompanyActiveRegularProductCount method of the MasterProductQueries class', function (): void {
    $integration = Integration::factory()->make([
        'id' => 1,
        'company_id' => 1,
    ]);

    $request = new Request();
    $request->setUserResolver(fn (): Integration => $integration);

    $this->mock(ProductQueries::class, function ($mock): void {
        $mock->shouldReceive('getCompanyActiveRegularProductCount')
            ->once()
            ->andReturn(1);
    });

    $productController = new ProductController();
    $response = $productController->getAllProductVariantsCount($request);

    expect($response['total_product_variants'])->toBe(1);
});

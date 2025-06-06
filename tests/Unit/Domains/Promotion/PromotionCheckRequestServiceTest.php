<?php

declare(strict_types=1);

use App\Domains\Category\CategoryQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\Promotion\Services\PromotionCheckRequestService;
use App\Exceptions\RedirectWithErrorException;
use App\Models\Product;

test('validateStoreIds method throws exception when store ids does not match with company', function (): void {
    $companyId = 1;
    setCompanyIdInSession($companyId);

    $this->mock(LocationQueries::class, function ($mock) use ($companyId): void {
        $mock->shouldReceive('doAllStoresExist')
            ->once()
            ->with($companyId, [1])
            ->andReturn(false);
    });

    $promotionCheckRequestService = new PromotionCheckRequestService();

    $response = $promotionCheckRequestService->validateLocationIds(1, [1]);

    expect($response)->toBeEmpty();
})->throws(RedirectWithErrorException::class);

test('validateCategoryIds method throws exception when category ids does not match with company', function (): void {
    $companyId = 1;
    setCompanyIdInSession($companyId);

    $this->mock(CategoryQueries::class, function ($mock) use ($companyId): void {
        $mock->shouldReceive('doAllCategoriesExist')
            ->once()
            ->with($companyId, [1])
            ->andReturn(false);
    });

    $promotionCheckRequestService = new PromotionCheckRequestService();

    $response = $promotionCheckRequestService->validateCategoryIds(1, [1]);

    expect($response)->toBeEmpty();
})->throws(RedirectWithErrorException::class);

test(
    'validateRegularProductIds method throws exception when product ids does not match with company',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession($companyId);

        $this->mock(ProductQueries::class, function ($mock) use ($companyId): void {
            $mock->shouldReceive('doAllProductsExist')
                ->once()
                ->with($companyId, [1])
                ->andReturn(false);
        });

        $promotionCheckRequestService = new PromotionCheckRequestService();

        $response = $promotionCheckRequestService->validateRegularProductIds(1, [1]);

        expect($response)->toBeEmpty();
    }
)->throws(RedirectWithErrorException::class);

test(
    'validateRegularProductPrice method throws exception when all product price not same',
    function (): void {
        $products = [];
        $companyId = 1;
        setCompanyIdInSession($companyId);

        $products[] = Product::factory()->make([
            'id' => 1,
            'name' => 'Product 1',
            'company_id' => 1,
            'unit_of_measure_id' => null,
            'season_id' => 1,
            'department_id' => 1,
            'color_id' => 1,
            'size_id' => 1,
            'brand_id' => 1,
            'style_id' => 1,
            'upc' => 'abd123',
            'has_batch' => false,
            'type_id' => 1,
            'retail_price' => 10.20,
        ]);

        $products[] = Product::factory()->make([
            'id' => 1,
            'name' => 'Product 1',
            'company_id' => 1,
            'unit_of_measure_id' => null,
            'season_id' => 1,
            'department_id' => 1,
            'color_id' => 1,
            'size_id' => 1,
            'brand_id' => 1,
            'style_id' => 1,
            'upc' => 'abd123',
            'has_batch' => false,
            'type_id' => 1,
            'retail_price' => 10.40,
        ]);

        $this->mock(ProductQueries::class, function ($mock) use ($companyId, $products): void {
            $mock->shouldReceive('getRetailPriceByIds')
                ->once()
                ->with($companyId, [1])
                ->andReturn(collect($products));
        });

        $promotionCheckRequestService = new PromotionCheckRequestService();

        $response = $promotionCheckRequestService->validateRegularProductPrice(1, [1]);

        expect($response)->toBeEmpty();
    }
)->throws(RedirectWithErrorException::class);

test(
    'validateRegularProductPrice method return null when all product price same',
    function (): void {
        $products = [];
        $companyId = 1;
        setCompanyIdInSession($companyId);

        $products[] = Product::factory()->make([
            'id' => 1,
            'name' => 'Product 1',
            'company_id' => 1,
            'unit_of_measure_id' => null,
            'season_id' => 1,
            'department_id' => 1,
            'color_id' => 1,
            'size_id' => 1,
            'brand_id' => 1,
            'style_id' => 1,
            'upc' => 'abd123',
            'has_batch' => false,
            'type_id' => 1,
            'retail_price' => 10.20,
        ]);

        $products[] = Product::factory()->make([
            'id' => 1,
            'name' => 'Product 1',
            'company_id' => 1,
            'unit_of_measure_id' => null,
            'season_id' => 1,
            'department_id' => 1,
            'color_id' => 1,
            'size_id' => 1,
            'brand_id' => 1,
            'style_id' => 1,
            'upc' => 'abd123',
            'has_batch' => false,
            'type_id' => 1,
            'retail_price' => 10.20,
        ]);

        $this->mock(ProductQueries::class, function ($mock) use ($companyId, $products): void {
            $mock->shouldReceive('getRetailPriceByIds')
                ->once()
                ->with($companyId, [1])
                ->andReturn(collect($products));
        });

        $promotionCheckRequestService = new PromotionCheckRequestService();

        $response = $promotionCheckRequestService->validateRegularProductPrice(1, [1]);

        expect($response)->toBeNull();
    }
);

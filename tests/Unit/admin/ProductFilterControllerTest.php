<?php

declare(strict_types=1);

use App\Domains\Product\ProductQueries;
use App\Http\Controllers\Admin\ProductFilterController;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

test(
    'It calls the getActiveFilteredProducts method of the ProductQueries class and returns the proper response',
    function (): void {
        $companyId = 1;

        setCompanyIdInSession($companyId);

        $filterData = [
            'search_text' => 'ab',
            'number_of_records' => 5,
        ];

        $productQueries = $this->mock(ProductQueries::class, function ($mock): void {
            $mock->shouldReceive('getActiveFilteredProducts')
            ->once()
            ->andReturn(new Collection());
        });

        $productFilterController = new ProductFilterController($productQueries);

        $response = $productFilterController->getFilteredProducts(new Request($filterData));

        expect($response)->toBeArray();
    }
);

test(
    'It calls the getActiveInventoryProductsFilteredByNameBrandAndCategory method of the ProductQueries class and returns the proper response',
    function (): void {
        $companyId = 1;

        setCompanyIdInSession($companyId);

        $filterData = [
            'search_text' => null,
            'category_id' => null,
            'brand_id' => null,
            'has_inventory' => false,
            'location_id' => null,
        ];

        $product = commonGetProductDetails();

        $productQueries = $this->mock(ProductQueries::class, function ($mock) use ($product): void {
            $mock->shouldReceive('getActiveInventoryProductsFilteredByNameBrandAndCategory')
                ->once()
                ->andReturn(collect([$product]));
        });

        $productFilterController = new ProductFilterController($productQueries);

        $response = $productFilterController->getFilteredInventoryProductsList(new Request($filterData));

        expect($response['products'][0])
            ->toHaveKeys(['name', 'color_id', 'brand_id']);
    }
);

test(
    'It calls the getActiveProductWithBasicColumnsById method of the ProductQueries class and returns proper response',
    function (): void {
        $companyId = 1;

        setCompanyIdInSession($companyId);

        $productQueries = $this->mock(ProductQueries::class, function ($mock): void {
            $mock->shouldReceive('getActiveProductWithBasicColumnsById')
            ->once()
            ->andReturn(new Product());
        });

        $productFilterController = new ProductFilterController($productQueries);

        $response = $productFilterController->getProduct(1);

        expect($response['product'])->toBeInstanceOf(Product::class);
    }
);

test(
    'It calls the getActiveProductsFilteredByNameBrandAndCategory method of the product queries class and returns proper response',
    function (): void {
        $companyId = 1;

        setCompanyIdInSession($companyId);

        $filterData = [
            'search_text' => 'ab',
            'category_id' => 1,
            'brand_id' => 1,
        ];

        $productQueries = $this->mock(ProductQueries::class, function ($mock): void {
            $mock->shouldReceive('getActiveProductsFilteredByNameBrandAndCategory')
                ->once()
                ->andReturn(new Collection());
        });

        $productFilterController = new ProductFilterController($productQueries);

        $response = $productFilterController->getFilteredProductsList(new Request($filterData));

        expect($response['products'])->toBeArray();
    }
);

test(
    'It calls the getFilteredRegularProductsList method of the product queries class and returns proper response',
    function (): void {
        $companyId = 1;

        setCompanyIdInSession($companyId);

        $filterData = [
            'search_text' => 'ab',
            'category_id' => 1,
            'brand_id' => 1,
        ];

        $productQueries = $this->mock(ProductQueries::class, function ($mock): void {
            $mock->shouldReceive('getActiveRegularProductsFilteredByNameBrandAndCategory')
                ->once()
                ->andReturn(new Collection());
        });

        $productFilterController = new ProductFilterController($productQueries);

        $response = $productFilterController->getFilteredRegularProductsList(new Request($filterData));

        expect($response['products'])->toBeArray();
    }
);

test(
    'It calls the getFilteredRegularProducts method of the ProductQueries class and returns the proper response',
    function (): void {
        $companyId = 1;

        setCompanyIdInSession($companyId);

        $filterData = [
            'search_text' => 'ab',
            'number_of_records' => 5,
        ];

        $productQueries = $this->mock(ProductQueries::class, function ($mock): void {
            $mock->shouldReceive('getActiveFilteredRegularProducts')
            ->once()
            ->andReturn(new Collection());
        });

        $productFilterController = new ProductFilterController($productQueries);

        $response = $productFilterController->getFilteredRegularProducts(new Request($filterData));

        expect($response)->toBeArray();
    }
);

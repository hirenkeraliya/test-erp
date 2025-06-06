<?php

declare(strict_types=1);

use App\Domains\BoxProduct\BoxProductQueries;
use App\Domains\Product\DataObjects\ProductArticleData;
use App\Domains\Product\DataObjects\ProductImageUploadData;
use App\Domains\Product\ProductQueries;
use App\Domains\Product\Services\ProductService;
use App\Domains\ProductLoyaltyPoint\ProductLoyaltyPointQueries;
use App\Http\Controllers\StoreManager\ProductController;
use App\Models\BoxProduct;
use App\Models\Product;
use App\Models\ProductLoyaltyPoint;
use App\Models\StoreManager;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test(
    'It calls the getActiveProductsByUpc method of the product queries class and returns proper response',
    function (): void {
        setStoreManagerStoreIdInSession();
        setStoreManagerStoreCompanyIdInSession();

        $product = commonGetProductDetails();

        $productsUpc = [
            'import_products' => [$product->upc],
        ];

        $request = new Request($productsUpc);

        $productQueries = $this->mock(ProductQueries::class, function ($mock) use ($productsUpc, $product): void {
            $mock->shouldReceive('getActiveProductsByUpc')
                ->once()
                ->with($productsUpc['import_products'], 1)
                ->andReturn(new Collection([$product]));
        });

        $productController = new ProductController($productQueries);
        $response = $productController->getMatchingUpcProducts($request);

        expect($response['products']->resource)->toBeInstanceOf(SupportCollection::class);
        $this->assertEquals(1, $response['products_count']);
    }
);

test('It calls the exportProducts method and returns a proper response', function (): void {
    setStoreManagerStoreCompanyIdInSession();

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'date_range' => 'null',
        'status' => null,
        'batch' => null,
        'product_type_id' => 'null',
        'category_ids' => 'null',
        'brand_ids' => 'null',
        'color_ids' => 'null',
        'size_ids' => 'null',
        'department_ids' => 'null',
        'article_numbers' => null,
        'tag_ids' => 'null',
        'style_ids' => 'null',
        'product_collection_ids' => null,
        'attributes' => [],
    ];

    $productQueries = $this->mock(ProductQueries::class, function ($mock) use ($requestParameter): void {
        $mock->shouldReceive('getProductsWithRelationsForExport')
            ->once()
            ->with($requestParameter, 1)
            ->andReturn(collect(new Product()));
    });

    $productController = new ProductController($productQueries);

    $response = $productController->exportProducts('filename.csv', new Request($requestParameter));

    $this->assertEquals(200, $response->getStatusCode());

    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});

test('It calls the searchByArticleNumber method of the product queries class as expected', function (): void {
    setStoreManagerStoreCompanyIdInSession();
    $storeOne = 1;
    $storeTwo = 2;

    $productArticleData = new ProductArticleData('123456', (string) $storeOne, (string) $storeTwo);
    $returnData = [
        'products' => [
            [
                'id' => 1,
                'has_batch' => 1,
                'color' => 'Red',
                'size' => 'Xl',
                'stock' => null,
                'combination' => 'Red Xl',
            ],
        ],
        'colors' => ['red', 'blue'],
        'sizes' => ['XL', 'XXl'],
    ];

    $this->mock(ProductService::class, function ($mock) use ($productArticleData, $returnData): void {
        $mock->shouldReceive('getActiveInventoryProductDetailsForArticleNumber')
            ->with($productArticleData, 1)
            ->once()
            ->andReturn($returnData);
    });

    $productController = new ProductController(new ProductQueries());
    $redirectResponse = $productController->searchByArticleNumber($productArticleData);

    $this->assertEquals($redirectResponse, $returnData);
});

test(
    'It calls the searchProductsByOnlyArticleNumber method of the product queries class as expected',
    function (): void {
        $companyId = 1;

        setStoreManagerStoreCompanyIdInSession($companyId);
        $returnData = [
            'products' => [
                [
                    'id' => 1,
                    'has_batch' => 1,
                    'color' => 'Red',
                    'size' => 'Xl',
                    'stock' => null,
                    'combination' => 'Red Xl',
                ],
            ],
            'colors' => ['red', 'blue'],
            'sizes' => ['XL', 'XXl'],
        ];

        $requestParameter = [
            'article_number' => '123456',
        ];

        $this->mock(ProductService::class, function ($mock) use (
            $requestParameter,
            $companyId,
            $returnData
        ): void {
            $mock->shouldReceive('getProductDetailsByArticleNumber')
                ->with($requestParameter, $companyId)
                    ->once()
                    ->andReturn($returnData);
        });

        $productController = new ProductController(new ProductQueries());
        $redirectResponse = $productController->searchProductsByOnlyArticleNumber(new Request($requestParameter));

        $this->assertEquals($redirectResponse, $returnData);
    }
);

test('It calls the exportProducts method and returns a proper response in store manager panel', function (): void {
    setStoreManagerStoreCompanyIdInSession();

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'date_range' => 'null',
        'status' => null,
        'batch' => null,
        'product_type_id' => 'null',
        'category_ids' => 'null',
        'brand_ids' => 'null',
        'color_ids' => 'null',
        'size_ids' => 'null',
        'department_ids' => 'null',
        'article_numbers' => 'null',
        'tag_ids' => 'null',
        'style_ids' => 'null',
        'product_collection_ids' => null,
        'attributes' => [],
    ];

    $productQueries = $this->mock(ProductQueries::class, function ($mock) use ($requestParameter): void {
        $mock->shouldReceive('getProductsWithRelationsForExport')
            ->once()
            ->with($requestParameter, 1)
            ->andReturn(collect(new Product()));
    });

    $productController = new ProductController($productQueries);

    $response = $productController->exportProducts('filename.csv', new Request($requestParameter));

    $this->assertEquals(200, $response->getStatusCode());

    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});

test('It calls the upload image method of the product queries class and returns proper response', function (): void {
    $companyId = 1;
    setStoreManagerStoreCompanyIdInSession($companyId);

    $product = Product::factory()->make([
        'id' => 1,
        'unit_of_measure_id' => null,
        'season_id' => null,
        'department_id' => null,
        'color_id' => null,
        'size_id' => null,
        'style_id' => null,
        'brand_id' => 1,
        'company_id' => $companyId,
    ]);

    $uploadedFile = UploadedFile::fake()->image('avatar.jpg', 500, 500)->size(100);

    $productImageUploadData = new ProductImageUploadData(...[
        'image' => $uploadedFile,
        'product_id' => $product->id,
    ]);

    $productQueries = $this->mock(ProductQueries::class, function ($mock) use (
        $productImageUploadData,
        $companyId
    ): void {
        $mock->shouldReceive('uploadImage')
            ->once()
            ->with($productImageUploadData, $companyId);
    });

    $request = new Request();
    $productController = new ProductController($productQueries);
    $redirectResponse = $productController->uploadImage($productImageUploadData);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Product image uploaded successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('store-manager/products', $redirectResponse->getTargetUrl());
});

test('It calls the checkProductExportLimit method of the ProductQueries class as expected', function (): void {
    $companyId = 1;

    setStoreManagerStoreCompanyIdInSession($companyId);

    $request = new Request();
    $request->merge([
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'date_range' => 'null',
        'status' => null,
        'batch' => null,
        'product_type_id' => 'null',
        'category_ids' => 'null',
        'brand_ids' => 'null',
        'color_ids' => 'null',
        'size_ids' => 'null',
        'department_ids' => 'null',
        'article_numbers' => 'null',
        'tag_ids' => 'null',
        'style_ids' => 'null',
        'product_collection_ids' => null,
    ]);
    $storeManager = StoreManager::factory()->make([
        'id' => 1,
        'employee_id' => 1,
    ]);

    $storeManager->roles = collect([]);

    $request->setUserResolver(fn (): StoreManager => $storeManager);

    $productQueries = $this->mock(ProductQueries::class, function ($mock): void {
        $mock->shouldReceive('checkProductExportLimit')
            ->andReturn([
                'exceeds_limit' => false,
                'message' => 'You can export the products.',
            ]);
    });

    $this->mock(ProductService::class, function ($mock): void {
        $mock->shouldReceive('exportProductWithJob')
            ->once()
            ->andReturn([]);
    });

    $productController = new ProductController($productQueries);
    expect($productController->checkProductExportLimit($request))
        ->toHaveKeys([]);
});

test(
    'It calls the exportLoyaltyPointProducts method and returns a proper response',
    function (bool $productVariant): void {
        Config::set('app.product_variant', $productVariant);
        $companyId = 1;
        setStoreManagerStoreCompanyIdInSession($companyId);

        $requestParameter = [
            'search_text' => 'test',
            'sort_by' => 'test',
            'sort_direction' => 'test',
            'date_range' => 'null',
            'status' => null,
            'batch' => null,
            'product_type_id' => 'null',
            'category_ids' => 'null',
            'brand_ids' => 'null',
            'color_ids' => 'null',
            'size_ids' => 'null',
            'department_ids' => 'null',
            'article_numbers' => 'null',
            'tag_ids' => 'null',
            'style_ids' => 'null',
            'product_collection_ids' => null,
            'attributes' => [],
        ];

        $request = new Request($requestParameter);

        $this->mock(ProductLoyaltyPointQueries::class, function ($mock) use ($requestParameter): void {
            $mock->shouldReceive('getLoyaltyPointProducts')
                ->once()
                ->with($requestParameter, 1)
                ->andReturn(collect(new ProductLoyaltyPoint()));
        });

        $productQueries = resolve(ProductQueries::class);
        $productController = new ProductController($productQueries);

        $response = $productController->exportLoyaltyPointProducts('filename.csv', $request);

        $this->assertEquals(200, $response->getStatusCode());

        expect($response)->toBeInstanceOf(BinaryFileResponse::class);
    }
)->with([[true], [false]]);

test('It calls the exportBoxProducts method and returns a proper response', function (bool $productVariant): void {
    Config::set('app.product_variant', $productVariant);
    $companyId = 1;
    setStoreManagerStoreCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'date_range' => 'null',
        'status' => null,
        'batch' => null,
        'product_type_id' => 'null',
        'category_ids' => 'null',
        'brand_ids' => 'null',
        'color_ids' => 'null',
        'size_ids' => 'null',
        'department_ids' => 'null',
        'article_numbers' => 'null',
        'tag_ids' => 'null',
        'style_ids' => 'null',
        'product_collection_ids' => null,
        'attributes' => [],
    ];

    $request = new Request($requestParameter);

    $this->mock(BoxProductQueries::class, function ($mock) use ($requestParameter): void {
        $mock->shouldReceive('getBoxProducts')
            ->once()
            ->with($requestParameter, 1)
            ->andReturn(collect(new BoxProduct()));
    });

    $productQueries = resolve(ProductQueries::class);
    $productController = new ProductController($productQueries);

    $response = $productController->exportBoxProducts('filename.csv', $request);

    $this->assertEquals(200, $response->getStatusCode());

    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
})->with([[true], [false]]);

test(
    'It calls the checkProductLoyaltyPointExportLimit method of the ProductQueries class as expected',
    function (): void {
        $companyId = 1;

        setStoreManagerStoreCompanyIdInSession($companyId);

        $request = new Request();
        $request->merge([
            'search_text' => 'test',
            'sort_by' => 'test',
            'sort_direction' => 'test',
            'date_range' => 'null',
            'status' => null,
            'batch' => null,
            'product_type_id' => 'null',
            'category_ids' => 'null',
            'brand_ids' => 'null',
            'color_ids' => 'null',
            'size_ids' => 'null',
            'department_ids' => 'null',
            'article_numbers' => 'null',
            'tag_ids' => 'null',
            'style_ids' => 'null',
            'product_collection_ids' => null,
        ]);
        $storeManager = StoreManager::factory()->make([
            'id' => 1,
            'employee_id' => 1,
        ]);

        $storeManager->roles = collect([]);

        $request->setUserResolver(fn (): StoreManager => $storeManager);

        $productQueries = $this->mock(ProductQueries::class, function ($mock): void {
            $mock->shouldReceive('checkProductLoyaltyPointExportLimit')
                ->andReturn([
                    'exceeds_limit' => false,
                    'message' => 'You can export the products.',
                ]);
        });

        $this->mock(ProductService::class, function ($mock): void {
            $mock->shouldReceive('exportProductLoyaltyPointWithJob')
                ->once()
                ->andReturn([]);
        });

        $productController = new ProductController($productQueries);
        expect($productController->checkProductLoyaltyPointExportLimit($request))
            ->toHaveKeys([]);
    }
);

test('It calls the checkBoxProductExportLimit method of the ProductQueries class as expected', function (): void {
    $companyId = 1;

    setStoreManagerStoreCompanyIdInSession($companyId);

    $request = new Request();
    $request->merge([
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'date_range' => 'null',
        'status' => null,
        'batch' => null,
        'product_type_id' => 'null',
        'category_ids' => 'null',
        'brand_ids' => 'null',
        'color_ids' => 'null',
        'size_ids' => 'null',
        'department_ids' => 'null',
        'article_numbers' => 'null',
        'tag_ids' => 'null',
        'style_ids' => 'null',
        'product_collection_ids' => null,
    ]);
    $storeManager = StoreManager::factory()->make([
        'id' => 1,
        'employee_id' => 1,
    ]);

    $storeManager->roles = collect([]);

    $request->setUserResolver(fn (): StoreManager => $storeManager);

    $productQueries = $this->mock(ProductQueries::class, function ($mock): void {
        $mock->shouldReceive('checkBoxProductExportLimit')
            ->andReturn([
                'exceeds_limit' => false,
                'message' => 'You can export the products.',
            ]);
    });

    $this->mock(ProductService::class, function ($mock): void {
        $mock->shouldReceive('exportBoxProductWithJob')
            ->once()
            ->andReturn([]);
    });

    $productController = new ProductController($productQueries);
    expect($productController->checkBoxProductExportLimit($request))
        ->toHaveKeys([]);
});

test('It calls the exportProductsForImportBulkUpdate method and returns a proper response', function (): void {
    setStoreManagerStoreCompanyIdInSession();

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'date_range' => 'null',
        'status' => null,
        'batch' => null,
        'product_type_id' => 'null',
        'category_ids' => 'null',
        'brand_ids' => 'null',
        'color_ids' => 'null',
        'size_ids' => 'null',
        'department_ids' => 'null',
        'article_numbers' => null,
        'tag_ids' => 'null',
        'style_ids' => 'null',
        'product_collection_ids' => null,
    ];

    $productQueries = $this->mock(ProductQueries::class, function ($mock) use ($requestParameter): void {
        $mock->shouldReceive('getProductsWithRelationsForExport')
            ->once()
            ->with($requestParameter, 1)
            ->andReturn(collect(new Product()));
    });

    $productController = new ProductController($productQueries);

    $response = $productController->exportProductsForImportBulkUpdate('filename.csv', new Request($requestParameter));

    $this->assertEquals(200, $response->getStatusCode());

    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});

test(
    'It calls the checkProductExportLimitForImportBulkUpdate method of the ProductQueries class as expected',
    function (): void {
        $companyId = 1;

        setStoreManagerStoreCompanyIdInSession($companyId);

        $request = new Request();
        $request->merge([
            'search_text' => 'test',
            'sort_by' => 'test',
            'sort_direction' => 'test',
            'date_range' => 'null',
            'status' => null,
            'batch' => null,
            'product_type_id' => 'null',
            'category_ids' => 'null',
            'brand_ids' => 'null',
            'color_ids' => 'null',
            'size_ids' => 'null',
            'department_ids' => 'null',
            'article_numbers' => 'null',
            'tag_ids' => 'null',
            'style_ids' => 'null',
            'product_collection_ids' => null,
        ]);
        $storeManager = StoreManager::factory()->make([
            'id' => 1,
            'employee_id' => 1,
        ]);

        $storeManager->roles = collect([]);

        $request->setUserResolver(fn (): StoreManager => $storeManager);

        $productQueries = $this->mock(ProductQueries::class, function ($mock): void {
            $mock->shouldReceive('checkProductExportLimit')
                ->andReturn([
                    'exceeds_limit' => false,
                    'message' => 'You can export the products.',
                ]);
        });

        $this->mock(ProductService::class, function ($mock): void {
            $mock->shouldReceive('exportProductWithJobForImportBulkUpdate')
                ->once()
                ->andReturn([]);
        });

        $productController = new ProductController($productQueries);
        expect($productController->checkProductExportLimitForImportBulkUpdate($request))
            ->toHaveKeys([]);
    }
);

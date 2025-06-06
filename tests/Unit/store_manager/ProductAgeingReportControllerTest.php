<?php

declare(strict_types=1);

use App\Domains\ProductAgeingReport\Enums\AgeOfProductTypes;
use App\Domains\ProductAgeingReport\ProductAgeingQueries;
use App\Domains\ProductAgeingReport\Services\ProductAgeingReportService;
use App\Http\Controllers\StoreManager\ProductAgeingReportController;
use App\Models\Product;
use App\Models\ProductAgeing;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test(
    'It calls the get paginated products ageing report for store manager method of the sale item queries class and returns proper response',
    function (): void {
        $companyId = 1;
        setStoreIdInSession();
        setStoreManagerStoreCompanyIdInSession(1);

        $requestParameter = [
            'search_text' => 'test',
            'sort_by' => 'test',
            'sort_direction' => 'test',
            'per_page' => 'test',
            'product_id' => null,
            'category_ids' => null,
            'location_ids' => [1],
            'brand_ids' => null,
            'department_ids' => null,
            'size_ids' => null,
            'color_ids' => null,
            'article_numbers' => null,
            'tag_ids' => null,
            'age_of_product_type' => AgeOfProductTypes::CREATED_AT->value,
            'age_category_id' => null,
            'last_selling_date_range' => [],
            'product_collection_id' => null,
        ];

        $productAgeingQueries = $this->mock(ProductAgeingQueries::class, function ($mock) use (
            $requestParameter,
            $companyId
        ): void {
            $mock->shouldReceive('getPaginatedProductsAgeingReport')
                ->once()
                ->with($requestParameter, $companyId)
                ->andReturn(new LengthAwarePaginator([], 50, 15));
        });

        $productAgeingReportController = new ProductAgeingReportController($productAgeingQueries);

        $response = $productAgeingReportController->fetchProductsAgeingReport(new Request($requestParameter));

        $this->assertEquals(50, $response['total_records']);
        expect($response['data']->resource)->toBeInstanceOf(LengthAwarePaginator::class);
    }
);

test('It calls the exportProductsAgeingReport method and returns a proper response', function (): void {
    setStoreIdInSession();
    setStoreManagerStoreCompanyIdInSession(1);

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 'test',
        'product_id' => null,
        'category_ids' => null,
        'location_ids' => [1],
        'brand_ids' => null,
        'department_ids' => null,
        'size_ids' => null,
        'color_ids' => null,
        'article_numbers' => null,
        'tag_ids' => null,
        'age_of_product_type' => AgeOfProductTypes::CREATED_AT->value,
        'age_category_id' => null,
        'last_selling_date_range' => [],
        'product_collection_id' => null,
        'export_columns' => null,
    ];

    $saleItemQueries = $this->mock(ProductAgeingQueries::class, function ($mock) use ($requestParameter): void {
        $mock->shouldReceive('getProductsAgeingReportForExport')
            ->once()
            ->with($requestParameter, 1)
            ->andReturn(collect(new Product()));
    });

    $productAgeingReportController = new ProductAgeingReportController($saleItemQueries);

    $response = $productAgeingReportController->exportProductsAgeingReport(
        'filename.csv',
        new Request($requestParameter)
    );

    $this->assertEquals(200, $response->getStatusCode());

    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});

test(
    'the printProductsAgeing method and returns the string',
    function (): void {
        setStoreIdInSession();
        setStoreManagerStoreCompanyIdInSession(1);

        $filterData = [
            'search_text' => [],
            'sort_by' => [],
            'sort_direction' => [],
            'per_page' => [],
            'product_id' => [],
            'category_ids' => [],
            'brand_ids' => [],
            'department_ids' => [],
            'location_ids' => [1],
            'size_ids' => [],
            'color_ids' => [],
            'article_numbers' => [],
            'tag_ids' => null,
            'age_of_product_type' => AgeOfProductTypes::CREATED_AT->value,
            'age_category_id' => null,
            'product_collection_id' => null,
        ];

        $this->mock(ProductAgeingReportService::class, function ($mock): void {
            $mock->shouldReceive('print')
                ->once();
        });

        $productAgeingQueries = $this->mock(ProductAgeingQueries::class);

        $productAgeingReportController = new ProductAgeingReportController($productAgeingQueries);
        $response = $productAgeingReportController->printProductsAgeing(new Request($filterData));

        expect($response)->toBeString();
    }
);

test(
    'It calls the get paginated products ageing report by month and year for store manager method of the sale item queries class and returns proper response',
    function (): void {
        $companyId = 1;
        setStoreIdInSession();
        setStoreManagerStoreCompanyIdInSession(1);

        $requestParameter = [
            'search_text' => 'test',
            'sort_by' => 'test',
            'sort_direction' => 'test',
            'per_page' => 'test',
            'product_id' => null,
            'category_ids' => null,
            'location_ids' => [1],
            'brand_ids' => null,
            'department_ids' => null,
            'size_ids' => null,
            'color_ids' => null,
            'article_numbers' => null,
            'tag_ids' => null,
            'age_of_product_type' => AgeOfProductTypes::CREATED_AT->value,
            'age_category_id' => null,
            'last_selling_date_range' => [],
            'product_collection_id' => null,
        ];

        $productAgeingQueries = $this->mock(ProductAgeingQueries::class, function ($mock) use (
            $requestParameter,
            $companyId
        ): void {
            $mock->shouldReceive('getPaginatedProductsAgeingReportByMonthAndYear')
                ->once()
                ->with($requestParameter, $companyId)
                ->andReturn(new LengthAwarePaginator([], 50, 15));
        });

        $productAgeingReportController = new ProductAgeingReportController($productAgeingQueries);

        $response = $productAgeingReportController->fetchProductsAgeingReportByMonthAndYear(
            new Request($requestParameter)
        );

        $this->assertEquals(50, $response['total_records']);
        expect($response['data']->resource)->toBeInstanceOf(LengthAwarePaginator::class);
    }
);

test('It calls the exportProductsAgeingReportByMonthAndYear method and returns a proper response', function (): void {
    setStoreIdInSession();
    setStoreManagerStoreCompanyIdInSession(1);

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 'test',
        'product_id' => null,
        'category_ids' => null,
        'location_ids' => [1],
        'brand_ids' => null,
        'department_ids' => null,
        'size_ids' => null,
        'color_ids' => null,
        'article_numbers' => null,
        'tag_ids' => null,
        'age_of_product_type' => AgeOfProductTypes::CREATED_AT->value,
        'age_category_id' => null,
        'last_selling_date_range' => [],
        'product_collection_id' => null,
        'export_columns' => null,
    ];

    $saleItemQueries = $this->mock(ProductAgeingQueries::class, function ($mock) use ($requestParameter): void {
        $mock->shouldReceive('getProductsAgeingReportByMonthAndYearForExport')
            ->once()
            ->with($requestParameter, 1)
            ->andReturn(collect(new Product()));
    });

    $productAgeingReportController = new ProductAgeingReportController($saleItemQueries);

    $response = $productAgeingReportController->exportProductsAgeingReportByMonthAndYear(
        'filename.csv',
        new Request($requestParameter)
    );

    $this->assertEquals(200, $response->getStatusCode());

    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});

test('It calls the fetchConsolidateProductsAgeingReport method and returns a proper response', function (): void {
    setStoreIdInSession();
    setStoreManagerStoreCompanyIdInSession(1);

    $productAgeing = ProductAgeing::factory()->make([
        'id' => 1,
        'product_id' => 1,
        'location_id' => 1,
    ]);

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 'test',
        'product_id' => null,
        'category_ids' => null,
        'location_ids' => [1],
        'brand_ids' => null,
        'department_ids' => null,
        'size_ids' => null,
        'color_ids' => null,
        'article_numbers' => null,
        'tag_ids' => null,
        'age_of_product_type' => AgeOfProductTypes::CREATED_AT->value,
        'age_category_id' => null,
        'last_selling_date_range' => [],
        'product_collection_id' => null,
    ];

    $saleItemQueries = $this->mock(ProductAgeingQueries::class, function ($mock) use ($productAgeing): void {
        $mock->shouldReceive('getProductsAgeingReportForConsolidate')
            ->once()
            ->andReturn($productAgeing);
    });

    $productAgeingReportController = new ProductAgeingReportController($saleItemQueries);

    $response = $productAgeingReportController->fetchConsolidateProductsAgeingReport(
        new Request($requestParameter)
    );

    expect($response)->toHaveKeys(['age_categories', 'total_quantity_sold', 'total_quantity_remaining']);
});

test(
    'It calls the fetchConsolidateProductsAgeingReportByMonthAndYear method and returns a proper response',
    function (): void {
        setStoreIdInSession();
        setStoreManagerStoreCompanyIdInSession(1);

        $productAgeing = ProductAgeing::factory()->make([
            'id' => 1,
            'product_id' => 1,
            'location_id' => 1,
        ]);

        $requestParameter = [
            'search_text' => 'test',
            'sort_by' => 'test',
            'sort_direction' => 'test',
            'per_page' => 'test',
            'product_id' => null,
            'category_ids' => null,
            'location_ids' => [1],
            'brand_ids' => null,
            'department_ids' => null,
            'size_ids' => null,
            'color_ids' => null,
            'article_numbers' => null,
            'tag_ids' => null,
            'age_of_product_type' => AgeOfProductTypes::CREATED_AT->value,
            'age_category_id' => null,
            'last_selling_date_range' => [],
            'product_collection_id' => null,
        ];

        $saleItemQueries = $this->mock(ProductAgeingQueries::class, function ($mock) use ($productAgeing): void {
            $mock->shouldReceive('getConsolidateProductsAgeingReportByMonthAndYear')
                ->once()
                ->andReturn($productAgeing);
        });

        $productAgeingReportController = new ProductAgeingReportController($saleItemQueries);

        $response = $productAgeingReportController->fetchConsolidateProductsAgeingReportByMonthAndYear(
            new Request($requestParameter)
        );

        expect($response)->toHaveKeys(['age_categories', 'total_quantity_sold', 'total_quantity_remaining']);
    }
);

test(
    'the printProductsAgeingByMonthAndYear method and returns the string',
    function (): void {
        setStoreIdInSession();
        setStoreManagerStoreCompanyIdInSession(1);

        $filterData = [
            'search_text' => [],
            'sort_by' => [],
            'sort_direction' => [],
            'per_page' => [],
            'product_id' => [],
            'category_ids' => [],
            'brand_ids' => [],
            'department_ids' => [],
            'location_ids' => [1],
            'size_ids' => [],
            'color_ids' => [],
            'article_numbers' => [],
            'tag_ids' => null,
            'age_of_product_type' => AgeOfProductTypes::CREATED_AT->value,
            'age_category_id' => null,
            'last_selling_date_range' => [],
            'product_collection_id' => null,
        ];

        $this->mock(ProductAgeingReportService::class, function ($mock): void {
            $mock->shouldReceive('printByMonthAndYear')
                ->once();
        });

        $productAgeingQueries = $this->mock(ProductAgeingQueries::class);

        $productAgeingReportController = new ProductAgeingReportController($productAgeingQueries);
        $response = $productAgeingReportController->printProductsAgeingByMonthAndYear(new Request($filterData));

        expect($response)->toBeString();
    }
);

test(
    'checkProductAgeingExportLimit method call exportProductAgeingWithJob method of ProductAgeingReportService and returns',
    function (): void {
        setStoreManagerStoreCompanyIdInSession();
        $filterData = [
            'search_text' => [],
            'sort_by' => [],
            'sort_direction' => [],
            'per_page' => [],
            'product_id' => [],
            'category_ids' => [],
            'brand_ids' => [],
            'department_ids' => [],
            'location_ids' => [],
            'size_ids' => [],
            'color_ids' => [],
            'article_numbers' => [],
            'tag_ids' => null,
            'age_of_product_type' => AgeOfProductTypes::CREATED_AT->value,
            'age_category_id' => null,
            'last_selling_date_range' => [],
            'product_collection_id' => null,
        ];

        [$storeManager, $request] = setRequestUserForStoreManager($filterData);

        $this->mock(ProductAgeingReportService::class, function ($mock): void {
            $mock->shouldReceive('exportProductAgeingWithJob')
                ->once()
                ->andReturn([]);
        });

        $productAgeingQueries = $this->mock(ProductAgeingQueries::class);

        $productAgeingReportController = new ProductAgeingReportController($productAgeingQueries);
        $response = $productAgeingReportController->checkProductAgeingExportLimit($request);

        $this->assertEquals($response, []);
    }
);

test(
    'checkProductAgeingByMonthAndYearExportLimit method call exportProductAgeingWithJob method of ProductAgeingReportService and returns',
    function (): void {
        setStoreManagerStoreCompanyIdInSession();
        $filterData = [
            'search_text' => [],
            'sort_by' => [],
            'sort_direction' => [],
            'per_page' => [],
            'product_id' => [],
            'category_ids' => [],
            'brand_ids' => [],
            'department_ids' => [],
            'location_ids' => [],
            'size_ids' => [],
            'color_ids' => [],
            'article_numbers' => [],
            'tag_ids' => null,
            'age_of_product_type' => AgeOfProductTypes::CREATED_AT->value,
            'age_category_id' => null,
            'last_selling_date_range' => [],
            'product_collection_id' => null,
        ];

        [$storeManager, $request] = setRequestUserForStoreManager($filterData);

        $this->mock(ProductAgeingReportService::class, function ($mock): void {
            $mock->shouldReceive('exportProductAgeingByMonthAndYearWithJob')
                ->once()
                ->andReturn([]);
        });

        $productAgeingQueries = $this->mock(ProductAgeingQueries::class);

        $productAgeingReportController = new ProductAgeingReportController($productAgeingQueries);
        $response = $productAgeingReportController->checkProductAgeingByMonthAndYearExportLimit($request);

        $this->assertEquals($response, []);
    }
);

test(
    'It calls the get paginated product ageing report by article number method of the product aging queries class and returns proper response',
    function (): void {
        $companyId = 1;

        setStoreManagerStoreCompanyIdInSession($companyId);
        setStoreIdInSession(1);

        $requestParameter = [
            'search_text' => 'test',
            'sort_by' => 'test',
            'sort_direction' => 'test',
            'per_page' => 'test',
            'product_id' => null,
            'category_ids' => null,
            'brand_ids' => null,
            'department_ids' => null,
            'size_ids' => null,
            'color_ids' => null,
            'article_numbers' => null,
            'location_ids' => [1],
            'tag_ids' => null,
            'age_of_product_type' => AgeOfProductTypes::CREATED_AT->value,
            'last_selling_date_range' => [],
            'product_collection_id' => null,
        ];

        $saleQueries = $this->mock(ProductAgeingQueries::class, function ($mock) use (
            $requestParameter,
            $companyId
        ): void {
            $mock->shouldReceive('getPaginatedProductsAgeingReportByArticleNumber')
                ->once()
                ->with($requestParameter, $companyId)
                ->andReturn(new LengthAwarePaginator([], 50, 15));
        });

        $productAgeingReportController = new ProductAgeingReportController($saleQueries);

        $response = $productAgeingReportController->fetchProductsAgeingReportByArticleNumber(
            new Request($requestParameter)
        );

        $this->assertEquals(50, $response['total_records']);
        expect($response['data']->resource)->toBeInstanceOf(LengthAwarePaginator::class);
    }
);

test('It calls the exportProductsAgeingReportByArticleNumber method and returns a proper response', function (): void {
    $companyId = 1;
    setStoreManagerStoreCompanyIdInSession($companyId);
    setStoreIdInSession(1);

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 'test',
        'product_id' => null,
        'category_ids' => null,
        'brand_ids' => null,
        'department_ids' => null,
        'size_ids' => null,
        'color_ids' => null,
        'article_numbers' => null,
        'location_ids' => [1],
        'tag_ids' => null,
        'age_of_product_type' => AgeOfProductTypes::CREATED_AT->value,
        'last_selling_date_range' => [],
        'product_collection_id' => null,
        'export_columns' => null,
    ];

    $saleItemQueries = $this->mock(ProductAgeingQueries::class, function ($mock) use (
        $requestParameter,
        $companyId
    ): void {
        $mock->shouldReceive('getProductsAgeingReportByArticleNumberForExport')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(collect(new Product()));
    });

    $productAgeingReportController = new ProductAgeingReportController($saleItemQueries);

    $response = $productAgeingReportController->exportProductsAgeingReportByArticleNumber(
        'filename.csv',
        new Request($requestParameter)
    );

    $this->assertEquals(200, $response->getStatusCode());

    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});

test(
    'the printProductsAgeingReportByArticleNumber method and returns the string',
    function (): void {
        setStoreManagerStoreCompanyIdInSession();
        $filterData = [
            'search_text' => [],
            'sort_by' => [],
            'sort_direction' => [],
            'per_page' => [],
            'product_id' => [],
            'category_ids' => [],
            'brand_ids' => [],
            'department_ids' => [],
            'location_ids' => [1],
            'size_ids' => [],
            'color_ids' => [],
            'article_numbers' => [],
            'tag_ids' => null,
            'age_of_product_type' => AgeOfProductTypes::CREATED_AT->value,
            'product_collection_id' => null,
        ];

        $this->mock(ProductAgeingReportService::class, function ($mock): void {
            $mock->shouldReceive('printByArticleNumber')
                ->once();
        });

        $productAgeingQueries = $this->mock(ProductAgeingQueries::class);

        $productAgeingReportController = new ProductAgeingReportController($productAgeingQueries);
        $response = $productAgeingReportController->printProductsAgeingReportByArticleNumber(new Request($filterData));

        expect($response)->toBeString();
    }
);

test(
    'checkProductAgeingExportLimitByArticleNumber method call exportProductAgeingByArticleNumberWithJob method of ProductAgeingReportService and returns',
    function (): void {
        setStoreManagerStoreCompanyIdInSession();
        $filterData = [
            'search_text' => [],
            'sort_by' => [],
            'sort_direction' => [],
            'per_page' => [],
            'product_id' => [],
            'category_ids' => [],
            'brand_ids' => [],
            'department_ids' => [],
            'location_ids' => [],
            'size_ids' => [],
            'color_ids' => [],
            'article_numbers' => [],
            'tag_ids' => null,
            'age_of_product_type' => AgeOfProductTypes::CREATED_AT->value,
            'last_selling_date_range' => [],
            'product_collection_id' => null,
            'export_columns' => null,
        ];

        [$storeManager, $request] = setRequestUserForStoreManager($filterData);

        $this->mock(ProductAgeingReportService::class, function ($mock): void {
            $mock->shouldReceive('exportProductAgeingByArticleNumberWithJob')
                ->once()
                ->andReturn([]);
        });

        $productAgeingQueries = $this->mock(ProductAgeingQueries::class);

        $productAgeingReportController = new ProductAgeingReportController($productAgeingQueries);
        $response = $productAgeingReportController->checkProductAgeingExportLimitByArticleNumber($request);

        $this->assertEquals($response, []);
    }
);

test(
    'It calls the fetchConsolidateProductsAgeingReportByArticleNumber method and returns a proper response',
    function (): void {
        $companyId = 1;
        setStoreManagerStoreCompanyIdInSession($companyId);

        $productAgeing = ProductAgeing::factory()->make([
            'id' => 1,
            'product_id' => 1,
            'location_id' => 1,
        ]);

        $requestParameter = [
            'search_text' => 'test',
            'sort_by' => 'test',
            'sort_direction' => 'test',
            'per_page' => 'test',
            'product_id' => null,
            'category_ids' => null,
            'brand_ids' => null,
            'department_ids' => null,
            'size_ids' => null,
            'color_ids' => null,
            'location_ids' => null,
            'article_numbers' => null,
            'tag_ids' => null,
            'age_of_product_type' => AgeOfProductTypes::CREATED_AT->value,
            'last_selling_date_range' => null,
            'product_collection_id' => null,
        ];

        $productAgeingQueries = $this->mock(ProductAgeingQueries::class, function ($mock) use ($productAgeing): void {
            $mock->shouldReceive('getProductsAgeingReportForConsolidateByArticleNumber')
                ->once()
                ->andReturn($productAgeing);
        });

        $productAgeingReportController = new ProductAgeingReportController($productAgeingQueries);

        $response = $productAgeingReportController->fetchConsolidateProductsAgeingReportByArticleNumber(
            new Request($requestParameter)
        );

        expect($response)->toHaveKeys(['total_quantity_sold', 'total_quantity_remaining']);
    }
);

test(
    'It calls the get paginated product ageing report by upc method of the product aging queries class and returns proper response',
    function (): void {
        $companyId = 1;

        setStoreManagerStoreCompanyIdInSession($companyId);
        setStoreIdInSession(1);

        $requestParameter = [
            'search_text' => 'test',
            'sort_by' => 'test',
            'sort_direction' => 'test',
            'per_page' => 'test',
            'product_id' => null,
            'category_ids' => null,
            'brand_ids' => null,
            'department_ids' => null,
            'size_ids' => null,
            'color_ids' => null,
            'article_numbers' => null,
            'location_ids' => [1],
            'tag_ids' => null,
            'age_of_product_type' => AgeOfProductTypes::CREATED_AT->value,
            'last_selling_date_range' => [],
            'product_collection_id' => null,
        ];

        $saleQueries = $this->mock(ProductAgeingQueries::class, function ($mock) use (
            $requestParameter,
            $companyId
        ): void {
            $mock->shouldReceive('getPaginatedProductsAgeingReportByUpc')
                ->once()
                ->with($requestParameter, $companyId)
                ->andReturn(new LengthAwarePaginator([], 50, 15));
        });

        $productAgeingReportController = new ProductAgeingReportController($saleQueries);

        $response = $productAgeingReportController->fetchProductsAgeingReportByUpc(new Request($requestParameter));

        $this->assertEquals(50, $response['total_records']);
        expect($response['data']->resource)->toBeInstanceOf(LengthAwarePaginator::class);
    }
);

test('It calls the exportProductsAgeingReportByUpc method and returns a proper response', function (): void {
    $companyId = 1;
    setStoreManagerStoreCompanyIdInSession($companyId);
    setStoreIdInSession(1);

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 'test',
        'product_id' => null,
        'category_ids' => null,
        'brand_ids' => null,
        'department_ids' => null,
        'size_ids' => null,
        'color_ids' => null,
        'article_numbers' => null,
        'location_ids' => [1],
        'tag_ids' => null,
        'age_of_product_type' => AgeOfProductTypes::CREATED_AT->value,
        'last_selling_date_range' => [],
        'product_collection_id' => null,
        'export_columns' => null,
    ];

    $saleItemQueries = $this->mock(ProductAgeingQueries::class, function ($mock) use (
        $requestParameter,
        $companyId
    ): void {
        $mock->shouldReceive('getProductsAgeingReportByUpcForExport')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(collect(new Product()));
    });

    $productAgeingReportController = new ProductAgeingReportController($saleItemQueries);

    $response = $productAgeingReportController->exportProductsAgeingReportByUpc(
        'filename.csv',
        new Request($requestParameter)
    );

    $this->assertEquals(200, $response->getStatusCode());

    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});

test(
    'the printProductsAgeingReportByUpc method and returns the string',
    function (): void {
        setStoreManagerStoreCompanyIdInSession();
        $filterData = [
            'search_text' => [],
            'sort_by' => [],
            'sort_direction' => [],
            'per_page' => [],
            'product_id' => [],
            'category_ids' => [],
            'brand_ids' => [],
            'department_ids' => [],
            'location_ids' => [],
            'size_ids' => [],
            'color_ids' => [],
            'article_numbers' => [],
            'tag_ids' => null,
            'age_of_product_type' => AgeOfProductTypes::CREATED_AT->value,
            'product_collection_id' => null,
        ];

        $this->mock(ProductAgeingReportService::class, function ($mock): void {
            $mock->shouldReceive('printByUpc')
                ->once();
        });

        $productAgeingQueries = $this->mock(ProductAgeingQueries::class);

        $productAgeingReportController = new ProductAgeingReportController($productAgeingQueries);
        $response = $productAgeingReportController->printProductsAgeingReportByUpc(new Request($filterData));

        expect($response)->toBeString();
    }
);

test(
    'checkProductAgeingExportLimitByUpc method call exportProductAgeingByUpcWithJob method of ProductAgeingReportService and returns',
    function (): void {
        setStoreManagerStoreCompanyIdInSession();
        $filterData = [
            'search_text' => [],
            'sort_by' => [],
            'sort_direction' => [],
            'per_page' => [],
            'product_id' => [],
            'category_ids' => [],
            'brand_ids' => [],
            'department_ids' => [],
            'location_ids' => [],
            'size_ids' => [],
            'color_ids' => [],
            'article_numbers' => [],
            'tag_ids' => null,
            'age_of_product_type' => AgeOfProductTypes::CREATED_AT->value,
            'last_selling_date_range' => [],
            'product_collection_id' => null,
            'export_columns' => null,
        ];

        [$storeManager, $request] = setRequestUserForStoreManager($filterData);

        $this->mock(ProductAgeingReportService::class, function ($mock): void {
            $mock->shouldReceive('exportProductAgeingByUpcWithJob')
                ->once()
                ->andReturn([]);
        });

        $productAgeingQueries = $this->mock(ProductAgeingQueries::class);

        $productAgeingReportController = new ProductAgeingReportController($productAgeingQueries);
        $response = $productAgeingReportController->checkProductAgeingExportLimitByUpc($request);

        $this->assertEquals($response, []);
    }
);

test(
    'It calls the fetchConsolidateProductsAgeingReportByUpc method and returns a proper response',
    function (): void {
        $companyId = 1;
        setStoreManagerStoreCompanyIdInSession($companyId);

        $productAgeing = ProductAgeing::factory()->make([
            'id' => 1,
            'product_id' => 1,
            'location_id' => 1,
        ]);

        $requestParameter = [
            'search_text' => 'test',
            'sort_by' => 'test',
            'sort_direction' => 'test',
            'per_page' => 'test',
            'product_id' => null,
            'category_ids' => null,
            'brand_ids' => null,
            'department_ids' => null,
            'size_ids' => null,
            'color_ids' => null,
            'location_ids' => null,
            'article_numbers' => null,
            'tag_ids' => null,
            'age_of_product_type' => AgeOfProductTypes::CREATED_AT->value,
            'last_selling_date_range' => null,
            'product_collection_id' => null,
        ];

        $productAgeingQueries = $this->mock(ProductAgeingQueries::class, function ($mock) use ($productAgeing): void {
            $mock->shouldReceive('getProductsAgeingReportForConsolidateByUpc')
                ->once()
                ->andReturn($productAgeing);
        });

        $productAgeingReportController = new ProductAgeingReportController($productAgeingQueries);

        $response = $productAgeingReportController->fetchConsolidateProductsAgeingReportByUpc(
            new Request($requestParameter)
        );

        expect($response)->toHaveKeys(['total_quantity_sold', 'total_quantity_remaining']);
    }
);

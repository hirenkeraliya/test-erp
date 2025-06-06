<?php

declare(strict_types=1);

use App\Domains\ExternalCompany\ExternalCompanyQueries;
use App\Domains\ExternalConnection\ExternalConnectionQueries;
use App\Domains\Inventory\DataObjects\ExternalInventoryReportListData;
use App\Http\Controllers\Admin\ExternalInventoryReportController;
use App\Models\ExternalCompany;
use App\Models\ExternalConnection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test(
    'It calls the fetchExternalInventories method and return proper response',
    function (): void {
        $externalCompany = ExternalCompany::factory()->make([
            'external_connection_id' => 1,
            'external_company_id' => 1,
        ]);

        $this->mock(ExternalCompanyQueries::class, function ($mock) use ($externalCompany): void {
            $mock->shouldReceive('getById')
                ->once()
                ->andReturn($externalCompany);
        });

        $this->mock(ExternalConnectionQueries::class, function ($mock): void {
            $mock->shouldReceive('getById')
                ->once()
                ->andReturn(new ExternalConnection());
        });

        Http::fake([
            '*' => Http::response([
                'data' => [],
            ], 200),
        ]);

        $filterData = [
            'search_text' => '',
            'per_page' => 10,
            'page' => 1,
            'sort_by' => '',
            'sort_direction' => '',
            'product_id' => null,
            'category_id' => null,
            'brand_id' => null,
            'color_id' => null,
            'size_id' => null,
            'location_ids' => [],
            'article_numbers' => [],
            'department_ids' => [],
            'tag_ids' => [],
            'stock_type' => null,
            'style_ids' => [],
            'region_ids' => [],
            'status' => '',
            'external_company_main_id' => null,
        ];

        $ExternalInventoryReportListData = new ExternalInventoryReportListData(...$filterData);

        $externalInventoryReportController = new ExternalInventoryReportController();
        $response = $externalInventoryReportController->fetchExternalInventories($ExternalInventoryReportListData);
        expect($response)->toBeArray();
    });

test(
    'It calls the getStoresWarehousesAndRegions method and return proper response',
    function (): void {
        $externalCompany = ExternalCompany::factory()->make([
            'external_connection_id' => 1,
            'external_company_id' => 1,
        ]);

        $this->mock(ExternalCompanyQueries::class, function ($mock) use ($externalCompany): void {
            $mock->shouldReceive('getById')
                ->once()
                ->andReturn($externalCompany);
        });

        $this->mock(ExternalConnectionQueries::class, function ($mock): void {
            $mock->shouldReceive('getById')
                ->once()
                ->andReturn(new ExternalConnection());
        });

        Http::fake([
            '*' => Http::response([
                'data' => [],
            ], 200),
        ]);

        $externalInventoryReportController = new ExternalInventoryReportController();
        $response = $externalInventoryReportController->getStoresWarehousesAndRegions(new Request([]));
        expect($response)->toBeArray();
    }
);

test(
    'It calls the exportExternalInventories method and return proper response',
    function (): void {
        $externalCompany = ExternalCompany::factory()->make([
            'external_connection_id' => 1,
            'external_company_id' => 1,
        ]);

        $this->mock(ExternalCompanyQueries::class, function ($mock) use ($externalCompany): void {
            $mock->shouldReceive('getById')
                ->once()
                ->andReturn($externalCompany);
        });

        $this->mock(ExternalConnectionQueries::class, function ($mock): void {
            $mock->shouldReceive('getById')
                ->once()
                ->andReturn(new ExternalConnection());
        });

        Http::fake([
            '*' => Http::response([
                'data' => [],
            ], 200),
        ]);

        $filterData = [
            'search_text' => '',
            'per_page' => 10,
            'page' => 1,
            'sort_by' => '',
            'sort_direction' => '',
            'product_id' => null,
            'category_id' => null,
            'brand_id' => null,
            'color_id' => null,
            'size_id' => null,
            'location_ids' => [],
            'article_numbers' => [],
            'department_ids' => [],
            'tag_ids' => [],
            'stock_type' => null,
            'style_ids' => [],
            'region_ids' => [],
            'status' => '',
            'external_company_main_id' => null,
        ];

        $ExternalInventoryReportListData = new ExternalInventoryReportListData(...$filterData);

        $externalInventoryReportController = new ExternalInventoryReportController();
        $response = $externalInventoryReportController->exportExternalInventories(
            'demo.csv',
            $ExternalInventoryReportListData
        );
        expect($response)->toBeInstanceOf(BinaryFileResponse::class);
    }
);

test(
    'It calls the getFilteredExternalInventoryProducts method and return proper response',
    function (): void {
        $externalCompany = ExternalCompany::factory()->make([
            'external_connection_id' => 1,
            'external_company_id' => 1,
        ]);

        $this->mock(ExternalCompanyQueries::class, function ($mock) use ($externalCompany): void {
            $mock->shouldReceive('getById')
                ->once()
                ->andReturn($externalCompany);
        });

        $this->mock(ExternalConnectionQueries::class, function ($mock): void {
            $mock->shouldReceive('getById')
                ->once()
                ->andReturn(new ExternalConnection());
        });

        Http::fake([
            '*' => Http::response([
                'data' => [],
            ], 200),
        ]);

        $externalInventoryReportController = new ExternalInventoryReportController();
        $response = $externalInventoryReportController->getFilteredExternalInventoryProducts(new Request([]));
        expect($response)->toBeArray();
    }
);

test(
    'It calls the getFilteredExternalInventoryCategories method and return proper response',
    function (): void {
        $externalCompany = ExternalCompany::factory()->make([
            'external_connection_id' => 1,
            'external_company_id' => 1,
        ]);

        $this->mock(ExternalCompanyQueries::class, function ($mock) use ($externalCompany): void {
            $mock->shouldReceive('getById')
                ->once()
                ->andReturn($externalCompany);
        });

        $this->mock(ExternalConnectionQueries::class, function ($mock): void {
            $mock->shouldReceive('getById')
                ->once()
                ->andReturn(new ExternalConnection());
        });

        Http::fake([
            '*' => Http::response([
                'data' => [],
            ], 200),
        ]);

        $externalInventoryReportController = new ExternalInventoryReportController();
        $response = $externalInventoryReportController->getFilteredExternalInventoryCategories(new Request([]));
        expect($response)->toBeArray();
    }
);

test(
    'It calls the getFilteredExternalInventoryBrands method and return proper response',
    function (): void {
        $externalCompany = ExternalCompany::factory()->make([
            'external_connection_id' => 1,
            'external_company_id' => 1,
        ]);

        $this->mock(ExternalCompanyQueries::class, function ($mock) use ($externalCompany): void {
            $mock->shouldReceive('getById')
                ->once()
                ->andReturn($externalCompany);
        });

        $this->mock(ExternalConnectionQueries::class, function ($mock): void {
            $mock->shouldReceive('getById')
                ->once()
                ->andReturn(new ExternalConnection());
        });

        Http::fake([
            '*' => Http::response([
                'data' => [],
            ], 200),
        ]);

        $externalInventoryReportController = new ExternalInventoryReportController();
        $response = $externalInventoryReportController->getFilteredExternalInventoryBrands(new Request([]));
        expect($response)->toBeArray();
    }
);

test(
    'It calls the getFilteredExternalInventorySizes method and return proper response',
    function (): void {
        $externalCompany = ExternalCompany::factory()->make([
            'external_connection_id' => 1,
            'external_company_id' => 1,
        ]);

        $this->mock(ExternalCompanyQueries::class, function ($mock) use ($externalCompany): void {
            $mock->shouldReceive('getById')
                ->once()
                ->andReturn($externalCompany);
        });

        $this->mock(ExternalConnectionQueries::class, function ($mock): void {
            $mock->shouldReceive('getById')
                ->once()
                ->andReturn(new ExternalConnection());
        });

        Http::fake([
            '*' => Http::response([
                'data' => [],
            ], 200),
        ]);

        $externalInventoryReportController = new ExternalInventoryReportController();
        $response = $externalInventoryReportController->getFilteredExternalInventorySizes(new Request([]));
        expect($response)->toBeArray();
    }
);

test(
    'It calls the getFilteredExternalInventoryColors method and return proper response',
    function (): void {
        $externalCompany = ExternalCompany::factory()->make([
            'external_connection_id' => 1,
            'external_company_id' => 1,
        ]);

        $this->mock(ExternalCompanyQueries::class, function ($mock) use ($externalCompany): void {
            $mock->shouldReceive('getById')
                ->once()
                ->andReturn($externalCompany);
        });

        $this->mock(ExternalConnectionQueries::class, function ($mock): void {
            $mock->shouldReceive('getById')
                ->once()
                ->andReturn(new ExternalConnection());
        });

        Http::fake([
            '*' => Http::response([
                'data' => [],
            ], 200),
        ]);

        $externalInventoryReportController = new ExternalInventoryReportController();
        $response = $externalInventoryReportController->getFilteredExternalInventoryColors(new Request([]));
        expect($response)->toBeArray();
    }
);

test(
    'It calls the getFilteredExternalInventoryDepartments method and return proper response',
    function (): void {
        $externalCompany = ExternalCompany::factory()->make([
            'external_connection_id' => 1,
            'external_company_id' => 1,
        ]);

        $this->mock(ExternalCompanyQueries::class, function ($mock) use ($externalCompany): void {
            $mock->shouldReceive('getById')
                ->once()
                ->andReturn($externalCompany);
        });

        $this->mock(ExternalConnectionQueries::class, function ($mock): void {
            $mock->shouldReceive('getById')
                ->once()
                ->andReturn(new ExternalConnection());
        });

        Http::fake([
            '*' => Http::response([
                'data' => [],
            ], 200),
        ]);

        $externalInventoryReportController = new ExternalInventoryReportController();
        $response = $externalInventoryReportController->getFilteredExternalInventoryDepartments(new Request([]));
        expect($response)->toBeArray();
    }
);

test(
    'It calls the getFilteredExternalInventoryArticleNumbers method and return proper response',
    function (): void {
        $externalCompany = ExternalCompany::factory()->make([
            'external_connection_id' => 1,
            'external_company_id' => 1,
        ]);

        $this->mock(ExternalCompanyQueries::class, function ($mock) use ($externalCompany): void {
            $mock->shouldReceive('getById')
                ->once()
                ->andReturn($externalCompany);
        });

        $this->mock(ExternalConnectionQueries::class, function ($mock): void {
            $mock->shouldReceive('getById')
                ->once()
                ->andReturn(new ExternalConnection());
        });

        Http::fake([
            '*' => Http::response([
                'data' => [],
            ], 200),
        ]);

        $externalInventoryReportController = new ExternalInventoryReportController();
        $response = $externalInventoryReportController->getFilteredExternalInventoryArticleNumbers(new Request([]));
        expect($response)->toBeArray();
    }
);

test(
    'It calls the getFilteredExternalInventoryTags method and return proper response',
    function (): void {
        $externalCompany = ExternalCompany::factory()->make([
            'external_connection_id' => 1,
            'external_company_id' => 1,
        ]);

        $this->mock(ExternalCompanyQueries::class, function ($mock) use ($externalCompany): void {
            $mock->shouldReceive('getById')
                ->once()
                ->andReturn($externalCompany);
        });

        $this->mock(ExternalConnectionQueries::class, function ($mock): void {
            $mock->shouldReceive('getById')
                ->once()
                ->andReturn(new ExternalConnection());
        });

        Http::fake([
            '*' => Http::response([
                'data' => [],
            ], 200),
        ]);

        $externalInventoryReportController = new ExternalInventoryReportController();
        $response = $externalInventoryReportController->getFilteredExternalInventoryTags(new Request([]));
        expect($response)->toBeArray();
    }
);

test(
    'It calls the getFilteredExternalInventoryStyles method and return proper response',
    function (): void {
        $externalCompany = ExternalCompany::factory()->make([
            'external_connection_id' => 1,
            'external_company_id' => 1,
        ]);

        $this->mock(ExternalCompanyQueries::class, function ($mock) use ($externalCompany): void {
            $mock->shouldReceive('getById')
                ->once()
                ->andReturn($externalCompany);
        });

        $this->mock(ExternalConnectionQueries::class, function ($mock): void {
            $mock->shouldReceive('getById')
                ->once()
                ->andReturn(new ExternalConnection());
        });

        Http::fake([
            '*' => Http::response([
                'data' => [],
            ], 200),
        ]);

        $externalInventoryReportController = new ExternalInventoryReportController();
        $response = $externalInventoryReportController->getFilteredExternalInventoryStyles(new Request([]));
        expect($response)->toBeArray();
    }
);

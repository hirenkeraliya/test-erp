<?php

declare(strict_types=1);

use App\Domains\Brand\BrandQueries;
use App\Domains\Category\CategoryQueries;
use App\Domains\Color\ColorQueries;
use App\Domains\Department\DepartmentQueries;
use App\Domains\Inventory\InventoryQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\Region\RegionQueries;
use App\Domains\Size\SizeQueries;
use App\Domains\Style\StyleQueries;
use App\Domains\Tag\TagQueries;
use App\Http\Controllers\Api\ExternalConnection\InventoryReportController;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\LazyCollection;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test('It fetchInventories method call and returns proper response', function (): void {
    $requestParameter = [
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => 'desc',
        'per_page' => 1,
        'product_id' => null,
        'category_id' => null,
        'brand_id' => null,
        'color_id' => null,
        'size_id' => null,
        'location_ids' => null,
        'article_numbers' => null,
        'department_ids' => null,
        'tag_ids' => null,
        'stock_type' => null,
        'style_ids' => null,
        'region_ids' => null,
        'selling_type' => null,
        'status' => null,
        'product_collection_id' => null,
    ];

    $companyId = 1;
    $totalStock = 100;

    $this->mock(InventoryQueries::class, function ($mock) use ($requestParameter, $companyId, $totalStock): void {
        $mock->shouldReceive('inventoryReportsList')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(new LengthAwarePaginator([], 50, 15));
        $mock->shouldReceive('getFilteredTotalsForInventoryReport')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn([
                'total_available_stock' => 10,
                'total_current_stock' => $totalStock,
                'total_reserved_stock' => 10,
                'total_transit_stock' => 10,
            ]);
    });
    $requestParameter['external_company_id'] = 1;
    $inventoryReportController = new InventoryReportController();
    $response = $inventoryReportController->fetchInventories(new Request($requestParameter));

    $this->assertEquals(50, $response['total_records']);
    $this->assertEquals($response['total_current_stock'], $totalStock);
    $this->assertEquals($response['total_available_stock'], 10);
    $this->assertEquals($response['total_reserved_stock'], 10);
    $this->assertEquals($response['total_transit_stock'], 10);
    expect($response['data']->resource->toArray())->toBeArray();
});

test('It getStoresWarehousesAndRegions method call and returns proper response', function (): void {
    $requestParameter = [
        'external_company_id' => 1,
    ];

    $this->mock(LocationQueries::class, function ($mock): void {
        $mock->shouldReceive('getWithExternalInventoriesByType')
            ->times(2)
            ->andReturn(collect());
    });

    $this->mock(RegionQueries::class, function ($mock): void {
        $mock->shouldReceive('getRegionByCompanyId')
            ->once()
            ->andReturn(collect());
    });

    $inventoryReportController = new InventoryReportController();
    $response = $inventoryReportController->getStoresWarehousesAndRegions(new Request($requestParameter));

    expect($response)
        ->toHaveKey('stores')
        ->toHaveKey('warehouses')
        ->toHaveKey('regions');
});

test('It getStoresAndRegions method call and returns proper response', function (): void {
    $requestParameter = [
        'external_company_id' => 1,
    ];

    $this->mock(LocationQueries::class, function ($mock): void {
        $mock->shouldReceive('getWithExternalInventoriesByType')
            ->once()
            ->andReturn(collect());
    });

    $this->mock(RegionQueries::class, function ($mock): void {
        $mock->shouldReceive('getRegionByCompanyId')
            ->once()
            ->andReturn(collect());
    });

    $inventoryReportController = new InventoryReportController();
    $response = $inventoryReportController->getStoresAndRegions(new Request($requestParameter));

    expect($response)
        ->toHaveKey('stores')
        ->toHaveKey('regions');
});

test('It getWarehousesAndRegions method call and returns proper response', function (): void {
    $requestParameter = [
        'external_company_id' => 1,
    ];

    $this->mock(LocationQueries::class, function ($mock): void {
        $mock->shouldReceive('getWithExternalInventoriesByType')
            ->once()
            ->andReturn(collect());
    });

    $this->mock(RegionQueries::class, function ($mock): void {
        $mock->shouldReceive('getRegionByCompanyId')
            ->once()
            ->andReturn(collect());
    });

    $inventoryReportController = new InventoryReportController();
    $response = $inventoryReportController->getWarehousesAndRegions(new Request($requestParameter));

    expect($response)
        ->toHaveKey('warehouses')
        ->toHaveKey('regions');
});

test('It exportInventories method call and returns proper response', function (): void {
    $requestParameter = [
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => 'desc',
        'per_page' => 1,
        'product_id' => null,
        'category_id' => null,
        'brand_id' => null,
        'color_id' => null,
        'size_id' => null,
        'location_ids' => null,
        'article_numbers' => null,
        'department_ids' => null,
        'tag_ids' => null,
        'stock_type' => null,
        'style_ids' => null,
        'region_ids' => null,
        'status' => null,
        'product_collection_id' => null,
        'export_columns' => null,
    ];

    $companyId = 1;

    $this->mock(InventoryQueries::class, function ($mock) use ($requestParameter, $companyId): void {
        $mock->shouldReceive('inventoryListsForExport')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(collect([]));
    });
    $requestParameter['external_company_id'] = 1;
    $inventoryReportController = new InventoryReportController();
    $response = $inventoryReportController->exportInventories('demo.csv', new Request($requestParameter));

    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});

test('It getFilteredInventoryProducts method call and returns proper response', function (): void {
    $this->mock(ProductQueries::class, function ($mock): void {
        $mock->shouldReceive('getActiveFilteredInventoryProducts')
            ->once()
            ->andReturn(collect());
    });

    $requestParameter = [
        'external_company_id' => 1,
    ];

    $inventoryReportController = new InventoryReportController();
    $response = $inventoryReportController->getFilteredInventoryProducts(new Request($requestParameter));
    expect($response)
        ->toHaveKey('products');
});

test('It getFilteredInventoryCategories method call and returns proper response', function (): void {
    $this->mock(CategoryQueries::class, function ($mock): void {
        $mock->shouldReceive('getFilteredCategoriesByCompanyId')
            ->once()
            ->andReturn(collect());
    });

    $requestParameter = [
        'external_company_id' => 1,
        'search_text' => 'test',
    ];

    $inventoryReportController = new InventoryReportController();
    $response = $inventoryReportController->getFilteredInventoryCategories(new Request($requestParameter));
    expect($response)
        ->toHaveKey('categories');
});

test('It getFilteredInventoryBrands method call and returns proper response', function (): void {
    $this->mock(BrandQueries::class, function ($mock): void {
        $mock->shouldReceive('getFilteredBrandsByCompanyId')
            ->once()
            ->andReturn(collect());
    });

    $requestParameter = [
        'external_company_id' => 1,
        'search_text' => 'test',
    ];

    $inventoryReportController = new InventoryReportController();
    $response = $inventoryReportController->getFilteredInventoryBrands(new Request($requestParameter));
    expect($response)
        ->toHaveKey('brands');
});

test('It getFilteredInventorySizes method call and returns proper response', function (): void {
    $this->mock(SizeQueries::class, function ($mock): void {
        $mock->shouldReceive('getFilteredSizesByCompanyId')
            ->once()
            ->andReturn(collect());
    });

    $requestParameter = [
        'external_company_id' => 1,
        'search_text' => 'test',
    ];

    $inventoryReportController = new InventoryReportController();
    $response = $inventoryReportController->getFilteredInventorySizes(new Request($requestParameter));
    expect($response)
        ->toHaveKey('sizes');
});

test('It getFilteredInventoryColors method call and returns proper response', function (): void {
    $this->mock(ColorQueries::class, function ($mock): void {
        $mock->shouldReceive('getFilteredColorsByCompanyId')
            ->once()
            ->andReturn(collect());
    });

    $requestParameter = [
        'external_company_id' => 1,
        'search_text' => 'test',
    ];

    $inventoryReportController = new InventoryReportController();
    $response = $inventoryReportController->getFilteredInventoryColors(new Request($requestParameter));
    expect($response)
        ->toHaveKey('colors');
});

test('It getFilteredInventoryDepartments method call and returns proper response', function (): void {
    $this->mock(DepartmentQueries::class, function ($mock): void {
        $mock->shouldReceive('getFilteredDepartmentsByCompanyId')
            ->once()
            ->andReturn(collect());
    });

    $requestParameter = [
        'external_company_id' => 1,
        'search_text' => 'test',
    ];

    $inventoryReportController = new InventoryReportController();
    $response = $inventoryReportController->getFilteredInventoryDepartments(new Request($requestParameter));
    expect($response)
        ->toHaveKey('departments');
});

test('It getFilteredInventoryArticleNumbers method call and returns proper response', function (): void {
    $this->mock(ProductQueries::class, function ($mock): void {
        $mock->shouldReceive('getFilteredArticleNumberByCompanyId')
            ->once()
            ->andReturn(new LazyCollection());
    });

    $requestParameter = [
        'external_company_id' => 1,
        'search_text' => 'test',
    ];

    $inventoryReportController = new InventoryReportController();
    $response = $inventoryReportController->getFilteredInventoryArticleNumbers(new Request($requestParameter));
    expect($response)
        ->toHaveKey('articleNumbers');
});

test('It getFilteredInventoryTags method call and returns proper response', function (): void {
    $this->mock(TagQueries::class, function ($mock): void {
        $mock->shouldReceive('getFilteredTagsByCompanyId')
            ->once()
            ->andReturn(collect());
    });

    $requestParameter = [
        'external_company_id' => 1,
        'search_text' => 'test',
    ];

    $inventoryReportController = new InventoryReportController();
    $response = $inventoryReportController->getFilteredInventoryTags(new Request($requestParameter));
    expect($response)
        ->toHaveKey('tags');
});

test('It getFilteredInventoryStyles method call and returns proper response', function (): void {
    $this->mock(StyleQueries::class, function ($mock): void {
        $mock->shouldReceive('getFilteredStylesByCompanyId')
            ->once()
            ->andReturn(collect());
    });

    $requestParameter = [
        'external_company_id' => 1,
        'search_text' => 'test',
    ];

    $inventoryReportController = new InventoryReportController();
    $response = $inventoryReportController->getFilteredInventoryStyles(new Request($requestParameter));
    expect($response)
        ->toHaveKey('styles');
});

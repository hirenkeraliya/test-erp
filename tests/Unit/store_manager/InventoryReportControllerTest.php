<?php

declare(strict_types=1);

use App\Domains\Inventory\InventoryQueries;
use App\Domains\Inventory\Services\InventoryService;
use App\Http\Controllers\StoreManager\InventoryReportController;
use App\Models\StoreManager;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test(
    'It calls the getPaginatedInventoryReportsListForStoreManager method of the inventory queries class and returns proper response',
    function (): void {
        $companyId = 1;
        setStoreManagerStoreCompanyIdInSession();

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
            'article_numbers' => null,
            'department_ids' => null,
            'tag_ids' => null,
            'style_ids' => null,
            'location_ids' => null,
            'stock_type' => null,
            'selling_type' => null,
            'region_ids' => null,
            'status' => null,
            'product_collection_id' => null,
            'attributes' => null,
        ];

        $totalStock = 100;

        $this->mock(InventoryQueries::class, function ($mock) use (
            $requestParameter,
            $companyId,
            $totalStock
        ): void {
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

        $inventoryReportController = new InventoryReportController();
        $response = $inventoryReportController->fetchInventories(new Request($requestParameter));

        $this->assertEquals(50, $response['total_records']);
        $this->assertEquals($response['total_current_stock'], $totalStock);
        $this->assertEquals($response['total_available_stock'], 10);
        $this->assertEquals($response['total_reserved_stock'], 10);
        $this->assertEquals($response['total_transit_stock'], 10);
        expect($response['data']->resource->toArray())->toBeArray();
    }
);

test(
    'It calls the exportInventories method of the inventory queries class and returns proper response',
    function (): void {
        setStoreManagerStoreCompanyIdInSession();

        $requestParameter = [
            'search_text' => null,
            'sort_by' => null,
            'sort_direction' => 'desc',
            'product_id' => null,
            'category_id' => null,
            'brand_id' => null,
            'color_id' => null,
            'size_id' => null,
            'article_numbers' => null,
            'department_ids' => null,
            'tag_ids' => null,
            'style_ids' => null,
            'location_ids' => null,
            'stock_type' => null,
            'selling_type' => null,
            'region_ids' => null,
            'status' => null,
            'product_collection_id' => null,
            'attributes' => null,
        ];

        $this->mock(InventoryQueries::class, function ($mock) use ($requestParameter): void {
            $mock->shouldReceive('inventoryListsForExport')
                ->once()
                ->with($requestParameter, 1)
                ->andReturn(collect([]));
        });

        $inventoryReportController = new InventoryReportController();
        $response = $inventoryReportController->exportInventories('filename.csv', new Request($requestParameter));

        $this->assertEquals(200, $response->getStatusCode());

        expect($response)->toBeInstanceOf(BinaryFileResponse::class);
    }
);

test(
    'It calls the exportInventories method of the inventory and returns proper response',
    function (): void {
        $companyId = 1;
        setStoreManagerStoreCompanyIdInSession();

        $requestParameter = [
            'search_text' => null,
            'sort_by' => null,
            'sort_direction' => 'desc',
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
            'selling_type' => null,
            'style_ids' => null,
            'region_ids' => null,
            'status' => null,
            'product_collection_id' => null,
            'attributes' => null,
        ];

        $this->mock(InventoryQueries::class, function ($mock) use ($requestParameter, $companyId): void {
            $mock->shouldReceive('inventoryListsForExport')
                ->once()
                ->with($requestParameter, $companyId)
                ->andReturn(collect([]));
        });

        $inventoryReportController = new InventoryReportController();
        $response = $inventoryReportController->exportInventories('filename.csv', new Request($requestParameter));

        $this->assertEquals(200, $response->getStatusCode());
        expect($response)->toBeInstanceOf(BinaryFileResponse::class);
    }
);

test('it calls checkInventoryExportLimit and returns proper response', function (): void {
    setStoreManagerStoreCompanyIdInSession();

    $requestParameter = [
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => 'desc',
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
        'selling_type' => null,
        'style_ids' => null,
        'region_ids' => null,
        'status' => null,
        'product_collection_id' => null,
        'attributes' => null,
    ];

    $storeManager = StoreManager::factory()->make([
        'employee_id' => 1,
    ]);

    $request = new Request($requestParameter);

    $request->setUserResolver(fn (): StoreManager => $storeManager);

    $this->mock(InventoryService::class, function ($mock): void {
        $mock->shouldReceive('exportInventoriesWithJob')
            ->once();
    });

    $inventoryReportController = new InventoryReportController();
    $response = $inventoryReportController->checkInventoryExportLimit($request);

    expect($response)->toBeArray();
});

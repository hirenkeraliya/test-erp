<?php

declare(strict_types=1);

use App\Domains\Company\CompanyQueries;
use App\Domains\InventoryUpdate\Enums\StockMovementFilters;
use App\Domains\InventoryUpdate\Enums\StockMovementReportTypes;
use App\Domains\InventoryUpdate\InventoryUpdateQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\StockMovement\Services\StockMovementReportService;
use App\Models\Brand;
use App\Models\Color;
use App\Models\Company;
use App\Models\Department;
use App\Models\InventoryUpdate;
use App\Models\Location;
use App\Models\Product;
use App\Models\Size;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

it(
    'exportStockMovementReport function exports inventory update data to expected BinaryFileResponse',
    function (
        string $locationName,
        string $locationQueries,
        string $locationMethodName,
        string $locationClass
    ): void {
        $company = Company::factory()->make([
            'id' => 1,
            'name' => 'Test Company',
            'default_country_id' => 1,
        ]);

        $filterData = [
            'location_ids' => [],
            'category_id' => null,
            'product_ids' => [],
            'date_range' => [now(), now()],
            'company_id' => $company->id,
            'report_type' => StockMovementReportTypes::BY_SUMMARY->value,
            'filter_by' => StockMovementFilters::BY_MASTER_PRODUCT->value,
        ];
        $location = ($locationClass)::factory()->make([
            'id' => 1,
            'company_id' => $company->id,
            'name' => 'Test ' . $locationName,
        ]);

        $product = Product::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'unit_of_measure_id' => 1,
            'season_id' => 1,
            'department_id' => 1,
            'color_id' => 1,
            'size_id' => 1,
            'brand_id' => 1,
            'style_id' => 1,
        ]);

        $color = Color::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'name' => 'test color',
        ]);

        $size = Size::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'name' => 'test color',
        ]);

        $brand = Brand::factory()->make([
            'id' => 1,
            'name' => 'brand',
        ]);

        $department = Department::factory()->make([
            'id' => 1,
            'name' => 'department',
            'company_id' => 1,
        ]);

        $inventoryUpdate = InventoryUpdate::factory()->make([
            'id' => 1,
            'product_id' => $product->id,
            'purchase_amount_id' => 1,
            'batch_id' => 1,
            'location_id' => $location->id,
        ]);

        $product->color = $color;
        $product->size = $size;
        $product->brand = $brand;
        $product->department = $department;

        $inventoryUpdate->product = $product;
        $inventoryUpdate->location = $location;

        $this->mock($locationQueries, function ($mock) use ($location, $locationMethodName): void {
            $mock->shouldReceive($locationMethodName)
                ->once()
                ->andReturn(new Collection([$location]));
        });

        $this->mock(InventoryUpdateQueries::class, function ($mock) use ($inventoryUpdate): void {
            $mock->shouldReceive('getStockMovementsOfProductsForALocationForPrint')
                ->once()
                ->andReturn(collect([$inventoryUpdate]));
            $mock->shouldReceive('getStockMovementsByLocationsAndProductIdsForPrint')
                ->once()
                ->andReturn(collect([]));
        });

        $this->mock(CompanyQueries::class, function ($mock) use ($company): void {
            $mock->shouldReceive('getNameAndCodeById')
                ->once()
                ->andReturn($company);
        });

        $this->mock(ProductQueries::class, function ($mock): void {
            $mock->shouldReceive('getFilteredProducts')
                ->once()
                ->andReturn(collect([])->toArray());
        });

        $stockMovementReportService = new StockMovementReportService();
        $result = $stockMovementReportService->exportStockMovementReport($filterData, 'demo.csv');

        expect($result)->toBeInstanceOf(BinaryFileResponse::class);
    }
)->with([
    ['Warehouse', LocationQueries::class, 'getByIdsWithNameAndCode', Location::class],
    ['Store', LocationQueries::class, 'getByIdsWithNameAndCode', Location::class],
]);

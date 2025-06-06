<?php

declare(strict_types=1);

use App\Domains\Batch\BatchQueries;
use App\Domains\Inventory\Services\StockAdjustmentInventoryService;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\PurchaseAmount\PurchaseAmountQueries;
use App\Domains\StockAdjustment\Services\StockAdjustmentService;
use App\Domains\StockAdjustmentItem\StockAdjustmentItemQueries;
use App\Models\Admin;
use App\Models\Batch;
use App\Models\Location;
use App\Models\MasterProduct;
use App\Models\StockAdjustment;
use App\Models\UnitOfMeasureDerivative;
use Illuminate\Support\Facades\Config;

test(
    'AddItemAndInventory method calls respective methods as expected when product variant is false',
    function (): void {
        $companyId = 1;

        Config::set('app.product_variant', false);

        $stockAdjustment = getStockAdjustment();

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
            'name' => 'new_store',
            'type_id' => LocationTypes::STORE->value,
        ]);

        $product = commonGetProductDetails();

        $batch = Batch::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
            'product_id' => $product->id,
            'number' => 'a123123',
        ]);

        $this->mock(LocationQueries::class, function ($mock) use ($location): void {
            $mock->shouldReceive('getIdAndNameByName')
                ->once()
                ->andReturn($location);
        });

        $this->mock(BatchQueries::class, function ($mock) use ($batch): void {
            $mock->shouldReceive('getByNumber')
                ->once()
                ->andReturn($batch);
        });

        $this->mock(StockAdjustmentItemQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->once();
        });

        $this->mock(StockAdjustmentInventoryService::class, function ($mock): void {
            $mock->shouldReceive('updateInventory')
                ->once();
        });

        $this->mock(PurchaseAmountQueries::class, function ($mock): void {
            $mock->shouldReceive('addBlankRecord')
                ->once();
        });

        $derivative = UnitOfMeasureDerivative::factory()->make([
            'unit_of_measure_id' => 1,
        ]);

        $stockAdjustmentProduct = [
            'location_type' => LocationTypes::STORE->name,
            'location_name' => $location->name,
            'upc' => 'abd123',
            'quantity' => 10,
            'landed_cost' => 10.10,
            'batch_number' => 'a123123',
            'batch_expiry_date' => '1990-10-10',
            'batch_external_id' => null,
            'batch_notes' => null,
        ];

        $stockAdjustmentService = new StockAdjustmentService();
        $stockAdjustmentService->addItemAndInventory(
            $stockAdjustment,
            $stockAdjustmentProduct,
            $product,
            $derivative,
            new Admin(),
            $companyId
        );
        $this->assertTrue(true);
    }
);

test(
    'AddItemAndInventory method calls respective methods as expected when product variant is true',
    function (): void {
        $companyId = 1;

        Config::set('app.product_variant', true);

        $stockAdjustment = getStockAdjustment();

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
            'name' => 'new_store',
            'type_id' => LocationTypes::STORE->value,
        ]);

        $product = commonGetProductDetails();

        $masterProduct = MasterProduct::factory()->make([
            'id' => 1,
            'variant_template_id' => 1,
            'company_id' => 1,
            'unit_of_measure_id' => 1,
            'has_batch' => true,
            'is_non_inventory' => false,
            'department_id' => 1,
            'brand_id' => 1,
        ]);

        $product->masterProduct = $masterProduct;

        $batch = Batch::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
            'product_id' => $product->id,
            'number' => 'a123123',
        ]);

        $this->mock(LocationQueries::class, function ($mock) use ($location): void {
            $mock->shouldReceive('getIdAndNameByName')
                ->once()
                ->andReturn($location);
        });

        $this->mock(BatchQueries::class, function ($mock) use ($batch): void {
            $mock->shouldReceive('getByNumber')
                ->once()
                ->andReturn($batch);
        });

        $this->mock(StockAdjustmentItemQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->once();
        });

        $this->mock(StockAdjustmentInventoryService::class, function ($mock): void {
            $mock->shouldReceive('updateInventory')
                ->once();
        });

        $this->mock(PurchaseAmountQueries::class, function ($mock): void {
            $mock->shouldReceive('addBlankRecord')
                ->once();
        });

        $derivative = UnitOfMeasureDerivative::factory()->make([
            'unit_of_measure_id' => 1,
        ]);

        $stockAdjustmentProduct = [
            'location_type' => LocationTypes::STORE->name,
            'location_name' => $location->name,
            'upc' => 'abd123',
            'quantity' => 10,
            'landed_cost' => 10.10,
            'batch_number' => 'a123123',
            'batch_expiry_date' => '1990-10-10',
            'batch_external_id' => null,
            'batch_notes' => null,
        ];

        $stockAdjustmentService = new StockAdjustmentService();
        $stockAdjustmentService->addItemAndInventory(
            $stockAdjustment,
            $stockAdjustmentProduct,
            $product,
            $derivative,
            new Admin(),
            $companyId
        );
        $this->assertTrue(true);
    }
);

test(
    'It calls addItemAndInventory method with warehouse and returns proper response when product variant is false',
    function (): void {
        $companyId = 1;

        $stockAdjustment = getStockAdjustment();

        Config::set('app.product_variant', false);

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
            'name' => 'new_warehouse',
            'type_id' => LocationTypes::WAREHOUSE->value,
        ]);

        $product = commonGetProductDetails();

        $batch = Batch::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
            'product_id' => $product->id,
            'number' => 'a123123',
        ]);

        $this->mock(LocationQueries::class, function ($mock) use ($location): void {
            $mock->shouldReceive('getIdAndNameByName')
                ->once()
                ->andReturn($location);
        });

        $this->mock(BatchQueries::class, function ($mock) use ($batch): void {
            $mock->shouldReceive('addNewAndGetId')
                ->times(0)
                ->andReturn(1);

            $mock->shouldReceive('getByNumber')
                ->once()
                ->andReturn($batch);
        });

        $this->mock(StockAdjustmentItemQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->once();
        });

        $this->mock(StockAdjustmentInventoryService::class, function ($mock): void {
            $mock->shouldReceive('updateInventory')
                ->once();
        });

        $this->mock(PurchaseAmountQueries::class, function ($mock): void {
            $mock->shouldReceive('addBlankRecord')
                ->once();
        });

        $derivative = UnitOfMeasureDerivative::factory()->make([
            'unit_of_measure_id' => 1,
        ]);

        $stockAdjustmentProduct = [
            'location_type' => LocationTypes::WAREHOUSE->name,
            'location_name' => $location->name,
            'upc' => 'abd123',
            'quantity' => 10,
            'landed_cost' => 10.10,
            'batch_number' => 'a123123',
            'batch_expiry_date' => '1990-10-10',
            'batch_external_id' => null,
            'batch_notes' => null,
        ];

        $stockAdjustmentService = new StockAdjustmentService();
        $stockAdjustmentService->addItemAndInventory(
            $stockAdjustment,
            $stockAdjustmentProduct,
            $product,
            $derivative,
            new Admin(),
            $companyId
        );
        $this->assertTrue(true);
    }
);

test(
    'It calls addItemAndInventory method with warehouse and returns proper response when product variant is true',
    function (): void {
        $companyId = 1;

        $stockAdjustment = getStockAdjustment();

        Config::set('app.product_variant', false);

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
            'name' => 'new_warehouse',
            'type_id' => LocationTypes::WAREHOUSE->value,
        ]);

        $product = commonGetProductDetails();

        $masterProduct = MasterProduct::factory()->make([
            'id' => 1,
            'variant_template_id' => 1,
            'company_id' => 1,
            'unit_of_measure_id' => 1,
            'has_batch' => true,
            'is_non_inventory' => false,
            'department_id' => 1,
            'brand_id' => 1,
        ]);

        $product->masterProduct = $masterProduct;

        $batch = Batch::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
            'product_id' => $product->id,
            'number' => 'a123123',
        ]);

        $this->mock(LocationQueries::class, function ($mock) use ($location): void {
            $mock->shouldReceive('getIdAndNameByName')
                ->once()
                ->andReturn($location);
        });

        $this->mock(BatchQueries::class, function ($mock) use ($batch): void {
            $mock->shouldReceive('addNewAndGetId')
                ->times(0)
                ->andReturn(1);
            $mock->shouldReceive('getByNumber')
                ->once()
                ->andReturn($batch);
        });

        $this->mock(StockAdjustmentItemQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->once();
        });

        $this->mock(StockAdjustmentInventoryService::class, function ($mock): void {
            $mock->shouldReceive('updateInventory')
                ->once();
        });

        $this->mock(PurchaseAmountQueries::class, function ($mock): void {
            $mock->shouldReceive('addBlankRecord')
                ->once();
        });

        $derivative = UnitOfMeasureDerivative::factory()->make([
            'unit_of_measure_id' => 1,
        ]);

        $stockAdjustmentProduct = [
            'location_type' => LocationTypes::WAREHOUSE->name,
            'location_name' => $location->name,
            'upc' => 'abd123',
            'quantity' => 10,
            'landed_cost' => 10.10,
            'batch_number' => 'a123123',
            'batch_expiry_date' => '1990-10-10',
            'batch_external_id' => null,
            'batch_notes' => null,
        ];

        $stockAdjustmentService = new StockAdjustmentService();
        $stockAdjustmentService->addItemAndInventory(
            $stockAdjustment,
            $stockAdjustmentProduct,
            $product,
            $derivative,
            new Admin(),
            $companyId
        );
        $this->assertTrue(true);
    }
);

function getStockAdjustment(): StockAdjustment
{
    return StockAdjustment::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'created_by_admin_id' => 1,
        'approved_by_employee_id' => 1,
        'adjustment_date' => now()->format('Y-m-d'),
    ]);
}

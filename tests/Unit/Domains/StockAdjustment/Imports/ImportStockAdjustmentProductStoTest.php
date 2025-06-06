<?php

declare(strict_types=1);

use App\Domains\Batch\BatchQueries;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\ImportRecord\Enums\ColumnValidationIssueTypes;
use App\Domains\Inventory\InventoryQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\StockAdjustment\Enums\StockAdjustmentImportStoColumns;
use App\Domains\StockAdjustment\Imports\ImportStockAdjustmentStoProduct;
use App\Domains\StockAdjustment\Services\StockAdjustmentService;
use App\Domains\StockAdjustment\StockAdjustmentQueries;
use App\Domains\UnitOfMeasureDerivative\UnitOfMeasureDerivativeQueries;
use App\Models\Admin;
use App\Models\Batch;
use App\Models\ImportRecord;
use App\Models\Inventory;
use App\Models\InventoryUnit;
use App\Models\Location;
use App\Models\MasterProduct;
use App\Models\Product;
use App\Models\UnitOfMeasure;
use App\Models\UnitOfMeasureDerivative;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;

test('validate import stock adjustment product import columns', function (): void {
    $requiredHeaderColumns = array_flip(StockAdjustmentImportStoColumns::getArrayValues());

    $importProduct = new ImportStockAdjustmentStoProduct();
    $response = $importProduct->validateColumns($requiredHeaderColumns, [], 1);

    $this->assertTrue(ColumnValidationIssueTypes::COLUMN_ISSUE->value === $response['type']);
    $this->assertFalse($response['status']);
});

test('validate method returns blank array when product variant is false', function (): void {
    Config::set('app.product_variant', false);

    $companyId = 1;
    $productData = getStockAdjustmentStoProductData();

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'created_by_id' => 1,
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
        'has_batch' => false,
        'is_non_inventory' => false,
    ]);

    $inventory = Inventory::factory()->make([
        'id' => 1,
        'product_id' => 1,
        'location_id' => 1,
        'stock' => 10,
    ]);

    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $this->mock(ProductQueries::class, function ($mock) use ($productData, $companyId, $product): void {
        $mock->shouldReceive('getActiveInventoryProductByUpcForStockAdjustment')
            ->once()
            ->with($productData['upc'], $companyId)
            ->andReturn($product);
    });

    $this->mock(LocationQueries::class, function ($mock) use ($location): void {
        $mock->shouldReceive('getIdAndNameByName')
            ->once()
            ->andReturn($location);
    });

    $this->mock(InventoryQueries::class, function ($mock) use ($inventory): void {
        $mock->shouldReceive('getByProductIdWithInventoryUnits')
            ->once()
            ->andReturn(collect([$inventory]));
    });

    $this->mock(UnitOfMeasureDerivativeQueries::class, function ($mock): void {
        $mock->shouldReceive('getDerivativesWithUnitsByName')
            ->once()
            ->andReturn(null);
    });

    $importProduct = new ImportStockAdjustmentStoProduct();
    $redirectResponse = $importProduct->validate($productData, $importRecord);
    $this->assertEquals([], $redirectResponse);
});

test('validate method returns blank array when product variant is true', function (): void {
    Config::set('app.product_variant', true);

    $companyId = 1;
    $productData = getStockAdjustmentStoProductData();

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'created_by_id' => 1,
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
        'has_batch' => false,
        'is_non_inventory' => false,
    ]);

    $masterProduct = MasterProduct::factory()->make([
        'id' => 1,
        'variant_template_id' => 1,
        'company_id' => 1,
        'unit_of_measure_id' => 1,
        'has_batch' => false,
        'is_non_inventory' => false,
        'department_id' => 1,
        'brand_id' => 1,
    ]);

    $product->masterProduct = $masterProduct;

    $inventory = Inventory::factory()->make([
        'id' => 1,
        'product_id' => 1,
        'location_id' => 1,
        'stock' => 10,
    ]);

    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $this->mock(ProductQueries::class, function ($mock) use ($productData, $companyId, $product): void {
        $mock->shouldReceive('getActiveInventoryProductByUpcForStockAdjustment')
            ->once()
            ->with($productData['upc'], $companyId)
            ->andReturn($product);
    });

    $this->mock(LocationQueries::class, function ($mock) use ($location): void {
        $mock->shouldReceive('getIdAndNameByName')
            ->once()
            ->andReturn($location);
    });

    $this->mock(InventoryQueries::class, function ($mock) use ($inventory): void {
        $mock->shouldReceive('getByProductIdWithInventoryUnits')
            ->once()
            ->andReturn(collect([$inventory]));
    });

    $this->mock(UnitOfMeasureDerivativeQueries::class, function ($mock): void {
        $mock->shouldReceive('getDerivativesWithUnitsByName')
            ->once()
            ->andReturn(null);
    });

    $importProduct = new ImportStockAdjustmentStoProduct();
    $redirectResponse = $importProduct->validate($productData, $importRecord);
    $this->assertEquals([], $redirectResponse);
});

test('validate method returns issue when quantity id not defined', function (): void {
    $companyId = 1;
    $productData = getStockAdjustmentStoProductData();

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'created_by_id' => 1,
    ]);

    $productData['quantity'] = null;

    $this->mock(ProductQueries::class, function ($mock) use ($productData, $companyId): void {
        $mock->shouldReceive('getActiveInventoryProductByUpcForStockAdjustment')
            ->once()
            ->with($productData['upc'], $companyId)
            ->andReturn(null);
    });

    $importProduct = new ImportStockAdjustmentStoProduct();
    $redirectResponse = $importProduct->validate($productData, $importRecord);
    $this->assertEquals(['quantity is required.'], $redirectResponse);
});

test('validate method returns issue when location type is store and store is not valid', function (): void {
    $companyId = 1;
    $productData = getStockAdjustmentStoProductData();

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'created_by_id' => 1,
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
        'has_batch' => false,
        'is_non_inventory' => false,
    ]);

    $productData['quantity'] = null;

    $this->mock(ProductQueries::class, function ($mock) use ($productData, $companyId, $product): void {
        $mock->shouldReceive('getActiveInventoryProductByUpcForStockAdjustment')
            ->once()
            ->with($productData['upc'], $companyId)
            ->andReturn($product);
    });

    $this->mock(LocationQueries::class, function ($mock): void {
        $mock->shouldReceive('getIdAndNameByName')
            ->once()
            ->andReturn(null);
    });

    $importProduct = new ImportStockAdjustmentStoProduct();
    $redirectResponse = $importProduct->validate($productData, $importRecord);
    $this->assertEquals(['The Selected Store is Invalid'], $redirectResponse);
});

test('validate method returns issue when location type is warehouse and warehouse is not valid', function (): void {
    $companyId = 1;
    $productData = getStockAdjustmentStoProductData();

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'created_by_id' => 1,
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
        'has_batch' => false,
        'is_non_inventory' => false,
    ]);

    $productData['location_type'] = 'WAREHOUSE';
    $productData['quantity'] = null;

    $this->mock(ProductQueries::class, function ($mock) use ($productData, $companyId, $product): void {
        $mock->shouldReceive('getActiveInventoryProductByUpcForStockAdjustment')
            ->once()
            ->with($productData['upc'], $companyId)
            ->andReturn($product);
    });

    $this->mock(LocationQueries::class, function ($mock): void {
        $mock->shouldReceive('getIdAndNameByName')
            ->once()
            ->andReturn(null);
    });

    $importProduct = new ImportStockAdjustmentStoProduct();
    $redirectResponse = $importProduct->validate($productData, $importRecord);
    $this->assertEquals(['The Selected Warehouse is Invalid'], $redirectResponse);
});

test(
    'validate method returns issue when product is not found from the inventory when product variant is false',
    function (): void {
        Config::set('app.product_variant', false);

        $companyId = 1;
        $productData = getStockAdjustmentStoProductData();

        $importRecord = ImportRecord::factory()->make([
            'company_id' => $companyId,
            'created_by_id' => 1,
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
            'has_batch' => false,
            'is_non_inventory' => false,
        ]);

        $inventory = Inventory::factory()->make([
            'id' => 1,
            'product_id' => 1,
            'location_id' => 2,
            'stock' => 10,
        ]);

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $this->mock(ProductQueries::class, function ($mock) use ($productData, $companyId, $product): void {
            $mock->shouldReceive('getActiveInventoryProductByUpcForStockAdjustment')
                ->once()
                ->with($productData['upc'], $companyId)
                ->andReturn($product);
        });

        $this->mock(LocationQueries::class, function ($mock) use ($location): void {
            $mock->shouldReceive('getIdAndNameByName')
                ->once()
                ->andReturn($location);
        });

        $this->mock(InventoryQueries::class, function ($mock) use ($inventory): void {
            $mock->shouldReceive('getByProductIdWithInventoryUnits')
                ->once()
                ->andReturn(collect([$inventory]));
        });

        $this->mock(UnitOfMeasureDerivativeQueries::class, function ($mock): void {
            $mock->shouldReceive('getDerivativesWithUnitsByName')
                ->once()
                ->andReturn(null);
        });

        $importProduct = new ImportStockAdjustmentStoProduct();
        $redirectResponse = $importProduct->validate($productData, $importRecord);
        $this->assertEquals([
            'The specified product UPC ' . $productData['upc'] . ' does not exist in our records.',
        ], $redirectResponse);
    }
);

test(
    'validate method returns issue when product is not found from the inventory when product variant is true',
    function (): void {
        Config::set('app.product_variant', true);

        $companyId = 1;
        $productData = getStockAdjustmentStoProductData();

        $importRecord = ImportRecord::factory()->make([
            'company_id' => $companyId,
            'created_by_id' => 1,
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
            'has_batch' => false,
            'is_non_inventory' => false,
        ]);

        $masterProduct = MasterProduct::factory()->make([
            'id' => 1,
            'variant_template_id' => 1,
            'company_id' => 1,
            'unit_of_measure_id' => 1,
            'has_batch' => false,
            'is_non_inventory' => false,
            'department_id' => 1,
            'brand_id' => 1,
        ]);

        $product->masterProduct = $masterProduct;

        $inventory = Inventory::factory()->make([
            'id' => 1,
            'product_id' => 1,
            'location_id' => 2,
            'stock' => 10,
        ]);

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $this->mock(ProductQueries::class, function ($mock) use ($productData, $companyId, $product): void {
            $mock->shouldReceive('getActiveInventoryProductByUpcForStockAdjustment')
                ->once()
                ->with($productData['upc'], $companyId)
                ->andReturn($product);
        });

        $this->mock(LocationQueries::class, function ($mock) use ($location): void {
            $mock->shouldReceive('getIdAndNameByName')
                ->once()
                ->andReturn($location);
        });

        $this->mock(InventoryQueries::class, function ($mock) use ($inventory): void {
            $mock->shouldReceive('getByProductIdWithInventoryUnits')
                ->once()
                ->andReturn(collect([$inventory]));
        });

        $this->mock(UnitOfMeasureDerivativeQueries::class, function ($mock): void {
            $mock->shouldReceive('getDerivativesWithUnitsByName')
                ->once()
                ->andReturn(null);
        });

        $importProduct = new ImportStockAdjustmentStoProduct();
        $redirectResponse = $importProduct->validate($productData, $importRecord);
        $this->assertEquals([
            'The specified product UPC ' . $productData['upc'] . ' does not exist in our records.',
        ], $redirectResponse);
    }
);

test(
    'validate method returns issue when sum of stock and quantity is less than zero when product variant is false',
    function (): void {
        Config::set('app.product_variant', false);

        $companyId = 1;
        $productData = getStockAdjustmentStoProductData();

        $importRecord = ImportRecord::factory()->make([
            'company_id' => $companyId,
            'created_by_id' => 1,
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
            'has_batch' => false,
            'is_non_inventory' => false,
        ]);

        $inventory = Inventory::factory()->make([
            'id' => 1,
            'product_id' => 1,
            'location_id' => 1,
            'stock' => 2,
        ]);

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $this->mock(ProductQueries::class, function ($mock) use ($productData, $companyId, $product): void {
            $mock->shouldReceive('getActiveInventoryProductByUpcForStockAdjustment')
                ->once()
                ->with($productData['upc'], $companyId)
                ->andReturn($product);
        });

        $this->mock(LocationQueries::class, function ($mock) use ($location): void {
            $mock->shouldReceive('getIdAndNameByName')
                ->once()
                ->andReturn($location);
        });

        $this->mock(InventoryQueries::class, function ($mock) use ($inventory): void {
            $mock->shouldReceive('getByProductIdWithInventoryUnits')
                ->once()
                ->andReturn(collect([$inventory]));
        });

        $this->mock(UnitOfMeasureDerivativeQueries::class, function ($mock): void {
            $mock->shouldReceive('getDerivativesWithUnitsByName')
                ->once()
                ->andReturn(null);
        });

        $importProduct = new ImportStockAdjustmentStoProduct();
        $redirectResponse = $importProduct->validate($productData, $importRecord);
        $this->assertEquals([
            'You cannot perform STO for the specified product UPC ' . $productData['upc'] . '. Because the original stock is' . $inventory->stock . ' and, you have requested to decrease it by ' . $productData['quantity'],
        ], $redirectResponse);
    }
);

test(
    'validate method returns issue when sum of stock and quantity is less than zero when product variant is true',
    function (): void {
        Config::set('app.product_variant', true);

        $companyId = 1;
        $productData = getStockAdjustmentStoProductData();

        $importRecord = ImportRecord::factory()->make([
            'company_id' => $companyId,
            'created_by_id' => 1,
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
            'has_batch' => false,
            'is_non_inventory' => false,
        ]);

        $masterProduct = MasterProduct::factory()->make([
            'id' => 1,
            'variant_template_id' => 1,
            'company_id' => 1,
            'unit_of_measure_id' => 1,
            'has_batch' => false,
            'is_non_inventory' => false,
            'department_id' => 1,
            'brand_id' => 1,
        ]);

        $product->masterProduct = $masterProduct;

        $inventory = Inventory::factory()->make([
            'id' => 1,
            'product_id' => 1,
            'location_id' => 1,
            'stock' => 2,
        ]);

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $this->mock(ProductQueries::class, function ($mock) use ($productData, $companyId, $product): void {
            $mock->shouldReceive('getActiveInventoryProductByUpcForStockAdjustment')
                ->once()
                ->with($productData['upc'], $companyId)
                ->andReturn($product);
        });

        $this->mock(LocationQueries::class, function ($mock) use ($location): void {
            $mock->shouldReceive('getIdAndNameByName')
                ->once()
                ->andReturn($location);
        });

        $this->mock(InventoryQueries::class, function ($mock) use ($inventory): void {
            $mock->shouldReceive('getByProductIdWithInventoryUnits')
                ->once()
                ->andReturn(collect([$inventory]));
        });

        $this->mock(UnitOfMeasureDerivativeQueries::class, function ($mock): void {
            $mock->shouldReceive('getDerivativesWithUnitsByName')
                ->once()
                ->andReturn(null);
        });

        $importProduct = new ImportStockAdjustmentStoProduct();
        $redirectResponse = $importProduct->validate($productData, $importRecord);
        $this->assertEquals([
            'You cannot perform STO for the specified product UPC ' . $productData['upc'] . '. Because the original stock is' . $inventory->stock . ' and, you have requested to decrease it by ' . $productData['quantity'],
        ], $redirectResponse);
    }
);

test(
    'validate method returns issue when sum of stock and quantity is less than zero with derivative name when product variant is false',
    function (): void {
        Config::set('app.product_variant', false);

        $companyId = 1;
        $productData = getStockAdjustmentStoProductData();

        $importRecord = ImportRecord::factory()->make([
            'company_id' => $companyId,
            'created_by_id' => 1,
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
            'has_batch' => false,
            'is_non_inventory' => false,
        ]);

        $inventory = Inventory::factory()->make([
            'id' => 1,
            'product_id' => 1,
            'location_id' => 1,
            'stock' => 0,
        ]);

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $unitOfMeasure = UnitOfMeasure::factory()->make([
            'id' => 1,
            'company_id' => 1,
        ]);

        $product->unitOfMeasure = $product;

        $derivative = UnitOfMeasureDerivative::factory()->make([
            'id' => 1,
            'unit_of_measure_id' => 1,
            'ratio' => 0.25,
        ]);

        $derivative->unitOfMeasure = $unitOfMeasure;

        $productData['derivative_name'] = $derivative->name;

        $this->mock(ProductQueries::class, function ($mock) use ($productData, $companyId, $product): void {
            $mock->shouldReceive('getActiveInventoryProductByUpcForStockAdjustment')
                ->once()
                ->with($productData['upc'], $companyId)
                ->andReturn($product);
        });

        $this->mock(LocationQueries::class, function ($mock) use ($location): void {
            $mock->shouldReceive('getIdAndNameByName')
                ->once()
                ->andReturn($location);
        });

        $this->mock(InventoryQueries::class, function ($mock) use ($inventory): void {
            $mock->shouldReceive('getByProductIdWithInventoryUnits')
                ->once()
                ->andReturn(collect([$inventory]));
        });

        $this->mock(UnitOfMeasureDerivativeQueries::class, function ($mock) use ($derivative): void {
            $mock->shouldReceive('getDerivativesWithUnitsByName')
                ->once()
                ->andReturn($derivative);
        });

        $importProduct = new ImportStockAdjustmentStoProduct();
        $redirectResponse = $importProduct->validate($productData, $importRecord);
        $this->assertEquals([
            'You cannot perform STO for the specified product UPC ' . $productData['upc'] . '. Because the original stock is' . $inventory->stock . ' and, you have requested to decrease it by ' . $productData['quantity'] / $derivative->ratio,
        ], $redirectResponse);
    }
);

test(
    'validate method returns issue when sum of stock and quantity is less than zero with derivative name when product variant is true',
    function (): void {
        Config::set('app.product_variant', true);

        $companyId = 1;
        $productData = getStockAdjustmentStoProductData();

        $importRecord = ImportRecord::factory()->make([
            'company_id' => $companyId,
            'created_by_id' => 1,
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
            'has_batch' => false,
            'is_non_inventory' => false,
        ]);

        $masterProduct = MasterProduct::factory()->make([
            'id' => 1,
            'variant_template_id' => 1,
            'company_id' => 1,
            'unit_of_measure_id' => 1,
            'has_batch' => false,
            'is_non_inventory' => false,
            'department_id' => 1,
            'brand_id' => 1,
        ]);

        $product->masterProduct = $masterProduct;

        $inventory = Inventory::factory()->make([
            'id' => 1,
            'product_id' => 1,
            'location_id' => 1,
            'stock' => 0,
        ]);

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $unitOfMeasure = UnitOfMeasure::factory()->make([
            'id' => 1,
            'company_id' => 1,
        ]);

        $masterProduct->unitOfMeasure = $masterProduct;

        $derivative = UnitOfMeasureDerivative::factory()->make([
            'id' => 1,
            'unit_of_measure_id' => 1,
            'ratio' => 0.25,
        ]);

        $derivative->unitOfMeasure = $unitOfMeasure;

        $productData['derivative_name'] = $derivative->name;

        $this->mock(ProductQueries::class, function ($mock) use ($productData, $companyId, $product): void {
            $mock->shouldReceive('getActiveInventoryProductByUpcForStockAdjustment')
                ->once()
                ->with($productData['upc'], $companyId)
                ->andReturn($product);
        });

        $this->mock(LocationQueries::class, function ($mock) use ($location): void {
            $mock->shouldReceive('getIdAndNameByName')
                ->once()
                ->andReturn($location);
        });

        $this->mock(InventoryQueries::class, function ($mock) use ($inventory): void {
            $mock->shouldReceive('getByProductIdWithInventoryUnits')
                ->once()
                ->andReturn(collect([$inventory]));
        });

        $this->mock(UnitOfMeasureDerivativeQueries::class, function ($mock) use ($derivative): void {
            $mock->shouldReceive('getDerivativesWithUnitsByName')
                ->once()
                ->andReturn($derivative);
        });

        $importProduct = new ImportStockAdjustmentStoProduct();
        $redirectResponse = $importProduct->validate($productData, $importRecord);
        $this->assertEquals([
            'You cannot perform STO for the specified product UPC ' . $productData['upc'] . '. Because the original stock is' . $inventory->stock . ' and, you have requested to decrease it by ' . $productData['quantity'] / $derivative->ratio,
        ], $redirectResponse);
    }
);

test(
    'validate method returns issue when quantity is greater than zero when product variant is false',
    function (): void {
        Config::set('app.product_variant', false);
        $companyId = 1;
        $productData = getStockAdjustmentStoProductData();

        $importRecord = ImportRecord::factory()->make([
            'company_id' => $companyId,
            'created_by_id' => 1,
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
            'has_batch' => false,
            'is_non_inventory' => false,
        ]);

        $inventory = Inventory::factory()->make([
            'id' => 1,
            'product_id' => 1,
            'location_id' => 1,
            'stock' => 0,
        ]);

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $unitOfMeasure = UnitOfMeasure::factory()->make([
            'id' => 1,
            'company_id' => 1,
        ]);

        $product->unitOfMeasure = $product;

        $derivative = UnitOfMeasureDerivative::factory()->make([
            'id' => 1,
            'unit_of_measure_id' => 1,
            'ratio' => 0.25,
        ]);

        $derivative->unitOfMeasure = $unitOfMeasure;

        $productData['derivative_name'] = $derivative->name;
        $productData['quantity'] = 1;

        $this->mock(ProductQueries::class, function ($mock) use ($productData, $companyId, $product): void {
            $mock->shouldReceive('getActiveInventoryProductByUpcForStockAdjustment')
                ->once()
                ->with($productData['upc'], $companyId)
                ->andReturn($product);
        });

        $this->mock(LocationQueries::class, function ($mock) use ($location): void {
            $mock->shouldReceive('getIdAndNameByName')
                ->once()
                ->andReturn($location);
        });

        $this->mock(InventoryQueries::class, function ($mock) use ($inventory): void {
            $mock->shouldReceive('getByProductIdWithInventoryUnits')
                ->once()
                ->andReturn(collect([$inventory]));
        });

        $this->mock(UnitOfMeasureDerivativeQueries::class, function ($mock) use ($derivative): void {
            $mock->shouldReceive('getDerivativesWithUnitsByName')
                ->once()
                ->andReturn($derivative);
        });

        $importProduct = new ImportStockAdjustmentStoProduct();
        $redirectResponse = $importProduct->validate($productData, $importRecord);
        $this->assertEquals([
            'The quantity of the product should be negative for the selected type.',
        ], $redirectResponse);
    }
);

test(
    'validate method returns issue when quantity is greater than zero when product variant is true',
    function (): void {
        Config::set('app.product_variant', true);
        $companyId = 1;
        $productData = getStockAdjustmentStoProductData();

        $importRecord = ImportRecord::factory()->make([
            'company_id' => $companyId,
            'created_by_id' => 1,
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
            'has_batch' => false,
            'is_non_inventory' => false,
        ]);

        $masterProduct = MasterProduct::factory()->make([
            'id' => 1,
            'variant_template_id' => 1,
            'company_id' => 1,
            'unit_of_measure_id' => 1,
            'has_batch' => false,
            'is_non_inventory' => false,
            'department_id' => 1,
            'brand_id' => 1,
        ]);

        $product->masterProduct = $masterProduct;

        $inventory = Inventory::factory()->make([
            'id' => 1,
            'product_id' => 1,
            'location_id' => 1,
            'stock' => 0,
        ]);

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $unitOfMeasure = UnitOfMeasure::factory()->make([
            'id' => 1,
            'company_id' => 1,
        ]);

        $masterProduct->unitOfMeasure = $masterProduct;

        $derivative = UnitOfMeasureDerivative::factory()->make([
            'id' => 1,
            'unit_of_measure_id' => 1,
            'ratio' => 0.25,
        ]);

        $derivative->unitOfMeasure = $unitOfMeasure;

        $productData['derivative_name'] = $derivative->name;
        $productData['quantity'] = 1;

        $this->mock(ProductQueries::class, function ($mock) use ($productData, $companyId, $product): void {
            $mock->shouldReceive('getActiveInventoryProductByUpcForStockAdjustment')
                ->once()
                ->with($productData['upc'], $companyId)
                ->andReturn($product);
        });

        $this->mock(LocationQueries::class, function ($mock) use ($location): void {
            $mock->shouldReceive('getIdAndNameByName')
                ->once()
                ->andReturn($location);
        });

        $this->mock(InventoryQueries::class, function ($mock) use ($inventory): void {
            $mock->shouldReceive('getByProductIdWithInventoryUnits')
                ->once()
                ->andReturn(collect([$inventory]));
        });

        $this->mock(UnitOfMeasureDerivativeQueries::class, function ($mock) use ($derivative): void {
            $mock->shouldReceive('getDerivativesWithUnitsByName')
                ->once()
                ->andReturn($derivative);
        });

        $importProduct = new ImportStockAdjustmentStoProduct();
        $redirectResponse = $importProduct->validate($productData, $importRecord);
        $this->assertEquals([
            'The quantity of the product should be negative for the selected type.',
        ], $redirectResponse);
    }
);

test('validate method returns issue when product has no derivative when product variant is false', function (): void {
    Config::set('app.product_variant', false);
    $companyId = 1;
    $productData = getStockAdjustmentStoProductData();

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'created_by_id' => 1,
    ]);

    $product = Product::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'unit_of_measure_id' => null,
        'season_id' => 1,
        'department_id' => 1,
        'color_id' => 1,
        'size_id' => 1,
        'brand_id' => 1,
        'style_id' => 1,
        'has_batch' => false,
        'is_non_inventory' => false,
    ]);

    $inventory = Inventory::factory()->make([
        'id' => 1,
        'product_id' => 1,
        'location_id' => 1,
        'stock' => 100,
    ]);

    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $unitOfMeasure = UnitOfMeasure::factory()->make([
        'id' => 1,
        'company_id' => 1,
    ]);

    $product->unitOfMeasure = $product;

    $derivative = UnitOfMeasureDerivative::factory()->make([
        'id' => 1,
        'unit_of_measure_id' => 1,
        'ratio' => 0.25,
    ]);

    $derivative->unitOfMeasure = $unitOfMeasure;

    $productData['derivative_name'] = $derivative->name;

    $this->mock(ProductQueries::class, function ($mock) use ($productData, $companyId, $product): void {
        $mock->shouldReceive('getActiveInventoryProductByUpcForStockAdjustment')
            ->once()
            ->with($productData['upc'], $companyId)
            ->andReturn($product);
    });

    $this->mock(LocationQueries::class, function ($mock) use ($location): void {
        $mock->shouldReceive('getIdAndNameByName')
            ->once()
            ->andReturn($location);
    });

    $this->mock(InventoryQueries::class, function ($mock) use ($inventory): void {
        $mock->shouldReceive('getByProductIdWithInventoryUnits')
            ->once()
            ->andReturn(collect([$inventory]));
    });

    $this->mock(UnitOfMeasureDerivativeQueries::class, function ($mock) use ($derivative): void {
        $mock->shouldReceive('getDerivativesWithUnitsByName')
            ->once()
            ->andReturn($derivative);
    });

    $importProduct = new ImportStockAdjustmentStoProduct();
    $redirectResponse = $importProduct->validate($productData, $importRecord);
    $this->assertEquals([
        'Derivate name is not required due to unit of measure does not set for the product with UPC ' . $productData['upc'],
    ], $redirectResponse);
});

test('validate method returns issue when product has no derivative when product variant is true', function (): void {
    Config::set('app.product_variant', true);
    $companyId = 1;
    $productData = getStockAdjustmentStoProductData();

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'created_by_id' => 1,
    ]);

    $product = Product::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'unit_of_measure_id' => null,
        'season_id' => 1,
        'department_id' => 1,
        'color_id' => 1,
        'size_id' => 1,
        'brand_id' => 1,
        'style_id' => 1,
        'has_batch' => false,
        'is_non_inventory' => false,
    ]);

    $masterProduct = MasterProduct::factory()->make([
        'id' => 1,
        'variant_template_id' => 1,
        'company_id' => 1,
        'unit_of_measure_id' => null,
        'has_batch' => false,
        'is_non_inventory' => false,
        'department_id' => 1,
        'brand_id' => 1,
    ]);

    $product->masterProduct = $masterProduct;

    $inventory = Inventory::factory()->make([
        'id' => 1,
        'product_id' => 1,
        'location_id' => 1,
        'stock' => 100,
    ]);

    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $unitOfMeasure = UnitOfMeasure::factory()->make([
        'id' => 1,
        'company_id' => 1,
    ]);

    $derivative = UnitOfMeasureDerivative::factory()->make([
        'id' => 1,
        'unit_of_measure_id' => 1,
        'ratio' => 0.25,
    ]);

    $derivative->unitOfMeasure = $unitOfMeasure;

    $productData['derivative_name'] = $derivative->name;

    $this->mock(ProductQueries::class, function ($mock) use ($productData, $companyId, $product): void {
        $mock->shouldReceive('getActiveInventoryProductByUpcForStockAdjustment')
            ->once()
            ->with($productData['upc'], $companyId)
            ->andReturn($product);
    });

    $this->mock(LocationQueries::class, function ($mock) use ($location): void {
        $mock->shouldReceive('getIdAndNameByName')
            ->once()
            ->andReturn($location);
    });

    $this->mock(InventoryQueries::class, function ($mock) use ($inventory): void {
        $mock->shouldReceive('getByProductIdWithInventoryUnits')
            ->once()
            ->andReturn(collect([$inventory]));
    });

    $this->mock(UnitOfMeasureDerivativeQueries::class, function ($mock) use ($derivative): void {
        $mock->shouldReceive('getDerivativesWithUnitsByName')
            ->once()
            ->andReturn($derivative);
    });

    $importProduct = new ImportStockAdjustmentStoProduct();
    $redirectResponse = $importProduct->validate($productData, $importRecord);

    $this->assertEquals([
        'Derivate name is not required due to unit of measure does not set for the product with UPC ' . $productData['upc'],
    ], $redirectResponse);
});

test(
    'validate method returns issue when product has derivative, but mentioned derivative does not exists when product variant is false',
    function (): void {
        Config::set('app.product_variant', false);
        $companyId = 1;
        $productData = getStockAdjustmentStoProductData();

        $importRecord = ImportRecord::factory()->make([
            'company_id' => $companyId,
            'created_by_id' => 1,
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
            'has_batch' => false,
            'is_non_inventory' => false,
        ]);

        $inventory = Inventory::factory()->make([
            'id' => 1,
            'product_id' => 1,
            'location_id' => 1,
            'stock' => 100,
        ]);

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $unitOfMeasure = UnitOfMeasure::factory()->make([
            'id' => 1,
            'company_id' => 1,
        ]);

        $product->unitOfMeasure = $product;

        $derivative = UnitOfMeasureDerivative::factory()->make([
            'id' => 1,
            'unit_of_measure_id' => 1,
            'ratio' => 0.25,
        ]);

        $derivative->unitOfMeasure = $unitOfMeasure;

        $productData['derivative_name'] = '123';
        $productData['upc'] = $product->upc;

        $this->mock(ProductQueries::class, function ($mock) use ($productData, $companyId, $product): void {
            $mock->shouldReceive('getActiveInventoryProductByUpcForStockAdjustment')
                ->once()
                ->with($productData['upc'], $companyId)
                ->andReturn($product);
        });

        $this->mock(LocationQueries::class, function ($mock) use ($location): void {
            $mock->shouldReceive('getIdAndNameByName')
                ->once()
                ->andReturn($location);
        });

        $this->mock(InventoryQueries::class, function ($mock) use ($inventory): void {
            $mock->shouldReceive('getByProductIdWithInventoryUnits')
                ->once()
                ->andReturn(collect([$inventory]));
        });

        $this->mock(UnitOfMeasureDerivativeQueries::class, function ($mock): void {
            $mock->shouldReceive('getDerivativesWithUnitsByName')
                ->once()
                ->andReturn(null);
        });

        $importProduct = new ImportStockAdjustmentStoProduct();
        $redirectResponse = $importProduct->validate($productData, $importRecord);

        $this->assertEquals([
            'Derivate name `' . $productData['derivative_name'] . '` does not exists in our records for the product with UPC ' . $productData['upc'] . '.',
        ], $redirectResponse);
    }
);

test(
    'validate method returns issue when product has derivative, but mentioned derivative does not exists when product variant is true',
    function (): void {
        Config::set('app.product_variant', true);
        $companyId = 1;
        $productData = getStockAdjustmentStoProductData();

        $importRecord = ImportRecord::factory()->make([
            'company_id' => $companyId,
            'created_by_id' => 1,
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
            'has_batch' => false,
            'is_non_inventory' => false,
        ]);

        $masterProduct = MasterProduct::factory()->make([
            'id' => 1,
            'variant_template_id' => 1,
            'company_id' => 1,
            'unit_of_measure_id' => 1,
            'has_batch' => false,
            'is_non_inventory' => false,
            'department_id' => 1,
            'brand_id' => 1,
        ]);

        $product->masterProduct = $masterProduct;

        $inventory = Inventory::factory()->make([
            'id' => 1,
            'product_id' => 1,
            'location_id' => 1,
            'stock' => 100,
        ]);

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $unitOfMeasure = UnitOfMeasure::factory()->make([
            'id' => 1,
            'company_id' => 1,
        ]);

        $masterProduct->unitOfMeasure = $masterProduct;

        $derivative = UnitOfMeasureDerivative::factory()->make([
            'id' => 1,
            'unit_of_measure_id' => 1,
            'ratio' => 0.25,
        ]);

        $derivative->unitOfMeasure = $unitOfMeasure;

        $productData['derivative_name'] = '123';
        $productData['upc'] = $product->upc;

        $this->mock(ProductQueries::class, function ($mock) use ($productData, $companyId, $product): void {
            $mock->shouldReceive('getActiveInventoryProductByUpcForStockAdjustment')
                ->once()
                ->with($productData['upc'], $companyId)
                ->andReturn($product);
        });

        $this->mock(LocationQueries::class, function ($mock) use ($location): void {
            $mock->shouldReceive('getIdAndNameByName')
                ->once()
                ->andReturn($location);
        });

        $this->mock(InventoryQueries::class, function ($mock) use ($inventory): void {
            $mock->shouldReceive('getByProductIdWithInventoryUnits')
                ->once()
                ->andReturn(collect([$inventory]));
        });

        $this->mock(UnitOfMeasureDerivativeQueries::class, function ($mock): void {
            $mock->shouldReceive('getDerivativesWithUnitsByName')
                ->once()
                ->andReturn(null);
        });

        $importProduct = new ImportStockAdjustmentStoProduct();
        $redirectResponse = $importProduct->validate($productData, $importRecord);

        $this->assertEquals([
            'Derivate name `' . $productData['derivative_name'] . '` does not exists in our records for the product with UPC ' . $productData['upc'] . '.',
        ], $redirectResponse);
    }
);

test(
    'validate method returns issue when product has derivative, but mentioned derivative unit of measure id is not matching when product variant is false',
    function (): void {
        Config::set('app.product_variant', false);
        $companyId = 1;
        $productData = getStockAdjustmentStoProductData();

        $importRecord = ImportRecord::factory()->make([
            'company_id' => $companyId,
            'created_by_id' => 1,
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
            'has_batch' => false,
            'is_non_inventory' => false,
        ]);

        $inventory = Inventory::factory()->make([
            'id' => 1,
            'product_id' => 1,
            'location_id' => 1,
            'stock' => 100,
        ]);

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $unitOfMeasure = UnitOfMeasure::factory()->make([
            'id' => 1,
            'company_id' => 1,
        ]);

        $product->unitOfMeasure = $unitOfMeasure;

        $derivative = UnitOfMeasureDerivative::factory()->make([
            'id' => 1,
            'unit_of_measure_id' => 2,
            'ratio' => 0.25,
        ]);

        $derivative->unitOfMeasure = $unitOfMeasure;

        $productData['derivative_name'] = $derivative->name . '123';

        $this->mock(ProductQueries::class, function ($mock) use ($productData, $companyId, $product): void {
            $mock->shouldReceive('getActiveInventoryProductByUpcForStockAdjustment')
                ->once()
                ->with($productData['upc'], $companyId)
                ->andReturn($product);
        });

        $this->mock(LocationQueries::class, function ($mock) use ($location): void {
            $mock->shouldReceive('getIdAndNameByName')
                ->once()
                ->andReturn($location);
        });

        $this->mock(InventoryQueries::class, function ($mock) use ($inventory): void {
            $mock->shouldReceive('getByProductIdWithInventoryUnits')
                ->once()
                ->andReturn(collect([$inventory]));
        });

        $this->mock(UnitOfMeasureDerivativeQueries::class, function ($mock) use ($derivative): void {
            $mock->shouldReceive('getDerivativesWithUnitsByName')
                ->once()
                ->andReturn($derivative);
        });

        $importProduct = new ImportStockAdjustmentStoProduct();
        $redirectResponse = $importProduct->validate($productData, $importRecord);
        $this->assertEquals([
            'Derivate name `' . $productData['derivative_name'] . '` have UOM `' . $unitOfMeasure->name . '` does not match with the product UPC ' . $productData['upc'] . ' have UOM `' . $unitOfMeasure->name,
        ], $redirectResponse);
    }
);

test(
    'validate method returns issue when product has derivative, but mentioned derivative unit of measure id is not matching when product variant is true',
    function (): void {
        Config::set('app.product_variant', true);

        $companyId = 1;
        $productData = getStockAdjustmentStoProductData();

        $importRecord = ImportRecord::factory()->make([
            'company_id' => $companyId,
            'created_by_id' => 1,
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
            'has_batch' => false,
            'is_non_inventory' => false,
        ]);

        $masterProduct = MasterProduct::factory()->make([
            'id' => 1,
            'variant_template_id' => 1,
            'company_id' => 1,
            'unit_of_measure_id' => 1,
            'has_batch' => false,
            'is_non_inventory' => false,
            'department_id' => 1,
            'brand_id' => 1,
        ]);

        $product->masterProduct = $masterProduct;

        $inventory = Inventory::factory()->make([
            'id' => 1,
            'product_id' => 1,
            'location_id' => 1,
            'stock' => 100,
        ]);

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $unitOfMeasure = UnitOfMeasure::factory()->make([
            'id' => 1,
            'company_id' => 1,
        ]);

        $masterProduct->unitOfMeasure = $unitOfMeasure;

        $derivative = UnitOfMeasureDerivative::factory()->make([
            'id' => 1,
            'unit_of_measure_id' => 2,
            'ratio' => 0.25,
        ]);

        $derivative->unitOfMeasure = $unitOfMeasure;

        $productData['derivative_name'] = $derivative->name . '123';

        $this->mock(ProductQueries::class, function ($mock) use ($productData, $companyId, $product): void {
            $mock->shouldReceive('getActiveInventoryProductByUpcForStockAdjustment')
                ->once()
                ->with($productData['upc'], $companyId)
                ->andReturn($product);
        });

        $this->mock(LocationQueries::class, function ($mock) use ($location): void {
            $mock->shouldReceive('getIdAndNameByName')
                ->once()
                ->andReturn($location);
        });

        $this->mock(InventoryQueries::class, function ($mock) use ($inventory): void {
            $mock->shouldReceive('getByProductIdWithInventoryUnits')
                ->once()
                ->andReturn(collect([$inventory]));
        });

        $this->mock(UnitOfMeasureDerivativeQueries::class, function ($mock) use ($derivative): void {
            $mock->shouldReceive('getDerivativesWithUnitsByName')
                ->once()
                ->andReturn($derivative);
        });

        $importProduct = new ImportStockAdjustmentStoProduct();
        $redirectResponse = $importProduct->validate($productData, $importRecord);
        $this->assertEquals([
            'Derivate name `' . $productData['derivative_name'] . '` have UOM `' . $unitOfMeasure->name . '` does not match with the product UPC ' . $productData['upc'] . ' have UOM `' . $unitOfMeasure->name,
        ], $redirectResponse);
    }
);

test(
    'validate method returns issue when product has batch, batch number not defined when product variant is false',
    function (): void {
        Config::set('app.product_variant', false);

        $companyId = 1;
        $productData = getStockAdjustmentStoProductData();

        $importRecord = ImportRecord::factory()->make([
            'company_id' => $companyId,
            'created_by_id' => 1,
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
            'has_batch' => true,
            'is_non_inventory' => false,
        ]);

        $inventory = Inventory::factory()->make([
            'id' => 1,
            'product_id' => 1,
            'location_id' => 1,
            'stock' => 100,
        ]);

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $unitOfMeasure = UnitOfMeasure::factory()->make([
            'id' => 1,
            'company_id' => 1,
        ]);

        $product->unitOfMeasure = $unitOfMeasure;

        $derivative = UnitOfMeasureDerivative::factory()->make([
            'id' => 1,
            'unit_of_measure_id' => 1,
            'ratio' => 0.25,
        ]);

        $derivative->unitOfMeasure = $unitOfMeasure;

        $productData['derivative_name'] = $derivative->name . '123';

        $this->mock(ProductQueries::class, function ($mock) use ($productData, $companyId, $product): void {
            $mock->shouldReceive('getActiveInventoryProductByUpcForStockAdjustment')
                ->once()
                ->with($productData['upc'], $companyId)
                ->andReturn($product);
        });

        $this->mock(LocationQueries::class, function ($mock) use ($location): void {
            $mock->shouldReceive('getIdAndNameByName')
                ->once()
                ->andReturn($location);
        });

        $this->mock(InventoryQueries::class, function ($mock) use ($inventory): void {
            $mock->shouldReceive('getByProductIdWithInventoryUnits')
                ->once()
                ->andReturn(collect([$inventory]));
        });

        $this->mock(UnitOfMeasureDerivativeQueries::class, function ($mock) use ($derivative): void {
            $mock->shouldReceive('getDerivativesWithUnitsByName')
                ->once()
                ->andReturn($derivative);
        });

        $this->mock(BatchQueries::class, function ($mock): void {
            $mock->shouldReceive('getByNumber')
                ->once()
                ->andReturn(null);
        });

        $importProduct = new ImportStockAdjustmentStoProduct();
        $redirectResponse = $importProduct->validate($productData, $importRecord);
        $this->assertEquals([
            'A batch number is required for this product',
            'Batch expiry date is required for this product',
        ], $redirectResponse);
    }
);

test(
    'validate method returns issue when product has batch, batch number not defined when product variant is true',
    function (): void {
        Config::set('app.product_variant', true);

        $companyId = 1;
        $productData = getStockAdjustmentStoProductData();

        $importRecord = ImportRecord::factory()->make([
            'company_id' => $companyId,
            'created_by_id' => 1,
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
            'has_batch' => true,
            'is_non_inventory' => false,
        ]);

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

        $inventory = Inventory::factory()->make([
            'id' => 1,
            'product_id' => 1,
            'location_id' => 1,
            'stock' => 100,
        ]);

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $unitOfMeasure = UnitOfMeasure::factory()->make([
            'id' => 1,
            'company_id' => 1,
        ]);

        $masterProduct->unitOfMeasure = $masterProduct;

        $derivative = UnitOfMeasureDerivative::factory()->make([
            'id' => 1,
            'unit_of_measure_id' => 1,
            'ratio' => 0.25,
        ]);

        $derivative->unitOfMeasure = $unitOfMeasure;

        $productData['derivative_name'] = $derivative->name . '123';

        $this->mock(ProductQueries::class, function ($mock) use ($productData, $companyId, $product): void {
            $mock->shouldReceive('getActiveInventoryProductByUpcForStockAdjustment')
                ->once()
                ->with($productData['upc'], $companyId)
                ->andReturn($product);
        });

        $this->mock(LocationQueries::class, function ($mock) use ($location): void {
            $mock->shouldReceive('getIdAndNameByName')
                ->once()
                ->andReturn($location);
        });

        $this->mock(InventoryQueries::class, function ($mock) use ($inventory): void {
            $mock->shouldReceive('getByProductIdWithInventoryUnits')
                ->once()
                ->andReturn(collect([$inventory]));
        });

        $this->mock(UnitOfMeasureDerivativeQueries::class, function ($mock) use ($derivative): void {
            $mock->shouldReceive('getDerivativesWithUnitsByName')
                ->once()
                ->andReturn($derivative);
        });

        $this->mock(BatchQueries::class, function ($mock): void {
            $mock->shouldReceive('getByNumber')
                ->once()
                ->andReturn(null);
        });

        $importProduct = new ImportStockAdjustmentStoProduct();
        $redirectResponse = $importProduct->validate($productData, $importRecord);
        $this->assertEquals([
            'A batch number is required for this product',
            'Batch expiry date is required for this product',
        ], $redirectResponse);
    }
);

test(
    'validate method returns issue when product has batch, batch number is of another product when product variant is  false',
    function (): void {
        Config::set('app.product_variant', false);
        $companyId = 1;
        $productData = getStockAdjustmentStoProductData();

        $importRecord = ImportRecord::factory()->make([
            'company_id' => $companyId,
            'created_by_id' => 1,
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
            'has_batch' => true,
            'is_non_inventory' => false,
        ]);

        $batch = Batch::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'product_id' => 3,
            'expiry_date' => Carbon::tomorrow()->format('Y-m-d'),
        ]);

        $inventory = Inventory::factory()->make([
            'id' => 1,
            'product_id' => 1,
            'location_id' => 1,
            'stock' => 100,
        ]);

        $inventoryUnit = InventoryUnit::factory()->make([
            'inventory_id' => 1,
            'purchase_amount_id' => 1,
            'batch_id' => 1,
        ]);

        $inventory->inventoryUnits = collect([$inventoryUnit]);

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $unitOfMeasure = UnitOfMeasure::factory()->make([
            'id' => 1,
            'company_id' => 1,
        ]);

        $product->unitOfMeasure = $unitOfMeasure;

        $derivative = UnitOfMeasureDerivative::factory()->make([
            'id' => 1,
            'unit_of_measure_id' => 1,
            'ratio' => 0.25,
        ]);

        $derivative->unitOfMeasure = $unitOfMeasure;

        $productData['derivative_name'] = $derivative->name . '123';
        $productData['batch_number'] = '123';
        $productData['batch_expiry_date'] = Carbon::now()->format('Y-m-d');

        $this->mock(ProductQueries::class, function ($mock) use ($productData, $companyId, $product): void {
            $mock->shouldReceive('getActiveInventoryProductByUpcForStockAdjustment')
                ->once()
                ->with($productData['upc'], $companyId)
                ->andReturn($product);
        });

        $this->mock(LocationQueries::class, function ($mock) use ($location): void {
            $mock->shouldReceive('getIdAndNameByName')
                ->once()
                ->andReturn($location);
        });

        $this->mock(InventoryQueries::class, function ($mock) use ($inventory): void {
            $mock->shouldReceive('getByProductIdWithInventoryUnits')
                ->once()
                ->andReturn(collect([$inventory]));
        });

        $this->mock(UnitOfMeasureDerivativeQueries::class, function ($mock) use ($derivative): void {
            $mock->shouldReceive('getDerivativesWithUnitsByName')
                ->once()
                ->andReturn($derivative);
        });

        $this->mock(BatchQueries::class, function ($mock) use ($batch): void {
            $mock->shouldReceive('getByNumber')
                ->once()
                ->andReturn($batch);
        });

        $importProduct = new ImportStockAdjustmentStoProduct();
        $redirectResponse = $importProduct->validate($productData, $importRecord);
        $this->assertEquals([
            'The batch number of the product with UPC: ' . $productData['upc'] . ' has already been used for another product.',
            'The provided expiry date does not match the current expiry date of the batch with the given number: ' . $batch->number,
        ], $redirectResponse);
    }
);

test(
    'validate method returns issue when product has batch, batch number is of another product when product variant is  true',
    function (): void {
        Config::set('app.product_variant', true);
        $companyId = 1;
        $productData = getStockAdjustmentStoProductData();

        $importRecord = ImportRecord::factory()->make([
            'company_id' => $companyId,
            'created_by_id' => 1,
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
            'has_batch' => true,
            'is_non_inventory' => false,
        ]);

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
            'company_id' => 1,
            'product_id' => 3,
            'expiry_date' => Carbon::tomorrow()->format('Y-m-d'),
        ]);

        $inventory = Inventory::factory()->make([
            'id' => 1,
            'product_id' => 1,
            'location_id' => 1,
            'stock' => 100,
        ]);

        $inventoryUnit = InventoryUnit::factory()->make([
            'inventory_id' => 1,
            'purchase_amount_id' => 1,
            'batch_id' => 1,
        ]);

        $inventory->inventoryUnits = collect([$inventoryUnit]);

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $unitOfMeasure = UnitOfMeasure::factory()->make([
            'id' => 1,
            'company_id' => 1,
        ]);

        $masterProduct->unitOfMeasure = $masterProduct;

        $derivative = UnitOfMeasureDerivative::factory()->make([
            'id' => 1,
            'unit_of_measure_id' => 1,
            'ratio' => 0.25,
        ]);

        $derivative->unitOfMeasure = $unitOfMeasure;

        $productData['derivative_name'] = $derivative->name . '123';
        $productData['batch_number'] = '123';
        $productData['batch_expiry_date'] = Carbon::now()->format('Y-m-d');

        $this->mock(ProductQueries::class, function ($mock) use ($productData, $companyId, $product): void {
            $mock->shouldReceive('getActiveInventoryProductByUpcForStockAdjustment')
                ->once()
                ->with($productData['upc'], $companyId)
                ->andReturn($product);
        });

        $this->mock(LocationQueries::class, function ($mock) use ($location): void {
            $mock->shouldReceive('getIdAndNameByName')
                ->once()
                ->andReturn($location);
        });

        $this->mock(InventoryQueries::class, function ($mock) use ($inventory): void {
            $mock->shouldReceive('getByProductIdWithInventoryUnits')
                ->once()
                ->andReturn(collect([$inventory]));
        });

        $this->mock(UnitOfMeasureDerivativeQueries::class, function ($mock) use ($derivative): void {
            $mock->shouldReceive('getDerivativesWithUnitsByName')
                ->once()
                ->andReturn($derivative);
        });

        $this->mock(BatchQueries::class, function ($mock) use ($batch): void {
            $mock->shouldReceive('getByNumber')
                ->once()
                ->andReturn($batch);
        });

        $importProduct = new ImportStockAdjustmentStoProduct();
        $redirectResponse = $importProduct->validate($productData, $importRecord);
        $this->assertEquals([
            'The batch number of the product with UPC: ' . $productData['upc'] . ' has already been used for another product.',
            'The provided expiry date does not match the current expiry date of the batch with the given number: ' . $batch->number,
        ], $redirectResponse);
    }
);

test(
    'validate method returns issue when product inventory unit is not found when product variant is false',
    function (): void {
        Config::set('app.product_variant', false);
        $companyId = 1;
        $productData = getStockAdjustmentStoProductData();

        $importRecord = ImportRecord::factory()->make([
            'company_id' => $companyId,
            'created_by_id' => 1,
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
            'has_batch' => true,
            'is_non_inventory' => false,
        ]);

        $batch = Batch::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'product_id' => 1,
            'expiry_date' => Carbon::tomorrow()->format('Y-m-d'),
        ]);

        $inventory = Inventory::factory()->make([
            'id' => 1,
            'product_id' => 1,
            'location_id' => 1,
            'stock' => 100,
        ]);

        $inventoryUnit = InventoryUnit::factory()->make([
            'inventory_id' => 2,
            'purchase_amount_id' => 1,
            'batch_id' => 1,
        ]);

        $inventory->inventoryUnits = collect([]);

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $unitOfMeasure = UnitOfMeasure::factory()->make([
            'id' => 1,
            'company_id' => 1,
        ]);

        $product->unitOfMeasure = $unitOfMeasure;

        $derivative = UnitOfMeasureDerivative::factory()->make([
            'id' => 1,
            'unit_of_measure_id' => 1,
            'ratio' => 0.25,
        ]);

        $derivative->unitOfMeasure = $unitOfMeasure;

        $productData['derivative_name'] = $derivative->name . '123';
        $productData['batch_number'] = $batch->number;
        $productData['batch_expiry_date'] = $batch->expiry_date;

        $this->mock(ProductQueries::class, function ($mock) use ($productData, $companyId, $product): void {
            $mock->shouldReceive('getActiveInventoryProductByUpcForStockAdjustment')
                ->once()
                ->with($productData['upc'], $companyId)
                ->andReturn($product);
        });

        $this->mock(LocationQueries::class, function ($mock) use ($location): void {
            $mock->shouldReceive('getIdAndNameByName')
                ->once()
                ->andReturn($location);
        });

        $this->mock(InventoryQueries::class, function ($mock) use ($inventory): void {
            $mock->shouldReceive('getByProductIdWithInventoryUnits')
                ->once()
                ->andReturn(collect([$inventory]));
        });

        $this->mock(UnitOfMeasureDerivativeQueries::class, function ($mock) use ($derivative): void {
            $mock->shouldReceive('getDerivativesWithUnitsByName')
                ->once()
                ->andReturn($derivative);
        });

        $this->mock(BatchQueries::class, function ($mock) use ($batch): void {
            $mock->shouldReceive('getByNumber')
                ->once()
                ->andReturn($batch);
        });

        $importProduct = new ImportStockAdjustmentStoProduct();
        $redirectResponse = $importProduct->validate($productData, $importRecord);
        $this->assertEquals([
            'inventory unit of upc ' . $productData['upc'] . ' does not exist in the our records.',
            'You cannot perform STO for the specified product UPC ' . $productData['upc'] . ' Because the specified batch number ' . $batch->number . ' only has a stock of 0 quantity, and you have requested to decrease it by  ' . $productData['quantity'] . '.',
        ], $redirectResponse);
    }
);

test(
    'validate method returns issue when product inventory unit is not found when product variant is true',
    function (): void {
        Config::set('app.product_variant', true);
        $companyId = 1;
        $productData = getStockAdjustmentStoProductData();

        $importRecord = ImportRecord::factory()->make([
            'company_id' => $companyId,
            'created_by_id' => 1,
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
            'has_batch' => true,
            'is_non_inventory' => false,
        ]);

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
            'company_id' => 1,
            'product_id' => 1,
            'expiry_date' => Carbon::tomorrow()->format('Y-m-d'),
        ]);

        $inventory = Inventory::factory()->make([
            'id' => 1,
            'product_id' => 1,
            'location_id' => 1,
            'stock' => 100,
        ]);

        $inventoryUnit = InventoryUnit::factory()->make([
            'inventory_id' => 2,
            'purchase_amount_id' => 1,
            'batch_id' => 1,
        ]);

        $inventory->inventoryUnits = collect([]);

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $unitOfMeasure = UnitOfMeasure::factory()->make([
            'id' => 1,
            'company_id' => 1,
        ]);

        $masterProduct->unitOfMeasure = $masterProduct;

        $derivative = UnitOfMeasureDerivative::factory()->make([
            'id' => 1,
            'unit_of_measure_id' => 1,
            'ratio' => 0.25,
        ]);

        $derivative->unitOfMeasure = $unitOfMeasure;

        $productData['derivative_name'] = $derivative->name . '123';
        $productData['batch_number'] = $batch->number;
        $productData['batch_expiry_date'] = $batch->expiry_date;

        $this->mock(ProductQueries::class, function ($mock) use ($productData, $companyId, $product): void {
            $mock->shouldReceive('getActiveInventoryProductByUpcForStockAdjustment')
                ->once()
                ->with($productData['upc'], $companyId)
                ->andReturn($product);
        });

        $this->mock(LocationQueries::class, function ($mock) use ($location): void {
            $mock->shouldReceive('getIdAndNameByName')
                ->once()
                ->andReturn($location);
        });

        $this->mock(InventoryQueries::class, function ($mock) use ($inventory): void {
            $mock->shouldReceive('getByProductIdWithInventoryUnits')
                ->once()
                ->andReturn(collect([$inventory]));
        });

        $this->mock(UnitOfMeasureDerivativeQueries::class, function ($mock) use ($derivative): void {
            $mock->shouldReceive('getDerivativesWithUnitsByName')
                ->once()
                ->andReturn($derivative);
        });

        $this->mock(BatchQueries::class, function ($mock) use ($batch): void {
            $mock->shouldReceive('getByNumber')
                ->once()
                ->andReturn($batch);
        });

        $importProduct = new ImportStockAdjustmentStoProduct();
        $redirectResponse = $importProduct->validate($productData, $importRecord);
        $this->assertEquals([
            'inventory unit of upc ' . $productData['upc'] . ' does not exist in the our records.',
            'You cannot perform STO for the specified product UPC ' . $productData['upc'] . ' Because the specified batch number ' . $batch->number . ' only has a stock of 0 quantity, and you have requested to decrease it by  ' . $productData['quantity'] . '.',
        ], $redirectResponse);
    }
);

test('save method saves the data', function (): void {
    $companyId = 1;
    $productData = getStockAdjustmentStoProductData();

    $product = Product::factory()->make([
        'id' => 1,
        'upc' => '123456',
        'company_id' => 1,
        'unit_of_measure_id' => 1,
        'season_id' => 1,
        'department_id' => 1,
        'color_id' => 1,
        'size_id' => 1,
        'brand_id' => 1,
        'style_id' => 1,
        'has_batch' => true,
        'is_non_inventory' => false,
    ]);

    $importRecord = ImportRecord::factory()->make([
        'company_id' => 1,
        'created_by_id' => 1,
        'created_by_type' => ModelMapping::ADMIN->name,
        'module_id' => 1,
        'module_type' => ModelMapping::STOCK_ADJUSTMENT->name,
    ]);

    $admin = Admin::factory()->make([
        'id' => 1,
        'employee_id' => 1,
    ]);

    $importRecord->createdBy = $admin;

    $productData['upc'] = $product->upc;

    $this->mock(ProductQueries::class, function ($mock) use ($productData, $companyId, $product): void {
        $mock->shouldReceive('getActiveInventoryProductByUpcForStockAdjustment')
            ->once()
            ->with($productData['upc'], $companyId)
            ->andReturn($product);
    });

    $this->mock(UnitOfMeasureDerivativeQueries::class, function ($mock) use ($productData): void {
        $mock->shouldReceive('getDerivativesWithUnitsByName')
            ->once()
            ->with($productData['derivative_name'], 1)
            ->andReturn(null);
    });

    $this->mock(StockAdjustmentQueries::class, function ($mock): void {
        $mock->shouldReceive('getById')
            ->once();
    });

    $this->mock(StockAdjustmentService::class, function ($mock): void {
        $mock->shouldReceive('addItemAndInventory')
           ->once();
    });

    $importProduct = new ImportStockAdjustmentStoProduct();
    $importProduct->save($productData, $importRecord);
});

function getStockAdjustmentStoProductData(): array
{
    return [
        'location_type' => 'STORE',
        'location_name' => 'new store',
        'upc' => '123456',
        'quantity' => -4,
        'derivative_name' => null,
        'batch_expiry_date' => null,
        'batch_number' => null,
        'batch_notes' => null,
        'batch_external_id' => null,
    ];
}

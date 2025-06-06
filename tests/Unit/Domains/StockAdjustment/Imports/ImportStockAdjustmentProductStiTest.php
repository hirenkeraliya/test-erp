<?php

declare(strict_types=1);

use App\Domains\Batch\BatchQueries;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\ImportRecord\Enums\ColumnValidationIssueTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\StockAdjustment\Enums\StockAdjustmentImportStiColumns;
use App\Domains\StockAdjustment\Imports\ImportStockAdjustmentStiProduct;
use App\Domains\StockAdjustment\Services\StockAdjustmentService;
use App\Domains\StockAdjustment\StockAdjustmentQueries;
use App\Domains\UnitOfMeasureDerivative\UnitOfMeasureDerivativeQueries;
use App\Models\Admin;
use App\Models\Batch;
use App\Models\ImportRecord;
use App\Models\MasterProduct;
use App\Models\Product;
use App\Models\UnitOfMeasure;
use App\Models\UnitOfMeasureDerivative;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;

test('validate import stock adjustment product import columns', function (): void {
    $requiredHeaderColumns = array_flip(StockAdjustmentImportStiColumns::getArrayValues());

    $importProduct = new ImportStockAdjustmentStiProduct();
    $response = $importProduct->validateColumns($requiredHeaderColumns, [], 1);

    $this->assertTrue(ColumnValidationIssueTypes::COLUMN_ISSUE->value === $response['type']);
    $this->assertFalse($response['status']);
});

test('validate method returns blank array when product variant is false', function (): void {
    Config::set('app.product_variant', false);

    $companyId = 1;
    $productData = getStockAdjustmentProductData();

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

    $this->mock(LocationQueries::class, function ($mock): void {
        $mock->shouldReceive('checkNameExists')
            ->once()
            ->andReturn(true);
    });

    $importProduct = new ImportStockAdjustmentStiProduct();
    $redirectResponse = $importProduct->validate($productData, $importRecord);
    $this->assertEquals([], $redirectResponse);
});

test('validate method returns blank array when product variant is true', function (): void {
    Config::set('app.product_variant', true);

    $companyId = 1;
    $productData = getStockAdjustmentProductData();

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

    $this->mock(LocationQueries::class, function ($mock): void {
        $mock->shouldReceive('checkNameExists')
            ->once()
            ->andReturn(true);
    });

    $importProduct = new ImportStockAdjustmentStiProduct();
    $redirectResponse = $importProduct->validate($productData, $importRecord);
    $this->assertEquals([], $redirectResponse);
});

test(
    'validate method returns issue when derivative is not in our records when product variant is false',
    function (): void {
        Config::set('app.product_variant', false);

        $companyId = 1;
        $productData = getStockAdjustmentProductData();

        $importRecord = ImportRecord::factory()->make([
            'company_id' => $companyId,
            'created_by_id' => 1,
        ]);

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
            'has_batch' => false,
            'is_non_inventory' => false,
        ]);

        $productData['derivative_name'] = '123';
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

        $this->mock(LocationQueries::class, function ($mock): void {
            $mock->shouldReceive('checkNameExists')
                ->once()
                ->andReturn(true);
        });

        $importProduct = new ImportStockAdjustmentStiProduct();
        $redirectResponse = $importProduct->validate($productData, $importRecord);
        $this->assertEquals([
            'Derivate name `' . $productData['derivative_name'] . '` does not exists in our records for the product with UPC ' . $productData['upc'] . '.',
        ], $redirectResponse);
    }
);

test(
    'validate method returns issue when derivative is not in our records when product variant is true',
    function (): void {
        Config::set('app.product_variant', true);

        $companyId = 1;
        $productData = getStockAdjustmentProductData();

        $importRecord = ImportRecord::factory()->make([
            'company_id' => $companyId,
            'created_by_id' => 1,
        ]);

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
            'has_batch' => false,
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

        $productData['derivative_name'] = '123';
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

        $this->mock(LocationQueries::class, function ($mock): void {
            $mock->shouldReceive('checkNameExists')
                ->once()
                ->andReturn(true);
        });

        $importProduct = new ImportStockAdjustmentStiProduct();
        $redirectResponse = $importProduct->validate($productData, $importRecord);
        $this->assertEquals([
            'Derivate name `' . $productData['derivative_name'] . '` does not exists in our records for the product with UPC ' . $productData['upc'] . '.',
        ], $redirectResponse);
    }
);

test('validate method returns issue when store is invalid', function (): void {
    $companyId = 1;
    $productData = getStockAdjustmentProductData();

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'created_by_id' => 1,
    ]);

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
        'has_batch' => false,
        'is_non_inventory' => false,
    ]);

    $productData['derivative_name'] = '123';
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

    $this->mock(LocationQueries::class, function ($mock): void {
        $mock->shouldReceive('checkNameExists')
            ->once()
            ->andReturn(false);
    });

    $importProduct = new ImportStockAdjustmentStiProduct();
    $redirectResponse = $importProduct->validate($productData, $importRecord);
    $this->assertEquals(['The Selected Store is Invalid'], $redirectResponse);
});

test('validate method returns issue when warehouse is invalid', function (): void {
    $companyId = 1;
    $productData = getStockAdjustmentProductData();

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'created_by_id' => 1,
    ]);

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
        'has_batch' => false,
        'is_non_inventory' => false,
    ]);

    $productData['derivative_name'] = '123';
    $productData['location_name'] = 'Warehouse';
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

    $this->mock(LocationQueries::class, function ($mock): void {
        $mock->shouldReceive('checkNameExists')
            ->once()
            ->andReturn(false);
    });

    $importProduct = new ImportStockAdjustmentStiProduct();
    $redirectResponse = $importProduct->validate($productData, $importRecord);
    $this->assertEquals(['The Selected Store is Invalid'], $redirectResponse);
});

test('validate method returns issue when type is sti and quantity is in negative', function (): void {
    $companyId = 1;
    $productData = getStockAdjustmentProductData();

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'created_by_id' => 1,
    ]);

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
        'has_batch' => false,
        'is_non_inventory' => false,
    ]);

    $productData['derivative_name'] = '123';
    $productData['quantity'] = -5;
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

    $this->mock(LocationQueries::class, function ($mock): void {
        $mock->shouldReceive('checkNameExists')
            ->once()
            ->andReturn(false);
    });

    $importProduct = new ImportStockAdjustmentStiProduct();
    $redirectResponse = $importProduct->validate($productData, $importRecord);
    $this->assertEquals(['The Selected Store is Invalid'], $redirectResponse);
});

test('validate method returns issue when derivative is not available in records', function (): void {
    $companyId = 1;
    $productData = getStockAdjustmentProductData();

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'created_by_id' => 1,
    ]);

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
        'has_batch' => false,
        'is_non_inventory' => false,
    ]);

    $productData['derivative_name'] = '123';
    $productData['quantity'] = -5;
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

    $this->mock(LocationQueries::class, function ($mock): void {
        $mock->shouldReceive('checkNameExists')
            ->once()
            ->andReturn(false);
    });

    $importProduct = new ImportStockAdjustmentStiProduct();
    $redirectResponse = $importProduct->validate($productData, $importRecord);
    $this->assertEquals(['The Selected Store is Invalid'], $redirectResponse);
});

test(
    'validate method returns issue when product unit of measure is not available when product variant is false',
    function (): void {
        Config::set('app.product_variant', false);
        $companyId = 1;
        $productData = getStockAdjustmentProductData();

        $importRecord = ImportRecord::factory()->make([
            'company_id' => $companyId,
            'created_by_id' => 1,
        ]);

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
            'has_batch' => false,
            'is_non_inventory' => false,
        ]);

        $productData['derivative_name'] = '123';
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

        $this->mock(LocationQueries::class, function ($mock): void {
            $mock->shouldReceive('checkNameExists')
                ->once()
                ->andReturn(true);
        });

        $importProduct = new ImportStockAdjustmentStiProduct();
        $redirectResponse = $importProduct->validate($productData, $importRecord);
        $this->assertEquals([
            'Derivate name `' . $productData['derivative_name'] . '` does not exists in our records for the product with UPC ' . $productData['upc'] . '.',
        ], $redirectResponse);
    }
);

test(
    'validate method returns issue when product unit of measure is not available when product variant is true',
    function (): void {
        Config::set('app.product_variant', true);
        $companyId = 1;
        $productData = getStockAdjustmentProductData();

        $importRecord = ImportRecord::factory()->make([
            'company_id' => $companyId,
            'created_by_id' => 1,
        ]);

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

        $productData['derivative_name'] = '123';
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

        $this->mock(LocationQueries::class, function ($mock): void {
            $mock->shouldReceive('checkNameExists')
                ->once()
                ->andReturn(true);
        });

        $importProduct = new ImportStockAdjustmentStiProduct();
        $redirectResponse = $importProduct->validate($productData, $importRecord);
        $this->assertEquals([
            'Derivate name `' . $productData['derivative_name'] . '` does not exists in our records for the product with UPC ' . $productData['upc'] . '.',
        ], $redirectResponse);
    }
);

test(
    'validate method returns issue when product unit of measure is not matching when product variant is false',
    function (): void {
        Config::set('app.product_variant', false);

        $companyId = 1;
        $productData = getStockAdjustmentProductData();

        $importRecord = ImportRecord::factory()->make([
            'company_id' => $companyId,
            'created_by_id' => 1,
        ]);

        $unitOfMeasure = UnitOfMeasure::factory()->make([
            'id' => 2,
            'company_id' => 1,
        ]);

        $unitOfMeasureDerivative = UnitOfMeasureDerivative::factory()->make([
            'id' => 2,
            'unit_of_measure_id' => $unitOfMeasure->id,
            'name' => 'test',
        ]);

        $unitOfMeasureDerivative->unitOfMeasure = $unitOfMeasure;

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
            'has_batch' => false,
            'is_non_inventory' => false,
        ]);
        $product->unitOfMeasure = $unitOfMeasure;

        $productData['derivative_name'] = '123';
        $productData['upc'] = $product->upc;

        $this->mock(ProductQueries::class, function ($mock) use ($productData, $companyId, $product): void {
            $mock->shouldReceive('getActiveInventoryProductByUpcForStockAdjustment')
                ->once()
                ->with($productData['upc'], $companyId)
                ->andReturn($product);
        });

        $this->mock(UnitOfMeasureDerivativeQueries::class, function ($mock) use (
            $productData,
            $unitOfMeasureDerivative
        ): void {
            $mock->shouldReceive('getDerivativesWithUnitsByName')
                ->once()
                ->with($productData['derivative_name'], 1)
                ->andReturn($unitOfMeasureDerivative);
        });

        $this->mock(LocationQueries::class, function ($mock): void {
            $mock->shouldReceive('checkNameExists')
                ->once()
                ->andReturn(true);
        });

        $importProduct = new ImportStockAdjustmentStiProduct();
        $redirectResponse = $importProduct->validate($productData, $importRecord);
        $this->assertEquals([
            'Derivate name `' . $productData['derivative_name'] . '` have UOM `' . $unitOfMeasure->name . '` does not match with the product UPC ' . $productData['upc'] . ' have UOM `' . $unitOfMeasure->name,
        ], $redirectResponse);
    }
);

test(
    'validate method returns issue when product unit of measure is not matching when product variant is true',
    function (): void {
        Config::set('app.product_variant', true);

        $companyId = 1;
        $productData = getStockAdjustmentProductData();

        $importRecord = ImportRecord::factory()->make([
            'company_id' => $companyId,
            'created_by_id' => 1,
        ]);

        $unitOfMeasure = UnitOfMeasure::factory()->make([
            'id' => 2,
            'company_id' => 1,
        ]);

        $unitOfMeasureDerivative = UnitOfMeasureDerivative::factory()->make([
            'id' => 2,
            'unit_of_measure_id' => $unitOfMeasure->id,
            'name' => 'test',
        ]);

        $unitOfMeasureDerivative->unitOfMeasure = $unitOfMeasure;

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

        $masterProduct->unitOfMeasure = $unitOfMeasure;

        $productData['derivative_name'] = '123';
        $productData['upc'] = $product->upc;

        $this->mock(ProductQueries::class, function ($mock) use ($productData, $companyId, $product): void {
            $mock->shouldReceive('getActiveInventoryProductByUpcForStockAdjustment')
                ->once()
                ->with($productData['upc'], $companyId)
                ->andReturn($product);
        });

        $this->mock(UnitOfMeasureDerivativeQueries::class, function ($mock) use (
            $productData,
            $unitOfMeasureDerivative
        ): void {
            $mock->shouldReceive('getDerivativesWithUnitsByName')
                ->once()
                ->with($productData['derivative_name'], 1)
                ->andReturn($unitOfMeasureDerivative);
        });

        $this->mock(LocationQueries::class, function ($mock): void {
            $mock->shouldReceive('checkNameExists')
                ->once()
                ->andReturn(true);
        });

        $importProduct = new ImportStockAdjustmentStiProduct();
        $redirectResponse = $importProduct->validate($productData, $importRecord);
        $this->assertEquals([
            'Derivate name `' . $productData['derivative_name'] . '` have UOM `' . $unitOfMeasure->name . '` does not match with the product UPC ' . $productData['upc'] . ' have UOM `' . $unitOfMeasure->name,
        ], $redirectResponse);
    }
);

test('validate method returns issue when product batch is required for the product', function (): void {
    $companyId = 1;
    $productData = getStockAdjustmentProductData();

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'created_by_id' => 1,
    ]);

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

    $this->mock(LocationQueries::class, function ($mock): void {
        $mock->shouldReceive('checkNameExists')
            ->once()
            ->andReturn(true);
    });

    $this->mock(BatchQueries::class, function ($mock) use ($productData): void {
        $mock->shouldReceive('getByNumber')
           ->once()
           ->with($productData['batch_number'], 1)
           ->andReturn(null);
    });

    $importProduct = new ImportStockAdjustmentStiProduct();
    $redirectResponse = $importProduct->validate($productData, $importRecord);
    $this->assertEquals([
        'A batch number is required for this product',
        'Batch expiry date is required for this product',
    ], $redirectResponse);
});

test('validate method returns issue when product batch number is already used by another product', function (): void {
    $companyId = 1;
    $productData = getStockAdjustmentProductData();

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'created_by_id' => 1,
    ]);

    $batch = Batch::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'product_id' => 2,
        'expiry_date' => Carbon::tomorrow()->format('Y-m-d'),
    ]);

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

    $productData['upc'] = $product->upc;
    $productData['batch_expiry_date'] = $batch->expiry_date;
    $productData['batch_number'] = $batch->number;

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

    $this->mock(LocationQueries::class, function ($mock): void {
        $mock->shouldReceive('checkNameExists')
            ->once()
            ->andReturn(true);
    });

    $this->mock(BatchQueries::class, function ($mock) use ($productData, $batch): void {
        $mock->shouldReceive('getByNumber')
           ->once()
           ->with($productData['batch_number'], 1)
           ->andReturn($batch);
    });

    $importProduct = new ImportStockAdjustmentStiProduct();
    $redirectResponse = $importProduct->validate($productData, $importRecord);
    $this->assertEquals([
        'The batch number of the product with UPC: ' . $productData['upc'] . ' has already been used for another product.',
    ], $redirectResponse);
});

test('validate method returns issue when product batch expiry date is not matching', function (): void {
    $companyId = 1;
    $productData = getStockAdjustmentProductData();

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'created_by_id' => 1,
    ]);

    $batch = Batch::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'product_id' => 1,
        'expiry_date' => Carbon::tomorrow()->format('Y-m-d'),
    ]);

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

    $productData['upc'] = $product->upc;
    $productData['batch_expiry_date'] = Carbon::today()->format('Y-m-d');
    $productData['batch_number'] = $batch->number;

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

    $this->mock(LocationQueries::class, function ($mock): void {
        $mock->shouldReceive('checkNameExists')
            ->once()
            ->andReturn(true);
    });

    $this->mock(BatchQueries::class, function ($mock) use ($productData, $batch): void {
        $mock->shouldReceive('getByNumber')
           ->once()
           ->with($productData['batch_number'], 1)
           ->andReturn($batch);
    });

    $importProduct = new ImportStockAdjustmentStiProduct();
    $redirectResponse = $importProduct->validate($productData, $importRecord);
    $this->assertEquals([
        'The provided expiry date does not match the current expiry date of the batch with the given number: ' . $batch->number,
    ], $redirectResponse);
});

test('save method saves the data', function (): void {
    $companyId = 1;
    $productData = getStockAdjustmentProductData();

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

    $importProduct = new ImportStockAdjustmentStiProduct();
    $importProduct->save($productData, $importRecord);
});

function getStockAdjustmentProductData(): array
{
    return [
        'location_type' => 'STORE',
        'location_name' => 'new store',
        'upc' => '123456',
        'quantity' => 4,
        'derivative_name' => null,
        'fob' => null,
        'freight_charges' => null,
        'insurance_charges' => null,
        'duty' => null,
        'sst' => null,
        'handling_charges' => null,
        'other_charges' => null,
        'batch_expiry_date' => null,
        'batch_number' => null,
        'batch_notes' => null,
        'batch_external_id' => null,
    ];
}

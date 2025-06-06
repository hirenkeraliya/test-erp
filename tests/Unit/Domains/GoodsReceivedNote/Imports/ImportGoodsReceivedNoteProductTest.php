<?php

declare(strict_types=1);

use App\Domains\Batch\BatchQueries;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\GoodsReceivedNote\Enums\GoodsReceivedNoteImportColumns;
use App\Domains\GoodsReceivedNote\GoodsReceivedNoteQueries;
use App\Domains\GoodsReceivedNote\Imports\ImportGoodsReceivedNoteProduct;
use App\Domains\GoodsReceivedNote\Services\GoodsReceivedNoteService;
use App\Domains\ImportRecord\Enums\ColumnValidationIssueTypes;
use App\Domains\Product\Enums\ProductTypes;
use App\Domains\Product\Imports\ImportProduct;
use App\Domains\Product\ProductQueries;
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

test('validate import goods received note product import columns', function (): void {
    $requiredHeaderColumns = array_flip(GoodsReceivedNoteImportColumns::getArrayValues());

    $importProduct = new ImportProduct();
    $response = $importProduct->validateColumns($requiredHeaderColumns, [], 1);
    $this->assertTrue(ColumnValidationIssueTypes::COLUMN_ISSUE->value === $response['type']);
    $this->assertTrue($response['status']);
});

test('validate method returns blank array when product variant is false', function (): void {
    $companyId = 1;
    $productData = getGoodsReceivedNoteProductData();

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'created_by_id' => 1,
    ]);

    Config::set('app.product_variant', false);

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
        $mock->shouldReceive('getActiveInventoryProductByUpcForGRN')
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

    $this->mock(BatchQueries::class, function ($mock) use ($productData): void {
        $mock->shouldReceive('getByNumber')
           ->once()
           ->with($productData['batch_number'], 1)
           ->andReturn(null);
    });

    $importProduct = new ImportGoodsReceivedNoteProduct();
    $redirectResponse = $importProduct->validate($productData, $importRecord);
    $this->assertEquals([], $redirectResponse);
});

test('validate method returns blank array when product variant is true', function (): void {
    $companyId = 1;
    $productData = getGoodsReceivedNoteProductData();

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'created_by_id' => 1,
    ]);

    Config::set('app.product_variant', true);

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
        'master_product_id' => $masterProduct->id,
    ]);

    $product->masterProduct = $masterProduct;

    $this->mock(ProductQueries::class, function ($mock) use ($productData, $companyId, $product): void {
        $mock->shouldReceive('getActiveInventoryProductByUpcForGRN')
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

    $this->mock(BatchQueries::class, function ($mock) use ($productData): void {
        $mock->shouldReceive('getByNumber')
           ->once()
           ->with($productData['batch_number'], 1)
           ->andReturn(null);
    });

    $importProduct = new ImportGoodsReceivedNoteProduct();
    $redirectResponse = $importProduct->validate($productData, $importRecord);
    $this->assertEquals([], $redirectResponse);
});

test(
    'validate method returns issue when derivative is not in our records when product variant is false',
    function (): void {
        $companyId = 1;
        $productData = getGoodsReceivedNoteProductData();

        $importRecord = ImportRecord::factory()->make([
            'company_id' => $companyId,
            'created_by_id' => 1,
        ]);

        Config::set('app.product_variant', false);

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
            $mock->shouldReceive('getActiveInventoryProductByUpcForGRN')
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

        $this->mock(BatchQueries::class, function ($mock) use ($productData): void {
            $mock->shouldReceive('getByNumber')
               ->once()
               ->with($productData['batch_number'], 1)
               ->andReturn(null);
        });

        $importProduct = new ImportGoodsReceivedNoteProduct();
        $redirectResponse = $importProduct->validate($productData, $importRecord);
        $this->assertEquals([
            'Derivate name `' . $productData['derivative_name'] . '` does not exists in our records for the product with UPC ' . $productData['upc'] . '.',
        ], $redirectResponse);
    }
);

test(
    'validate method returns issue when derivative is not in our records when product variant is true',
    function (): void {
        $companyId = 1;
        $productData = getGoodsReceivedNoteProductData();

        $importRecord = ImportRecord::factory()->make([
            'company_id' => $companyId,
            'created_by_id' => 1,
        ]);

        Config::set('app.product_variant', true);

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
            'master_product_id' => $masterProduct->id,
        ]);

        $product->masterProduct = $masterProduct;
        $productData['derivative_name'] = '123';
        $productData['upc'] = $product->upc;

        $this->mock(ProductQueries::class, function ($mock) use ($productData, $companyId, $product): void {
            $mock->shouldReceive('getActiveInventoryProductByUpcForGRN')
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

        $this->mock(BatchQueries::class, function ($mock) use ($productData): void {
            $mock->shouldReceive('getByNumber')
               ->once()
               ->with($productData['batch_number'], 1)
               ->andReturn(null);
        });

        $importProduct = new ImportGoodsReceivedNoteProduct();
        $redirectResponse = $importProduct->validate($productData, $importRecord);

        $this->assertEquals([
            'Derivate name `' . $productData['derivative_name'] . '` does not exists in our records for the product with UPC ' . $productData['upc'] . '.',
        ], $redirectResponse);
    }
);

test(
    'validate method returns issue when derivative column is not found when product variant is false.',
    function (): void {
        $companyId = 1;
        $productData = getGoodsReceivedNoteProductData();

        $importRecord = ImportRecord::factory()->make([
            'company_id' => $companyId,
            'created_by_id' => 1,
        ]);

        Config::set('app.product_variant', false);

        $product = Product::factory()->make([
            'id' => 1,
            'upc' => '123456',
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

        $productData['derivative_name'] = '123';
        $productData['upc'] = $product->upc;

        $this->mock(ProductQueries::class, function ($mock) use ($productData, $companyId, $product): void {
            $mock->shouldReceive('getActiveInventoryProductByUpcForGRN')
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

        $this->mock(BatchQueries::class, function ($mock) use ($productData): void {
            $mock->shouldReceive('getByNumber')
               ->once()
               ->with($productData['batch_number'], 1)
               ->andReturn(null);
        });

        $importProduct = new ImportGoodsReceivedNoteProduct();
        $redirectResponse = $importProduct->validate($productData, $importRecord);
        $this->assertEquals([
            'Derivate name is not required due to unit of measure does not set for the product with UPC ' . $productData['upc'] . '.',
        ], $redirectResponse);
    }
);

test(
    'validate method returns issue when derivative column is not found when product variant is true.',
    function (): void {
        $companyId = 1;
        $productData = getGoodsReceivedNoteProductData();

        $importRecord = ImportRecord::factory()->make([
            'company_id' => $companyId,
            'created_by_id' => 1,
        ]);

        Config::set('app.product_variant', true);

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

        $product = Product::factory()->make([
            'id' => 1,
            'upc' => '123456',
            'company_id' => 1,
            'unit_of_measure_id' => null,
            'season_id' => 1,
            'department_id' => 1,
            'color_id' => 1,
            'size_id' => 1,
            'brand_id' => 1,
            'style_id' => 1,
            'master_product_id' => $masterProduct->id,
        ]);

        $product->masterProduct = $masterProduct;
        $productData['derivative_name'] = '123';
        $productData['upc'] = $product->upc;

        $this->mock(ProductQueries::class, function ($mock) use ($productData, $companyId, $product): void {
            $mock->shouldReceive('getActiveInventoryProductByUpcForGRN')
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

        $this->mock(BatchQueries::class, function ($mock) use ($productData): void {
            $mock->shouldReceive('getByNumber')
               ->once()
               ->with($productData['batch_number'], 1)
               ->andReturn(null);
        });

        $importProduct = new ImportGoodsReceivedNoteProduct();
        $redirectResponse = $importProduct->validate($productData, $importRecord);
        $this->assertEquals([
            'Derivate name is not required due to unit of measure does not set for the product with UPC ' . $productData['upc'] . '.',
        ], $redirectResponse);
    }
);

test('validate method returns issue when derivative not matching when product variant is false.', function (): void {
    $companyId = 1;
    $productData = getGoodsReceivedNoteProductData();

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'created_by_id' => 1,
    ]);

    Config::set('app.product_variant', false);

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
        $mock->shouldReceive('getActiveInventoryProductByUpcForGRN')
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

    $this->mock(BatchQueries::class, function ($mock) use ($productData): void {
        $mock->shouldReceive('getByNumber')
           ->once()
           ->with($productData['batch_number'], 1)
           ->andReturn(null);
    });

    $importProduct = new ImportGoodsReceivedNoteProduct();
    $redirectResponse = $importProduct->validate($productData, $importRecord);
    $this->assertEquals([
        'Derivate name `' . $productData['derivative_name'] . '` have UOM `' . $unitOfMeasureDerivative->unitOfMeasure->name . '` does not match with the product UPC ' . $productData['upc'] . ' have UOM `' . $product->unitOfMeasure->name,
    ], $redirectResponse);
});

test('validate method returns issue when derivative not matching when product variant is true.', function (): void {
    $companyId = 1;
    $productData = getGoodsReceivedNoteProductData();

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'created_by_id' => 1,
    ]);

    Config::set('app.product_variant', true);

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
    $product->masterProduct = $masterProduct;

    $productData['derivative_name'] = '123';
    $productData['upc'] = $product->upc;

    $this->mock(ProductQueries::class, function ($mock) use ($productData, $companyId, $product): void {
        $mock->shouldReceive('getActiveInventoryProductByUpcForGRN')
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

    $this->mock(BatchQueries::class, function ($mock) use ($productData): void {
        $mock->shouldReceive('getByNumber')
           ->once()
           ->with($productData['batch_number'], 1)
           ->andReturn(null);
    });

    $importProduct = new ImportGoodsReceivedNoteProduct();
    $redirectResponse = $importProduct->validate($productData, $importRecord);
    $this->assertEquals([
        'Derivate name `' . $productData['derivative_name'] . '` have UOM `' . $unitOfMeasureDerivative->unitOfMeasure->name . '` does not match with the product UPC ' . $productData['upc'] . ' have UOM `' . $product->unitOfMeasure->name,
    ], $redirectResponse);
});

test(
    'validate method returns issue when product is not has batch and proving the batch details product variant is false.',
    function (): void {
        $companyId = 1;
        $productData = getGoodsReceivedNoteProductData();

        $importRecord = ImportRecord::factory()->make([
            'company_id' => $companyId,
            'created_by_id' => 1,
        ]);

        Config::set('app.product_variant', false);

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

        $productData['upc'] = $product->upc;
        $productData['batch_expiry_date'] = $product->created_at;
        $productData['batch_number'] = $product->upc;

        $this->mock(ProductQueries::class, function ($mock) use ($productData, $companyId, $product): void {
            $mock->shouldReceive('getActiveInventoryProductByUpcForGRN')
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

        $this->mock(BatchQueries::class, function ($mock) use ($productData): void {
            $mock->shouldReceive('getByNumber')
               ->once()
               ->with($productData['batch_number'], 1)
               ->andReturn(null);
        });

        $importProduct = new ImportGoodsReceivedNoteProduct();
        $redirectResponse = $importProduct->validate($productData, $importRecord);
        $this->assertEquals([
            'Batch number is not required for the product with UPC ' . $productData['upc'] . '.',
        ], $redirectResponse);
    }
);

test(
    'validate method returns issue when product is not has batch and proving the batch details product variant is true.',
    function (): void {
        $companyId = 1;
        $productData = getGoodsReceivedNoteProductData();

        $importRecord = ImportRecord::factory()->make([
            'company_id' => $companyId,
            'created_by_id' => 1,
        ]);

        Config::set('app.product_variant', true);

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

        $product->masterProduct = $masterProduct;

        $productData['upc'] = $product->upc;
        $productData['batch_expiry_date'] = $product->created_at;
        $productData['batch_number'] = $product->upc;

        $this->mock(ProductQueries::class, function ($mock) use ($productData, $companyId, $product): void {
            $mock->shouldReceive('getActiveInventoryProductByUpcForGRN')
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

        $this->mock(BatchQueries::class, function ($mock) use ($productData): void {
            $mock->shouldReceive('getByNumber')
               ->once()
               ->with($productData['batch_number'], 1)
               ->andReturn(null);
        });

        $importProduct = new ImportGoodsReceivedNoteProduct();
        $redirectResponse = $importProduct->validate($productData, $importRecord);
        $this->assertEquals([
            'Batch number is not required for the product with UPC ' . $productData['upc'] . '.',
        ], $redirectResponse);
    }
);

test(
    'validate method returns issue when product is has batch and details are not provided when product variant ',
    function (bool $productVariant): void {
        $companyId = 1;
        $productData = getGoodsReceivedNoteProductData();

        Config::set('app.product_variant', $productVariant);

        if ($productVariant) {
            $masterProduct = MasterProduct::factory()->make([
                'id' => 1,
                'variant_template_id' => 1,
                'company_id' => 1,
                'unit_of_measure_id' => 1,
                'has_batch' => true,
                'is_non_inventory' => false,
                'department_id' => 1,
                'brand_id' => 1,
                'type_id' => ProductTypes::REGULAR_PRODUCT->value,
            ]);
        }

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
            'type_id' => ProductTypes::REGULAR_PRODUCT->value,
        ]);

        if ($productVariant) {
            $product->masterProduct = $masterProduct;
        }

        $productData['upc'] = $product->upc;

        $this->mock(ProductQueries::class, function ($mock) use ($productData, $companyId, $product): void {
            $mock->shouldReceive('getActiveInventoryProductByUpcForGRN')
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

        $this->mock(BatchQueries::class, function ($mock) use ($productData): void {
            $mock->shouldReceive('getByNumber')
               ->once()
               ->with($productData['batch_number'], 1)
               ->andReturn(null);
        });

        $importProduct = new ImportGoodsReceivedNoteProduct();
        $redirectResponse = $importProduct->validate($productData, $importRecord);
        $this->assertEquals([
            'Batch number is required for the batch product with UPC ' . $productData['upc'] . '.',
            'Batch expiry date is required for the batch product with UPC ' . $productData['upc'] . '.',
            'Batch expiry date must be a date in the future. But the specified date is ' . $productData['batch_expiry_date'] . '.',
        ], $redirectResponse);
    }
)->with([[true], [false]]);

test(
    'validate method returns issue when product is has batch but of different batch product when product variant ',
    function (bool $productVariant): void {
        $companyId = 1;
        $productData = getGoodsReceivedNoteProductData();

        Config::set('app.product_variant', $productVariant);

        if ($productVariant) {
            $masterProduct = MasterProduct::factory()->make([
                'id' => 1,
                'variant_template_id' => 1,
                'company_id' => 1,
                'unit_of_measure_id' => 1,
                'has_batch' => true,
                'is_non_inventory' => false,
                'department_id' => 1,
                'brand_id' => 1,
                'type_id' => ProductTypes::REGULAR_PRODUCT->value,
            ]);
        }

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

        if ($productVariant) {
            $product->masterProduct = $masterProduct;
        }

        $productData['upc'] = $product->upc;
        $productData['batch_expiry_date'] = $batch->expiry_date;
        $productData['batch_number'] = $batch->number;

        $this->mock(ProductQueries::class, function ($mock) use ($productData, $companyId, $product): void {
            $mock->shouldReceive('getActiveInventoryProductByUpcForGRN')
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

        $this->mock(BatchQueries::class, function ($mock) use ($productData, $batch): void {
            $mock->shouldReceive('getByNumber')
               ->once()
               ->with($productData['batch_number'], 1)
               ->andReturn($batch);
        });

        $importProduct = new ImportGoodsReceivedNoteProduct();
        $redirectResponse = $importProduct->validate($productData, $importRecord);
        $this->assertEquals([
            'Batch number of the batch product with UPC: ' . $productData['upc'] . ' is already used for another product.',
        ], $redirectResponse);
    }
)->with([[true], [false]]);

test(
    'validate method returns issue when product is has batch but of expiry date is not matching when product variant ',
    function (bool $productVariant): void {
        $companyId = 1;
        $productData = getGoodsReceivedNoteProductData();

        Config::set('app.product_variant', $productVariant);

        $importRecord = ImportRecord::factory()->make([
            'company_id' => $companyId,
            'created_by_id' => 1,
        ]);

        if ($productVariant) {
            $masterProduct = MasterProduct::factory()->make([
                'id' => 1,
                'variant_template_id' => 1,
                'company_id' => 1,
                'unit_of_measure_id' => 1,
                'has_batch' => true,
                'is_non_inventory' => false,
                'department_id' => 1,
                'brand_id' => 1,
                'type_id' => ProductTypes::REGULAR_PRODUCT->value,
            ]);
        }

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

        if ($productVariant) {
            $product->masterProduct = $masterProduct;
        }

        $productData['upc'] = $product->upc;
        $productData['batch_expiry_date'] = Carbon::today()->format('Y-m-d');
        $productData['batch_number'] = $batch->number;

        $this->mock(ProductQueries::class, function ($mock) use ($productData, $companyId, $product): void {
            $mock->shouldReceive('getActiveInventoryProductByUpcForGRN')
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

        $this->mock(BatchQueries::class, function ($mock) use ($productData, $batch): void {
            $mock->shouldReceive('getByNumber')
               ->once()
               ->with($productData['batch_number'], 1)
               ->andReturn($batch);
        });

        $importProduct = new ImportGoodsReceivedNoteProduct();
        $redirectResponse = $importProduct->validate($productData, $importRecord);
        $this->assertEquals([
            'The provided expiry date' . $productData['batch_expiry_date'] . ' does not match with the current expiry date of the batch with number: ' . $batch->number,
        ], $redirectResponse);
    }
)->with([[true], [false]]);

test('save method saves the data.', function (): void {
    $companyId = 1;
    $productData = getGoodsReceivedNoteProductData();

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

    $admin = Admin::factory()->make([
        'id' => 1,
        'employee_id' => 1,
    ]);

    $importRecord = ImportRecord::factory()->make([
        'company_id' => 1,
        'created_by_id' => 1,
        'created_by_type' => ModelMapping::ADMIN->name,
        'module_id' => 1,
        'module_type' => ModelMapping::GOODS_RECEIVED_NOTE->name,
    ]);

    $importRecord->createdBy = $admin;

    $this->mock(ProductQueries::class, function ($mock) use ($productData, $companyId, $product): void {
        $mock->shouldReceive('getActiveInventoryProductByUpcForGRN')
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

    $this->mock(GoodsReceivedNoteQueries::class, function ($mock): void {
        $mock->shouldReceive('getById')
           ->once();
    });

    $this->mock(GoodsReceivedNoteService::class, function ($mock): void {
        $mock->shouldReceive('addProductAndInventory')
           ->once();
    });

    $importProduct = new ImportGoodsReceivedNoteProduct();
    $importProduct->save($productData, $importRecord);
});

function getGoodsReceivedNoteProductData(): array
{
    return [
        'upc' => 'abd123',
        'quantity' => 10,
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

<?php

declare(strict_types=1);

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\StockTransfer\Enums\StatusTypes;
use App\Domains\StockTransfer\Services\StockTransferPrintService;
use App\Models\Attribute;
use App\Models\Color;
use App\Models\MasterProduct;
use App\Models\PackageType;
use App\Models\Product;
use App\Models\ProductVariantValue;
use App\Models\Size;
use App\Models\StockTransferItem;
use App\Models\StockTransferItemTransaction;
use App\Models\UnitOfMeasureDerivative;
use Illuminate\Support\Facades\Config;

it('can format stock transfer item data with article numbers when product variant is false', function (): void {
    Config::set('app.product_variant', false);

    $packageType = PackageType::factory()->make([
        'company_id' => 1,
    ]);

    $color = Color::factory()->make([
        'company_id' => 1,
    ]);

    $size = Size::factory()->make([
        'company_id' => 1,
    ]);

    $product1 = Product::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'unit_of_measure_id' => 1,
        'season_id' => 1,
        'department_id' => 1,
        'sub_department_id' => 1,
        'color_id' => 1,
        'size_id' => 1,
        'brand_id' => 1,
        'style_id' => 1,
        'article_number' => '123',
    ]);

    $product2 = Product::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'unit_of_measure_id' => 1,
        'season_id' => 1,
        'department_id' => 1,
        'sub_department_id' => 1,
        'color_id' => 1,
        'size_id' => 1,
        'brand_id' => 1,
        'style_id' => 1,
        'article_number' => '123',
    ]);

    $product1->color = $color;
    $product2->color = $color;

    $product1->size = $size;
    $product2->size = $size;

    $unitOfMeasureDerivative = UnitOfMeasureDerivative::factory()->make([
        'id' => 1,
        'unit_of_measure_id' => 1,
    ]);

    $stockTransferItem1 = StockTransferItem::factory()->make([
        'id' => 1,
        'stock_transfer_id' => 1,
        'unit_of_measure_derivative_id' => $unitOfMeasureDerivative->id,
        'product_id' => $product1->id,
        'package_type_id' => 1,
        'quantity' => 10,
        'received_quantity' => 10,
        'package_quantity' => 1,
    ]);

    $stockTransferItem2 = StockTransferItem::factory()->make([
        'id' => 1,
        'stock_transfer_id' => 1,
        'product_id' => $product2->id,
        'package_type_id' => 1,
        'quantity' => 10,
        'received_quantity' => 10,
        'package_quantity' => 1,
    ]);

    $stockTransferItemTransaction1 = StockTransferItemTransaction::factory()->make([
        'id' => 1,
        'stock_transfer_item_id' => $stockTransferItem1->id,
        'user_id' => 1,
        'user_type' => ModelMapping::ADMIN->name,
    ]);

    $stockTransferItemTransaction2 = StockTransferItemTransaction::factory()->make([
        'id' => 2,
        'stock_transfer_item_id' => $stockTransferItem2->id,
        'user_id' => 1,
        'user_type' => ModelMapping::ADMIN->name,
    ]);

    $stockTransferItem1->transaction = $stockTransferItemTransaction1;
    $stockTransferItem2->transaction = $stockTransferItemTransaction2;

    $stockTransferItem1->product = $product1;
    $stockTransferItem2->product = $product2;

    $stockTransferItem1->unitOfMeasureDerivative = $unitOfMeasureDerivative;

    $stockTransferItem1->packageType = $packageType;
    $stockTransferItem2->packageType = $packageType;

    $stockTransferPrintService = resolve(StockTransferPrintService::class);

    $response = $stockTransferPrintService->getFormattedData(
        collect([$stockTransferItem1, $stockTransferItem2]),
        StatusTypes::CLOSED->value
    );

    expect($response)->toBeArray();
    expect(is_countable($response) ? count($response) : 0)->toBe(1);

    $expectedData = [
        'name' => $product1->name,
        'products' => [
            [
                'upc' => $product1->upc,
                'color' => $color->name,
                'size' => $size->name,
                'attributes' => [],
                'derivative' => $unitOfMeasureDerivative->name,
                'quantity' => 10,
                'received_quantity' => 10,
                'remarks' => $stockTransferItemTransaction1->remarks,
            ],
            [
                'upc' => $product2->upc,
                'color' => $color->name,
                'size' => $size->name,
                'attributes' => [],
                'derivative' => null,
                'quantity' => 10,
                'received_quantity' => 10,
                'remarks' => $stockTransferItemTransaction2->remarks,
            ],
        ],
        'article_number' => $product1->article_number,
        'quantity' => 20,
        'received_quantity' => 20,
        'package_type' => $packageType->name,
        'package_quantity' => 2,
    ];

    expect($response[0])->toBe($expectedData);
});

it('can format stock transfer item data with article numbers when product variant is true', function (): void {
    Config::set('app.product_variant', true);

    $packageType = PackageType::factory()->make([
        'company_id' => 1,
    ]);

    $product1 = Product::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'unit_of_measure_id' => 1,
        'season_id' => 1,
        'department_id' => 1,
        'sub_department_id' => 1,
        'color_id' => 1,
        'size_id' => 1,
        'brand_id' => 1,
        'style_id' => 1,
        'article_number' => '123',
    ]);

    $product2 = Product::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'unit_of_measure_id' => 1,
        'season_id' => 1,
        'department_id' => 1,
        'sub_department_id' => 1,
        'color_id' => 1,
        'size_id' => 1,
        'brand_id' => 1,
        'style_id' => 1,
        'article_number' => '123',
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

    $attributeSize = Attribute::factory()->make([
        'id' => 1,
        'template_id' => 1,
        'name' => 'size',
        'company_id' => 1,
    ]);

    $attributeColor = Attribute::factory()->make([
        'id' => 1,
        'template_id' => 1,
        'name' => 'color',
        'company_id' => 1,
    ]);

    $productVariantValue1 = ProductVariantValue::factory()->make([
        'id' => 1,
        'product_id' => $product1->id,
        'attribute_id' => $attributeSize->id,
        'value' => 'sizeA',
    ]);

    $productVariantValue2 = ProductVariantValue::factory()->make([
        'id' => 1,
        'product_id' => $product2->id,
        'attribute_id' => $attributeColor->id,
        'value' => 'colorA',
    ]);

    $productVariantValue1->attribute = $attributeSize;
    $productVariantValue2->attribute = $attributeColor;

    $product1->productVariantValues = collect([$productVariantValue1, $productVariantValue2]);
    $product2->productVariantValues = collect([$productVariantValue1, $productVariantValue2]);

    $masterProduct->productVariants = collect([$product1, $product2]);

    $product1->masterProduct = $masterProduct;
    $product2->masterProduct = $masterProduct;

    $unitOfMeasureDerivative = UnitOfMeasureDerivative::factory()->make([
        'id' => 1,
        'unit_of_measure_id' => 1,
    ]);

    $stockTransferItem1 = StockTransferItem::factory()->make([
        'id' => 1,
        'stock_transfer_id' => 1,
        'unit_of_measure_derivative_id' => $unitOfMeasureDerivative->id,
        'product_id' => $product1->id,
        'package_type_id' => 1,
        'quantity' => 10,
        'received_quantity' => 10,
        'package_quantity' => 1,
    ]);

    $stockTransferItem2 = StockTransferItem::factory()->make([
        'id' => 1,
        'stock_transfer_id' => 1,
        'product_id' => $product2->id,
        'package_type_id' => 1,
        'quantity' => 10,
        'received_quantity' => 10,
        'package_quantity' => 1,
    ]);

    $stockTransferItemTransaction1 = StockTransferItemTransaction::factory()->make([
        'id' => 1,
        'stock_transfer_item_id' => $stockTransferItem1->id,
        'user_id' => 1,
        'user_type' => ModelMapping::ADMIN->name,
    ]);

    $stockTransferItemTransaction2 = StockTransferItemTransaction::factory()->make([
        'id' => 2,
        'stock_transfer_item_id' => $stockTransferItem2->id,
        'user_id' => 1,
        'user_type' => ModelMapping::ADMIN->name,
    ]);

    $stockTransferItem1->transaction = $stockTransferItemTransaction1;
    $stockTransferItem2->transaction = $stockTransferItemTransaction2;

    $stockTransferItem1->product = $product1;
    $stockTransferItem2->product = $product2;

    $stockTransferItem1->unitOfMeasureDerivative = $unitOfMeasureDerivative;

    $stockTransferItem1->packageType = $packageType;
    $stockTransferItem2->packageType = $packageType;

    $stockTransferPrintService = resolve(StockTransferPrintService::class);

    $response = $stockTransferPrintService->getFormattedData(
        collect([$stockTransferItem1, $stockTransferItem2]),
        StatusTypes::CLOSED->value
    );

    expect($response)->toBeArray();
    expect(is_countable($response) ? count($response) : 0)->toBe(1);

    $expectedData = [
        'name' => $product1->name,
        'products' => [
            [
                'upc' => $product1->upc,
                'color' => null,
                'size' => null,
                'attributes' => [
                    $attributeSize->name => $productVariantValue1->value,
                    $attributeColor->name => $productVariantValue2->value,
                ],
                'derivative' => $unitOfMeasureDerivative->name,
                'quantity' => 10,
                'received_quantity' => 10,
                'remarks' => $stockTransferItemTransaction1->remarks,
            ],
            [
                'upc' => $product2->upc,
                'color' => null,
                'size' => null,
                'attributes' => [
                    $attributeSize->name => $productVariantValue1->value,
                    $attributeColor->name => $productVariantValue2->value,
                ],
                'derivative' => null,
                'quantity' => 10,
                'received_quantity' => 10,
                'remarks' => $stockTransferItemTransaction2->remarks,
            ],
        ],
        'article_number' => $masterProduct->article_number,
        'quantity' => 20,
        'received_quantity' => 20,
        'package_type' => $packageType->name,
        'package_quantity' => 2,
    ];

    expect($response[0])->toBe($expectedData);
});

it('can format stock transfer item data without article numbers when product variant is true', function (): void {
    Config::set('app.product_variant', true);

    $packageType = PackageType::factory()->make([
        'company_id' => 1,
    ]);

    $product1 = Product::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'unit_of_measure_id' => 1,
        'season_id' => 1,
        'department_id' => 1,
        'sub_department_id' => 1,
        'color_id' => 1,
        'size_id' => 1,
        'brand_id' => 1,
        'style_id' => 1,
        'article_number' => null,
    ]);

    $attributeSize = Attribute::factory()->make([
        'id' => 1,
        'template_id' => 1,
        'name' => 'size',
        'company_id' => 1,
    ]);

    $attributeColor = Attribute::factory()->make([
        'id' => 1,
        'template_id' => 1,
        'name' => 'color',
        'company_id' => 1,
    ]);

    $productVariantValue1 = ProductVariantValue::factory()->make([
        'id' => 1,
        'product_id' => $product1->id,
        'attribute_id' => $attributeSize->id,
        'value' => 'sizeA',
    ]);

    $productVariantValue2 = ProductVariantValue::factory()->make([
        'id' => 1,
        'product_id' => $product1->id,
        'attribute_id' => $attributeColor->id,
        'value' => 'colorA',
    ]);

    $productVariantValue1->attribute = $attributeSize;
    $productVariantValue2->attribute = $attributeColor;

    $product1->productVariantValues = collect([$productVariantValue1, $productVariantValue2]);

    $masterProduct = MasterProduct::factory()->make([
        'id' => 1,
        'variant_template_id' => 1,
        'company_id' => 1,
        'unit_of_measure_id' => 1,
        'has_batch' => true,
        'is_non_inventory' => false,
        'department_id' => 1,
        'brand_id' => 1,
        'article_number' => null,
    ]);

    $product1->masterProduct = $masterProduct;

    $masterProduct->productVariants = collect([$product1]);

    $unitOfMeasureDerivative = UnitOfMeasureDerivative::factory()->make([
        'id' => 1,
        'unit_of_measure_id' => 1,
    ]);

    $stockTransferItem1 = StockTransferItem::factory()->make([
        'id' => 1,
        'stock_transfer_id' => 1,
        'product_id' => $product1->id,
        'unit_of_measure_derivative_id' => $unitOfMeasureDerivative->id,
        'package_type_id' => 1,
        'quantity' => 10,
        'received_quantity' => 10,
        'package_quantity' => 1,
    ]);

    $stockTransferItemTransaction1 = StockTransferItemTransaction::factory()->make([
        'id' => 1,
        'stock_transfer_item_id' => $stockTransferItem1->id,
        'user_id' => 1,
        'user_type' => ModelMapping::ADMIN->name,
    ]);

    $stockTransferItem1->transaction = $stockTransferItemTransaction1;

    $stockTransferItem1->product = $product1;

    $stockTransferItem1->unitOfMeasureDerivative = $unitOfMeasureDerivative;

    $stockTransferItem1->packageType = $packageType;

    $stockTransferPrintService = resolve(StockTransferPrintService::class);

    $response = $stockTransferPrintService->getFormattedData(
        collect([$stockTransferItem1]),
        StatusTypes::CLOSED->value
    );

    expect($response)->toBeArray();
    expect(is_countable($response) ? count($response) : 0)->toBe(1);

    $expectedData = [
        [
            'name' => $product1->name,
            'products' => [
                [
                    'upc' => $product1->upc,
                    'color' => null,
                    'size' => null,
                    'attributes' => [
                        $attributeSize->name => $productVariantValue1->value,
                        $attributeColor->name => $productVariantValue2->value,
                    ],
                    'derivative' => $unitOfMeasureDerivative->name,
                    'quantity' => 10,
                    'received_quantity' => 10,
                    'remarks' => $stockTransferItemTransaction1->remarks,
                ],
            ],
            'article_number' => 'N/A',
            'quantity' => 10,
            'received_quantity' => 10,
            'package_type' => $packageType->name,
            'package_quantity' => 1,
        ],
    ];

    expect($response)->toBe($expectedData);
});

it('can format stock transfer item data without article numbers when product variant is false', function (): void {
    Config::set('app.product_variant', false);

    $packageType = PackageType::factory()->make([
        'company_id' => 1,
    ]);

    $color = Color::factory()->make([
        'company_id' => 1,
    ]);

    $size = Size::factory()->make([
        'company_id' => 1,
    ]);

    $product1 = Product::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'unit_of_measure_id' => 1,
        'season_id' => 1,
        'department_id' => 1,
        'sub_department_id' => 1,
        'color_id' => 1,
        'size_id' => 1,
        'brand_id' => 1,
        'style_id' => 1,
        'article_number' => null,
    ]);

    $product2 = Product::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'unit_of_measure_id' => 1,
        'season_id' => 1,
        'department_id' => 1,
        'sub_department_id' => 1,
        'color_id' => 1,
        'size_id' => 1,
        'brand_id' => 1,
        'style_id' => 1,
        'article_number' => null,
    ]);

    $product1->color = $color;
    $product2->color = $color;

    $product1->size = $size;
    $product2->size = $size;

    $unitOfMeasureDerivative = UnitOfMeasureDerivative::factory()->make([
        'id' => 1,
        'unit_of_measure_id' => 1,
    ]);

    $stockTransferItem1 = StockTransferItem::factory()->make([
        'id' => 1,
        'stock_transfer_id' => 1,
        'product_id' => $product1->id,
        'unit_of_measure_derivative_id' => $unitOfMeasureDerivative->id,
        'package_type_id' => 1,
        'quantity' => 10,
        'received_quantity' => 10,
        'package_quantity' => 1,
    ]);

    $stockTransferItem2 = StockTransferItem::factory()->make([
        'id' => 1,
        'stock_transfer_id' => 1,
        'product_id' => $product2->id,
        'package_type_id' => 1,
        'quantity' => 10,
        'received_quantity' => 10,
        'package_quantity' => 1,
    ]);

    $stockTransferItemTransaction1 = StockTransferItemTransaction::factory()->make([
        'id' => 1,
        'stock_transfer_item_id' => $stockTransferItem1->id,
        'user_id' => 1,
        'user_type' => ModelMapping::ADMIN->name,
    ]);

    $stockTransferItemTransaction2 = StockTransferItemTransaction::factory()->make([
        'id' => 2,
        'stock_transfer_item_id' => $stockTransferItem2->id,
        'user_id' => 1,
        'user_type' => ModelMapping::ADMIN->name,
    ]);

    $stockTransferItem1->transaction = $stockTransferItemTransaction1;
    $stockTransferItem2->transaction = $stockTransferItemTransaction2;

    $stockTransferItem1->product = $product1;
    $stockTransferItem2->product = $product2;

    $stockTransferItem1->unitOfMeasureDerivative = $unitOfMeasureDerivative;

    $stockTransferItem1->packageType = $packageType;
    $stockTransferItem2->packageType = $packageType;

    $stockTransferPrintService = resolve(StockTransferPrintService::class);

    $response = $stockTransferPrintService->getFormattedData(
        collect([$stockTransferItem1, $stockTransferItem2]),
        StatusTypes::CLOSED->value
    );

    expect($response)->toBeArray();
    expect(is_countable($response) ? count($response) : 0)->toBe(2);

    $expectedData = [
        [
            'name' => $product1->name,
            'products' => [
                [
                    'upc' => $product1->upc,
                    'color' => $color->name,
                    'size' => $size->name,
                    'attributes' => [],
                    'derivative' => $unitOfMeasureDerivative->name,
                    'quantity' => 10,
                    'received_quantity' => 10,
                    'remarks' => $stockTransferItemTransaction1->remarks,
                ],
            ],
            'article_number' => 'N/A',
            'quantity' => 10,
            'received_quantity' => 10,
            'package_type' => $packageType->name,
            'package_quantity' => 1,
        ],
        [
            'name' => $product2->name,
            'products' => [
                [
                    'upc' => $product2->upc,
                    'color' => $color->name,
                    'size' => $size->name,
                    'attributes' => [],
                    'derivative' => null,
                    'quantity' => 10,
                    'received_quantity' => 10,
                    'remarks' => $stockTransferItemTransaction2->remarks,
                ],
            ],
            'article_number' => 'N/A',
            'quantity' => 10,
            'received_quantity' => 10,
            'package_type' => $packageType->name,
            'package_quantity' => 1,
        ],
    ];

    expect($response)->toBe($expectedData);
});

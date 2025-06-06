<?php

declare(strict_types=1);

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\StockTransfer\Enums\StatusTypes;
use App\Domains\StockTransfer\Enums\StockTransferCustomReportDateTypes;
use App\Domains\StockTransfer\Enums\TransferTypeForReport;
use App\Domains\StockTransferItem\Enums\StockTransferDiscrepancyTypes;
use App\Domains\StockTransferItem\StockTransferItemQueries;
use App\Models\Attribute;
use App\Models\Company;
use App\Models\Inventory;
use App\Models\InventoryUnit;
use App\Models\Location;
use App\Models\MasterProduct;
use App\Models\PackageType;
use App\Models\Product;
use App\Models\ProductVariantValue;
use App\Models\ReservedStock;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\Template;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

test('fetch stock transfer items by stock transfer id when product variant is false', function (): void {
    Config::set('app.product_variant', false);

    $stockTransferItem = StockTransferItem::factory()->create();
    $companyId = $stockTransferItem->stockTransfer->company_id;
    $stockTransferItemQueries = new StockTransferItemQueries();
    $response = $stockTransferItemQueries->getByStockTransferId($stockTransferItem->stock_transfer_id, $companyId);
    expect($response->first()->toArray())
        ->toHaveKey('id', $stockTransferItem->id)
        ->toHaveKey('product_id', $stockTransferItem->product_id)
        ->toHaveKey('quantity', $stockTransferItem->quantity)
        ->toHaveKey('received_quantity', $stockTransferItem->received_quantity)
        ->toHaveKey('discrepancy_type', $stockTransferItem->discrepancy_type)
        ->toHaveKey('is_extra_item', $stockTransferItem->is_extra_item)
         ->toHaveKey('unit_of_measure_derivative_id', $stockTransferItem->unit_of_measure_derivative_id)
        ->toHaveKeys(['product.color', 'product.size', 'transactions', 'product.unit_of_measure']);
});

test('fetch stock transfer items by stock transfer id when product variant is true', function (): void {
    Config::set('app.product_variant', true);

    $companyId = Company::factory()->create()->id;

    $template = Template::factory()->create([
        'company_id' => $companyId,
    ]);

    $masterProduct = MasterProduct::factory()->create([
        'variant_template_id' => $template->id,
        'company_id' => $companyId,
        'has_batch' => true,
        'is_non_inventory' => false,
    ]);

    $attributeSize = Attribute::factory()->create([
        'name' => 'size',
        'company_id' => $companyId,
    ]);

    $attributeColor = Attribute::factory()->create([
        'name' => 'color',
        'company_id' => $companyId,
    ]);

    $product = Product::factory()->create([
        'company_id' => $companyId,
        'master_product_id' => $masterProduct->id,
    ]);

    $productVariantValue1 = ProductVariantValue::factory()->create([
        'product_id' => $product->id,
        'attribute_id' => $attributeSize->id,
        'value' => 'sizeA',
    ]);

    $productVariantValue2 = ProductVariantValue::factory()->create([
        'product_id' => $product->id,
        'attribute_id' => $attributeColor->id,
        'value' => 'colorA',
    ]);

    $productVariantValue1->attribute = $attributeSize;
    $productVariantValue2->attribute = $attributeColor;

    $product->productVariantValues = collect([$productVariantValue1, $productVariantValue2]);

    $masterProduct->productVariants = collect([$product]);

    $stockTransferItem = StockTransferItem::factory()->create([
        'product_id' => $product->id,
    ]);

    $companyId = $stockTransferItem->stockTransfer->company_id;
    $stockTransferItemQueries = new StockTransferItemQueries();
    $response = $stockTransferItemQueries->getByStockTransferId($stockTransferItem->stock_transfer_id, $companyId);

    expect($response->first()->toArray())
        ->toHaveKey('id', $stockTransferItem->id)
        ->toHaveKey('product_id', $stockTransferItem->product_id)
        ->toHaveKey('quantity', $stockTransferItem->quantity)
        ->toHaveKey('received_quantity', $stockTransferItem->received_quantity)
        ->toHaveKey('discrepancy_type', $stockTransferItem->discrepancy_type)
        ->toHaveKey('is_extra_item', $stockTransferItem->is_extra_item)
         ->toHaveKey('unit_of_measure_derivative_id', $stockTransferItem->unit_of_measure_derivative_id)
        ->toHaveKeys(['product.master_product', 'transactions', 'product.master_product.unit_of_measure']);
});

test(
    'fetch stock transfer items by stock transfer id with product and batches when product variant is true',
    function (): void {
        Config::set('app.product_variant', true);

        $companyId = Company::factory()->create()->id;

        $template = Template::factory()->create([
            'company_id' => $companyId,
        ]);

        $masterProduct = MasterProduct::factory()->create([
            'variant_template_id' => $template->id,
            'company_id' => $companyId,
            'has_batch' => true,
            'is_non_inventory' => false,
        ]);

        $attributeSize = Attribute::factory()->create([
            'company_id' => $companyId,
            'name' => 'size',
        ]);

        $attributeColor = Attribute::factory()->create([
            'company_id' => $companyId,
            'name' => 'color',
        ]);

        $product = Product::factory()->create([
            'company_id' => $companyId,
            'master_product_id' => $masterProduct->id,
        ]);

        $productVariantValue1 = ProductVariantValue::factory()->create([
            'product_id' => $product->id,
            'attribute_id' => $attributeSize->id,
            'value' => 'sizeA',
        ]);

        $productVariantValue2 = ProductVariantValue::factory()->create([
            'product_id' => $product->id,
            'attribute_id' => $attributeColor->id,
            'value' => 'colorA',
        ]);

        $productVariantValue1->attribute = $attributeSize;
        $productVariantValue2->attribute = $attributeColor;

        $product->productVariantValues = collect([$productVariantValue1, $productVariantValue2]);

        $masterProduct->productVariants = collect([$product]);

        $stockTransfer = StockTransfer::factory()->create([
            'status' => StatusTypes::DISCREPANCY->value,
            'company_id' => $companyId,
        ]);

        $stockTransferItem = StockTransferItem::factory()->create([
            'stock_transfer_id' => $stockTransfer->id,
            'product_id' => $product->id,
        ]);

        $stockTransferItemQueries = new StockTransferItemQueries();
        $response = $stockTransferItemQueries->getByStockTransferIdWithProductAndBatches(
            $stockTransferItem->stock_transfer_id,
            $companyId
        );

        expect($response->first()->toArray())
            ->toHaveKeys(
                [
                    'id',
                    'quantity',
                    'received_quantity',
                    'discrepancy_type',
                    'is_extra_item',
                    'unit_of_measure_derivative_id',
                    'product.master_product',
                    'unit_of_measure_derivative',
                ]
            );
    }
);

test(
    'fetch stock transfer items by stock transfer id with product and batches when product variant is false',
    function (): void {
        Config::set('app.product_variant', false);

        $stockTransfer = StockTransfer::factory()->create([
            'status' => StatusTypes::DISCREPANCY->value,
        ]);
        $stockTransferItem = StockTransferItem::factory()->create([
            'stock_transfer_id' => $stockTransfer->id,
        ]);
        $companyId = $stockTransferItem->stockTransfer->company_id;
        $stockTransferItemQueries = new StockTransferItemQueries();
        $response = $stockTransferItemQueries->getByStockTransferIdWithProductAndBatches(
            $stockTransferItem->stock_transfer_id,
            $companyId
        );
        expect($response->first()->toArray())
            ->toHaveKeys(
                [
                    'id',
                    'quantity',
                    'received_quantity',
                    'discrepancy_type',
                    'is_extra_item',
                    'unit_of_measure_derivative_id',
                    'product.color',
                    'product.size',
                    'transactions',
                    'unit_of_measure_derivative',
                ]
            );
    }
);

test('fetch stock transfer items with relations by stock transfer item id', function (): void {
    $stockTransferItem = StockTransferItem::factory()->create();
    $stockTransferItemQueries = new StockTransferItemQueries();
    $response = $stockTransferItemQueries->getByIdWithRelations($stockTransferItem->id);
    expect($response->first()->toArray())
        ->toHaveKeys(['id', 'quantity', 'received_quantity', 'discrepancy_type']);
});

test('stock transfer items can be added', function (): void {
    $stockTransferItemQueries = new StockTransferItemQueries();

    $stockTransfer = StockTransfer::factory()->create();
    $product = Product::factory()->create();

    $itemDetails = [
        'product_id' => $product->id,
        'quantity' => 10.22,
    ];

    $stockTransferItemQueries->createMany($stockTransfer, [$itemDetails]);

    $this->assertDatabaseHas('stock_transfer_items', $itemDetails);
});

test('stock transfer item can be added', function (): void {
    $stockTransferItemQueries = new StockTransferItemQueries();

    $stockTransfer = StockTransfer::factory()->create();
    $product = Product::factory()->create();

    $itemDetails = [
        'stock_transfer_id' => $stockTransfer->id,
        'product_id' => $product->id,
        'quantity' => 10.22,
    ];

    $stockTransferItemQueries->addNew($itemDetails);

    $this->assertDatabaseHas('stock_transfer_items', $itemDetails);
});

test('deleteItemAndBatches method deletes the stock transfer items and its batches details', function (): void {
    $stockTransferItemQueries = new StockTransferItemQueries();

    $stockTransfer = StockTransfer::factory()->create();
    $stockTransferItem = StockTransferItem::factory()->create([
        'stock_transfer_id' => $stockTransfer->id,
    ]);

    $inventory = Inventory::factory()->create([
        'stock' => 100,
        'reserved_stock' => 5,
    ]);

    $inventoryUnit = InventoryUnit::factory()->create([
        'inventory_id' => $inventory->id,
        'batch_id' => null,
        'quantity' => 100,
        'reserved_stock' => 5,
    ]);

    $reservedStock = ReservedStock::factory()->create([
        'inventory_id' => $inventory->id,
        'inventory_unit_id' => $inventoryUnit->id,
        'affected_by_id' => $stockTransferItem->id,
        'affected_by_type' => ModelMapping::STOCK_TRANSFER_ITEM->name,
        'quantity' => 5,
    ]);

    $stockTransferItemQueries->deleteItemAndBatches($stockTransfer);

    $this->assertSoftDeleted('stock_transfer_items', [
        'id' => $stockTransferItem->id,
    ]);

    $this->assertSoftDeleted('reserved_stocks', [
        'id' => $reservedStock->id,
    ]);

    $this->assertDatabaseHas('inventory_units', [
        'id' => $inventoryUnit->id,
        'reserved_stock' => 0,
    ]);

    $this->assertDatabaseHas('inventories', [
        'id' => $inventory->id,
        'reserved_stock' => 0,
    ]);
});

test('it updates the received quantities of the specified stock transfer items', function (): void {
    $stockTransferItemQueries = new StockTransferItemQueries();

    $stockTransfer = StockTransfer::factory()->create();
    $stockTransferItem = StockTransferItem::factory()->create([
        'stock_transfer_id' => $stockTransfer->id,
        'received_quantity' => null,
        'discrepancy_type' => 2,
    ]);

    $itemDetails = [
        'item_id' => $stockTransferItem->id,
        'received_quantity' => 10,
        'status' => StockTransferDiscrepancyTypes::POSITIVE->value,
    ];

    $stockTransferItemQueries->updateReceivedQuantityAndDiscrepancyStatusByIdAndStockTransferId(
        $itemDetails,
        $stockTransfer->id,
        $stockTransfer->company_id
    );

    $this->assertDatabaseHas('stock_transfer_items', [
        'id' => $stockTransferItem->id,
        'received_quantity' => $itemDetails['received_quantity'],
        'package_total_quantity' => $itemDetails['received_quantity'],
        'discrepancy_type' => StockTransferDiscrepancyTypes::POSITIVE->value,
    ]);
});

test(
    'it updates the shipping package and packageType details of the specified stock transfer items',
    function (): void {
        $stockTransferItemQueries = new StockTransferItemQueries();

        $stockTransfer = StockTransfer::factory()->create();
        $stockTransferItem = StockTransferItem::factory()->create([
            'stock_transfer_id' => $stockTransfer->id,
            'package_type_id' => null,
            'package_quantity' => null,
            'package_total_quantity' => null,
            'received_quantity' => null,
            'discrepancy_type' => 2,
        ]);

        $packageType = PackageType::factory()->create([
            'company_id' => $stockTransfer->company_id,
        ]);

        $itemDetails = [
            'id' => $stockTransferItem->id,
            'package_type_id' => $packageType->id,
            'package_quantity' => 1,
            'package_total_quantity' => $stockTransferItem->quantity,
        ];

        $stockTransferItemQueries->updateShippingDetailsRecordsById(
            $itemDetails,
            $stockTransfer->id,
            $stockTransfer->company_id
        );

        $this->assertDatabaseHas('stock_transfer_items', [
            'id' => $stockTransferItem->id,
            'package_type_id' => $packageType->id,
            'package_quantity' => 1,
            'package_total_quantity' => $stockTransferItem->quantity,
        ]);
    }
);

test('it save discrepancy proof image', function (): void {
    $validatedData = [];
    Storage::fake('public');

    $uploadedFile = UploadedFile::fake()->image('avatar.jpg');

    $validatedData['discrepancy_proof'] = $uploadedFile;

    $stockTransferItemQueries = new StockTransferItemQueries();

    $stockTransfer = StockTransfer::factory()->create();
    $stockTransferItem = StockTransferItem::factory()->create([
        'stock_transfer_id' => $stockTransfer->id,
    ]);

    $stockTransferItemQueries->uploadDiscrepancyProof($validatedData, $stockTransferItem->id);

    $this->assertDatabaseHas('media', [
        'model_type' => ModelMapping::getCaseName($stockTransferItem::class),
        'model_id' => $stockTransferItem->id,
        'collection_name' => 'discrepancy_proof',
        'file_name' => $uploadedFile->name,
        'mime_type' => 'image/jpeg',
    ]);
});

test('it remove discrepancy proof image', function (): void {
    Storage::fake('public');

    $uploadedFile = UploadedFile::fake()->image('avatar.jpg');

    $stockTransferItemQueries = new StockTransferItemQueries();

    $stockTransfer = StockTransfer::factory()->create();
    $stockTransferItem = StockTransferItem::factory()->create([
        'stock_transfer_id' => $stockTransfer->id,
    ]);

    $stockTransferItem->addMedia($uploadedFile)->toMediaCollection('discrepancy_proof');

    $stockTransferItemQueries->removeDiscrepancyProof($stockTransferItem->id);

    $this->assertDatabaseMissing('media', [
        'model_type' => $stockTransferItem::class,
        'model_id' => $stockTransferItem->id,
        'collection_name' => 'discrepancy_proof',
        'file_name' => $uploadedFile->name,
        'mime_type' => 'image/jpeg',
    ]);
});

test('stock transfer items set received quantity as quantity', function (): void {
    $stockTransferItemQueries = new StockTransferItemQueries();

    $stockTransfer = StockTransfer::factory()->create();

    $stockTransferItemOne = StockTransferItem::factory()->create([
        'stock_transfer_id' => $stockTransfer->id,
        'quantity' => 5,
        'received_quantity' => null,
    ]);

    $stockTransferItemTwo = StockTransferItem::factory()->create([
        'stock_transfer_id' => $stockTransfer->id,
        'quantity' => 10,
        'received_quantity' => null,
    ]);

    $stockTransferItemQueries->setReceivedQuantitySameAsQuantity($stockTransfer->id, $stockTransfer->company_id);

    $this->assertDatabaseHas('stock_transfer_items', [
        'id' => $stockTransferItemOne->id,
        'received_quantity' => $stockTransferItemOne->quantity,
    ]);

    $this->assertDatabaseHas('stock_transfer_items', [
        'id' => $stockTransferItemTwo->id,
        'received_quantity' => $stockTransferItemTwo->quantity,
    ]);
});

test(
    'getByWithProductAndStockTransferForStockOutReport fetch stock transfer items by with product',
    function (): void {
        $location = Location::factory([
            'type_id' => LocationTypes::STORE->value,
        ])->create();
        $stockTransfer = StockTransfer::factory()->create([
            'source_location_id' => $location->id,
        ]);
        $stockTransferItem = StockTransferItem::factory()->create([
            'stock_transfer_id' => $stockTransfer->id,
        ]);
        $companyId = $stockTransferItem->stockTransfer->company_id;
        $stockTransferItemQueries = new StockTransferItemQueries();
        $filterData = [
            'location_id' => $location->id,
            'department_id' => '',
            'date_range' => [now()->subDay()->format('Y-m-d'), now()->addDay()->format('Y-m-d')],
            'product_id' => null,
            'article_number' => null,
            'brand_id' => null,
        ];
        $response = $stockTransferItemQueries->getWithProductAndStockTransferForStockOutReport($filterData, $companyId);
        expect($response->first()->toArray())
            ->toHaveKeys(['id', 'received_quantity', 'product']);
    }
);

test(
    'getWithProductAndStockTransferForStockInReport fetch stock transfer items by with product',
    function (): void {
        $companyId = Company::factory()->create()->id;
        $location = Location::factory([
            'type_id' => LocationTypes::STORE->value,
            'company_id' => $companyId,
        ])->create();
        $stockTransfer = StockTransfer::factory()->create([
            'destination_location_id' => $location->id,
            'created_at' => now(),
            'company_id' => $companyId,
        ]);
        $stockTransferItem = StockTransferItem::factory()->create([
            'stock_transfer_id' => $stockTransfer->id,
        ]);
        $companyId = $stockTransferItem->stockTransfer->company_id;
        $stockTransferItemQueries = new StockTransferItemQueries();
        $filterData = [
            'location_id' => $location->id,
            'department_id' => '',
            'date_range' => [now()->subDay()->format('Y-m-d'), now()->addDay()->format('Y-m-d')],
            'product_id' => null,
            'article_number' => null,
            'brand_id' => null,
        ];
        $response = $stockTransferItemQueries->getWithProductAndStockTransferForStockInReport($filterData, $companyId);
        expect($response->first()->toArray())
            ->toHaveKeys(['id', 'received_quantity', 'product']);
    }
);

test('fetch stock transfer items by date and location', function (): void {
    $company = Company::factory()->create();
    $productId = Product::factory()->create([
        'company_id' => $company->id,
        'is_non_selling_item' => false,
    ])->id;
    $currentDate = now();
    $stockTransfer = StockTransfer::factory()->create([
        'company_id' => $company->id,
        'created_at' => $currentDate->format('Y-m-d'),
        'status' => StatusTypes::SHIPPED->value,
    ]);
    $stockTransferItem = StockTransferItem::factory()->create([
        'stock_transfer_id' => $stockTransfer->id,
        'product_id' => $productId,
    ]);
    $filterData = [
        'location_ids' => [$stockTransfer->destination_location_id],
        'additional_location_id' => $stockTransfer->source_location_id,
        'transfer_type' => TransferTypeForReport::TRANSFER_IN->value,
        'status_type' => null,
        'display_date_type' => StockTransferCustomReportDateTypes::CREATED_AT->value,
        'date_type' => StockTransferCustomReportDateTypes::CREATED_AT->value,
        'date_range' => [$currentDate->subDay()->format('Y-m-d'), $currentDate->addDay()->format('Y-m-d')],
        'product_id' => null,
        'product_collection_id' => null,
        'article_number' => null,
    ];
    $stockTransferItemQueries = new StockTransferItemQueries();
    $response = $stockTransferItemQueries->getByDateAndLocationWithProduct($filterData, $company->id);
    expect($response->first()->toArray())
        ->toHaveKey('id', $stockTransferItem->id)
        ->toHaveKey('product_id', $stockTransferItem->product_id)
        ->toHaveKey('stock_transfer_id', $stockTransfer->id)
        ->toHaveKey('quantity', $stockTransferItem->quantity)
        ->toHaveKey('received_quantity', $stockTransferItem->received_quantity)
        ->toHaveKeys(['product.color', 'product.size']);
});

test('removeAdditionalItemAndRelations method remove item & units', function (): void {
    $stockTransferItem = StockTransferItem::factory()->create([
        'is_extra_item' => true,
    ]);

    $stockTransferItemQueries = new StockTransferItemQueries();
    $stockTransferItemQueries->removeAdditionalItemAndRelations($stockTransferItem->id);

    $this->assertSoftDeleted('stock_transfer_items', [
        'id' => $stockTransferItem->id,
    ]);
});

test('getProductIdsBy method remove item & units', function (): void {
    $stockTransferItem = StockTransferItem::factory()->create();
    $stockTransferItemQueries = new StockTransferItemQueries();
    $response = $stockTransferItemQueries->getProductIdsBy($stockTransferItem->stock_transfer_id);
    expect($response->first()->toArray())
        ->toHaveKey('id', $stockTransferItem->id)
        ->toHaveKey('product_id', $stockTransferItem->product_id);
});

test('getStatusById method call and return status by id.', function (): void {
    $stockTransferItem = StockTransferItem::factory()->create();
    $stockTransferItemQueries = new StockTransferItemQueries();
    $response = $stockTransferItemQueries->getStatusById($stockTransferItem->id);
    expect($response)->toBeInt();
});

test('if product is merged then the product id is updated', function (): void {
    $companyId = Company::factory()->create()->id;
    $productAId = Product::factory()->create([
        'company_id' => $companyId,
    ])->id;
    $productBId = Product::factory()->create([
        'company_id' => $companyId,
    ])->id;
    $stockTransfer = StockTransfer::factory()->create([
        'company_id' => $companyId,
    ]);
    $stockTransferItem = StockTransferItem::factory()->create([
        'stock_transfer_id' => $stockTransfer,
        'product_id' => $productBId,
    ]);

    $stockTransferItemQueries = new StockTransferItemQueries();
    $stockTransferItemQueries->updateProductId($companyId, $productBId, $productAId);

    $this->assertDatabaseHas('stock_transfer_items', [
        'stock_transfer_id' => $stockTransfer->id,
        'product_id' => $productAId,
    ]);
});

test(
    'fetch stock transfer items by stock transfer id with pagination when product variant is false',
    function (): void {
        Config::set('app.product_variant', false);

        $stockTransferItem = StockTransferItem::factory()->create();
        $companyId = $stockTransferItem->stockTransfer->company_id;
        $stockTransferItemQueries = new StockTransferItemQueries();
        $filterData = [
            'id' => $stockTransferItem->stock_transfer_id,
            'per_page' => 10,
            'page' => 1,
            'search_text' => null,
        ];
        $response = $stockTransferItemQueries->getByPaginatedStockTransferId($filterData, $companyId);
        expect($response[0])
            ->toHaveKeys(
                [
                    'id',
                    'stock_transfer_id',
                    'product_id',
                    'quantity',
                    'received_quantity',
                    'discrepancy_type',
                    'is_extra_item',
                    'unit_of_measure_derivative_id',
                    'product',
                    'product.color',
                    'product.size',
                ]
            );
    }
);

test('fetch stock transfer items by stock transfer id with pagination when product variant is true', function (): void {
    Config::set('app.product_variant', true);

    $companyId = Company::factory()->create()->id;

    $template = Template::factory()->create([
        'company_id' => $companyId,
    ]);

    $masterProduct = MasterProduct::factory()->create([
        'variant_template_id' => $template->id,
        'company_id' => $companyId,
        'has_batch' => true,
        'is_non_inventory' => false,
    ]);

    $attributeSize = Attribute::factory()->create([
        'company_id' => $companyId,
        'name' => 'size',
    ]);

    $attributeColor = Attribute::factory()->create([
        'company_id' => $companyId,
        'name' => 'color',
    ]);

    $product = Product::factory()->create([
        'company_id' => $companyId,
        'master_product_id' => $masterProduct->id,
    ]);

    $productVariantValue1 = ProductVariantValue::factory()->create([
        'product_id' => $product->id,
        'attribute_id' => $attributeSize->id,
        'value' => 'sizeA',
    ]);

    $productVariantValue2 = ProductVariantValue::factory()->create([
        'product_id' => $product->id,
        'attribute_id' => $attributeColor->id,
        'value' => 'colorA',
    ]);

    $productVariantValue1->attribute = $attributeSize;
    $productVariantValue2->attribute = $attributeColor;

    $product->productVariantValues = collect([$productVariantValue1, $productVariantValue2]);

    $masterProduct->productVariants = collect([$product]);

    $stockTransferItem = StockTransferItem::factory()->create([
        'product_id' => $product->id,
    ]);

    $companyId = $stockTransferItem->stockTransfer->company_id;
    $stockTransferItemQueries = new StockTransferItemQueries();
    $filterData = [
        'id' => $stockTransferItem->stock_transfer_id,
        'per_page' => 10,
        'page' => 1,
        'search_text' => null,
    ];
    $response = $stockTransferItemQueries->getByPaginatedStockTransferId($filterData, $companyId);
    expect($response[0])
        ->toHaveKeys(
            [
                'id',
                'stock_transfer_id',
                'product_id',
                'quantity',
                'received_quantity',
                'discrepancy_type',
                'is_extra_item',
                'unit_of_measure_derivative_id',
                'product',
                'product.master_product',
            ]
        );
});

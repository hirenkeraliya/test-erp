<?php

declare(strict_types=1);

use App\Domains\PurchaseOrderFulfillment\DataObjects\PurchaseOrderFulfillmentStoreForStoreManagerData;
use App\Domains\PurchaseOrderFulfillment\Services\PurchaseOrderFulfillmentCheckRequestForInternalAppService;
use App\Models\Batch;
use App\Models\Inventory;
use App\Models\Product;
use Symfony\Component\HttpKernel\Exception\HttpException;

test('checkRequestDetails throws exception when transfer quantity is less than one', function (): void {
    $purchaseOrderFulfillmentData = [
        'store_id' => 1,
        'location_id' => 1,
        'purchase_order_id' => 1,
        'happened_at' => now()->format('Y-m-d h:i:s'),
        'notes' => '',
        'transfer_items' => [
            [
                'purchase_order_item_id' => 1,
                'product_id' => 1,
                'transfer_quantity' => 0,
                'package_type_id' => 2,
                'package_quantity' => 1,
                'package_total_quantity' => 1,
                'remarks' => 'add',
            ],
        ],
    ];
    $purchaseOrderFulfillmentData = new PurchaseOrderFulfillmentStoreForStoreManagerData(
        ...$purchaseOrderFulfillmentData
    );
    $purchaseOrderFulfillmentCheckRequestForInternalAppService = new PurchaseOrderFulfillmentCheckRequestForInternalAppService();
    $purchaseOrderFulfillmentCheckRequestForInternalAppService->checkRequestDetails(
        $purchaseOrderFulfillmentData,
        collect(),
        collect(),
        collect()
    );
})->throws(
    HttpException::class,
    'Please ensure at least one transfer quantity is requested for adding to the Delivery Order.'
);

test(
    'checkRequestDetails throws exception when transfer quantity is greater than available stock',
    function (): void {
        $purchaseOrderFulfillmentData = [
            'store_id' => 1,
            'location_id' => 1,
            'purchase_order_id' => 1,
            'happened_at' => now()->format('Y-m-d h:i:s'),
            'notes' => '',
            'transfer_items' => [
                [
                    'purchase_order_item_id' => 1,
                    'product_id' => 10,
                    'transfer_quantity' => 20,
                    'package_type_id' => 2,
                    'package_quantity' => 1,
                    'package_total_quantity' => 1,
                    'remarks' => 'add',
                ],
            ],
        ];
        $product = Product::factory()->make([
            'id' => 1,
            'unit_of_measure_id' => null,
            'season_id' => null,
            'department_id' => null,
            'color_id' => null,
            'size_id' => null,
            'style_id' => null,
            'brand_id' => 0,
            'company_id' => 1,
        ]);
        $purchaseOrderFulfillmentData = new PurchaseOrderFulfillmentStoreForStoreManagerData(
            ...$purchaseOrderFulfillmentData
        );
        $purchaseOrderFulfillmentCheckRequestForInternalAppService = new PurchaseOrderFulfillmentCheckRequestForInternalAppService();
        $purchaseOrderFulfillmentCheckRequestForInternalAppService->checkRequestDetails(
            $purchaseOrderFulfillmentData,
            collect([$product]),
            collect(),
            collect()
        );
    }
)->throws(HttpException::class);

test('checkRequestDetails throws exception when transfer quantity exceeds available stock', function (): void {
    $purchaseOrderFulfillmentData = [
        'store_id' => 1,
        'location_id' => 1,
        'purchase_order_id' => 1,
        'happened_at' => now()->format('Y-m-d h:i:s'),
        'notes' => '',
        'transfer_items' => [
            [
                'purchase_order_item_id' => 1,
                'product_id' => 1,
                'transfer_quantity' => 10,
                'package_type_id' => 2,
                'package_quantity' => 1,
                'package_total_quantity' => 1,
                'remarks' => 'add',
            ],
        ],
    ];
    $product = Product::factory()->make([
        'id' => 1,
        'unit_of_measure_id' => null,
        'season_id' => null,
        'department_id' => null,
        'color_id' => null,
        'size_id' => null,
        'style_id' => null,
        'brand_id' => 0,
        'company_id' => 1,
    ]);
    $inventory = Inventory::factory()->make([
        'id' => 1,
        'product_id' => $product->id,
        'location_id' => 1,
        'stock' => 1,
    ]);
    $purchaseOrderFulfillmentData = new PurchaseOrderFulfillmentStoreForStoreManagerData(
        ...$purchaseOrderFulfillmentData
    );
    $purchaseOrderFulfillmentCheckRequestForInternalAppService = new PurchaseOrderFulfillmentCheckRequestForInternalAppService();
    $purchaseOrderFulfillmentCheckRequestForInternalAppService->checkRequestDetails(
        $purchaseOrderFulfillmentData,
        collect([$product]),
        collect([$inventory]),
        collect()
    );
})->throws(HttpException::class);

test('checkRequestDetails throws exception when inventory is not available', function (): void {
    $purchaseOrderFulfillmentData = [
        'store_id' => 1,
        'location_id' => 1,
        'purchase_order_id' => 1,
        'happened_at' => now()->format('Y-m-d h:i:s'),
        'notes' => '',
        'transfer_items' => [
            [
                'purchase_order_item_id' => 1,
                'product_id' => 1,
                'transfer_quantity' => 10,
                'package_type_id' => 2,
                'package_quantity' => 1,
                'package_total_quantity' => 1,
                'remarks' => 'add',
            ],
        ],
    ];
    $product = Product::factory()->make([
        'id' => 1,
        'unit_of_measure_id' => null,
        'season_id' => null,
        'department_id' => null,
        'color_id' => null,
        'size_id' => null,
        'style_id' => null,
        'brand_id' => 0,
        'company_id' => 1,
    ]);
    $inventory = Inventory::factory()->make([
        'id' => 1,
        'product_id' => 80,
        'location_id' => 1,
        'stock' => 1,
    ]);
    $purchaseOrderFulfillmentData = new PurchaseOrderFulfillmentStoreForStoreManagerData(
        ...$purchaseOrderFulfillmentData
    );
    $purchaseOrderFulfillmentCheckRequestForInternalAppService = new PurchaseOrderFulfillmentCheckRequestForInternalAppService();
    $purchaseOrderFulfillmentCheckRequestForInternalAppService->checkRequestDetails(
        $purchaseOrderFulfillmentData,
        collect([$product]),
        collect([$inventory]),
        collect()
    );
})->throws(HttpException::class);

test('checkRequestDetails1 throws exception when inventory is not available', function (): void {
    $purchaseOrderFulfillmentData = [
        'store_id' => 1,
        'location_id' => 1,
        'purchase_order_id' => 1,
        'happened_at' => now()->format('Y-m-d h:i:s'),
        'notes' => '',
        'transfer_items' => [
            [
                'purchase_order_item_id' => 1,
                'product_id' => 1,
                'transfer_quantity' => 10,
                'package_type_id' => 2,
                'package_quantity' => 1,
                'package_total_quantity' => 1,
                'remarks' => 'add',
            ],
        ],
    ];
    $product = Product::factory()->make([
        'id' => 1,
        'unit_of_measure_id' => null,
        'season_id' => null,
        'department_id' => null,
        'color_id' => null,
        'size_id' => null,
        'style_id' => null,
        'brand_id' => 0,
        'company_id' => 1,
    ]);
    $inventory = Inventory::factory()->make([
        'id' => 1,
        'product_id' => $product->id,
        'location_id' => 1,
        'stock' => 1000,
    ]);
    $purchaseOrderFulfillmentData = new PurchaseOrderFulfillmentStoreForStoreManagerData(
        ...$purchaseOrderFulfillmentData
    );
    $purchaseOrderFulfillmentCheckRequestForInternalAppService = new PurchaseOrderFulfillmentCheckRequestForInternalAppService();
    $purchaseOrderFulfillmentCheckRequestForInternalAppService->checkRequestDetails(
        $purchaseOrderFulfillmentData,
        collect([$product]),
        collect([$inventory]),
        collect()
    );
})->throws(HttpException::class);

test('checkRequestDetails throws exception when batches is not available', function (): void {
    $purchaseOrderFulfillmentData = [
        'store_id' => 1,
        'location_id' => 1,
        'purchase_order_id' => 1,
        'happened_at' => now()->format('Y-m-d h:i:s'),
        'notes' => '',
        'transfer_items' => [
            [
                'purchase_order_item_id' => 1,
                'product_id' => 1,
                'transfer_quantity' => 10,
                'package_type_id' => 2,
                'package_quantity' => 5,
                'package_total_quantity' => 2,
                'remarks' => 'add',
                'batch_details' => [
                    'batch_number' => '123',
                    'quantity' => 11,
                ],
            ],
        ],
    ];
    $product = Product::factory()->make([
        'id' => 1,
        'unit_of_measure_id' => null,
        'season_id' => null,
        'department_id' => null,
        'color_id' => null,
        'size_id' => null,
        'style_id' => null,
        'brand_id' => 0,
        'company_id' => 1,
        'has_batch' => 1,
    ]);
    $inventory = Inventory::factory()->make([
        'id' => 1,
        'product_id' => $product->id,
        'location_id' => 1,
        'stock' => 1000,
    ]);
    $batch = Batch::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'product_id' => $product->id,
    ]);
    $purchaseOrderFulfillmentData = new PurchaseOrderFulfillmentStoreForStoreManagerData(
        ...$purchaseOrderFulfillmentData
    );
    $purchaseOrderFulfillmentCheckRequestForInternalAppService = new PurchaseOrderFulfillmentCheckRequestForInternalAppService();
    $purchaseOrderFulfillmentCheckRequestForInternalAppService->checkRequestDetails(
        $purchaseOrderFulfillmentData,
        collect([$product]),
        collect([$inventory]),
        collect([$batch])
    );
})->throws(HttpException::class);

<?php

declare(strict_types=1);

use App\Domains\StockTransfer\DataObjects\StockTransferData;
use App\Domains\StockTransfer\DataObjects\StockTransferShippedData;
use App\Domains\StockTransfer\Enums\ShippedTypes;
use App\Domains\StockTransfer\Enums\StatusTypes;
use App\Domains\StockTransfer\Enums\StockTransferTypes;
use App\Domains\StockTransfer\Services\StockTransferCheckRequestService;
use App\Domains\StockTransfer\StockTransferQueries;
use App\Domains\StockTransferItem\Enums\StockTransferDiscrepancyTypes;
use App\Domains\StockTransferItem\StockTransferItemQueries;
use App\Exceptions\RedirectBackWithErrorException;
use App\Models\Batch;
use App\Models\Inventory;
use App\Models\InventoryUnit;
use App\Models\MasterProduct;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\UnitOfMeasureDerivative;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

test(
    'checkAdditionalItemsRequest method throws an exception when the specified product id is not exist in records',
    function (): void {
        $stockTransferData = [
            'source_location_id' => 1,
            'destination_location_id' => 2,
            'additional_items' => [
                [
                    'product_id' => 0,
                    'quantity' => 100,
                    'received_quantity' => 100,
                ],
            ],
        ];

        $stockTransferItem = StockTransferItem::factory()->make([
            'id' => 1,
            'stock_transfer_id' => 1,
            'product_id' => 1,
            'package_type_id' => null,
            'quantity' => 10,
            'received_quantity' => null,
        ]);

        $product = commonGetProductDetails();

        $this->mock(StockTransferItemQueries::class, function ($mock) use ($stockTransferItem): void {
            $mock->shouldReceive('getProductIdsBy')
                ->once()
                ->andReturn(new EloquentCollection([$stockTransferItem]));
        });

        $stockTransferCheckRequestService = new StockTransferCheckRequestService();

        $stockTransferCheckRequestService->checkAdditionalItemsRequest(
            $stockTransferData,
            collect([$product]),
            1,
            collect([])
        );
    }
)->throws(HttpException::class);

test(
    'checkAdditionalItemsRequest method throws an exception when the specified product id already exist in records',
    function (): void {
        $stockTransferData = [
            'source_location_id' => 1,
            'destination_location_id' => 2,
            'additional_items' => [
                [
                    'product_id' => 1,
                    'quantity' => 100,
                    'received_quantity' => 100,
                ],
            ],
        ];

        $inventories = [
            new Inventory([
                'product_id' => 1,
                'location_id' => 1,
                'stock' => 1000,
            ]),
        ];

        $stockTransferItem = StockTransferItem::factory()->make([
            'id' => 1,
            'stock_transfer_id' => 1,
            'product_id' => 1,
            'package_type_id' => null,
            'quantity' => 10,
            'received_quantity' => null,
        ]);

        $product = commonGetProductDetails();

        $this->mock(StockTransferItemQueries::class, function ($mock) use ($stockTransferItem): void {
            $mock->shouldReceive('getProductIdsBy')
                ->once()
                ->andReturn(new EloquentCollection([$stockTransferItem]));
        });

        $stockTransferCheckRequestService = new StockTransferCheckRequestService();

        $stockTransferCheckRequestService->checkAdditionalItemsRequest(
            $stockTransferData,
            collect([$product]),
            1,
            collect([])
        );
    }
)->throws(HttpException::class);

test(
    'checkAdditionalItemsRequest method throws an exception when additional received stock does not match with package total quantity',
    function (): void {
        $stockTransferData = [
            'source_location_id' => 1,
            'destination_location_id' => 2,
            'additional_items' => [
                [
                    'product_id' => 1,
                    'quantity' => 100,
                    'received_quantity' => 100,
                    'package_total_quantity' => 99,
                ],
            ],
        ];

        $product = commonGetProductDetails();

        $stockTransferItem = StockTransferItem::factory()->make([
            'id' => 1,
            'stock_transfer_id' => 1,
            'product_id' => 456,
            'package_type_id' => null,
            'quantity' => 10,
            'received_quantity' => null,
        ]);

        $this->mock(StockTransferItemQueries::class, function ($mock) use ($stockTransferItem): void {
            $mock->shouldReceive('getProductIdsBy')
                ->once()
                ->andReturn(new EloquentCollection([$stockTransferItem]));
        });

        $stockTransferCheckRequestService = new StockTransferCheckRequestService();

        $stockTransferCheckRequestService->checkAdditionalItemsRequest(
            $stockTransferData,
            collect([$product]),
            1,
            collect([])
        );
    }
)->throws(HttpException::class);

test(
    'checkAdditionalItemsRequest method throws an exception when additional UOM item derivative does not match.',
    function (): void {
        $stockTransferData = [
            'source_location_id' => 1,
            'destination_location_id' => 2,
            'additional_items' => [
                [
                    'product_id' => 1,
                    'unit_of_measure_derivative_id' => 1,
                    'quantity' => 0,
                    'received_quantity' => 100,
                    'package_total_quantity' => 100,
                ],
            ],
        ];

        $product = commonGetProductDetails();

        $derivative = UnitOfMeasureDerivative::factory()->make([
            'id' => 100000,
            'unit_of_measure_id' => $product->unit_of_measure_id,
            'name' => 'Gram',
            'ratio' => 1000,
        ]);

        $stockTransferItem = StockTransferItem::factory()->make([
            'id' => 1,
            'stock_transfer_id' => 1,
            'product_id' => 456,
            'package_type_id' => null,
            'quantity' => 10,
            'received_quantity' => null,
        ]);

        $this->mock(StockTransferItemQueries::class, function ($mock) use ($stockTransferItem): void {
            $mock->shouldReceive('getProductIdsBy')
                ->once()
                ->andReturn(new EloquentCollection([$stockTransferItem]));
        });

        $stockTransferCheckRequestService = new StockTransferCheckRequestService();

        $stockTransferCheckRequestService->checkAdditionalItemsRequest(
            $stockTransferData,
            collect([$product]),
            1,
            collect([$derivative])
        );
    }
)->throws(RedirectBackWithErrorException::class);

test(
    'checkAdditionalItemsRequest method throws an exception when product UOM & additional item derivate UOM does not match when product variant is false.',
    function (): void {
        Config::set('app.product_variant', false);

        $derivative = UnitOfMeasureDerivative::factory()->make([
            'id' => 1,
            'unit_of_measure_id' => 0,
            'name' => 'Gram',
            'ratio' => 1000,
        ]);

        $stockTransferData = [
            'source_location_id' => 1,
            'destination_location_id' => 2,
            'additional_items' => [
                [
                    'product_id' => 1,
                    'unit_of_measure_derivative_id' => $derivative->id,
                    'quantity' => 0,
                    'received_quantity' => 100,
                    'package_total_quantity' => 100,
                ],
            ],
        ];

        $product = commonGetProductDetails();

        $stockTransferItem = StockTransferItem::factory()->make([
            'id' => 1,
            'stock_transfer_id' => 1,
            'product_id' => 456,
            'package_type_id' => null,
            'quantity' => 10,
            'received_quantity' => null,
        ]);

        $this->mock(StockTransferItemQueries::class, function ($mock) use ($stockTransferItem): void {
            $mock->shouldReceive('getProductIdsBy')
                ->once()
                ->andReturn(new EloquentCollection([$stockTransferItem]));
        });

        $stockTransferCheckRequestService = new StockTransferCheckRequestService();

        $stockTransferCheckRequestService->checkAdditionalItemsRequest(
            $stockTransferData,
            collect([$product]),
            1,
            collect([$derivative])
        );
    }
)->throws(RedirectBackWithErrorException::class);

test(
    'checkAdditionalItemsRequest method throws an exception when product UOM & additional item derivate UOM does not match when product variant is true.',
    function (): void {
        Config::set('app.product_variant', true);

        $derivative = UnitOfMeasureDerivative::factory()->make([
            'id' => 1,
            'unit_of_measure_id' => 0,
            'name' => 'Gram',
            'ratio' => 1000,
        ]);

        $stockTransferData = [
            'source_location_id' => 1,
            'destination_location_id' => 2,
            'additional_items' => [
                [
                    'product_id' => 1,
                    'unit_of_measure_derivative_id' => $derivative->id,
                    'quantity' => 0,
                    'received_quantity' => 100,
                    'package_total_quantity' => 100,
                ],
            ],
        ];

        $product = commonGetProductDetails();

        $masterProduct = MasterProduct::factory()->make([
            'id' => 1,
            'variant_template_id' => 1,
            'company_id' => 1,
            'unit_of_measure_id' => null,
            'has_batch' => true,
            'is_non_inventory' => false,
            'department_id' => 1,
            'brand_id' => 1,
        ]);

        $product->masterProduct = $masterProduct;

        $stockTransferItem = StockTransferItem::factory()->make([
            'id' => 1,
            'stock_transfer_id' => 1,
            'product_id' => 456,
            'package_type_id' => null,
            'quantity' => 10,
            'received_quantity' => null,
        ]);

        $this->mock(StockTransferItemQueries::class, function ($mock) use ($stockTransferItem): void {
            $mock->shouldReceive('getProductIdsBy')
                ->once()
                ->andReturn(new EloquentCollection([$stockTransferItem]));
        });

        $stockTransferCheckRequestService = new StockTransferCheckRequestService();

        $stockTransferCheckRequestService->checkAdditionalItemsRequest(
            $stockTransferData,
            collect([$product]),
            1,
            collect([$derivative])
        );
    }
)->throws(RedirectBackWithErrorException::class);

test(
    'checkAdditionalItemsRequest method throws an exception when additional item derivate UOM quantity & package quantity does not match when product variant is false.',
    function (): void {
        Config::set('app.product_variant', false);

        $product = commonGetProductDetails();
        $product->id = 2;

        $derivative = UnitOfMeasureDerivative::factory()->make([
            'id' => 1,
            'unit_of_measure_id' => $product->unit_of_measure_id,
            'name' => 'Gram',
            'ratio' => 1000,
        ]);

        $stockTransferData = [
            'source_location_id' => 1,
            'destination_location_id' => 2,
            'additional_items' => [
                [
                    'product_id' => 2,
                    'unit_of_measure_derivative_id' => $derivative->id,
                    'quantity' => 0,
                    'received_quantity' => 100,
                    'package_total_quantity' => 101,
                ],
            ],
        ];

        $stockTransferItem = StockTransferItem::factory()->make([
            'id' => 1,
            'stock_transfer_id' => 1,
            'product_id' => 1,
            'package_type_id' => null,
            'quantity' => 10,
            'received_quantity' => 10,
        ]);

        $this->mock(StockTransferItemQueries::class, function ($mock) use ($stockTransferItem): void {
            $mock->shouldReceive('getProductIdsBy')
                ->once()
                ->andReturn(new EloquentCollection([$stockTransferItem]));
        });

        $stockTransferCheckRequestService = new StockTransferCheckRequestService();

        $stockTransferCheckRequestService->checkAdditionalItemsRequest(
            $stockTransferData,
            collect([$product]),
            1,
            collect([$derivative])
        );
    }
)->throws(HttpException::class);

test(
    'checkAdditionalItemsRequest method throws an exception when additional item derivate UOM quantity & package quantity does not match when product variant is true.',
    function (): void {
        Config::set('app.product_variant', true);

        $product = commonGetProductDetails();
        $product->id = 2;

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

        $derivative = UnitOfMeasureDerivative::factory()->make([
            'id' => 1,
            'unit_of_measure_id' => $masterProduct->unit_of_measure_id,
            'name' => 'Gram',
            'ratio' => 1000,
        ]);

        $stockTransferData = [
            'source_location_id' => 1,
            'destination_location_id' => 2,
            'additional_items' => [
                [
                    'product_id' => 2,
                    'unit_of_measure_derivative_id' => $derivative->id,
                    'quantity' => 0,
                    'received_quantity' => 100,
                    'package_total_quantity' => 101,
                ],
            ],
        ];

        $stockTransferItem = StockTransferItem::factory()->make([
            'id' => 1,
            'stock_transfer_id' => 1,
            'product_id' => 1,
            'package_type_id' => null,
            'quantity' => 10,
            'received_quantity' => 10,
        ]);

        $this->mock(StockTransferItemQueries::class, function ($mock) use ($stockTransferItem): void {
            $mock->shouldReceive('getProductIdsBy')
                ->once()
                ->andReturn(new EloquentCollection([$stockTransferItem]));
        });

        $stockTransferCheckRequestService = new StockTransferCheckRequestService();

        $stockTransferCheckRequestService->checkAdditionalItemsRequest(
            $stockTransferData,
            collect([$product]),
            1,
            collect([$derivative])
        );
    }
)->throws(HttpException::class);

test(
    'checkRequestDetails method throws an exception when the specified batch number doest not exist in our records when product variant is false',
    function (): void {
        Config::set('app.product_variant', false);

        $stockTransferData = new StockTransferData(1, 2, null, null, null, null, 'test', 1, [
            [
                'product_id' => 1,
                'transfer_stock' => 100,
                'initial_transfer_quantity' => 0,
                'batch_details' => [
                    [
                        'batch_number' => 'A123',
                        'quantity' => 100,
                    ],
                ],
            ],
        ], 'transfer_order');
        [$stockTransfer, $stockTransferItem, $product, $batch, $inventory] = commonStockTransferCheckRequestSeed();
        $stockTransfer->items = collect([$stockTransferItem]);
        $inventories = [
            new Inventory([
                'product_id' => 1,
                'location_id' => 1,
                'stock' => 1000,
            ]),
        ];
        $stockTransferCheckRequestService = new StockTransferCheckRequestService();
        $stockTransferCheckRequestService->checkRequestDetails(
            $stockTransferData,
            collect([$product]),
            collect($inventories),
            collect([$batch]),
            collect([])
        );
    }
)->throws(RedirectBackWithErrorException::class);

test(
    'checkRequestDetails method throws an exception when the specified batch number doest not exist in our records when product variant is true',
    function (): void {
        Config::set('app.product_variant', true);

        $stockTransferData = new StockTransferData(1, 2, null, null, null, null, 'test', 1, [
            [
                'product_id' => 1,
                'transfer_stock' => 100,
                'initial_transfer_quantity' => 0,
                'batch_details' => [
                    [
                        'batch_number' => 'A123',
                        'quantity' => 100,
                    ],
                ],
            ],
        ], 'transfer_order');
        [$stockTransfer, $stockTransferItem, $product, $batch, $inventory] = commonStockTransferCheckRequestSeed();

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

        $stockTransfer->items = collect([$stockTransferItem]);
        $inventories = [
            new Inventory([
                'product_id' => 1,
                'location_id' => 1,
                'stock' => 1000,
            ]),
        ];
        $stockTransferCheckRequestService = new StockTransferCheckRequestService();
        $stockTransferCheckRequestService->checkRequestDetails(
            $stockTransferData,
            collect([$product]),
            collect($inventories),
            collect([$batch]),
            collect([])
        );
    }
)->throws(RedirectBackWithErrorException::class);

test(
    'checkRequestDetails method throws an exception when source & destination location is same',
    function (): void {
        $stockTransferData = new StockTransferData(1, 1, null, null, null, null, 'test', 1, []);
        $product = commonGetProductDetails();
        $stockTransferCheckRequestService = new StockTransferCheckRequestService();
        $stockTransferCheckRequestService->checkRequestDetails(
            $stockTransferData,
            collect([$product]),
            collect([]),
            collect([]),
            collect([])
        );
    }
)->throws(RedirectBackWithErrorException::class);

test(
    'checkRequestDetails method throws an exception when transfer stock more than actual stock',
    function (): void {
        $stockTransferData = new StockTransferData(1, 2, null, null, null, null, 'test', 1, [
            [
                'product_id' => 1,
                'transfer_stock' => 100,
                'initial_transfer_quantity' => 0,
            ],
        ]);
        $product = commonGetProductDetails();
        $inventories = [
            new Inventory([
                'product_id' => 1,
                'location_id' => 1,
                'stock' => 0,
            ]),
        ];
        $stockTransferCheckRequestService = new StockTransferCheckRequestService();
        $stockTransferCheckRequestService->checkRequestDetails(
            $stockTransferData,
            collect([$product]),
            collect($inventories),
            collect([]),
            collect([])
        );
    }
)->throws(RedirectBackWithErrorException::class);

test('checkRequestDetails method throws an exception when product not found', function (): void {
    $stockTransferData = new StockTransferData(1, 2, null, null, null, null, 'test', 1, [
        [
            'product_id' => 0,
            'transfer_stock' => 100,
            'initial_transfer_quantity' => 0,
        ],
    ]);
    $product = commonGetProductDetails();
    $stockTransferCheckRequestService = new StockTransferCheckRequestService();
    $stockTransferCheckRequestService->checkRequestDetails(
        $stockTransferData,
        collect([$product]),
        collect([]),
        collect([]),
        collect([])
    );
})->throws(RedirectBackWithErrorException::class);

test('checkRequestDetails method throws an exception when item derivative not found.', function (): void {
    $derivative = UnitOfMeasureDerivative::factory()->make([
        'id' => 1,
        'unit_of_measure_id' => 1,
        'name' => 'Gram',
        'ratio' => 1000,
    ]);
    $stockTransferData = new StockTransferData(1, 2, null, null, null, null, 'test', 1, [
        [
            'product_id' => 1,
            'unit_of_measure_derivative_id' => 0,
            'transfer_stock' => 100,
            'initial_transfer_quantity' => 0,
        ],
    ]);
    $product = commonGetProductDetails();
    $stockTransferCheckRequestService = new StockTransferCheckRequestService();
    $stockTransferCheckRequestService->checkRequestDetails(
        $stockTransferData,
        collect([$product]),
        collect([]),
        collect([]),
        collect([$derivative])
    );
})->throws(RedirectBackWithErrorException::class);

test(
    'checkRequestDetails method throws an exception when product UOM & item derivate UOM does not match when product variant is false.',
    function (): void {
        Config::set('app.product_variant', false);

        $derivative = UnitOfMeasureDerivative::factory()->make([
            'id' => 1,
            'unit_of_measure_id' => 0,
            'name' => 'Gram',
            'ratio' => 1000,
        ]);
        $product = commonGetProductDetails();
        $stockTransferData = new StockTransferData(1, 2, null, null, null, null, 'test', 1, [
            [
                'product_id' => 1,
                'unit_of_measure_derivative_id' => $derivative->id,
                'transfer_stock' => 100,
                'initial_transfer_quantity' => 0,
            ],
        ]);
        $stockTransferCheckRequestService = new StockTransferCheckRequestService();
        $stockTransferCheckRequestService->checkRequestDetails(
            $stockTransferData,
            collect([$product]),
            collect([]),
            collect([]),
            collect([$derivative])
        );
    }
)->throws(RedirectBackWithErrorException::class);

test(
    'checkRequestDetails method throws an exception when product UOM & item derivate UOM does not match when product variant is true.',
    function (): void {
        Config::set('app.product_variant', true);

        $derivative = UnitOfMeasureDerivative::factory()->make([
            'id' => 1,
            'unit_of_measure_id' => 0,
            'name' => 'Gram',
            'ratio' => 1000,
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

        $stockTransferData = new StockTransferData(1, 2, null, null, null, null, 'test', 1, [
            [
                'product_id' => 1,
                'unit_of_measure_derivative_id' => $derivative->id,
                'transfer_stock' => 100,
                'initial_transfer_quantity' => 0,
            ],
        ]);
        $stockTransferCheckRequestService = new StockTransferCheckRequestService();
        $stockTransferCheckRequestService->checkRequestDetails(
            $stockTransferData,
            collect([$product]),
            collect([]),
            collect([]),
            collect([$derivative])
        );
    }
)->throws(RedirectBackWithErrorException::class);

test(
    'checkRequestDetails method throws an exception when transfer UOM product stock more than actual stock when product variant is false',
    function (): void {
        Config::set('app.product_variant', false);
        $product = commonGetProductDetails();
        $derivative = UnitOfMeasureDerivative::factory()->make([
            'id' => 1,
            'unit_of_measure_id' => $product->unit_of_measure_id,
            'name' => 'Gram',
            'ratio' => 1000,
        ]);
        $stockTransferData = new StockTransferData(1, 2, null, null, null, null, 'test', 1, [
            [
                'product_id' => $product->id,
                'unit_of_measure_derivative_id' => $derivative->id,
                'transfer_stock' => 100,
                'initial_transfer_quantity' => 0,
            ],
        ]);
        $inventories = [
            new Inventory([
                'product_id' => 1,
                'location_id' => 1,
                'stock' => 0,
            ]),
        ];
        $stockTransferCheckRequestService = new StockTransferCheckRequestService();
        $stockTransferCheckRequestService->checkRequestDetails(
            $stockTransferData,
            collect([$product]),
            collect($inventories),
            collect([]),
            collect([$derivative])
        );
    }
)->throws(RedirectBackWithErrorException::class);

test(
    'checkRequestDetails method throws an exception when transfer UOM product stock more than actual stock when product variant is true',
    function (): void {
        Config::set('app.product_variant', true);
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

        $derivative = UnitOfMeasureDerivative::factory()->make([
            'id' => 1,
            'unit_of_measure_id' => $product->unit_of_measure_id,
            'name' => 'Gram',
            'ratio' => 1000,
        ]);
        $stockTransferData = new StockTransferData(1, 2, null, null, null, null, 'test', 1, [
            [
                'product_id' => $product->id,
                'unit_of_measure_derivative_id' => $derivative->id,
                'transfer_stock' => 100,
                'initial_transfer_quantity' => 0,
            ],
        ]);
        $inventories = [
            new Inventory([
                'product_id' => 1,
                'location_id' => 1,
                'stock' => 0,
            ]),
        ];
        $stockTransferCheckRequestService = new StockTransferCheckRequestService();
        $stockTransferCheckRequestService->checkRequestDetails(
            $stockTransferData,
            collect([$product]),
            collect($inventories),
            collect([]),
            collect([$derivative])
        );
    }
)->throws(RedirectBackWithErrorException::class);

test(
    'checkRequestDetails method throws an exception when transfer UOM product stock does not match with package quantity when product variant is false',
    function (): void {
        Config::set('app.product_variant', false);
        $product = commonGetProductDetails();
        $derivative = UnitOfMeasureDerivative::factory()->make([
            'id' => 1,
            'unit_of_measure_id' => $product->unit_of_measure_id,
            'name' => 'Gram',
            'ratio' => 1000,
        ]);
        $stockTransferData = new StockTransferData(1, 2, null, null, null, null, 'test', 1, [
            [
                'product_id' => $product->id,
                'package_total_quantity' => 101,
                'unit_of_measure_derivative_id' => $derivative->id,
                'transfer_stock' => 100,
                'initial_transfer_quantity' => 0,
            ],
        ]);
        $inventories = [
            new Inventory([
                'product_id' => 1,
                'location_id' => 1,
                'stock' => 100,
            ]),
        ];
        $stockTransferCheckRequestService = new StockTransferCheckRequestService();
        $stockTransferCheckRequestService->checkRequestDetails(
            $stockTransferData,
            collect([$product]),
            collect($inventories),
            collect([]),
            collect([$derivative])
        );
    }
)->throws(RedirectBackWithErrorException::class);

test(
    'checkRequestDetails method throws an exception when transfer UOM product stock does not match with package quantity when product variant is true',
    function (): void {
        Config::set('app.product_variant', true);
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

        $derivative = UnitOfMeasureDerivative::factory()->make([
            'id' => 1,
            'unit_of_measure_id' => $product->unit_of_measure_id,
            'name' => 'Gram',
            'ratio' => 1000,
        ]);
        $stockTransferData = new StockTransferData(1, 2, null, null, null, null, 'test', 1, [
            [
                'product_id' => $product->id,
                'package_total_quantity' => 101,
                'unit_of_measure_derivative_id' => $derivative->id,
                'transfer_stock' => 100,
                'initial_transfer_quantity' => 0,
            ],
        ]);
        $inventories = [
            new Inventory([
                'product_id' => 1,
                'location_id' => 1,
                'stock' => 100,
            ]),
        ];
        $stockTransferCheckRequestService = new StockTransferCheckRequestService();
        $stockTransferCheckRequestService->checkRequestDetails(
            $stockTransferData,
            collect([$product]),
            collect($inventories),
            collect([]),
            collect([$derivative])
        );
    }
)->throws(RedirectBackWithErrorException::class);

test('checkRequestDetails method throws an exception when inventory not found', function (): void {
    $stockTransferData = new StockTransferData(1, 2, null, null, null, null, 'test', 1, [
        [
            'product_id' => 1,
            'transfer_stock' => 100,
            'initial_transfer_quantity' => 0,
        ],
    ]);
    $product = commonGetProductDetails();
    $inventories = [
        new Inventory([
            'product_id' => 2,
            'location_id' => 1,
            'stock' => 0,
        ]),
    ];
    $stockTransferCheckRequestService = new StockTransferCheckRequestService();
    $stockTransferCheckRequestService->checkRequestDetails(
        $stockTransferData,
        collect([$product]),
        collect($inventories),
        collect([]),
        collect([])
    );
})->throws(RedirectBackWithErrorException::class);

test(
    'checkRequestDetails method throws an exception when edit transfer stock more than actual stock',
    function (): void {
        $stockTransferData = new StockTransferData(1, 2, null, null, null, null, 'test', 1, [
            [
                'product_id' => 1,
                'transfer_stock' => 101,
                'initial_transfer_quantity' => 50,
            ],
        ]);
        $product = commonGetProductDetails();
        $inventories = [
            new Inventory([
                'product_id' => 1,
                'location_id' => 1,
                'stock' => 50,
            ]),
        ];
        $stockTransferCheckRequestService = new StockTransferCheckRequestService();
        $stockTransferCheckRequestService->checkRequestDetails(
            $stockTransferData,
            collect([$product]),
            collect($inventories),
            collect([]),
            collect([])
        );
    }
)->throws(RedirectBackWithErrorException::class);

test(
    'checkRequestDetails method throws an exception when transfer stock does not match with package total quantity',
    function (): void {
        $stockTransferData = new StockTransferData(1, 2, null, null, null, null, 'test', 1, [
            [
                'product_id' => 1,
                'transfer_stock' => 100,
                'initial_transfer_quantity' => 0,
                'package_total_quantity' => 99,
            ],
        ]);
        $product = commonGetProductDetails();
        $inventories = [
            new Inventory([
                'product_id' => 1,
                'location_id' => 1,
                'stock' => 1000,
            ]),
        ];
        $stockTransferCheckRequestService = new StockTransferCheckRequestService();
        $stockTransferCheckRequestService->checkRequestDetails(
            $stockTransferData,
            collect([$product]),
            collect($inventories),
            collect([]),
            collect([])
        );
    }
)->throws(RedirectBackWithErrorException::class);

test(
    'checkRequestDetails method throws an exception when product has batch but does not provided batch details when product variant is false.',
    function (): void {
        Config::set('app.product_variant', false);

        $stockTransferData = new StockTransferData(1, 2, null, null, null, null, 'test', 1, [
            [
                'product_id' => 1,
                'transfer_stock' => 100,
                'initial_transfer_quantity' => 0,
                'package_total_quantity' => 100,
            ],
        ], 'transfer_order');
        $product = commonGetProductDetails();
        $inventories = [
            new Inventory([
                'product_id' => 1,
                'location_id' => 1,
                'stock' => 1000,
            ]),
        ];
        $stockTransferCheckRequestService = new StockTransferCheckRequestService();
        $stockTransferCheckRequestService->checkRequestDetails(
            $stockTransferData,
            collect([$product]),
            collect($inventories),
            collect([]),
            collect([])
        );
    }
)->throws(RedirectBackWithErrorException::class);

test(
    'checkRequestDetails method throws an exception when product has batch but does not provided batch details when product variant is true.',
    function (): void {
        Config::set('app.product_variant', true);

        $stockTransferData = new StockTransferData(1, 2, null, null, null, null, 'test', 1, [
            [
                'product_id' => 1,
                'transfer_stock' => 100,
                'initial_transfer_quantity' => 0,
                'package_total_quantity' => 100,
            ],
        ], 'transfer_order');
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

        $inventories = [
            new Inventory([
                'product_id' => 1,
                'location_id' => 1,
                'stock' => 1000,
            ]),
        ];
        $stockTransferCheckRequestService = new StockTransferCheckRequestService();
        $stockTransferCheckRequestService->checkRequestDetails(
            $stockTransferData,
            collect([$product]),
            collect($inventories),
            collect([]),
            collect([])
        );
    }
)->throws(RedirectBackWithErrorException::class);

test(
    'checkRequestDetails method throws an exception when product has batch but does not provided batch number when product variant is false.',
    function (): void {
        Config::set('app.product_variant', false);

        $stockTransferData = new StockTransferData(1, 2, null, null, null, null, 'test', 1, [
            [
                'product_id' => 1,
                'transfer_stock' => 100,
                'initial_transfer_quantity' => 0,
                'package_total_quantity' => 100,
                'batch_details' => [
                    [
                        'batch_number' => null,
                        'quantity' => 100,
                    ],
                ],
            ],
        ], 'transfer_order');
        $product = commonGetProductDetails();
        $inventories = [
            new Inventory([
                'product_id' => 1,
                'location_id' => 1,
                'stock' => 1000,
            ]),
        ];
        $stockTransferCheckRequestService = new StockTransferCheckRequestService();
        $stockTransferCheckRequestService->checkRequestDetails(
            $stockTransferData,
            collect([$product]),
            collect($inventories),
            collect([]),
            collect([])
        );
    }
)->throws(RedirectBackWithErrorException::class);

test(
    'checkRequestDetails method throws an exception when product has batch but does not provided batch number when product variant is true.',
    function (): void {
        Config::set('app.product_variant', true);

        $stockTransferData = new StockTransferData(1, 2, null, null, null, null, 'test', 1, [
            [
                'product_id' => 1,
                'transfer_stock' => 100,
                'initial_transfer_quantity' => 0,
                'package_total_quantity' => 100,
                'batch_details' => [
                    [
                        'batch_number' => null,
                        'quantity' => 100,
                    ],
                ],
            ],
        ], 'transfer_order');
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

        $inventories = [
            new Inventory([
                'product_id' => 1,
                'location_id' => 1,
                'stock' => 1000,
            ]),
        ];
        $stockTransferCheckRequestService = new StockTransferCheckRequestService();
        $stockTransferCheckRequestService->checkRequestDetails(
            $stockTransferData,
            collect([$product]),
            collect($inventories),
            collect([]),
            collect([])
        );
    }
)->throws(RedirectBackWithErrorException::class);

test(
    'checkRequestDetails method throws an exception when transfer stock does not match with batch number quantity when product variant is false',
    function (): void {
        Config::set('app.product_variant', false);

        $stockTransferData = new StockTransferData(1, 2, null, null, null, null, 'test', 1, [
            [
                'product_id' => 1,
                'transfer_stock' => 100,
                'initial_transfer_quantity' => 0,
                'package_total_quantity' => 100,
                'batch_details' => [
                    [
                        'batch_number' => 'A12345',
                        'quantity' => 101,
                    ],
                ],
            ],
        ], 'transfer_order');
        $product = commonGetProductDetails();
        $inventories = [
            new Inventory([
                'product_id' => 1,
                'location_id' => 1,
                'stock' => 1000,
            ]),
        ];
        $stockTransferCheckRequestService = new StockTransferCheckRequestService();
        $stockTransferCheckRequestService->checkRequestDetails(
            $stockTransferData,
            collect([$product]),
            collect($inventories),
            collect([]),
            collect([])
        );
    }
)->throws(RedirectBackWithErrorException::class);

test(
    'checkRequestDetails method throws an exception when transfer stock does not match with batch number quantity when product variant is true',
    function (): void {
        Config::set('app.product_variant', true);

        $stockTransferData = new StockTransferData(1, 2, null, null, null, null, 'test', 1, [
            [
                'product_id' => 1,
                'transfer_stock' => 100,
                'initial_transfer_quantity' => 0,
                'package_total_quantity' => 100,
                'batch_details' => [
                    [
                        'batch_number' => 'A12345',
                        'quantity' => 101,
                    ],
                ],
            ],
        ], 'transfer_order');
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

        $inventories = [
            new Inventory([
                'product_id' => 1,
                'location_id' => 1,
                'stock' => 1000,
            ]),
        ];
        $stockTransferCheckRequestService = new StockTransferCheckRequestService();
        $stockTransferCheckRequestService->checkRequestDetails(
            $stockTransferData,
            collect([$product]),
            collect($inventories),
            collect([]),
            collect([])
        );
    }
)->throws(RedirectBackWithErrorException::class);

test(
    'checkRequestDetails method throws an exception when transfer batch quantity more than available batch unit quantity when product variant is false.',
    function (): void {
        Config::set('app.product_variant', false);

        $batch = Batch::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'product_id' => 1,
            'number' => 'A12345',
        ]);
        $stockTransferData = new StockTransferData(1, 2, null, null, null, null, 'test', 1, [
            [
                'product_id' => $batch->product_id,
                'transfer_stock' => 100,
                'initial_transfer_quantity' => 0,
                'package_total_quantity' => 100,
                'batch_details' => [
                    [
                        'batch_number' => $batch->number,
                        'quantity' => 100,
                    ],
                ],
            ],
        ], 'transfer_order');
        $product = commonGetProductDetails();
        $inventory = Inventory::factory()->make([
            'id' => 1,
            'product_id' => 1,
            'location_id' => 1,
            'stock' => 1000,
        ]);
        $inventoryUnit = InventoryUnit::factory()->make([
            'inventory_id' => $inventory->id,
            'purchase_amount_id' => 1,
            'batch_id' => $batch->id,
            'quantity' => 1,
        ]);
        $inventory->inventoryUnits = collect([$inventoryUnit]);
        $stockTransferCheckRequestService = new StockTransferCheckRequestService();
        $stockTransferCheckRequestService->checkRequestDetails(
            $stockTransferData,
            collect([$product]),
            collect([$inventory]),
            collect([$batch]),
            collect([])
        );
    }
)->throws(RedirectBackWithErrorException::class);

test(
    'checkRequestDetails method throws an exception when transfer batch quantity more than available batch unit quantity when product variant is true.',
    function (): void {
        Config::set('app.product_variant', true);
        $batch = Batch::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'product_id' => 1,
            'number' => 'A12345',
        ]);
        $stockTransferData = new StockTransferData(1, 2, null, null, null, null, 'test', 1, [
            [
                'product_id' => $batch->product_id,
                'transfer_stock' => 100,
                'initial_transfer_quantity' => 0,
                'package_total_quantity' => 100,
                'batch_details' => [
                    [
                        'batch_number' => $batch->number,
                        'quantity' => 100,
                    ],
                ],
            ],
        ], 'transfer_order');
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

        $inventory = Inventory::factory()->make([
            'id' => 1,
            'product_id' => 1,
            'location_id' => 1,
            'stock' => 1000,
        ]);
        $inventoryUnit = InventoryUnit::factory()->make([
            'inventory_id' => $inventory->id,
            'purchase_amount_id' => 1,
            'batch_id' => $batch->id,
            'quantity' => 1,
        ]);
        $inventory->inventoryUnits = collect([$inventoryUnit]);
        $stockTransferCheckRequestService = new StockTransferCheckRequestService();
        $stockTransferCheckRequestService->checkRequestDetails(
            $stockTransferData,
            collect([$product]),
            collect([$inventory]),
            collect([$batch]),
            collect([])
        );
    }
)->throws(RedirectBackWithErrorException::class);

test(
    'checkClosingDiscrepancyRequestBatchDetails method throws an exception when discrepancy quantity batch details not provided when product variant is false.',
    function (): void {
        Config::set('app.product_variant', false);

        $validatedData = [
            'stock_transfer_items' => [[
                'id' => 1,
                'batch_details' => [],
            ]],
        ];
        [$stockTransfer, $stockTransferItem, $product, $batch, $inventory] = commonStockTransferCheckRequestSeed();
        $stockTransferItem->received_quantity = 500;
        $stockTransfer->items = collect([$stockTransferItem]);
        $companyId = 1;
        setCompanyIdInSession($companyId);
        $stockTransferCheckRequestService = new StockTransferCheckRequestService();
        $stockTransferCheckRequestService->checkClosingDiscrepancyRequestBatchDetails(
            $stockTransfer,
            $validatedData,
            collect([$product]),
            collect([$inventory]),
            collect([$batch]),
        );
    }
)->throws(RedirectBackWithErrorException::class);

test(
    'checkClosingDiscrepancyRequestBatchDetails method throws an exception when discrepancy quantity batch details not provided when product variant is true.',
    function (): void {
        Config::set('app.product_variant', true);

        $validatedData = [
            'stock_transfer_items' => [[
                'id' => 1,
                'batch_details' => [],
            ]],
        ];
        [$stockTransfer, $stockTransferItem, $product, $batch, $inventory] = commonStockTransferCheckRequestSeed();

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

        $stockTransferItem->received_quantity = 500;
        $stockTransfer->items = collect([$stockTransferItem]);
        $companyId = 1;
        setCompanyIdInSession($companyId);
        $stockTransferCheckRequestService = new StockTransferCheckRequestService();
        $stockTransferCheckRequestService->checkClosingDiscrepancyRequestBatchDetails(
            $stockTransfer,
            $validatedData,
            collect([$product]),
            collect([$inventory]),
            collect([$batch]),
        );
    }
)->throws(RedirectBackWithErrorException::class);

test(
    'checkClosingDiscrepancyRequestBatchDetails method throws an exception when discrepancy item batch details does not match with actual product batches when product variant is false.',
    function (): void {
        Config::set('app.product_variant', false);
        $validatedData = [
            'stock_transfer_items' => [[
                'id' => 1,
                'batch_details' => [
                    [
                        'batch_number' => 'A',
                        'quantity' => 5,
                    ],
                ],
            ]],
        ];
        [$stockTransfer, $stockTransferItem, $product, $batch, $inventory] = commonStockTransferCheckRequestSeed();
        $stockTransferItem->received_quantity = 500;
        $stockTransfer->items = collect([$stockTransferItem]);
        $companyId = 1;
        setCompanyIdInSession($companyId);
        $stockTransferCheckRequestService = new StockTransferCheckRequestService();
        $stockTransferCheckRequestService->checkClosingDiscrepancyRequestBatchDetails(
            $stockTransfer,
            $validatedData,
            collect([$product]),
            collect([$inventory]),
            collect([$batch]),
        );
    }
)->throws(RedirectBackWithErrorException::class);

test(
    'checkClosingDiscrepancyRequestBatchDetails method throws an exception when discrepancy item batch details does not match with actual product batches when product variant is true.',
    function (): void {
        Config::set('app.product_variant', true);
        $validatedData = [
            'stock_transfer_items' => [[
                'id' => 1,
                'batch_details' => [
                    [
                        'batch_number' => 'A',
                        'quantity' => 5,
                    ],
                ],
            ]],
        ];
        [$stockTransfer, $stockTransferItem, $product, $batch, $inventory] = commonStockTransferCheckRequestSeed();

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

        $stockTransferItem->received_quantity = 500;
        $stockTransfer->items = collect([$stockTransferItem]);
        $companyId = 1;
        setCompanyIdInSession($companyId);
        $stockTransferCheckRequestService = new StockTransferCheckRequestService();
        $stockTransferCheckRequestService->checkClosingDiscrepancyRequestBatchDetails(
            $stockTransfer,
            $validatedData,
            collect([$product]),
            collect([$inventory]),
            collect([$batch]),
        );
    }
)->throws(RedirectBackWithErrorException::class);

test(
    'checkClosingDiscrepancyRequestBatchDetails method throws an exception when additional item does not have enough inventory when product variant is false',
    function (): void {
        Config::set('app.product_variant', false);

        $validatedData = [
            'stock_transfer_items' => [[
                'id' => 1,
                'batch_details' => [],
            ]],
        ];
        [$stockTransfer, $stockTransferItem, $product, $batch, $inventory] = commonStockTransferCheckRequestSeed();
        $product->has_batch = false;
        $stockTransferItem->is_extra_item = true;
        $stockTransferItem->received_quantity = 500;
        $stockTransfer->items = collect([$stockTransferItem]);
        $companyId = 1;
        setCompanyIdInSession($companyId);
        $stockTransferCheckRequestService = new StockTransferCheckRequestService();
        $stockTransferCheckRequestService->checkClosingDiscrepancyRequestBatchDetails(
            $stockTransfer,
            $validatedData,
            collect([$product]),
            collect([$inventory]),
            collect([$batch]),
        );
    }
)->throws(RedirectBackWithErrorException::class);

test(
    'checkClosingDiscrepancyRequestBatchDetails method throws an exception when additional item does not have enough inventory when product variant is true',
    function (): void {
        Config::set('app.product_variant', true);

        $validatedData = [
            'stock_transfer_items' => [[
                'id' => 1,
                'batch_details' => [],
            ]],
        ];
        [$stockTransfer, $stockTransferItem, $product, $batch, $inventory] = commonStockTransferCheckRequestSeed();

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

        $product->has_batch = false;
        $stockTransferItem->is_extra_item = true;
        $stockTransferItem->received_quantity = 500;
        $stockTransfer->items = collect([$stockTransferItem]);
        $companyId = 1;
        setCompanyIdInSession($companyId);
        $stockTransferCheckRequestService = new StockTransferCheckRequestService();
        $stockTransferCheckRequestService->checkClosingDiscrepancyRequestBatchDetails(
            $stockTransfer,
            $validatedData,
            collect([$product]),
            collect([$inventory]),
            collect([$batch]),
        );
    }
)->throws(RedirectBackWithErrorException::class);

test(
    'checkClosingDiscrepancyRequestBatchDetails method throws an exception when item quantity does not have enough inventory when product variant is false',
    function (): void {
        Config::set('app.product_variant', false);
        $validatedData = [
            'stock_transfer_items' => [[
                'id' => 1,
                'batch_details' => [],
            ]],
        ];
        [$stockTransfer, $stockTransferItem, $product, $batch, $inventory] = commonStockTransferCheckRequestSeed();
        $product->has_batch = false;
        $stockTransferItem->quantity = 5;
        $stockTransferItem->received_quantity = 10;
        $inventory->stock = 1;
        $stockTransfer->items = collect([$stockTransferItem]);
        $companyId = 1;
        setCompanyIdInSession($companyId);
        $stockTransferCheckRequestService = new StockTransferCheckRequestService();
        $stockTransferCheckRequestService->checkClosingDiscrepancyRequestBatchDetails(
            $stockTransfer,
            $validatedData,
            collect([$product]),
            collect([$inventory]),
            collect([$batch]),
        );
    }
)->throws(RedirectBackWithErrorException::class);

test(
    'checkClosingDiscrepancyRequestBatchDetails method throws an exception when item quantity does not have enough inventory when product variant is true',
    function (): void {
        Config::set('app.product_variant', true);

        $validatedData = [
            'stock_transfer_items' => [[
                'id' => 1,
                'batch_details' => [],
            ]],
        ];
        [$stockTransfer, $stockTransferItem, $product, $batch, $inventory] = commonStockTransferCheckRequestSeed();

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

        $product->has_batch = false;
        $stockTransferItem->quantity = 5;
        $stockTransferItem->received_quantity = 10;
        $inventory->stock = 1;
        $stockTransfer->items = collect([$stockTransferItem]);
        $companyId = 1;
        setCompanyIdInSession($companyId);
        $stockTransferCheckRequestService = new StockTransferCheckRequestService();
        $stockTransferCheckRequestService->checkClosingDiscrepancyRequestBatchDetails(
            $stockTransfer,
            $validatedData,
            collect([$product]),
            collect([$inventory]),
            collect([$batch]),
        );
    }
)->throws(RedirectBackWithErrorException::class);

test(
    'checkClosingDiscrepancyRequestBatchDetails method throws an exception when batch quantity and exceed quantity doest not match when product variant is false',
    function (): void {
        Config::set('app.product_variant', false);

        $validatedData = [
            'stock_transfer_items' => [[
                'id' => 1,
                'batch_details' => [
                    [
                        'batch_number' => '12345678',
                        'quantity' => 5,
                    ],
                ],
            ]],
        ];
        [$stockTransfer, $stockTransferItem, $product, $batch, $inventory] = commonStockTransferCheckRequestSeed();
        $stockTransferItem->received_quantity = 100;
        $stockTransferItem->discrepancy_type = StockTransferDiscrepancyTypes::POSITIVE->value;
        $batch->number = '12345678';
        $stockTransfer->items = collect([$stockTransferItem]);
        $companyId = 1;
        setCompanyIdInSession($companyId);
        $stockTransferCheckRequestService = new StockTransferCheckRequestService();
        $stockTransferCheckRequestService->checkClosingDiscrepancyRequestBatchDetails(
            $stockTransfer,
            $validatedData,
            collect([$product]),
            collect([$inventory]),
            collect([$batch]),
        );
    }
)->throws(RedirectBackWithErrorException::class);

test(
    'checkClosingDiscrepancyRequestBatchDetails method throws an exception when batch quantity and exceed quantity doest not match when product variant is true',
    function (): void {
        $validatedData = [
            'stock_transfer_items' => [[
                'id' => 1,
                'batch_details' => [
                    [
                        'batch_number' => '12345678',
                        'quantity' => 5,
                    ],
                ],
            ]],
        ];
        [$stockTransfer, $stockTransferItem, $product, $batch, $inventory] = commonStockTransferCheckRequestSeed();

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

        $stockTransferItem->received_quantity = 100;
        $stockTransferItem->discrepancy_type = StockTransferDiscrepancyTypes::POSITIVE->value;
        $batch->number = '12345678';
        $stockTransfer->items = collect([$stockTransferItem]);
        $companyId = 1;
        setCompanyIdInSession($companyId);
        $stockTransferCheckRequestService = new StockTransferCheckRequestService();
        $stockTransferCheckRequestService->checkClosingDiscrepancyRequestBatchDetails(
            $stockTransfer,
            $validatedData,
            collect([$product]),
            collect([$inventory]),
            collect([$batch]),
        );
    }
)->throws(RedirectBackWithErrorException::class);

test(
    'checkClosingDiscrepancyRequestBatchDetails method throws an exception when exceed batch number quantity does not have enough inventory when product variant is false',
    function (): void {
        Config::set('app.product_variant', false);

        $validatedData = [
            'stock_transfer_items' => [[
                'id' => 1,
                'batch_details' => [
                    [
                        'batch_number' => '12345678',
                        'quantity' => 90,
                    ],
                ],
            ]],
        ];
        [$stockTransfer, $stockTransferItem, $product, $batch, $inventory] = commonStockTransferCheckRequestSeed();
        $stockTransferItem->received_quantity = 100;
        $stockTransferItem->discrepancy_type = StockTransferDiscrepancyTypes::POSITIVE->value;
        $batch->number = '12345678';
        $stockTransfer->items = collect([$stockTransferItem]);
        $inventoryUnit = InventoryUnit::factory()->make([
            'id' => 1,
            'inventory_id' => $inventory->id,
            'purchase_amount_id' => 1,
            'batch_id' => $batch->id,
            'quantity' => 1,
        ]);
        $companyId = 1;
        setCompanyIdInSession($companyId);
        $inventory->inventoryUnits = collect([$inventoryUnit]);
        $stockTransferCheckRequestService = new StockTransferCheckRequestService();
        $stockTransferCheckRequestService->checkClosingDiscrepancyRequestBatchDetails(
            $stockTransfer,
            $validatedData,
            collect([$product]),
            collect([$inventory]),
            collect([$batch]),
        );
    }
)->throws(RedirectBackWithErrorException::class);

test(
    'checkClosingDiscrepancyRequestBatchDetails method throws an exception when exceed batch number quantity does not have enough inventory when product variant is true',
    function (): void {
        Config::set('app.product_variant', true);
        $validatedData = [
            'stock_transfer_items' => [[
                'id' => 1,
                'batch_details' => [
                    [
                        'batch_number' => '12345678',
                        'quantity' => 90,
                    ],
                ],
            ]],
        ];
        [$stockTransfer, $stockTransferItem, $product, $batch, $inventory] = commonStockTransferCheckRequestSeed();

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

        $stockTransferItem->received_quantity = 100;
        $stockTransferItem->discrepancy_type = StockTransferDiscrepancyTypes::POSITIVE->value;
        $batch->number = '12345678';
        $stockTransfer->items = collect([$stockTransferItem]);
        $inventoryUnit = InventoryUnit::factory()->make([
            'id' => 1,
            'inventory_id' => $inventory->id,
            'purchase_amount_id' => 1,
            'batch_id' => $batch->id,
            'quantity' => 1,
        ]);
        $companyId = 1;
        setCompanyIdInSession($companyId);
        $inventory->inventoryUnits = collect([$inventoryUnit]);
        $stockTransferCheckRequestService = new StockTransferCheckRequestService();
        $stockTransferCheckRequestService->checkClosingDiscrepancyRequestBatchDetails(
            $stockTransfer,
            $validatedData,
            collect([$product]),
            collect([$inventory]),
            collect([$batch]),
        );
    }
)->throws(RedirectBackWithErrorException::class);

test(
    'checkClosingDiscrepancyRequestBatchDetails method throws an exception when shortage batch number quantity does not match when product variant is false',
    function (): void {
        Config::set('app.product_variant', false);
        $validatedData = [
            'stock_transfer_items' => [[
                'id' => 1,
                'batch_details' => [
                    [
                        'batch_number' => '12345678',
                        'quantity' => 2,
                    ],
                ],
            ]],
        ];
        [$stockTransfer, $stockTransferItem, $product, $batch, $inventory] = commonStockTransferCheckRequestSeed();
        $batch->number = '12345678';
        $stockTransferItem->received_quantity = 1;
        $stockTransfer->items = collect([$stockTransferItem]);
        $companyId = 1;
        setCompanyIdInSession($companyId);
        $stockTransferCheckRequestService = new StockTransferCheckRequestService();
        $stockTransferCheckRequestService->checkClosingDiscrepancyRequestBatchDetails(
            $stockTransfer,
            $validatedData,
            collect([$product]),
            collect([$inventory]),
            collect([$batch]),
        );
    }
)->throws(RedirectBackWithErrorException::class);

test(
    'checkClosingDiscrepancyRequestBatchDetails method throws an exception when shortage batch number quantity does not match when product variant is true',
    function (): void {
        Config::set('app.product_variant', true);
        $validatedData = [
            'stock_transfer_items' => [[
                'id' => 1,
                'batch_details' => [
                    [
                        'batch_number' => '12345678',
                        'quantity' => 2,
                    ],
                ],
            ]],
        ];
        [$stockTransfer, $stockTransferItem, $product, $batch, $inventory] = commonStockTransferCheckRequestSeed();

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

        $batch->number = '12345678';
        $stockTransferItem->received_quantity = 1;
        $stockTransfer->items = collect([$stockTransferItem]);
        $companyId = 1;
        setCompanyIdInSession($companyId);
        $stockTransferCheckRequestService = new StockTransferCheckRequestService();
        $stockTransferCheckRequestService->checkClosingDiscrepancyRequestBatchDetails(
            $stockTransfer,
            $validatedData,
            collect([$product]),
            collect([$inventory]),
            collect([$batch]),
        );
    }
)->throws(RedirectBackWithErrorException::class);

test(
    'checkClosingDiscrepancyRequestBatchDetails method throws an exception when shortage batch number quantity does not have enough inventory when product variant is false',
    function (): void {
        Config::set('app.product_variant', false);

        $validatedData = [
            'stock_transfer_items' => [[
                'id' => 1,
                'batch_details' => [
                    [
                        'batch_number' => '12345678',
                        'quantity' => 1,
                    ],
                ],
            ]],
        ];
        [$stockTransfer, $stockTransferItem, $product, $batch, $inventory] = commonStockTransferCheckRequestSeed();
        $batch->number = '12345678';
        $stockTransferItem->received_quantity = '1';
        $stockTransfer->items = collect([$stockTransferItem]);
        $inventoryUnit = InventoryUnit::factory()->make([
            'id' => 1,
            'inventory_id' => $inventory->id,
            'purchase_amount_id' => 1,
            'batch_id' => $batch->id,
            'quantity' => 0,
        ]);
        $inventory->stock = 0;
        $inventory->inventoryUnits = collect([$inventoryUnit]);
        $companyId = 1;
        setCompanyIdInSession($companyId);
        $stockTransferCheckRequestService = new StockTransferCheckRequestService();
        $stockTransferCheckRequestService->checkClosingDiscrepancyRequestBatchDetails(
            $stockTransfer,
            $validatedData,
            collect([$product]),
            collect([$inventory]),
            collect([$batch]),
        );
    }
)->throws(RedirectBackWithErrorException::class);

test(
    'checkClosingDiscrepancyRequestBatchDetails method throws an exception when shortage batch number quantity does not have enough inventory when product variant is true',
    function (): void {
        Config::set('app.product_variant', true);

        $validatedData = [
            'stock_transfer_items' => [[
                'id' => 1,
                'batch_details' => [
                    [
                        'batch_number' => '12345678',
                        'quantity' => 1,
                    ],
                ],
            ]],
        ];
        [$stockTransfer, $stockTransferItem, $product, $batch, $inventory] = commonStockTransferCheckRequestSeed();

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

        $batch->number = '12345678';
        $stockTransferItem->received_quantity = '1';
        $stockTransfer->items = collect([$stockTransferItem]);
        $inventoryUnit = InventoryUnit::factory()->make([
            'id' => 1,
            'inventory_id' => $inventory->id,
            'purchase_amount_id' => 1,
            'batch_id' => $batch->id,
            'quantity' => 0,
        ]);
        $inventory->stock = 0;
        $inventory->inventoryUnits = collect([$inventoryUnit]);
        $companyId = 1;
        setCompanyIdInSession($companyId);
        $stockTransferCheckRequestService = new StockTransferCheckRequestService();
        $stockTransferCheckRequestService->checkClosingDiscrepancyRequestBatchDetails(
            $stockTransfer,
            $validatedData,
            collect([$product]),
            collect([$inventory]),
            collect([$batch]),
        );
    }
)->throws(RedirectBackWithErrorException::class);

test(
    'checkClosingDiscrepancyRequestBatchDetails method throws an exception when extra item batch quantity and exceed quantity doest not match when product variant is false',
    function (): void {
        Config::set('app.product_variant', false);
        $validatedData = [
            'stock_transfer_items' => [[
                'id' => 1,
                'batch_details' => [
                    [
                        'batch_number' => '12345678',
                        'quantity' => 5,
                    ],
                ],
            ]],
        ];
        [$stockTransfer, $stockTransferItem, $product, $batch, $inventory] = commonStockTransferCheckRequestSeed();
        $stockTransferItem->quantity = 0;
        $stockTransferItem->received_quantity = 10;
        $stockTransferItem->is_extra_item = true;
        $batch->number = '12345678';
        $stockTransfer->items = collect([$stockTransferItem]);
        $companyId = 1;
        setCompanyIdInSession($companyId);
        $stockTransferCheckRequestService = new StockTransferCheckRequestService();
        $stockTransferCheckRequestService->checkClosingDiscrepancyRequestBatchDetails(
            $stockTransfer,
            $validatedData,
            collect([$product]),
            collect([$inventory]),
            collect([$batch]),
        );
    }
)->throws(RedirectBackWithErrorException::class);

test(
    'checkClosingDiscrepancyRequestBatchDetails method throws an exception when extra item batch quantity and exceed quantity doest not match when product variant is true',
    function (): void {
        Config::set('app.product_variant', true);
        $validatedData = [
            'stock_transfer_items' => [[
                'id' => 1,
                'batch_details' => [
                    [
                        'batch_number' => '12345678',
                        'quantity' => 5,
                    ],
                ],
            ]],
        ];
        [$stockTransfer, $stockTransferItem, $product, $batch, $inventory] = commonStockTransferCheckRequestSeed();

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

        $stockTransferItem->quantity = 0;
        $stockTransferItem->received_quantity = 10;
        $stockTransferItem->is_extra_item = true;
        $batch->number = '12345678';
        $stockTransfer->items = collect([$stockTransferItem]);
        $companyId = 1;
        setCompanyIdInSession($companyId);
        $stockTransferCheckRequestService = new StockTransferCheckRequestService();
        $stockTransferCheckRequestService->checkClosingDiscrepancyRequestBatchDetails(
            $stockTransfer,
            $validatedData,
            collect([$product]),
            collect([$inventory]),
            collect([$batch]),
        );
    }
)->throws(RedirectBackWithErrorException::class);

test(
    'checkClosingDiscrepancyRequestBatchDetails method throws an exception when extra item batch number quantity does not have enough inventory when product variant is false',
    function (): void {
        Config::set('app.product_variant', false);

        $validatedData = [
            'stock_transfer_items' => [[
                'id' => 1,
                'batch_details' => [
                    [
                        'batch_number' => '12345678',
                        'quantity' => 10,
                    ],
                ],
            ]],
        ];
        [$stockTransfer, $stockTransferItem, $product, $batch, $inventory] = commonStockTransferCheckRequestSeed();
        $stockTransferItem->quantity = 0;
        $stockTransferItem->received_quantity = 10;
        $stockTransferItem->is_extra_item = true;
        $batch->number = '12345678';
        $stockTransfer->items = collect([$stockTransferItem]);
        $inventoryUnit = InventoryUnit::factory()->make([
            'id' => 1,
            'inventory_id' => $inventory->id,
            'purchase_amount_id' => 1,
            'batch_id' => $batch->id,
            'quantity' => 1,
        ]);
        $companyId = 1;
        setCompanyIdInSession($companyId);
        $inventory->inventoryUnits = collect([$inventoryUnit]);
        $stockTransferCheckRequestService = new StockTransferCheckRequestService();
        $stockTransferCheckRequestService->checkClosingDiscrepancyRequestBatchDetails(
            $stockTransfer,
            $validatedData,
            collect([$product]),
            collect([$inventory]),
            collect([$batch]),
        );
    }
)->throws(RedirectBackWithErrorException::class);

test(
    'checkClosingDiscrepancyRequestBatchDetails method throws an exception when extra item batch number quantity does not have enough inventory when product variant is true',
    function (): void {
        Config::set('app.product_variant', true);
        $validatedData = [
            'stock_transfer_items' => [[
                'id' => 1,
                'batch_details' => [
                    [
                        'batch_number' => '12345678',
                        'quantity' => 10,
                    ],
                ],
            ]],
        ];
        [$stockTransfer, $stockTransferItem, $product, $batch, $inventory] = commonStockTransferCheckRequestSeed();

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

        $stockTransferItem->quantity = 0;
        $stockTransferItem->received_quantity = 10;
        $stockTransferItem->is_extra_item = true;
        $batch->number = '12345678';
        $stockTransfer->items = collect([$stockTransferItem]);
        $inventoryUnit = InventoryUnit::factory()->make([
            'id' => 1,
            'inventory_id' => $inventory->id,
            'purchase_amount_id' => 1,
            'batch_id' => $batch->id,
            'quantity' => 1,
        ]);
        $companyId = 1;
        setCompanyIdInSession($companyId);
        $inventory->inventoryUnits = collect([$inventoryUnit]);
        $stockTransferCheckRequestService = new StockTransferCheckRequestService();
        $stockTransferCheckRequestService->checkClosingDiscrepancyRequestBatchDetails(
            $stockTransfer,
            $validatedData,
            collect([$product]),
            collect([$inventory]),
            collect([$batch]),
        );
    }
)->throws(RedirectBackWithErrorException::class);

test(
    'checkClosingDiscrepancyRequestBatchDetails method throws an exception when UOM item exceed quantity does not have enough inventory when product variant is false',
    function (): void {
        Config::set('app.product_variant', false);

        $validatedData = [
            'stock_transfer_items' => [[
                'id' => 1,
                'batch_details' => [],
            ]],
        ];
        $derivative = UnitOfMeasureDerivative::factory()->make([
            'id' => 1,
            'unit_of_measure_id' => 1,
            'name' => 'Gram',
            'ratio' => 1000,
        ]);
        [$stockTransfer, $stockTransferItem, $product, $batch, $inventory] = commonStockTransferCheckRequestSeed();
        $product->has_batch = false;
        $stockTransferItem->quantity = 5000;
        $stockTransferItem->received_quantity = 5500;
        $stockTransferItem->unitOfMeasureDerivative = $derivative;
        $inventory->stock = 0.3;
        $stockTransfer->items = collect([$stockTransferItem]);
        $stockTransferCheckRequestService = new StockTransferCheckRequestService();
        $stockTransferCheckRequestService->checkClosingDiscrepancyRequestBatchDetails(
            $stockTransfer,
            $validatedData,
            collect([$product]),
            collect([$inventory]),
            collect([$batch]),
        );
    }
)->throws(RedirectBackWithErrorException::class);

test(
    'checkClosingDiscrepancyRequestBatchDetails method throws an exception when UOM item exceed quantity does not have enough inventory when product variant is true',
    function (): void {
        Config::set('app.product_variant', true);

        $validatedData = [
            'stock_transfer_items' => [[
                'id' => 1,
                'batch_details' => [],
            ]],
        ];
        $derivative = UnitOfMeasureDerivative::factory()->make([
            'id' => 1,
            'unit_of_measure_id' => 1,
            'name' => 'Gram',
            'ratio' => 1000,
        ]);
        [$stockTransfer, $stockTransferItem, $product, $batch, $inventory] = commonStockTransferCheckRequestSeed();

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

        $product->has_batch = false;
        $stockTransferItem->quantity = 5000;
        $stockTransferItem->received_quantity = 5500;
        $stockTransferItem->unitOfMeasureDerivative = $derivative;
        $inventory->stock = 0.3;
        $stockTransfer->items = collect([$stockTransferItem]);
        $stockTransferCheckRequestService = new StockTransferCheckRequestService();
        $stockTransferCheckRequestService->checkClosingDiscrepancyRequestBatchDetails(
            $stockTransfer,
            $validatedData,
            collect([$product]),
            collect([$inventory]),
            collect([$batch]),
        );
    }
)->throws(RedirectBackWithErrorException::class);

test(
    'checkClosingDiscrepancyRequestBatchDetails method throws an exception when UOM item shortage quantity does not match with batch total quantity when product variant is false.',
    function (): void {
        Config::set('app.product_variant', false);

        $validatedData = [
            'stock_transfer_items' => [[
                'id' => 1,
                'batch_details' => [
                    [
                        'batch_number' => '12345678',
                        'quantity' => 1,
                    ],
                ],
            ]],
        ];
        $derivative = UnitOfMeasureDerivative::factory()->make([
            'id' => 1,
            'unit_of_measure_id' => 1,
            'name' => 'Gram',
            'ratio' => 1000,
        ]);
        [$stockTransfer, $stockTransferItem, $product, $batch, $inventory] = commonStockTransferCheckRequestSeed();
        $batch->number = '12345678';
        $product->has_batch = true;
        $stockTransferItem->quantity = 5500;
        $stockTransferItem->received_quantity = 5000;
        $stockTransferItem->unitOfMeasureDerivative = $derivative;
        $stockTransfer->items = collect([$stockTransferItem]);
        $companyId = 1;
        setCompanyIdInSession($companyId);
        $stockTransferCheckRequestService = new StockTransferCheckRequestService();
        $stockTransferCheckRequestService->checkClosingDiscrepancyRequestBatchDetails(
            $stockTransfer,
            $validatedData,
            collect([$product]),
            collect([$inventory]),
            collect([$batch]),
        );
    }
)->throws(RedirectBackWithErrorException::class);

test(
    'checkClosingDiscrepancyRequestBatchDetails method throws an exception when UOM item shortage quantity does not match with batch total quantity when product variant is true.',
    function (): void {
        Config::set('app.product_variant', true);
        $validatedData = [
            'stock_transfer_items' => [[
                'id' => 1,
                'batch_details' => [
                    [
                        'batch_number' => '12345678',
                        'quantity' => 1,
                    ],
                ],
            ]],
        ];
        $derivative = UnitOfMeasureDerivative::factory()->make([
            'id' => 1,
            'unit_of_measure_id' => 1,
            'name' => 'Gram',
            'ratio' => 1000,
        ]);
        [$stockTransfer, $stockTransferItem, $product, $batch, $inventory] = commonStockTransferCheckRequestSeed();

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

        $batch->number = '12345678';
        $product->has_batch = true;
        $stockTransferItem->quantity = 5500;
        $stockTransferItem->received_quantity = 5000;
        $stockTransferItem->unitOfMeasureDerivative = $derivative;
        $stockTransfer->items = collect([$stockTransferItem]);
        $companyId = 1;
        setCompanyIdInSession($companyId);
        $stockTransferCheckRequestService = new StockTransferCheckRequestService();
        $stockTransferCheckRequestService->checkClosingDiscrepancyRequestBatchDetails(
            $stockTransfer,
            $validatedData,
            collect([$product]),
            collect([$inventory]),
            collect([$batch]),
        );
    }
)->throws(RedirectBackWithErrorException::class);

test(
    'checkClosingDiscrepancyRequestBatchDetails method throws an exception when extra UOM item batch quantity does not match with batch total quantity when product variant is false',
    function (): void {
        Config::set('app.product_variant', false);

        $validatedData = [
            'stock_transfer_items' => [[
                'id' => 1,
                'batch_details' => [
                    [
                        'batch_number' => '12345678',
                        'quantity' => 5000,
                    ],
                ],
            ]],
        ];
        $derivative = UnitOfMeasureDerivative::factory()->make([
            'id' => 1,
            'unit_of_measure_id' => 1,
            'name' => 'Gram',
            'ratio' => 1000,
        ]);
        [$stockTransfer, $stockTransferItem, $product, $batch, $inventory] = commonStockTransferCheckRequestSeed();
        $batch->number = '12345678';
        $product->has_batch = true;
        $stockTransferItem->quantity = 0;
        $stockTransferItem->received_quantity = 5500;
        $stockTransferItem->unitOfMeasureDerivative = $derivative;
        $stockTransferItem->is_extra_item = true;
        $stockTransfer->items = collect([$stockTransferItem]);
        $companyId = 1;
        setCompanyIdInSession($companyId);
        $stockTransferCheckRequestService = new StockTransferCheckRequestService();
        $stockTransferCheckRequestService->checkClosingDiscrepancyRequestBatchDetails(
            $stockTransfer,
            $validatedData,
            collect([$product]),
            collect([$inventory]),
            collect([$batch]),
        );
    }
)->throws(RedirectBackWithErrorException::class);

test(
    'checkClosingDiscrepancyRequestBatchDetails method throws an exception when extra UOM item batch quantity does not match with batch total quantity when product variant is true',
    function (): void {
        Config::set('app.product_variant', true);

        $validatedData = [
            'stock_transfer_items' => [[
                'id' => 1,
                'batch_details' => [
                    [
                        'batch_number' => '12345678',
                        'quantity' => 5000,
                    ],
                ],
            ]],
        ];
        $derivative = UnitOfMeasureDerivative::factory()->make([
            'id' => 1,
            'unit_of_measure_id' => 1,
            'name' => 'Gram',
            'ratio' => 1000,
        ]);
        [$stockTransfer, $stockTransferItem, $product, $batch, $inventory] = commonStockTransferCheckRequestSeed();

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

        $batch->number = '12345678';
        $product->has_batch = true;
        $stockTransferItem->quantity = 0;
        $stockTransferItem->received_quantity = 5500;
        $stockTransferItem->unitOfMeasureDerivative = $derivative;
        $stockTransferItem->is_extra_item = true;
        $stockTransfer->items = collect([$stockTransferItem]);
        $companyId = 1;
        setCompanyIdInSession($companyId);
        $stockTransferCheckRequestService = new StockTransferCheckRequestService();
        $stockTransferCheckRequestService->checkClosingDiscrepancyRequestBatchDetails(
            $stockTransfer,
            $validatedData,
            collect([$product]),
            collect([$inventory]),
            collect([$batch]),
        );
    }
)->throws(RedirectBackWithErrorException::class);

test(
    'checkTransferType method throws an exception while store id mismatch in send products',
    function (): void {
        $stockTransferData = new StockTransferData(1, 2, null, null, null, null, 'test', 1, [
            [
                'product_id' => 1,
                'transfer_stock' => 100,
            ],
        ], Str::lower(StockTransferTypes::TRANSFER_ORDER->name));
        $stockTransferCheckRequestService = new StockTransferCheckRequestService();
        $stockTransferCheckRequestService->checkTransferType($stockTransferData, 2);
    }
)->throws(RedirectBackWithErrorException::class);

test(
    'checkTransferType method throws an exception while store id mismatch in receive products',
    function (): void {
        $stockTransferData = new StockTransferData(1, 2, null, null, 'abcd', null, 'test', 1, [
            [
                'product_id' => 1,
                'transfer_stock' => 100,
            ],
        ], Str::lower(StockTransferTypes::REQUEST_ORDER->name));
        $stockTransferCheckRequestService = new StockTransferCheckRequestService();
        $stockTransferCheckRequestService->checkTransferType($stockTransferData, 1);
    }
)->throws(RedirectBackWithErrorException::class);

test(
    'checkPrintTransferType method throws an exception while condition does not match',
    function ($transferType, $status, $locationId): void {
        $stockTransfer = StockTransfer::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'source_location_id' => 445,
            'destination_location_id' => 2,
            'requested_by_id' => 1,
            'stock_transfer_reason_id' => null,
            'status' => $status,
        ]);
        $stockTransferCheckRequestService = new StockTransferCheckRequestService();
        $stockTransferCheckRequestService->checkPrintTransferType($stockTransfer, $transferType, $locationId);
    }
)->throws(RedirectBackWithErrorException::class)->with([
    ['OUT', StatusTypes::DRAFT->value, 1],
    ['OUT', StatusTypes::OPEN->value, 10],
    ['IN', StatusTypes::DRAFT->value, 1],
    ['IN', StatusTypes::CLOSED->value, 10],
]);

test(
    'checkRequestOrderEditor method throws an exception while request order editor is mismatch and status is not open',
    function (): void {
        $stockTransfer = StockTransfer::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'source_location_id' => 1,
            'destination_location_id' => 2,
            'requested_by_id' => 1,
            'stock_transfer_reason_id' => null,
            'status' => StatusTypes::DRAFT->value,
            'transfer_type' => StockTransferTypes::TRANSFER_ORDER->value,
        ]);
        $stockTransferCheckRequestService = new StockTransferCheckRequestService();
        $stockTransferCheckRequestService->checkRequestOrderEditor($stockTransfer, 2);
    }
)->throws(RedirectBackWithErrorException::class);

test(
    'checkShippingDetails method throws an exception when the specified batch number quantity mismatch with actual quantity when product variant is false',
    function (): void {
        Config::set('app.product_variant', false);
        $validatedData = [
            'stock_transfer_items' => [
                'id' => 1,
                'batch_details' => [
                    [
                        'batch_number' => 'A',
                        'quantity' => 2,
                    ],
                ],
            ],
        ];
        [$stockTransfer, $stockTransferItem, $product, $batch, $inventory] = commonStockTransferCheckRequestSeed();
        $stockTransfer->items = collect([$stockTransferItem]);
        $stockTransferCheckRequestService = new StockTransferCheckRequestService();
        $stockTransferCheckRequestService->checkShippingDetails(
            collect([$validatedData['stock_transfer_items']]),
            $stockTransfer,
            collect([$product]),
            collect([$batch]),
            collect([])
        );
    }
)->throws(RedirectBackWithErrorException::class);

test(
    'checkShippingDetails method throws an exception when the specified batch number quantity mismatch with actual quantity when product variant is true',
    function (): void {
        Config::set('app.product_variant', true);

        $validatedData = [
            'stock_transfer_items' => [
                'id' => 1,
                'batch_details' => [
                    [
                        'batch_number' => 'A',
                        'quantity' => 2,
                    ],
                ],
            ],
        ];
        [$stockTransfer, $stockTransferItem, $product, $batch, $inventory] = commonStockTransferCheckRequestSeed();

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

        $stockTransfer->items = collect([$stockTransferItem]);
        $stockTransferCheckRequestService = new StockTransferCheckRequestService();
        $stockTransferCheckRequestService->checkShippingDetails(
            collect([$validatedData['stock_transfer_items']]),
            $stockTransfer,
            collect([$product]),
            collect([$batch]),
            collect([])
        );
    }
)->throws(RedirectBackWithErrorException::class);

test(
    'checkShippingDetails method throws an exception when the specified batch number doest not exist in our records when product variant is false',
    function (): void {
        Config::set('app.product_variant', false);
        $validatedData = [
            'stock_transfer_items' => [
                'id' => 1,
                'batch_details' => [
                    [
                        'batch_number' => 'A112312312',
                        'quantity' => 10,
                    ],
                ],
            ],
        ];
        [$stockTransfer, $stockTransferItem, $product, $batch, $inventory] = commonStockTransferCheckRequestSeed();
        $stockTransfer->items = collect([$stockTransferItem]);
        $stockTransferCheckRequestService = new StockTransferCheckRequestService();
        $stockTransferCheckRequestService->checkShippingDetails(
            collect([$validatedData['stock_transfer_items']]),
            $stockTransfer,
            collect([$product]),
            collect([$batch]),
            collect([])
        );
    }
)->throws(RedirectBackWithErrorException::class);

test(
    'checkShippingDetails method throws an exception when the specified batch number doest not exist in our records when product variant is true',
    function (): void {
        Config::set('app.product_variant', true);

        $validatedData = [
            'stock_transfer_items' => [
                'id' => 1,
                'batch_details' => [
                    [
                        'batch_number' => 'A112312312',
                        'quantity' => 10,
                    ],
                ],
            ],
        ];
        [$stockTransfer, $stockTransferItem, $product, $batch, $inventory] = commonStockTransferCheckRequestSeed();

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

        $stockTransfer->items = collect([$stockTransferItem]);
        $stockTransferCheckRequestService = new StockTransferCheckRequestService();
        $stockTransferCheckRequestService->checkShippingDetails(
            collect([$validatedData['stock_transfer_items']]),
            $stockTransfer,
            collect([$product]),
            collect([$batch]),
            collect([])
        );
    }
)->throws(RedirectBackWithErrorException::class);

test(
    'checkShippingDetails method throws an exception when additional UOM item derivative does not match.',
    function (): void {
        $validatedData = [
            'stock_transfer_items' => [
                'id' => 1,
                'batch_details' => [],
            ],
        ];
        [$stockTransfer, $stockTransferItem, $product, $batch, $inventory] = commonStockTransferCheckRequestSeed();
        $stockTransferItem->unit_of_measure_derivative_id = 1000;
        $stockTransfer->items = collect([$stockTransferItem]);
        $product = commonGetProductDetails();
        $derivative = UnitOfMeasureDerivative::factory()->make([
            'id' => 100000,
            'unit_of_measure_id' => $product->unit_of_measure_id,
            'name' => 'Gram',
            'ratio' => 1000,
        ]);
        $stockTransferCheckRequestService = new StockTransferCheckRequestService();
        $stockTransferCheckRequestService->checkShippingDetails(
            collect([$validatedData['stock_transfer_items']]),
            $stockTransfer,
            collect([$product]),
            collect([$batch]),
            collect([$derivative])
        );
    }
)->throws(RedirectBackWithErrorException::class);

test(
    'checkShippingDetails method throws an exception when additional UOM item derivative UOM & product UOM does not match when product variant is false.',
    function (): void {
        Config::set('app.product_variant', false);

        $validatedData = [
            'stock_transfer_items' => [
                'id' => 1,
                'batch_details' => [],
            ],
        ];
        $product = commonGetProductDetails();
        $derivative = UnitOfMeasureDerivative::factory()->make([
            'id' => 1,
            'unit_of_measure_id' => 0,
            'name' => 'Gram',
            'ratio' => 1000,
        ]);
        [$stockTransfer, $stockTransferItem, $product, $batch, $inventory] = commonStockTransferCheckRequestSeed();
        $stockTransferItem->unit_of_measure_derivative_id = $derivative->id;
        $stockTransfer->items = collect([$stockTransferItem]);
        $stockTransferCheckRequestService = new StockTransferCheckRequestService();
        $stockTransferCheckRequestService->checkShippingDetails(
            collect([$validatedData['stock_transfer_items']]),
            $stockTransfer,
            collect([$product]),
            collect([$batch]),
            collect([$derivative])
        );
    }
)->throws(RedirectBackWithErrorException::class);

test(
    'checkShippingDetails method throws an exception when additional UOM item derivative UOM & product UOM does not match when product variant is true.',
    function (): void {
        Config::set('app.product_variant', true);

        $validatedData = [
            'stock_transfer_items' => [
                'id' => 1,
                'batch_details' => [],
            ],
        ];
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

        $derivative = UnitOfMeasureDerivative::factory()->make([
            'id' => 1,
            'unit_of_measure_id' => 0,
            'name' => 'Gram',
            'ratio' => 1000,
        ]);
        [$stockTransfer, $stockTransferItem, $product, $batch, $inventory] = commonStockTransferCheckRequestSeed();

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

        $stockTransferItem->unit_of_measure_derivative_id = $derivative->id;
        $stockTransfer->items = collect([$stockTransferItem]);
        $stockTransferCheckRequestService = new StockTransferCheckRequestService();
        $stockTransferCheckRequestService->checkShippingDetails(
            collect([$validatedData['stock_transfer_items']]),
            $stockTransfer,
            collect([$product]),
            collect([$batch]),
            collect([$derivative])
        );
    }
)->throws(RedirectBackWithErrorException::class);

test(
    'checkShippingDetails method throws an exception when additional UOM item derivative quantity & batches total quantity does not match when product variant is false.',
    function (): void {
        Config::set('app.product_variant', false);
        $validatedData = [
            'stock_transfer_items' => [
                'id' => 1,
                'batch_details' => [
                    [
                        'batch_number' => 'A112312312',
                        'quantity' => 10,
                    ],
                ],
            ],
        ];
        $product = commonGetProductDetails();
        $derivative = UnitOfMeasureDerivative::factory()->make([
            'id' => 1,
            'unit_of_measure_id' => $product->unit_of_measure_id,
            'name' => 'Gram',
            'ratio' => 1000,
        ]);
        [$stockTransfer, $stockTransferItem, $product, $batch, $inventory] = commonStockTransferCheckRequestSeed();
        $stockTransferItem->unit_of_measure_derivative_id = $derivative->id;
        $stockTransfer->items = collect([$stockTransferItem]);
        $stockTransferCheckRequestService = new StockTransferCheckRequestService();
        $stockTransferCheckRequestService->checkShippingDetails(
            collect([$validatedData['stock_transfer_items']]),
            $stockTransfer,
            collect([$product]),
            collect([$batch]),
            collect([$derivative])
        );
    }
)->throws(RedirectBackWithErrorException::class);

test(
    'checkShippingDetails method throws an exception when additional UOM item derivative quantity & batches total quantity does not match when product variant is true.',
    function (): void {
        Config::set('app.product_variant', true);

        $validatedData = [
            'stock_transfer_items' => [
                'id' => 1,
                'batch_details' => [
                    [
                        'batch_number' => 'A112312312',
                        'quantity' => 10,
                    ],
                ],
            ],
        ];
        $product = commonGetProductDetails();
        $derivative = UnitOfMeasureDerivative::factory()->make([
            'id' => 1,
            'unit_of_measure_id' => $product->unit_of_measure_id,
            'name' => 'Gram',
            'ratio' => 1000,
        ]);
        [$stockTransfer, $stockTransferItem, $product, $batch, $inventory] = commonStockTransferCheckRequestSeed();

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

        $stockTransferItem->unit_of_measure_derivative_id = $derivative->id;
        $stockTransfer->items = collect([$stockTransferItem]);
        $stockTransferCheckRequestService = new StockTransferCheckRequestService();
        $stockTransferCheckRequestService->checkShippingDetails(
            collect([$validatedData['stock_transfer_items']]),
            $stockTransfer,
            collect([$product]),
            collect([$batch]),
            collect([$derivative])
        );
    }
)->throws(RedirectBackWithErrorException::class);

test(
    'locationChanged method throws an exception while source location changed',
    function ($locationId): void {
        $stockTransfer = StockTransfer::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'source_location_id' => 1,
            'destination_location_id' => 2,
            'requested_by_id' => 1,
            'stock_transfer_reason_id' => null,
        ]);
        $stockTransferData = new StockTransferData($locationId, 2, null, null, null, null, 'test', 1, [
            [
                'product_id' => 1,
                'transfer_stock' => 100,
                'initial_transfer_quantity' => 0,
            ],
        ], 'transfer_order');
        $stockTransferCheckRequestService = new StockTransferCheckRequestService();
        $stockTransferCheckRequestService->locationChanged($stockTransfer, $stockTransferData);
    }
)->throws(RedirectBackWithErrorException::class)->with([[1111], [10]]);

test(
    'locationChanged method throws an exception while destination location changed',
    function ($locationId): void {
        $stockTransfer = StockTransfer::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'source_location_id' => 1,
            'destination_location_id' => 2,
            'requested_by_id' => 1,
            'stock_transfer_reason_id' => null,
        ]);
        $stockTransferData = new StockTransferData(1, $locationId, null, null, null, null, 'test', 1, [
            [
                'product_id' => 1,
                'transfer_stock' => 100,
                'initial_transfer_quantity' => 0,
            ],
        ], 'transfer_order');
        $stockTransferCheckRequestService = new StockTransferCheckRequestService();
        $stockTransferCheckRequestService->locationChanged($stockTransfer, $stockTransferData);
    }
)->throws(RedirectBackWithErrorException::class)->with([[111], [10]]);

test(
    'validateTransitLocation method throw an exception if transit location is same source/destination location.',
    function ($locationId): void {
        $stockTransferShippedData = new StockTransferShippedData(
            shipped_type: ShippedTypes::TRANSIT->value,
            location_id: $locationId,
        );

        $stockTransfer = StockTransfer::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'stock_transfer_reason_id' => 1,
            'source_location_id' => $locationId,
            'destination_location_id' => $locationId,
            'requested_by_id' => 1,
        ]);

        $this->mock(StockTransferQueries::class, function ($mock) use ($stockTransfer): void {
            $mock->shouldReceive('getLocationById')
                ->once()
                ->andReturn($stockTransfer);
        });

        $stockTransferCheckRequestService = new StockTransferCheckRequestService();
        $stockTransferCheckRequestService->validateTransitLocation(
            $stockTransferShippedData,
            $stockTransfer->id,
            $stockTransfer->company_id
        );
    }
)->with([['1'], ['2']])->throws(HttpException::class);

function commonStockTransferCheckRequestSeed(): array
{
    $product = commonGetProductDetails();
    $batch = Batch::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'product_id' => 1,
    ]);

    $stockTransfer = StockTransfer::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'source_location_id' => 1,
        'destination_location_id' => 2,
        'requested_by_id' => 1,
        'stock_transfer_reason_id' => null,
        'status' => StatusTypes::OPEN->value,
    ]);

    $stockTransferItem = StockTransferItem::factory()->make([
        'id' => 1,
        'stock_transfer_id' => $stockTransfer->id,
        'product_id' => 1,
        'package_type_id' => null,
        'quantity' => 10,
        'received_quantity' => null,
    ]);

    $inventory = Inventory::factory()->make([
        'id' => 1,
        'product_id' => 1,
        'location_id' => 1,
        'stock' => 100,
    ]);

    return [$stockTransfer, $stockTransferItem, $product, $batch, $inventory];
}

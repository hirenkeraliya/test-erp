<?php

declare(strict_types=1);

use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\PurchaseOrder\Enums\OrderTypes;
use App\Domains\PurchaseOrder\Enums\Statuses;
use App\Domains\PurchaseOrder\PurchaseOrderQueries;
use App\Domains\PurchaseOrder\Services\PurchaseOrderCheckRequestService;
use App\Domains\PurchaseOrderFulfillment\DataObjects\PurchaseOrderFulfillmentStoreForWarehouseManagerData;
use App\Domains\PurchaseOrderFulfillment\DataObjects\WarehouseManagerApiPurchaseOrderFulfillmentData;
use App\Domains\PurchaseOrderFulfillment\PurchaseOrderFulfillmentQueries;
use App\Domains\PurchaseOrderFulfillment\Services\PurchaseOrderFulfillmentCheckRequestForInternalAppService;
use App\Domains\PurchaseOrderFulfillment\Services\PurchaseOrderFulfillmentService;
use App\Domains\PurchaseOrderFulfillmentItem\PurchaseOrderFulfillmentItemQueries;
use App\Domains\PurchaseOrderItem\PurchaseOrderItemQueries;
use App\Domains\Sequence\Enums\SequenceTypes;
use App\Domains\Sequence\SequenceQueries;
use App\Domains\WarehouseManager\WarehouseManagerQueries;
use App\Http\Controllers\Api\WarehouseManager\PurchaseOrderFulfillmentController;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Location;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderFulfillment;
use App\Models\PurchaseOrderFulfillmentItem;
use App\Models\PurchaseOrderItem;
use App\Models\Sequence;
use App\Models\WarehouseManager;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function (): void {
    $this->company = Company::factory()->make([
        'id' => 1,
        'default_country_id' => 1,
    ]);

    $this->employee = Employee::factory()->make([
        'id' => 1,
        'company_id' => $this->company->id,
        'designation_id' => 1,
    ]);

    $this->warehouseManager = WarehouseManager::factory()->make([
        'id' => 1,
        'employee_id' => $this->employee->id,
    ]);

    $this->location = Location::factory()->make([
        'id' => 1,
        'company_id' => $this->company->id,
        'type_id' => LocationTypes::WAREHOUSE->value,
    ]);

    $this->purchaseOrder = PurchaseOrder::factory()->make([
        'id' => 1,
        'external_company_id' => 1,
        'external_location_id' => 1,
        'company_id' => $this->company->id,
        'location_id' => 1,
        'status' => Statuses::DRAFT->value,
        'order_type' => OrderTypes::PURCHASE_REQUEST->value,
        'created_by_company_id' => $this->company->id,
    ]);

    $this->purchaseOrderItem = PurchaseOrderItem::factory()->make([
        'id' => 1,
        'purchase_order_id' => $this->purchaseOrder->id,
        'product_id' => 1,
        'quantity' => 10,
        'rejected_quantity' => 1,
        'transferred_quantity' => 1,
        'price_per_unit' => 1,
    ]);

    $this->purchaseOrder->items = $this->purchaseOrderItem;
});

test(
    'It Calls listQueryForInternalApplication of purchaseOrderFulfillmentQueries class and return the list delivery orders',
    function (): void {
        $companyId = 1;
        $filterData = [
            'warehouse_id' => 1,
            'location_id' => 1,
            'purchase_order_id' => 1,
            'per_page' => 10,
            'page' => 1,
            'search_text' => '',
            'sort_by' => 'id',
            'sort_direction' => 'asc',
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-01',
        ];

        $request = new Request();
        $request->setUserResolver(fn (): WarehouseManager => $this->warehouseManager);

        $warehouseManagerApiPurchaseOrderFulfillmentData = new WarehouseManagerApiPurchaseOrderFulfillmentData(
            ...$filterData
        );

        $this->mock(WarehouseManagerQueries::class, function ($mock): void {
            $mock->shouldReceive('existsByIdAndWarehouseId')
                ->once()
                ->andReturn(true);
        });

        $this->mock(LocationQueries::class, function ($mock) use ($companyId): void {
            $mock->shouldReceive('getCompanyIdOfWarehouse')
                ->once()
                ->andReturn($companyId);
        });

        $this->mock(PurchaseOrderQueries::class, function ($mock): void {
            $mock->shouldReceive('getByIdAndCompanyId')
                ->once()
                ->andReturn($this->purchaseOrder);
        });

        $this->mock(PurchaseOrderCheckRequestService::class, function ($mock): void {
            $mock->shouldReceive('canPurchaseOrderDeliveryOrder')
                ->once()
                ->andReturn(true);
        });

        $purchaseOrderFulfillmentQueries = $this->mock(
            PurchaseOrderFulfillmentQueries::class,
            function ($mock): void {
                $mock->shouldReceive('listQueryForInternalApplication')
                    ->once()
                    ->andReturn(new LengthAwarePaginator([], 50, 15));
            }
        );

        $purchaseOrderFulfillmentController = new PurchaseOrderFulfillmentController($purchaseOrderFulfillmentQueries);
        $response = $purchaseOrderFulfillmentController->getPaginatedDeliveryOrders(
            $request,
            $warehouseManagerApiPurchaseOrderFulfillmentData
        );

        expect($response)
            ->toHaveKeys(['data', 'total_records', 'last_page', 'current_page', 'per_page']);
    }
);

test('calls the addShippingDetails method save record', function (): void {
    $sequence = Sequence::factory()->make([
        'id' => 1,
        'location_id' => $this->location->id,
        'type_id' => SequenceTypes::PODO->value,
    ]);

    $sequence->location = $this->location;

    $purchaseOrderFulfillmentData = [
        'warehouse_id' => $this->location->id,
        'location_id' => $this->location->id,
        'purchase_order_id' => $this->purchaseOrder->id,
        'happened_at' => now()->format('Y-m-d h:i:s'),
        'notes' => '',
        'transfer_items' => [
            [
                'purchase_order_item_id' => $this->purchaseOrderItem->id,
                'product_id' => 1,
                'transfer_quantity' => 1,
                'package_type_id' => 2,
                'package_quantity' => 1,
                'package_total_quantity' => 1,
                'remarks' => 'add',
            ],
        ],
    ];

    $purchaseOrderFulfillment = PurchaseOrderFulfillment::factory()->make([
        'id' => 1,
        'purchase_order_id' => $this->purchaseOrder->id,
    ]);

    $purchaseOrderFulfillmentItem = PurchaseOrderFulfillmentItem::factory()->make([
        'id' => 1,
        'purchase_order_fulfillment_id' => $purchaseOrderFulfillment->id,
        'purchase_order_item_id' => $this->purchaseOrderItem->id,
        'product_id' => 1,
        'remarks' => 'add',
    ]);

    $purchaseOrderFulfillmentData = new PurchaseOrderFulfillmentStoreForWarehouseManagerData(
        ...$purchaseOrderFulfillmentData
    );

    $this->mock(WarehouseManagerQueries::class, function ($mock): void {
        $mock->shouldReceive('existsByIdAndWarehouseId')
            ->once()
            ->with((int) $this->warehouseManager->id, (int) $this->location->id)
            ->andReturn(true);
    });

    $this->mock(LocationQueries::class, function ($mock): void {
        $mock->shouldReceive('getCompanyIdOfWarehouse')
            ->once()
            ->with((int) $this->location->id)
            ->andReturn(true);
    });

    $this->mock(PurchaseOrderQueries::class, function ($mock): void {
        $mock->shouldReceive('getByIdAndCompanyId')
            ->once()
            ->andReturn($this->purchaseOrder);
    });

    $this->mock(PurchaseOrderFulfillmentService::class, function ($mock): void {
        $mock->shouldReceive('prepareActiveBatchesProductsAndInventories')
            ->once()
            ->andReturn([collect(), collect(), collect(), collect()]);
        $mock->shouldReceive('prepareTransferTypeForDeliveryNote')
            ->once();
    });

    $this->mock(PurchaseOrderFulfillmentCheckRequestForInternalAppService::class, function ($mock): void {
        $mock->shouldReceive('checkRequestDetails')
            ->once();
    });

    $this->mock(PurchaseOrderItemQueries::class, function ($mock): void {
        $mock->shouldReceive('getByPurchaseOrderId')
            ->once()
            ->andReturn(collect([$this->purchaseOrderItem]));

        $mock->shouldReceive('updateTransferredQuantity')
            ->once();
    });

    $this->mock(SequenceQueries::class, function ($mock) use ($sequence): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->andReturn($sequence);
    });

    $purchaseOrderFulfillmentQueries = $this->mock(PurchaseOrderFulfillmentQueries::class, function ($mock) use (
        $purchaseOrderFulfillment
    ): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->andReturn($purchaseOrderFulfillment);
    });

    $this->mock(PurchaseOrderFulfillmentItemQueries::class, function ($mock) use (
        $purchaseOrderFulfillmentItem
    ): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->andReturn($purchaseOrderFulfillmentItem);
    });

    $request = new Request();
    $request->setUserResolver(fn (): WarehouseManager => $this->warehouseManager);

    $purchaseOrderFullfillmentController = new PurchaseOrderFulfillmentController($purchaseOrderFulfillmentQueries);

    $purchaseOrderFullfillmentController->addShippingDetails($request, $purchaseOrderFulfillmentData);
});

test(
    'addShippingDetails method throw exception when items that were to be added to the Delivery Order have already been included',
    function (): void {
        $this->purchaseOrderItem->quantity = 1;

        $purchaseOrderFulfillmentData = [
            'warehouse_id' => $this->location->id,
            'location_id' => $this->location->id,
            'purchase_order_id' => $this->purchaseOrder->id,
            'happened_at' => now()->format('Y-m-d h:i:s'),
            'notes' => '',
            'transfer_items' => [
                [
                    'purchase_order_item_id' => $this->purchaseOrderItem->id,
                    'product_id' => 1,
                    'transfer_quantity' => 1,
                    'package_type_id' => 2,
                    'package_quantity' => 1,
                    'package_total_quantity' => 1,
                    'remarks' => 'add',
                ],
            ],
        ];

        $purchaseOrderFulfillmentData = new PurchaseOrderFulfillmentStoreForWarehouseManagerData(
            ...$purchaseOrderFulfillmentData
        );

        $this->mock(WarehouseManagerQueries::class, function ($mock): void {
            $mock->shouldReceive('existsByIdAndWarehouseId')
                ->once()
                ->with((int) $this->warehouseManager->id, (int) $this->location->id)
                ->andReturn(true);
        });

        $this->mock(LocationQueries::class, function ($mock): void {
            $mock->shouldReceive('getCompanyIdOfWarehouse')
                ->once()
                ->with((int) $this->location->id)
                ->andReturn(true);
        });

        $this->mock(PurchaseOrderQueries::class, function ($mock): void {
            $mock->shouldReceive('getByIdAndCompanyId')
                ->once()
                ->andReturn($this->purchaseOrder);
        });

        $this->mock(PurchaseOrderFulfillmentService::class, function ($mock): void {
            $mock->shouldReceive('prepareActiveBatchesProductsAndInventories')
                ->once()
                ->andReturn([collect(), collect(), collect(), collect()]);
        });

        $this->mock(PurchaseOrderFulfillmentCheckRequestForInternalAppService::class, function ($mock): void {
            $mock->shouldReceive('checkRequestDetails')
                ->once();
        });

        $this->mock(PurchaseOrderItemQueries::class, function ($mock): void {
            $mock->shouldReceive('getByPurchaseOrderId')
                ->once()
                ->andReturn(collect([$this->purchaseOrderItem]));
        });

        $request = new Request();
        $request->setUserResolver(fn (): WarehouseManager => $this->warehouseManager);

        $purchaseOrderFullfillmentController = new PurchaseOrderFulfillmentController(
            new PurchaseOrderFulfillmentQueries()
        );

        $purchaseOrderFullfillmentController->addShippingDetails($request, $purchaseOrderFulfillmentData);
    }
)->throws(HttpException::class);

test(
    'addShippingDetails method throws an Exception when the warehouse manager specify a different warehouse',
    function (): void {
        $purchaseOrderFulfillmentData = [
            'warehouse_id' => $this->location->id,
            'location_id' => $this->location->id,
            'purchase_order_id' => $this->purchaseOrder->id,
            'happened_at' => now()->format('Y-m-d h:i:s'),
            'notes' => '',
            'transfer_items' => [
                [
                    'purchase_order_item_id' => $this->purchaseOrderItem->id,
                    'product_id' => 1,
                    'transfer_quantity' => 1,
                    'package_type_id' => 2,
                    'package_quantity' => 1,
                    'package_total_quantity' => 1,
                    'remarks' => 'add',
                ],
            ],
        ];

        $purchaseOrderFulfillmentData = new PurchaseOrderFulfillmentStoreForWarehouseManagerData(
            ...$purchaseOrderFulfillmentData
        );

        $this->mock(WarehouseManagerQueries::class, function ($mock): void {
            $mock->shouldReceive('existsByIdAndWarehouseId')
                ->once()
                ->with((int) $this->warehouseManager->id, (int) $this->location->id)
                ->andReturn(false);
        });

        $request = new Request();
        $request->setUserResolver(fn (): WarehouseManager => $this->warehouseManager);

        $purchaseOrderFullfillmentController = new PurchaseOrderFulfillmentController(
            new PurchaseOrderFulfillmentQueries()
        );

        $purchaseOrderFullfillmentController->addShippingDetails($request, $purchaseOrderFulfillmentData);
    }
)->throws(HttpException::class);

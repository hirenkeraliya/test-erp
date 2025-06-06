<?php

declare(strict_types=1);

use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Sequence\Enums\SequenceTypes;
use App\Domains\Sequence\SequenceQueries;
use App\Domains\StockTransfer\DataObjects\StockTransferData;
use App\Domains\StockTransfer\DataObjects\StockTransferShippedData;
use App\Domains\StockTransfer\DataObjects\StockTransferUpdateStatusData;
use App\Domains\StockTransfer\Enums\ShippedTypes;
use App\Domains\StockTransfer\Enums\StatusTypes;
use App\Domains\StockTransfer\Enums\StockTransferTypes;
use App\Domains\StockTransfer\Services\StockTransferCheckRequestService;
use App\Domains\StockTransfer\Services\StockTransferPrintService;
use App\Domains\StockTransfer\Services\StockTransferService;
use App\Domains\StockTransfer\StockTransferQueries;
use App\Domains\StockTransferItem\StockTransferItemQueries;
use App\Exceptions\RedirectBackWithErrorException;
use App\Http\Controllers\Admin\StockTransferController;
use App\Models\Admin;
use App\Models\Batch;
use App\Models\Inventory;
use App\Models\Location;
use App\Models\MasterProduct;
use App\Models\PackageType;
use App\Models\Sequence;
use App\Models\StockTransfer;
use App\Models\StockTransferAverageLeadDays;
use App\Models\StockTransferItem;
use App\Models\StoreManager;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test(
    'It calls the list query method of the stock transfer queries class and returns proper response',
    function (): void {
        $companyId = 1;

        setCompanyIdInSession($companyId);

        $requestParameter = [
            'search_text' => 'test',
            'sort_by' => 'test',
            'sort_direction' => 'test',
            'per_page' => 'test',
            'stock_transfer_date' => null,
            'location_id' => 1,
            'select_status' => StatusTypes::getValueByCaseName('CANCELLED'),
            'transfer_type' => 1,
            'stock_transfer_id' => null,
            'dashboard_transfer_type' => null,
        ];

        $stockTransferQueries = $this->mock(StockTransferQueries::class, function ($mock) use (
            $requestParameter,
            $companyId
        ): void {
            $mock->shouldReceive('listQuery')
                ->once()
                ->with($requestParameter, $companyId)
                ->andReturn(new LengthAwarePaginator([], 50, 15));
            $mock->shouldReceive('transferOrderStatusCount')
                ->once()
                ->with(StockTransferTypes::TRANSFER_ORDER->value, $requestParameter, $companyId)
                ->andReturn(new Collection([]));
            $mock->shouldReceive('requestOrderStatusCount')
                ->once()
                ->with(StockTransferTypes::REQUEST_ORDER->value, $requestParameter, $companyId)
                ->andReturn(new Collection([]));
        });

        $stockTransferController = new StockTransferController($stockTransferQueries);

        $requestParameter['stock_transfer_number'] = 'test';
        $response = $stockTransferController->fetchStockTransfers(new Request($requestParameter));

        $this->assertEquals(50, $response['total_records']);
        $this->assertEquals(collect([]), $response['data']->resource);
    }
);

test('It calls the exportStockTransferItems method and returns a proper response', function (): void {
    $stockTransferQueries = new StockTransferQueries();
    setCompanyIdInSession();

    $this->mock(StockTransferItemQueries::class, function ($mock): void {
        $mock->shouldReceive('getByStockTransferId')
            ->once()
            ->with(1, 1)
            ->andReturn(new Collection([]));
    });

    $stockTransferController = new StockTransferController($stockTransferQueries);
    $response = $stockTransferController->exportStockTransferItems(1, 'filename.csv');
    $this->assertEquals(200, $response->getStatusCode());

    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});

test('It calls the store method of StockTransferQueries class and returns a proper response', function (): void {
    $companyId = 1;
    setCompanyIdInSession($companyId);

    $request = new Request();

    $request->setUserResolver(fn (): Admin => new Admin([
        'employee_id' => 1,
    ]));

    $stockTransferData = new StockTransferData(1, 2, null, null, null, null, 'test', null, [], 'request_order');

    $product = commonGetProductDetails();

    [$stockTransfer, $storeOne, $storeManagerOne] = loadStockTransferLocationRelation(
        $companyId,
        StatusTypes::DRAFT->value,
        StockTransferTypes::REQUEST_ORDER->value
    );

    $sequence = setSequenceAndStore($storeOne);

    $this->mock(StockTransferCheckRequestService::class, function ($mock): void {
        $mock->shouldReceive('checkRequestDetails')
            ->once();
    });

    $this->mock(SequenceQueries::class, function ($mock) use ($sequence): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->andReturn($sequence);
    });

    $stockTransferQueries = $this->mock(StockTransferQueries::class, function ($mock) use ($stockTransfer): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->andReturn($stockTransfer);
        $mock->shouldReceive('loadItemsAndBatches')
            ->once()
            ->andReturn($stockTransfer);
    });

    $this->mock(StockTransferService::class, function ($mock) use ($product, $storeOne): void {
        $mock->shouldReceive('prepareLocationIdAndTransferType')
            ->once()
            ->andReturn([SequenceTypes::TO->value, $storeOne->id]);
        $mock->shouldReceive('prepareStockTransferDetails')
            ->once();
        $mock->shouldReceive('prepareActiveBatchesProductsAndInventories')
            ->once()
            ->andReturn([collect([$product]), collect([]), collect([]), collect([])]);
        $mock->shouldReceive('saveStockTransferItemAndBatchRecords')
            ->once();
        $mock->shouldReceive('reserveStockTransferItemStocks')
            ->once();
    });

    $stockTransferController = new StockTransferController($stockTransferQueries);

    $response = $stockTransferController->store($stockTransferData, $request);

    $this->assertEquals(302, $response->getStatusCode());
    $this->assertEquals('Stock transfer added successfully.', $response->getSession()->all()['success']);
    $this->assertStringContainsString('admin/stock-transfers', $response->getTargetUrl());
});

test('It calls the update method of StockTransferQueries class and returns a proper response', function (): void {
    $companyId = 1;
    setCompanyIdInSession($companyId);

    $stockTransferData = new StockTransferData(1, 1, null, null, null, null, 'test', null, [], 'request_order');

    $product = commonGetProductDetails();

    [$admin, $request] = setRequestUserForAdmin();

    $this->mock(StockTransferCheckRequestService::class, function ($mock): void {
        $mock->shouldReceive('checkRequestDetails')
            ->once();
        $mock->shouldReceive('locationChanged')
            ->once();
    });

    $stockTransfer = StockTransfer::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'source_location_id' => 1,
        'stock_transfer_reason_id' => null,
        'destination_location_id' => 2,
        'requested_by_id' => 1,
        'status' => StatusTypes::DRAFT->value,
        'transfer_type' => StockTransferTypes::REQUEST_ORDER->value,
    ]);

    $this->mock(StockTransferService::class, function ($mock) use ($product): void {
        $mock->shouldReceive('prepareStockTransferDetailsForUpdate')
            ->once();
        $mock->shouldReceive('reserveStockTransferItemStocks')
            ->once();
        $mock->shouldReceive('saveStockTransferItems')
            ->once();
        $mock->shouldReceive('prepareActiveBatchesProductsAndInventories')
            ->once()
            ->andReturn([collect([$product]), collect([]), collect([]), collect([])]);
    });

    $stockTransferQueries = $this->mock(StockTransferQueries::class, function ($mock) use ($stockTransfer): void {
        $mock->shouldReceive('update')
            ->once()
            ->andReturn($stockTransfer);
        $mock->shouldReceive('loadItemsAndBatches')
            ->once()
            ->andReturn($stockTransfer);
        $mock->shouldReceive('getLocationAndStatusById')
            ->once()
            ->andReturn($stockTransfer);
    });

    $this->mock(StockTransferItemQueries::class, function ($mock): void {
        $mock->shouldReceive('deleteItemAndBatches')
            ->once();
    });

    $stockTransferController = new StockTransferController($stockTransferQueries);

    $response = $stockTransferController->update($request, $stockTransferData, 1);

    $this->assertEquals(302, $response->getStatusCode());
    $this->assertEquals('The stock transfer has been updated successfully.', $response->getSession()->all()['success']);
    $this->assertStringContainsString('admin/stock-transfers', $response->getTargetUrl());
});

test('It calls the updateStatus method while change to open status and returns a proper response', function (): void {
    $companyId = 1;
    setCompanyIdInSession($companyId);

    $stockTransferUpdateStatusData = new StockTransferUpdateStatusData(StatusTypes::OPEN->value, null);

    $request = new Request();
    setAdminStatusIdAndUser($request);
    $stockTransferQueries = new StockTransferQueries();
    $this->mock(StockTransferService::class, function ($mock): void {
        $mock->shouldReceive('markAsOpen')
            ->once();
    });

    $stockTransferController = new StockTransferController($stockTransferQueries);

    $response = $stockTransferController->updateStatus($request, $stockTransferUpdateStatusData, 1);

    $this->assertEquals(302, $response->getStatusCode());
    $this->assertEquals('Status changed successfully.', $response->getSession()->all()['success']);
    $this->assertStringContainsString('admin/stock-transfers', $response->getTargetUrl());
});

test(
    'It calls the updateStatus method while change to cancel status and returns a proper response',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession($companyId);

        $stockTransferUpdateStatusData = new StockTransferUpdateStatusData(StatusTypes::CANCELLED->value, null);

        $request = new Request();

        setAdminStatusIdAndUser($request);

        $stockTransferQueries = resolve(StockTransferQueries::class);

        $this->mock(StockTransferService::class, function ($mock): void {
            $mock->shouldReceive('markAsCancelled')
                ->once();
        });

        $stockTransferController = new StockTransferController($stockTransferQueries);

        $response = $stockTransferController->updateStatus($request, $stockTransferUpdateStatusData, 1);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('Status changed successfully.', $response->getSession()->all()['success']);
        $this->assertStringContainsString('admin/stock-transfers', $response->getTargetUrl());
    }
);

test(
    'It calls the updateStatus method while change to reject status while request order and returns a proper response',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession($companyId);

        [$stockTransfer, $storeOne, $storeManagerOne] = loadStockTransferLocationRelation(
            $companyId,
            StatusTypes::OPEN->value,
            StockTransferTypes::REQUEST_ORDER->value
        );

        $stockTransferUpdateStatusData = new StockTransferUpdateStatusData(StatusTypes::REJECTED->value, 'remarks');

        $request = new Request();

        setAdminStatusIdAndUser($request);

        $stockTransferQueries = $this->mock(StockTransferQueries::class, function ($mock) use (
            $stockTransfer
        ): void {
            $mock->shouldReceive('getByIdWithItemsBatchesAndUnits')
                ->once()
                ->andReturn($stockTransfer);
        });

        $this->mock(StockTransferService::class, function ($mock): void {
            $mock->shouldReceive('requestOrderMarkAsRejected')
                ->once();
        });

        $stockTransferController = new StockTransferController($stockTransferQueries);

        $response = $stockTransferController->updateStatus($request, $stockTransferUpdateStatusData, 1);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('Status changed successfully.', $response->getSession()->all()['success']);
        $this->assertStringContainsString('admin/stock-transfers', $response->getTargetUrl());
    }
);

test(
    'It calls the updateStatus method while change to reject status while transfer order and returns a proper response',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession($companyId);

        [$stockTransfer, $storeOne, $storeManagerOne] = loadStockTransferLocationRelation(
            $companyId,
            StatusTypes::OPEN->value,
            StockTransferTypes::TRANSFER_ORDER->value
        );

        $stockTransferUpdateStatusData = new StockTransferUpdateStatusData(StatusTypes::REJECTED->value, null);

        $request = new Request();

        setAdminStatusIdAndUser($request);

        $stockTransferQueries = $this->mock(StockTransferQueries::class, function ($mock) use (
            $stockTransfer
        ): void {
            $mock->shouldReceive('getByIdWithItemsBatchesAndUnits')
                ->once()
                ->andReturn($stockTransfer);
        });

        $this->mock(StockTransferService::class, function ($mock): void {
            $mock->shouldReceive('revertBackInventory')
                ->once();
        });

        $stockTransferController = new StockTransferController($stockTransferQueries);

        $response = $stockTransferController->updateStatus($request, $stockTransferUpdateStatusData, 1);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('Status changed successfully.', $response->getSession()->all()['success']);
        $this->assertStringContainsString('admin/stock-transfers', $response->getTargetUrl());
    }
);

test(
    'exception is thrown if status is shipped and try to reject.',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession($companyId);

        $stockTransfer = StockTransfer::factory()->make([
            'company_id' => $companyId,
            'source_location_id' => 1,
            'destination_location_id' => 2,
            'requested_by_id' => 1,
            'stock_transfer_reason_id' => null,
            'status' => StatusTypes::SHIPPED->value,
        ]);

        $stockTransferUpdateStatusData = new StockTransferUpdateStatusData(StatusTypes::REJECTED->value, null);

        $request = new Request();
        setAdminStatusIdAndUser($request);

        $stockTransferQueries = $this->mock(StockTransferQueries::class, function ($mock) use (
            $stockTransfer
        ): void {
            $mock->shouldReceive('getByIdWithItemsBatchesAndUnits')
                ->once()
                ->andReturn($stockTransfer);
        });

        $stockTransferController = new StockTransferController($stockTransferQueries);

        $stockTransferController->updateStatus($request, $stockTransferUpdateStatusData, 1);
    }
)->throws(RedirectBackWithErrorException::class);

test(
    'It calls the updateStatus method while change to transit IN status and returns a proper response',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession($companyId);

        $stockTransferUpdateStatusData = new StockTransferUpdateStatusData(StatusTypes::TRANSIT_IN->value, null);

        $request = new Request();

        setAdminStatusIdAndUser($request);

        $this->mock(StockTransferService::class, function ($mock): void {
            $mock->shouldReceive('markAsTransitIn')
                ->once();
        });

        $stockTransferQueries = new StockTransferQueries();

        $stockTransferController = new StockTransferController($stockTransferQueries);

        $response = $stockTransferController->updateStatus($request, $stockTransferUpdateStatusData, 1);
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('Status changed successfully.', $response->getSession()->all()['success']);
        $this->assertStringContainsString('admin/stock-transfers', $response->getTargetUrl());
    }
);

test(
    'It calls the updateStatus method while change to transit OUT status and returns a proper response',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession($companyId);

        $stockTransferUpdateStatusData = new StockTransferUpdateStatusData(StatusTypes::TRANSIT_OUT->value, null);

        $request = new Request();

        setAdminStatusIdAndUser($request);

        $this->mock(StockTransferService::class, function ($mock): void {
            $mock->shouldReceive('markAsTransitOut')
                ->once();
        });

        $stockTransferQueries = new StockTransferQueries();

        $stockTransferController = new StockTransferController($stockTransferQueries);

        $response = $stockTransferController->updateStatus($request, $stockTransferUpdateStatusData, 1);
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('Status changed successfully.', $response->getSession()->all()['success']);
        $this->assertStringContainsString('admin/stock-transfers', $response->getTargetUrl());
    }
);

test(
    'It calls the updateReceivedDateAndStatus method while change to received status and returns a proper response',
    function (): void {
        setCompanyIdInSession();

        $request = new Request([
            'received_date' => Carbon::now()->format('Y-m-d'),
        ]);

        setAdminStatusIdAndUser($request);

        $stockTransferQueries = resolve(StockTransferQueries::class);

        $this->mock(StockTransferService::class, function ($mock): void {
            $mock->shouldReceive('markAsReceived')
                ->once();
        });

        $stockTransferController = new StockTransferController($stockTransferQueries);

        $stockTransferController->updateReceivedDateAndStatus($request, 1);
    }
);

test(
    'It calls the updateStatus method while change to discrepancy status and returns a proper response',
    function (): void {
        setCompanyIdInSession();
        $stockTransferUpdateStatusData = new StockTransferUpdateStatusData(StatusTypes::DISCREPANCY->value, null);

        $request = new Request();

        setAdminStatusIdAndUser($request);

        $stockTransferQueries = resolve(StockTransferQueries::class);

        $this->mock(StockTransferService::class, function ($mock): void {
            $mock->shouldReceive('markAsDiscrepancy')
                ->once();
        });
        $stockTransferController = new StockTransferController($stockTransferQueries);

        $response = $stockTransferController->updateStatus($request, $stockTransferUpdateStatusData, 1);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals(
            'There is a discrepancy in the stock transfer. Stock will be transferred only when the stock transfer is closed.',
            $response->getSession()->all()['success']
        );
        $this->assertStringContainsString('admin/stock-transfers', $response->getTargetUrl());
    }
);

test(
    'It calls the updateReceivedQuantityAndDiscrepancyStatusByIdAndStockTransferId method of StockTransferItemQueries class',
    function (): void {
        setCompanyIdInSession();

        $request = new Request([
            'item_id' => 1,
            'received_quantity' => 2,
        ]);

        $stockTransfer = StockTransfer::factory()->make([
            'company_id' => 1,
            'source_location_id' => 1,
            'destination_location_id' => 2,
            'requested_by_id' => 1,
            'stock_transfer_reason_id' => null,
            'status' => StatusTypes::RECEIVED->value,
        ]);

        $this->mock(StockTransferItemQueries::class, function ($mock): void {
            $mock->shouldReceive('updateReceivedQuantityAndDiscrepancyStatusByIdAndStockTransferId')
                ->once();
            $mock->shouldReceive('removeDiscrepancyProof')
                ->once();
        });

        $this->mock(StockTransferQueries::class, function ($mock) use ($stockTransfer): void {
            $mock->shouldReceive('getLocationAndStatusById')
                ->once()
                ->andReturn($stockTransfer);
            $mock->shouldReceive('setUpdatedAt')
                ->once();
        });

        $stockTransferQueries = resolve(StockTransferQueries::class);

        $stockTransferController = new StockTransferController($stockTransferQueries, 1);

        $stockTransferController->updateReceivedQuantities($request, 1);
    }
);

test('It calls the updateStatus method while change to close status and returns a proper response', function (): void {
    $companyId = 1;
    setCompanyIdInSession($companyId);

    [$stockTransfer, $storeOne, $storeManagerOne] = loadStockTransferLocationRelation(
        $companyId,
        StatusTypes::RECEIVED->value,
        StockTransferTypes::TRANSFER_ORDER->value
    );

    $request = new Request([
        'status_id' => StatusTypes::CLOSED->value,
    ]);

    $stockTransferItem = StockTransferItem::factory()->make([
        'id' => 1,
        'stock_transfer_id' => $stockTransfer->id,
        'product_id' => 1,
        'package_type_id' => null,
        'quantity' => 1,
        'received_quantity' => 1,
    ]);

    $stockTransfer->items = collect([$stockTransferItem]);

    setAdminStatusIdAndUser($request);

    $stockTransferQueries = $this->mock(StockTransferQueries::class, function ($mock) use ($stockTransfer): void {
        $mock->shouldReceive('getByIdWithItemsAndUnits')
            ->once()
            ->andReturn($stockTransfer);
    });

    $this->mock(StockTransferService::class, function ($mock): void {
        $mock->shouldReceive('closeTransfer')
            ->once();
    });

    $stockTransferController = new StockTransferController($stockTransferQueries);

    $response = $stockTransferController->closeStockTransfer($request, 1);

    $this->assertEquals(302, $response->getStatusCode());
    $this->assertEquals('Stock Transfer Closed Successfully.', $response->getSession()->all()['success']);
    $this->assertStringContainsString('admin/stock-transfers', $response->getTargetUrl());
});

test(
    'It calls the closeDiscrepancy method while keep positive discrepancy and returns a proper response',
    function (): void {
        $request = new Request([
            'stock_transfer_items' => [
                [
                    'id' => 1,
                    'product_id' => 1,
                    'batch_number' => 'A12323',
                ],
            ],
        ]);

        $user = Admin::factory()->make([
            'id' => 1,
            'employee_id' => 1,
        ]);
        $request->setUserResolver(fn (): Admin => $user);

        $companyId = 1;

        setCompanyIdInSession($companyId);
        [$storeOne, $storeTwo, $storeManagerOne] = seedStoreAndStoreManagers($companyId);
        $stockTransfer = StockTransfer::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
            'source_location_id' => $storeOne->id,
            'destination_location_id' => $storeTwo->id,
            'requested_by_id' => 1,
            'stock_transfer_reason_id' => null,
            'status' => StatusTypes::DISCREPANCY->value,
        ]);

        $stockTransferItem = StockTransferItem::factory()->make([
            'id' => 1,
            'stock_transfer_id' => $stockTransfer->id,
            'product_id' => 1,
            'package_type_id' => null,
        ]);

        $stockTransfer->items = collect([$stockTransferItem]);

        $sourceInventory = Inventory::factory()->make([
            'id' => 1,
            'product_id' => 1,
            'location_id' => 1,
            'stock' => 5,
        ]);

        $sourceInventories = collect([$sourceInventory->toArray()]);

        $product = commonGetProductDetails();
        $products = collect([$product->toArray()]);

        $stockTransferQueries = $this->mock(StockTransferQueries::class, function ($mock) use (
            $stockTransfer
        ): void {
            $mock->shouldReceive('getByIdWithItemsBatchesAndUnits')
                ->once()
                ->with(1, 1)
                ->andReturn($stockTransfer);
        });

        $this->mock(StockTransferCheckRequestService::class, function ($mock): void {
            $mock->shouldReceive('checkClosingDiscrepancyRequestBatchDetails')
                ->once();
        });

        $this->mock(StockTransferService::class, function ($mock) use ($products, $sourceInventories): void {
            $mock->shouldReceive('closeDiscrepancy')
                ->once();
            $mock->shouldReceive('fetchProductsWithArchivedAndSourceInventories')
                ->once()
                ->andReturn([$products, $sourceInventories, collect([])]);
        });

        $stockTransferController = new StockTransferController($stockTransferQueries);

        $response = $stockTransferController->closeDiscrepancy($request, 1);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('Stock Transfer closed Successfully.', $response->getSession()->all()['success']);
        $this->assertStringContainsString('admin/stock-transfers', $response->getTargetUrl());
    }
);

test(
    'It calls the closeDiscrepancy method when there is no discrepancy and returns a proper response',
    function (): void {
        $request = new Request([
            'stock_transfer_items' => [
                [
                    'id' => 1,
                    'product_id' => 1,
                ],
            ],
        ]);

        $user = Admin::factory()->make([
            'id' => 1,
            'employee_id' => 1,
        ]);
        $request->setUserResolver(fn (): Admin => $user);

        $companyId = 1;

        [$storeOne, $storeTwo, $storeManagerOne] = seedStoreAndStoreManagers($companyId);
        $stockTransfer = StockTransfer::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
            'source_location_id' => $storeOne->id,
            'destination_location_id' => $storeTwo->id,
            'requested_by_id' => 1,
            'stock_transfer_reason_id' => null,
            'status' => StatusTypes::DISCREPANCY->value,
        ]);

        $stockTransferItem = StockTransferItem::factory()->make([
            'id' => 1,
            'stock_transfer_id' => $stockTransfer->id,
            'product_id' => 1,
            'package_type_id' => null,
        ]);

        $stockTransfer->items = collect([$stockTransferItem]);

        setCompanyIdInSession($companyId);

        $sourceInventory = Inventory::factory()->make([
            'id' => 1,
            'product_id' => 1,
            'location_id' => 1,
            'stock' => 5,
        ]);

        $sourceInventories = collect([$sourceInventory->toArray()]);

        $product = commonGetProductDetails();
        $products = collect([$product->toArray()]);

        $this->mock(StockTransferCheckRequestService::class, function ($mock): void {
            $mock->shouldReceive('checkClosingDiscrepancyRequestBatchDetails')
                ->once();
        });

        $stockTransferQueries = $this->mock(StockTransferQueries::class, function ($mock) use (
            $stockTransfer
        ): void {
            $mock->shouldReceive('getByIdWithItemsBatchesAndUnits')
                ->once()
                ->andReturn($stockTransfer);
        });

        $this->mock(StockTransferService::class, function ($mock) use ($products, $sourceInventories): void {
            $mock->shouldReceive('closeDiscrepancy')
                ->once();
            $mock->shouldReceive('fetchProductsWithArchivedAndSourceInventories')
                ->once()
                ->andReturn([$products, $sourceInventories, collect([])]);
        });

        $stockTransferController = new StockTransferController($stockTransferQueries);

        $response = $stockTransferController->closeDiscrepancy($request, 1);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('Stock Transfer closed Successfully.', $response->getSession()->all()['success']);
        $this->assertStringContainsString('admin/stock-transfers', $response->getTargetUrl());
    }
);

test('It calls the setReceivedQuantitySameAsQuantity method of the StockTransferItemQueries class.', function (): void {
    setCompanyIdInSession();

    $stockTransferQueries = resolve(StockTransferQueries::class);

    $this->mock(StockTransferItemQueries::class, function ($mock): void {
        $mock->shouldReceive('setReceivedQuantitySameAsQuantity')
            ->with(1, 1)
            ->once();
    });

    $stockTransferController = new StockTransferController($stockTransferQueries);

    $response = $stockTransferController->setReceivedQuantitySameAsQuantity(1);

    $this->assertEquals(302, $response->getStatusCode());
    $this->assertEquals(
        'The received quantity has been successfully set to match the specified quantity.',
        $response->getSession()->all()['success']
    );
});

test(
    'It calls the uploadDiscrepancyProof method of StockTransferItemQueries class and returns a proper response',
    function (): void {
        setCompanyIdInSession();

        $stockTransfer = StockTransfer::factory()->make([
            'company_id' => 1,
            'source_location_id' => 1,
            'stock_transfer_reason_id' => null,
            'destination_location_id' => 2,
            'requested_by_id' => 1,
            'status' => StatusTypes::RECEIVED->value,
        ]);

        Storage::fake('public');

        $uploadedFile = UploadedFile::fake()->image('avatar.jpg');

        $request = new Request([
            'discrepancy_proof' => $uploadedFile,
        ]);

        $stockTransferQueries = $this->mock(StockTransferQueries::class, function ($mock) use (
            $stockTransfer
        ): void {
            $mock->shouldReceive('getLocationAndStatusById')
                ->once()
                ->andReturn($stockTransfer);
            $mock->shouldReceive('setUpdatedAtById')
                ->once();
        });

        $this->mock(StockTransferItemQueries::class, function ($mock): void {
            $mock->shouldReceive('uploadDiscrepancyProof')
                ->once();
        });

        $stockTransferController = new StockTransferController($stockTransferQueries);

        $response = $stockTransferController->discrepancyProof($request, 1, 1);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals(
            'The discrepancy proof has been uploaded successfully.',
            $response->getSession()->all()['success']
        );
    }
);

test(
    'discrepancyProof method throws an exception when the status is not received while upload discrepancy proof',
    function (): void {
        setCompanyIdInSession();

        $stockTransfer = StockTransfer::factory()->make([
            'company_id' => 1,
            'source_location_id' => 1,
            'stock_transfer_reason_id' => null,
            'destination_location_id' => 2,
            'requested_by_id' => 1,
            'status' => StatusTypes::DRAFT->value,
        ]);

        Storage::fake('public');

        $uploadedFile = UploadedFile::fake()->image('avatar.jpg');

        $request = new Request([
            'discrepancy_proof' => $uploadedFile,
        ]);

        $stockTransferQueries = $this->mock(StockTransferQueries::class, function ($mock) use (
            $stockTransfer
        ): void {
            $mock->shouldReceive('getLocationAndStatusById')
                ->once()
                ->andReturn($stockTransfer);
        });

        $stockTransferController = new StockTransferController($stockTransferQueries);

        $stockTransferController->discrepancyProof($request, 1, 1);
    }
)->throws(RedirectBackWithErrorException::class);

test(
    'It calls the removeDiscrepancyProof method of StockTransferItemQueries class and returns a proper response',
    function (): void {
        $this->mock(StockTransferItemQueries::class, function ($mock): void {
            $mock->shouldReceive('removeDiscrepancyProof')
                ->once();
        });

        $stockTransferController = new StockTransferController(new StockTransferQueries());

        $response = $stockTransferController->removeDiscrepancyProof(1);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('Discrepancy proof removed successfully.', $response->getSession()->all()['success']);
    }
);

test(
    'getStockTransferTypes method returns Stock Transfer Types response.',
    function (): void {
        $stockTransferController = new StockTransferController(new StockTransferQueries());
        $response = $stockTransferController->getStockTransferTypes();
        expect($response['types'][0])
            ->toHaveKey('id', StockTransferTypes::REQUEST_ORDER->value);
    }
);

test('It calls the exportStockTransfers method and returns a proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 'test',
        'stock_transfer_date' => null,
        'location_id' => 1,
        'transfer_type' => 1,
        'select_status' => StatusTypes::getValueByCaseName('CANCELLED'),
        'stock_transfer_id' => null,
        'dashboard_transfer_type' => null,
    ];

    $stockTransferQueries = $this->mock(StockTransferQueries::class, function ($mock) use (
        $requestParameter,
        $companyId
    ): void {
        $mock->shouldReceive('getStockTransfersExport')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(collect(new StockTransfer()));
    });

    $stockTransferController = new StockTransferController($stockTransferQueries);

    $requestParameter['stock_transfer_number'] = 'test';
    $response = $stockTransferController->exportStockTransfers('filename.csv', new Request($requestParameter));

    $this->assertEquals(200, $response->getStatusCode());
    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});

it('can print stock transfer', function (): void {
    $stockTransferController = new StockTransferController(new StockTransferQueries());

    setCompanyIdInSession();

    $this->mock(StockTransferPrintService::class, function ($mock): void {
        $mock->shouldReceive('printStockTransfer')
            ->once();
    });

    $response = $stockTransferController->printStockTransfer(1, 'IN');

    expect($response)->toBe('');
});

it(
    'It calls the updateAdditionalItems method of stockTransferService class when product variant is false',
    function (): void {
        Config::set('app.product_variant', false);

        $additionalItems = [
            'additional_items' => [
                [
                    'stock_transfer_id' => 1,
                    'product_id' => 1,
                    'has_batch' => 1,
                    'package_type_id' => 1,
                    'quantity' => 1,
                    'received_quantity' => 1,
                    'package_quantity' => 1,
                    'package_total_quantity' => 1,
                    'remarks' => 'abcd',
                ],
            ],
        ];

        $request = new Request($additionalItems);

        $admin = Admin::factory()->make([
            'id' => 1,
            'employee_id' => 1,
        ]);

        $request->setUserResolver(fn (): Admin => $admin);

        setCompanyIdInSession();

        $product = commonGetProductDetails();

        $stockTransfer = StockTransfer::factory()->make([
            'company_id' => 1,
            'source_location_id' => 1,
            'destination_location_id' => 2,
            'requested_by_id' => 1,
            'stock_transfer_reason_id' => null,
            'status' => StatusTypes::RECEIVED->value,
        ]);

        $this->mock(StockTransferService::class, function ($mock) use ($product): void {
            $mock->shouldReceive('updateAdditionalItems')
                ->once();
            $mock->shouldReceive('fetchDerivatives')
                ->once()
                ->andReturn(collect([]));
            $mock->shouldReceive('fetchProducts')
                ->once()
                ->andReturn(collect([$product]));
        });

        $this->mock(StockTransferCheckRequestService::class, function ($mock): void {
            $mock->shouldReceive('checkAdditionalItemsRequest')
                ->once();
        });

        $stockTransferQueries = $this->mock(StockTransferQueries::class, function ($mock) use ($stockTransfer): void {
            $mock->shouldReceive('getLocationAndStatusById')
                ->once()
                ->andReturn($stockTransfer);
        });

        $stockTransferController = new StockTransferController($stockTransferQueries);
        $stockTransferController->updateAdditionalItems($request, 1);
    }
);

it(
    'It calls the updateAdditionalItems method of stockTransferService class when product variant is true',
    function (): void {
        Config::set('app.product_variant', true);

        $additionalItems = [
            'additional_items' => [
                [
                    'stock_transfer_id' => 1,
                    'product_id' => 1,
                    'has_batch' => 1,
                    'package_type_id' => 1,
                    'quantity' => 1,
                    'received_quantity' => 1,
                    'package_quantity' => 1,
                    'package_total_quantity' => 1,
                    'remarks' => 'abcd',
                ],
            ],
        ];

        $request = new Request($additionalItems);

        $admin = Admin::factory()->make([
            'id' => 1,
            'employee_id' => 1,
        ]);

        $request->setUserResolver(fn (): Admin => $admin);

        setCompanyIdInSession();

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

        $stockTransfer = StockTransfer::factory()->make([
            'company_id' => 1,
            'source_location_id' => 1,
            'destination_location_id' => 2,
            'requested_by_id' => 1,
            'stock_transfer_reason_id' => null,
            'status' => StatusTypes::RECEIVED->value,
        ]);

        $this->mock(StockTransferService::class, function ($mock) use ($product): void {
            $mock->shouldReceive('updateAdditionalItems')
                ->once();
            $mock->shouldReceive('fetchDerivatives')
                ->once()
                ->andReturn(collect([]));
            $mock->shouldReceive('fetchProducts')
                ->once()
                ->andReturn(collect([$product]));
        });

        $this->mock(StockTransferCheckRequestService::class, function ($mock): void {
            $mock->shouldReceive('checkAdditionalItemsRequest')
                ->once();
        });

        $stockTransferQueries = $this->mock(StockTransferQueries::class, function ($mock) use ($stockTransfer): void {
            $mock->shouldReceive('getLocationAndStatusById')
                ->once()
                ->andReturn($stockTransfer);
        });

        $stockTransferController = new StockTransferController($stockTransferQueries);
        $stockTransferController->updateAdditionalItems($request, 1);
    }
);

test(
    'It calls the updateShippingDetailsAndMarkAsApproved method throws an error if request order and status is not open.',
    function (): void {
        $companyId = 1;

        setCompanyIdInSession($companyId);

        $stockTransfer = StockTransfer::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
            'transfer_type' => StockTransferTypes::REQUEST_ORDER->value,
            'source_location_id' => 1,
            'destination_location_id' => 1,
            'requested_by_id' => 1,
            'stock_transfer_reason_id' => null,
            'status' => StatusTypes::APPROVED->value,
        ]);

        $stockTransferItem = StockTransferItem::factory()->make([
            'id' => 1,
            'stock_transfer_id' => $stockTransfer->id,
            'product_id' => 1,
            'package_type_id' => null,
            'package_quantity' => null,
            'package_total_quantity' => null,
            'quantity' => 5,
            'requested_by_id' => null,
        ]);

        $packageType = PackageType::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
        ]);

        $stockTransfer->items = collect([$stockTransferItem]);

        $requestData = [
            'stock_transfer_items' => [
                [
                    'id' => $stockTransferItem->id,
                    'transfer_stock' => $stockTransferItem->quantity,
                    'package_quantity' => 1,
                    'package_total_quantity' => $stockTransferItem->quantity,
                    'package_type_id' => $packageType->id,
                    'product' => [
                        'id' => 1,
                        'has_batch' => false,
                    ],
                ],
            ],
        ];

        [$admin, $request] = setRequestUserForAdmin($requestData);

        $stockTransferQueries = resolve(StockTransferQueries::class);

        $stockTransferQueries = $this->mock(StockTransferQueries::class, function ($mock) use (
            $stockTransfer
        ): void {
            $mock->shouldReceive('getByIdWithItemsAndBatches')
                ->once()
                ->andReturn($stockTransfer);
        });

        $stockTransferController = new StockTransferController($stockTransferQueries);

        $stockTransferController->updateShippingDetailsAndMarkAsApproved($request, $stockTransfer->id);
    }
)->throws(RedirectBackWithErrorException::class);

test(
    'It calls the updateShippingDetailsAndMarkAsApproved method and return proper response.',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession($companyId);

        [$storeOne, $storeTwo, $storeManagerOne] = seedStoreAndStoreManagers($companyId);

        $stockTransfer = StockTransfer::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
            'transfer_type' => StockTransferTypes::REQUEST_ORDER->value,
            'source_location_id' => $storeOne->id,
            'destination_location_id' => $storeTwo->id,
            'requested_by_id' => 1,
            'stock_transfer_reason_id' => null,
            'status' => StatusTypes::OPEN->value,
        ]);

        $stockTransfer->sourceLocation = $storeOne;
        $stockTransfer->destinationLocation = $storeTwo;

        $stockTransferItem = StockTransferItem::factory()->make([
            'id' => 1,
            'stock_transfer_id' => $stockTransfer->id,
            'product_id' => 1,
            'package_type_id' => null,
            'package_quantity' => null,
            'package_total_quantity' => null,
            'quantity' => 5,
            'requested_by_id' => null,
        ]);

        $packageType = PackageType::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
        ]);

        $stockTransfer->items = collect([$stockTransferItem]);

        $request = new Request([
            'stock_transfer_items' => [
                [
                    'id' => $stockTransferItem->id,
                    'transfer_stock' => $stockTransferItem->quantity,
                    'package_quantity' => 1,
                    'package_total_quantity' => $stockTransferItem->quantity,
                    'package_type_id' => $packageType->id,
                    'product' => [
                        'id' => 1,
                        'has_batch' => true,
                    ],
                    'batch_details' => [
                        [
                            'batch_number' => 'A123',
                            'quantity' => 5,
                        ],
                    ],
                ],
            ],
        ]);

        $request->setUserResolver(fn (): StoreManager => $storeManagerOne);

        $stockTransferQueries = resolve(StockTransferQueries::class);

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $sequence = Sequence::factory()->make([
            'location_id' => $location->id,
            'type_id' => SequenceTypes::TIN->value,
            'number' => '00000001',
        ]);

        $sequence->location = $location;

        $product = commonGetProductDetails(true);
        $batch = Batch::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'product_id' => $product->id,
        ]);

        $this->mock(StockTransferService::class, function ($mock) use ($product, $batch): void {
            $mock->shouldReceive('updateShippingDetailsAndMarkAsApproved')
                ->once();
            $mock->shouldReceive('fetchProductsBatchesAndDerivatives')
                ->once()
                ->andReturn([collect([$product]), collect([$batch]), collect([])]);
        });

        $this->mock(StockTransferCheckRequestService::class, function ($mock): void {
            $mock->shouldReceive('checkShippingDetails')
                ->once();
        });

        $stockTransferQueries = $this->mock(StockTransferQueries::class, function ($mock) use (
            $stockTransfer
        ): void {
            $mock->shouldReceive('getByIdWithItemsAndBatches')
                ->once()
                ->andReturn($stockTransfer);
        });

        $stockTransferController = new StockTransferController($stockTransferQueries);

        $response = $stockTransferController->updateShippingDetailsAndMarkAsApproved($request, $stockTransfer->id);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals(
            'The specified stock transfer has been marked as approved successfully.',
            $response->getSession()->all()['success']
        );
    }
);

test('It calls the fetchStockTransferItemByStockTransferId method and returns a proper response', function (): void {
    $stockTransferQueries = new StockTransferQueries();
    setCompanyIdInSession();

    $this->mock(StockTransferItemQueries::class, function ($mock): void {
        $mock->shouldReceive('getByStockTransferId')
            ->once()
            ->with(1, 1)
            ->andReturn(new Collection([]));
    });

    $stockTransferController = new StockTransferController($stockTransferQueries);
    $response = $stockTransferController->fetchStockTransferItemByStockTransferId(1);

    $this->assertEquals(new Collection([]), $response['stock_transfer_items']->resource);
});

test('It calls the removeAdditionalItem method and returns a proper response', function (): void {
    $stockTransferQueries = new StockTransferQueries();

    $this->mock(StockTransferService::class, function ($mock): void {
        $mock->shouldReceive('removeAdditionalItem')
            ->once();
    });

    $stockTransferController = new StockTransferController($stockTransferQueries);
    $stockTransferController->removeAdditionalItem(1);
});

test('the validateTransitLocation method call if shipped by transit location', function (): void {
    $request = new Request();
    setAdminStatusIdAndUser($request);
    $companyId = 1;
    setCompanyIdInSession($companyId);

    [$stockTransfer, $storeOne, $storeManagerOne] = loadStockTransferLocationRelation(
        $companyId,
        StatusTypes::OPEN->value,
        StockTransferTypes::TRANSFER_ORDER->value
    );

    $stockTransferShippedData = new StockTransferShippedData(
        shipped_type: ShippedTypes::TRANSIT->value,
        location_id: '2',
    );

    $stockTransferQueries = new StockTransferQueries();

    $this->mock(StockTransferCheckRequestService::class, function ($mock): void {
        $mock->shouldReceive('validateTransitLocation')
            ->once();
    });

    $this->mock(StockTransferService::class, function ($mock): void {
        $mock->shouldReceive('markAsShippedOrTransit')
            ->once();
    });

    $stockTransferController = new StockTransferController($stockTransferQueries);
    $stockTransferController->markAsShippedOrTransit($request, $stockTransferShippedData, $stockTransfer->id);
});

test('the validateTransitLocation method not call if shipped by direct', function (): void {
    $request = new Request();
    setAdminStatusIdAndUser($request);
    $companyId = 1;
    setCompanyIdInSession($companyId);

    [$stockTransfer, $storeOne, $storeManagerOne] = loadStockTransferLocationRelation(
        $companyId,
        StatusTypes::OPEN->value,
        StockTransferTypes::TRANSFER_ORDER->value
    );

    $stockTransferShippedData = new StockTransferShippedData(
        shipped_type: ShippedTypes::DIRECT->value,
        location_id: null,
    );

    $stockTransferQueries = new StockTransferQueries();

    $this->mock(StockTransferCheckRequestService::class, function ($mock): void {
        $mock->shouldNotReceive('validateTransitLocation');
    });

    $this->mock(StockTransferService::class, function ($mock): void {
        $mock->shouldReceive('markAsShippedOrTransit')
            ->once();
    });

    $stockTransferController = new StockTransferController($stockTransferQueries);
    $stockTransferController->markAsShippedOrTransit($request, $stockTransferShippedData, $stockTransfer->id);
});

test('the fetchAggregateAverageDays method  call and return average days', function (): void {
    $stockTransferAverageLeadDays = StockTransferAverageLeadDays::factory()->make([
        'from_location_id' => 1,
        'to_location_id' => 1,
        'average_days' => 2,
    ]);

    $request = new Request([
        'source_location_id' => 1,
        'destination_location_id' => 1,
    ]);

    setAdminStatusIdAndUser($request);
    $companyId = 1;
    setCompanyIdInSession($companyId);

    $stockTransferQueries = new StockTransferQueries();

    $this->mock(StockTransferService::class, function ($mock) use ($stockTransferAverageLeadDays): void {
        $mock->shouldReceive('getAverageAggregateDays')
            ->once()
            ->andReturn([
                'success_ratio' => '100%',
                'aggregate_average_days' => $stockTransferAverageLeadDays->average_days,
            ]);
    });

    $stockTransferController = new StockTransferController($stockTransferQueries);
    $response = $stockTransferController->fetchAggregateAverageDays($request);

    expect($response)
        ->toHaveKey('aggregate_average_days');
});

function setAdminStatusIdAndUser(Request $request): void
{
    $admin = Admin::factory()->make([
        'id' => 1,
        'employee_id' => 1,
    ]);

    $request->setUserResolver(fn (): Admin => $admin);
}

function setSequenceAndStore(Location $location): Sequence
{
    $sequence = Sequence::factory()->make([
        'id' => 1,
        'location_id' => $location->id,
    ]);

    $sequence->location = $location;

    return $sequence;
}

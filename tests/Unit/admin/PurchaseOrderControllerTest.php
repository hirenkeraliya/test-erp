<?php

declare(strict_types=1);

use App\Domains\Admin\AdminQueries;
use App\Domains\PurchaseOrder\DataObjects\PurchaseOrderData;
use App\Domains\PurchaseOrder\Enums\Statuses;
use App\Domains\PurchaseOrder\PurchaseOrderQueries;
use App\Domains\PurchaseOrder\Resource\PurchaseOrderListResource;
use App\Domains\PurchaseOrder\Services\PurchaseOrderCheckRequestService;
use App\Domains\PurchaseOrder\Services\PurchaseOrderPrintService;
use App\Domains\PurchaseOrder\Services\PurchaseOrderService;
use App\Domains\PurchaseOrderItem\PurchaseOrderItemQueries;
use App\Exceptions\RedirectWithErrorException;
use App\Http\Controllers\Admin\PurchaseOrderController;
use App\Models\Admin;
use App\Models\PurchaseOrder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

test('It calls the fetchPurchaseOrderItemByPurchaseOrderId method and returns a proper response', function (): void {
    $purchaseOrderQueries = new PurchaseOrderQueries();
    setCompanyIdInSession();

    $this->mock(PurchaseOrderItemQueries::class, function ($mock): void {
        $mock->shouldReceive('getByPurchaseOrderId')
            ->once()
            ->with(1, 1)
            ->andReturn(new Collection([]));
    });

    $purchaseOrderController = new PurchaseOrderController($purchaseOrderQueries);
    $response = $purchaseOrderController->fetchPurchaseOrderItemByPurchaseOrderId(1);

    $this->assertEquals(new Collection([]), $response['purchase_order_items']->resource);
});

test('It calls the exportPurchaseOrderItems method and returns a proper response', function (): void {
    $purchaseOrderQueries = new PurchaseOrderQueries();
    setCompanyIdInSession();

    $this->mock(PurchaseOrderItemQueries::class, function ($mock): void {
        $mock->shouldReceive('getByPurchaseOrderId')
            ->once()
            ->with(1, 1)
            ->andReturn(new Collection([]));
    });

    $purchaseOrderController = new PurchaseOrderController($purchaseOrderQueries);
    $response = $purchaseOrderController->exportPurchaseOrderItems(1, 'filename.csv');
    $this->assertEquals(200, $response->getStatusCode());

    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});

test(
    'It calls the list query method of the purchase queries class and returns proper response',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession($companyId);

        $requestParameter = [
            'search_text' => '',
            'sort_by' => '',
            'sort_direction' => '',
            'per_page' => '',
            'order_type' => '',
            'select_status' => '',
            'date_range' => '',
            'location_id' => '',
            'external_location_id' => '',
            'order_number' => '',
        ];

        $this->mock(PurchaseOrderService::class, function ($mock): void {
            $mock->shouldReceive('fetchPurchaseOrders')
                ->once()
                ->andReturn([
                    'total_records' => 50,
                    'data' => new PurchaseOrderListResource(collect()),
                    'transferRequestStatusCounts' => null,
                    'purchaseRequestStatusCounts' => null,
                    'salesOrderStatusCounts' => null,
                    'purchaseOrderStatusCounts' => null,
                    'deliveryOrdersStatusCounts' => null,
                ]);
        });

        $purchaseOrderController = new PurchaseOrderController(new PurchaseOrderQueries());

        $response = $purchaseOrderController->fetchPurchaseOrders(new Request($requestParameter));

        $this->assertEquals(50, $response['total_records']);
        $this->assertEquals(collect([]), $response['data']->resource);
        expect($response)
            ->toHaveKeys([
                'transferRequestStatusCounts',
                'purchaseRequestStatusCounts',
                'salesOrderStatusCounts',
                'purchaseOrderStatusCounts',
                'deliveryOrdersStatusCounts',
            ]);
    }
);

test('It calls the store method of PurchaseOrderQueries class and returns a proper response', function (): void {
    $companyId = 1;
    setCompanyIdInSession($companyId);

    $request = new Request();

    $request->setUserResolver(fn (): Admin => new Admin([
        'employee_id' => 1,
    ]));

    $purchaseOrderData = new PurchaseOrderData(1, 1, 1, null, null, null, null, 1, []);

    $this->mock(PurchaseOrderCheckRequestService::class, function ($mock): void {
        $mock->shouldReceive('checkRequestDetails')
            ->once();
        $mock->shouldReceive('getProducts')
            ->once();
    });

    $this->mock(PurchaseOrderService::class, function ($mock): void {
        $mock->shouldReceive('savePurchaseOrder')
            ->once();
    });

    $purchaseOrderController = new PurchaseOrderController(new PurchaseOrderQueries());

    $response = $purchaseOrderController->store($purchaseOrderData);

    $this->assertEquals(302, $response->getStatusCode());
    $this->assertEquals('Purchase Request is created successfully.', $response->getSession()->all()['success']);
    $this->assertStringContainsString('admin/purchase-orders', $response->getTargetUrl());
});

test('It calls the update method of PurchaseOrderQueries class and returns a proper response', function (): void {
    $companyId = 1;
    setCompanyIdInSession($companyId);

    $purchaseOrderData = new PurchaseOrderData(1, 1, 2, null, null, null, null, 1, []);

    $this->mock(PurchaseOrderCheckRequestService::class, function ($mock): void {
        $mock->shouldReceive('checkRequestDetails')
            ->once();
        $mock->shouldReceive('getProducts')
            ->once();
    });

    $purchaseOrderQueries = $this->mock(PurchaseOrderQueries::class, function ($mock): void {
        $mock->shouldReceive('update')
            ->once();
    });

    $purchaseOrderController = new PurchaseOrderController($purchaseOrderQueries);

    $response = $purchaseOrderController->update($purchaseOrderData, 1);

    $this->assertEquals(302, $response->getStatusCode());
    $this->assertEquals('Purchase Order Request Update successfully.', $response->getSession()->all()['success']);
    $this->assertStringContainsString('admin/purchase-orders', $response->getTargetUrl());
});

test(
    'cancel method throws an exception when status is not draft',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession($companyId);

        $request = new Request();

        $admin = new Admin([
            'employee_id' => 1,
        ]);

        $request->setUserResolver(fn (): Admin => $admin);

        $purchaseOrder = PurchaseOrder::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'location_id' => 1,
            'created_by_company_id' => 1,
            'status' => Statuses::OPENED->value,
            'external_purchase_order_id' => null,
            'parent_purchase_order_id' => null,
            'external_company_id' => null,
            'external_location_id' => null,
        ]);

        $purchaseOrderQueries = $this->mock(PurchaseOrderQueries::class, function ($mock) use (
            $purchaseOrder
        ): void {
            $mock->shouldReceive('getByIdAndCompanyId')
                ->once()
                ->andReturn($purchaseOrder);
            $mock->shouldNotReceive('updateStatus');
        });

        $this->mock(AdminQueries::class, function ($mock) use ($admin): void {
            $mock->shouldReceive('loadEmployee')
                ->times(1)
                ->andReturn($admin);
        });

        $purchaseOrderController = new PurchaseOrderController($purchaseOrderQueries);

        $purchaseOrderController->cancel($request, $purchaseOrder->id);
    }
)->throws(RedirectWithErrorException::class);

test(
    'It calls the cancel method while change to status and returns a proper response',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession($companyId);

        $request = new Request();

        $admin = new Admin([
            'employee_id' => 1,
        ]);

        $request->setUserResolver(fn (): Admin => $admin);

        $purchaseOrder = PurchaseOrder::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'location_id' => 1,
            'created_by_company_id' => 1,
            'status' => Statuses::DRAFT->value,
            'external_purchase_order_id' => null,
            'parent_purchase_order_id' => null,
            'external_company_id' => null,
            'external_location_id' => null,
        ]);

        $this->mock(PurchaseOrderService::class, function ($mock): void {
            $mock->shouldReceive('checkMarkAsCanceled')
                ->once();
            $mock->shouldReceive('purchaseOrderMarkAsCanceled')
                ->once();
        });

        $purchaseOrderQueries = $this->mock(PurchaseOrderQueries::class, function ($mock) use (
            $purchaseOrder
        ): void {
            $mock->shouldReceive('getByIdAndCompanyId')
                ->once()
                ->andReturn($purchaseOrder);
        });

        $this->mock(AdminQueries::class, function ($mock) use ($admin): void {
            $mock->shouldReceive('loadEmployee')
                ->times(1)
                ->andReturn($admin);
        });

        $purchaseOrderController = new PurchaseOrderController($purchaseOrderQueries);

        $response = $purchaseOrderController->cancel($request, $purchaseOrder->id);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals(
            'The specified purchase order has been marked as canceled successfully',
            $response->getSession()->all()['success']
        );
        $this->assertStringContainsString('admin/purchase-orders', $response->getTargetUrl());
    }
);

test(
    'reject method throws an exception when status is not opened',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession($companyId);

        $request = new Request();

        $purchaseOrder = PurchaseOrder::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'location_id' => 1,
            'created_by_company_id' => 1,
            'status' => Statuses::DRAFT->value,
            'external_purchase_order_id' => null,
            'parent_purchase_order_id' => null,
            'external_company_id' => null,
            'external_location_id' => null,
        ]);

        $purchaseOrderQueries = $this->mock(PurchaseOrderQueries::class, function ($mock) use (
            $purchaseOrder
        ): void {
            $mock->shouldReceive('getByIdAndCompanyIdWithRelation')
                ->once()
                ->andReturn($purchaseOrder);
            $mock->shouldNotReceive('updateStatus');
        });

        $purchaseOrderController = new PurchaseOrderController($purchaseOrderQueries);

        $purchaseOrderController->reject($request, $purchaseOrder->id);
    }
)->throws(RedirectWithErrorException::class);

test(
    'It calls the reject method while change to status and returns a proper response',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession($companyId);

        $request = new Request();

        $admin = new Admin([
            'employee_id' => 1,
        ]);

        $request->setUserResolver(fn (): Admin => $admin);

        $purchaseOrder = PurchaseOrder::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'location_id' => 1,
            'created_by_company_id' => null,
            'status' => Statuses::OPENED->value,
            'external_purchase_order_id' => null,
            'parent_purchase_order_id' => null,
            'external_company_id' => null,
            'external_location_id' => null,
        ]);

        $purchaseOrderQueries = $this->mock(PurchaseOrderQueries::class, function ($mock) use (
            $purchaseOrder
        ): void {
            $mock->shouldReceive('getByIdAndCompanyIdWithRelation')
                ->once()
                ->andReturn($purchaseOrder);
        });

        $this->mock(PurchaseOrderService::class, function ($mock): void {
            $mock->shouldReceive('purchaseOrderMarkAsRejected')
                ->once();
            $mock->shouldReceive('checkMarkAsRejected')
                ->once();
        });

        $this->mock(AdminQueries::class, function ($mock) use ($admin): void {
            $mock->shouldReceive('loadEmployee')
                ->times(1)
                ->andReturn($admin);
        });

        $purchaseOrderController = new PurchaseOrderController($purchaseOrderQueries);

        $response = $purchaseOrderController->reject($request, $purchaseOrder->id);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals(
            'The specified purchase order has been marked as rejected successfully',
            $response->getSession()->all()['success']
        );
        $this->assertStringContainsString('admin/purchase-orders', $response->getTargetUrl());
    }
);

test(
    'open method throws an exception when status is not draft',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession($companyId);

        $request = new Request();

        $purchaseOrder = PurchaseOrder::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'location_id' => 1,
            'created_by_company_id' => 1,
            'status' => Statuses::OPENED->value,
            'external_purchase_order_id' => null,
            'parent_purchase_order_id' => null,
            'external_company_id' => null,
            'external_location_id' => null,
        ]);

        $purchaseOrderQueries = $this->mock(PurchaseOrderQueries::class, function ($mock) use (
            $purchaseOrder
        ): void {
            $mock->shouldReceive('getByIdAndCompanyIdWithRelation')
                ->once()
                ->andReturn($purchaseOrder);
            $mock->shouldNotReceive('updateStatus');
        });

        $purchaseOrderController = new PurchaseOrderController($purchaseOrderQueries);

        $purchaseOrderController->open($request, $purchaseOrder->id);
    }
)->throws(RedirectWithErrorException::class);

test(
    'It calls the open method while change to status and returns a proper response',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession($companyId);

        $request = new Request();

        $admin = new Admin([
            'employee_id' => 1,
        ]);

        $request->setUserResolver(fn (): Admin => $admin);

        $purchaseOrder = PurchaseOrder::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'location_id' => 1,
            'created_by_company_id' => null,
            'status' => Statuses::DRAFT->value,
            'external_purchase_order_id' => null,
            'parent_purchase_order_id' => null,
            'external_company_id' => null,
            'external_location_id' => null,
        ]);

        $data = PurchaseOrder::factory()->make([
            'external_location_id' => 1,
            'company_id' => 1,
            'location_id' => 1,
            'status' => Statuses::OPENED->value,
            'external_purchase_order_id' => 1,
            'parent_purchase_order_id' => 1,
            'external_company_id' => 1,
            'purchase_order_id' => $purchaseOrder->id,
            'created_by_company_id' => 1,
        ])->toArray();

        $data['items'] = [];

        $purchaseOrderQueries = $this->mock(PurchaseOrderQueries::class, function ($mock) use (
            $purchaseOrder
        ): void {
            $mock->shouldReceive('getByIdAndCompanyIdWithRelation')
                ->once()
                ->andReturn($purchaseOrder);
        });

        $this->mock(PurchaseOrderService::class, function ($mock): void {
            $mock->shouldReceive('openPurchaseOrderAndSyncExternalData')
                ->once()
                ->andReturn(1);
            $mock->shouldReceive('postAutoApproveExternalSalesOrder')
                ->once();
        });

        $this->mock(AdminQueries::class, function ($mock) use ($admin): void {
            $mock->shouldReceive('loadEmployee')
                ->times(1)
                ->andReturn($admin);
        });

        $purchaseOrderController = new PurchaseOrderController($purchaseOrderQueries);

        $response = $purchaseOrderController->open($request, $purchaseOrder->id);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals(
            'The specified purchase order has been marked as open successfully',
            $response->getSession()->all()['success']
        );
        $this->assertStringContainsString('admin/purchase-orders', $response->getTargetUrl());
    }
);

test(
    'approve method throws an exception when status is not open',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession($companyId);

        $request = new Request();

        $purchaseOrder = PurchaseOrder::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'location_id' => 1,
            'created_by_company_id' => null,
            'status' => Statuses::DRAFT->value,
            'external_purchase_order_id' => null,
            'parent_purchase_order_id' => null,
            'external_company_id' => null,
            'external_location_id' => null,
        ]);

        $purchaseOrderQueries = $this->mock(PurchaseOrderQueries::class, function ($mock) use (
            $purchaseOrder
        ): void {
            $mock->shouldReceive('getByIdAndCompanyIdWithRelation')
                ->once()
                ->andReturn($purchaseOrder);
        });

        $purchaseOrderController = new PurchaseOrderController($purchaseOrderQueries);

        $purchaseOrderController->approve($request, $purchaseOrder->id);
    }
)->throws(
    HttpException::class,
    'At this moment, we are unable to approve the purchase order, as it is not in an open status.'
);

test(
    'It calls the approve method while change to status and returns a proper response',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession($companyId);

        $request = new Request();

        $admin = new Admin([
            'employee_id' => 1,
        ]);

        $request->setUserResolver(fn (): Admin => $admin);

        $purchaseOrder = PurchaseOrder::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'location_id' => 1,
            'created_by_company_id' => null,
            'status' => Statuses::OPENED->value,
            'external_purchase_order_id' => null,
            'parent_purchase_order_id' => null,
            'external_company_id' => null,
            'external_location_id' => null,
        ]);

        $this->mock(AdminQueries::class, function ($mock) use ($admin): void {
            $mock->shouldReceive('loadEmployee')
                ->times(1)
                ->andReturn($admin);
        });

        $purchaseOrderQueries = $this->mock(PurchaseOrderQueries::class, function ($mock) use (
            $purchaseOrder
        ): void {
            $mock->shouldReceive('getByIdAndCompanyIdWithRelation')
                ->once()
                ->andReturn($purchaseOrder);
        });

        $this->mock(PurchaseOrderService::class, function ($mock): void {
            $mock->shouldReceive('checkPurchaseOrderApprove')
                ->once();
            $mock->shouldReceive('purchaseOrderApprove')
                ->once();
        });

        $purchaseOrderController = new PurchaseOrderController($purchaseOrderQueries);

        $response = $purchaseOrderController->approve($request, $purchaseOrder->id);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals(
            'The specified purchase order has been marked as approved successfully',
            $response->getSession()->all()['success']
        );
        $this->assertStringContainsString('admin/purchase-orders', $response->getTargetUrl());
    }
);

test(
    'the print method and returns the string',
    function (): void {
        setCompanyIdInSession();

        $this->mock(PurchaseOrderPrintService::class, function ($mock): void {
            $mock->shouldReceive('print')
                ->once();
        });
        $purchaseOrderController = new PurchaseOrderController(new PurchaseOrderQueries());
        $response = $purchaseOrderController->print(1);
        expect($response)->toBeString();
    }
);

test('It calls the exportPurchaseOrders method and returns a proper response', function (): void {
    $companyId = 1;
    setCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => '',
        'sort_by' => '',
        'sort_direction' => '',
        'per_page' => '',
        'order_type' => '',
        'select_status' => '',
        'date_range' => '',
        'location_id' => '',
        'external_location_id' => '',
        'order_number' => '',
    ];

    $purchaseOrderQueries = $this->mock(PurchaseOrderQueries::class, function ($mock) use (
        $requestParameter,
        $companyId
    ): void {
        $mock->shouldReceive('exportPurchaseOrder')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(new Collection([]));
    });

    $purchaseOrderController = new PurchaseOrderController($purchaseOrderQueries);

    $response = $purchaseOrderController->exportPurchaseOrders('filename.csv', new Request($requestParameter));

    $this->assertEquals(200, $response->getStatusCode());

    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});

<?php

declare(strict_types=1);

use App\Domains\PurchaseOrderFulfillment\Enums\FulfillmentStatuses;
use App\Domains\PurchaseOrderFulfillment\PurchaseOrderFulfillmentQueries;
use App\Domains\PurchaseOrderInvoice\Enums\InvoiceStatuses;
use App\Domains\PurchaseOrderInvoice\PurchaseOrderInvoiceQueries;
use App\Domains\PurchaseOrderInvoice\Services\PurchaseOrderInvoiceService;
use App\Domains\PurchaseOrderInvoice\Services\PurchaseOrderPrintInvoiceService;
use App\Domains\PurchaseOrderItem\PurchaseOrderItemQueries;
use App\Http\Controllers\Admin\PurchaseOrderInvoiceController;
use App\Models\Admin;
use App\Models\PurchaseOrderFulfillment;
use App\Models\PurchaseOrderInvoice;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

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
            'select_status' => '',
            'date_range' => '',
            'location_id' => null,
        ];

        $purchaseOrderInvoiceQueries = $this->mock(PurchaseOrderInvoiceQueries::class, function ($mock) use (
            $requestParameter,
            $companyId
        ): void {
            $mock->shouldReceive('listQuery')
                ->once()
                ->with($requestParameter, $companyId)
                ->andReturn(new LengthAwarePaginator([], 50, 15));
            $mock->shouldReceive('allInvoiceStatusCount')
              ->once()
              ->with($requestParameter, $companyId)
              ->andReturn(new Collection([]));
        });

        $purchaseOrderInvoiceController = new PurchaseOrderInvoiceController($purchaseOrderInvoiceQueries);

        $response = $purchaseOrderInvoiceController->fetchPurchaseOrderInvoices(new Request($requestParameter));

        $this->assertEquals(50, $response['total_records']);
        $this->assertEquals(collect([]), $response['data']->resource);
    }
);

test(
    'the print method and returns the string',
    function (): void {
        setCompanyIdInSession();

        $this->mock(PurchaseOrderPrintInvoiceService::class, function ($mock): void {
            $mock->shouldReceive('printInvoice')
                ->once();
        });
        $purchaseOrderInvoiceController = new PurchaseOrderInvoiceController(new PurchaseOrderInvoiceQueries());
        $response = $purchaseOrderInvoiceController->print(1);
        expect($response)->toBeString();
    }
);

test(
    'It calls the fulfillmentDetails method of the purchase queries class and returns proper response',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession($companyId);

        $this->mock(PurchaseOrderFulfillmentQueries::class, function ($mock) use ($companyId): void {
            $mock->shouldReceive('getFulfillmentDetailsByOrderNumber')
              ->once()
              ->with(1, $companyId)
              ->andReturn(new Collection([]));
        });

        $purchaseOrderInvoiceController = new PurchaseOrderInvoiceController(new PurchaseOrderInvoiceQueries());

        $response = $purchaseOrderInvoiceController->fulfillmentDetails(1);

        $this->assertEquals(collect([]), $response['purchaseOrderFulfillments']);
    }
);

test(
    'sent method throws an exception when status is not draft',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession($companyId);

        $request = new Request();

        $purchaseOrderInvoice = PurchaseOrderInvoice::factory()->make([
            'id' => 1,
            'purchase_order_id' => 1,
            'created_by_company_id' => null,
            'status' => InvoiceStatuses::CANCELLED->value,
        ]);

        $purchaseOrderInvoiceQueries = $this->mock(PurchaseOrderInvoiceQueries::class, function ($mock) use (
            $purchaseOrderInvoice
        ): void {
            $mock->shouldReceive('getByIdForSent')
                ->once()
                ->andReturn($purchaseOrderInvoice);
        });

        $purchaseOrderInvoiceController = new PurchaseOrderInvoiceController($purchaseOrderInvoiceQueries);

        $purchaseOrderInvoiceController->sent($request, $purchaseOrderInvoice->id);
    }
)->throws(
    HttpException::class,
    'Sending the purchase order invoice is currently unavailable since it is not in a draft status.'
);

test(
    'It calls the sent method while change to status and returns a proper response',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession($companyId);

        $admin = Admin::factory()->make([
            'id' => 1,
            'employee_id' => 1,
        ]);

        $request = new Request();

        $request->setUserResolver(fn (): Admin => $admin);

        $purchaseOrderInvoice = PurchaseOrderInvoice::factory()->make([
            'id' => 1,
            'purchase_order_id' => 1,
            'created_by_company_id' => null,
            'status' => InvoiceStatuses::DRAFT->value,
        ]);

        $fulfillments = PurchaseOrderFulfillment::factory()->make([
            'id' => 1,
            'purchase_order_id' => 1,
            'purchase_order_invoice_id' => 1,
            'delivery_order_number' => '1234567',
            'status' => FulfillmentStatuses::DRAFT->value,
        ]);

        $purchaseOrderInvoice->fulfillments = collect($fulfillments->toArray());

        $purchaseOrderInvoice->purchase_order_invoice_id = 1;
        $purchaseOrderInvoice->external_purchase_order_invoice_id = $purchaseOrderInvoice->id;

        $this->mock(PurchaseOrderInvoiceService::class, function ($mock) use ($purchaseOrderInvoice): void {
            $mock->shouldReceive('purchaseOrderInvoiceSent')
                ->once()
                ->andReturn($purchaseOrderInvoice->toArray());
        });

        $purchaseOrderInvoiceQueries = $this->mock(PurchaseOrderInvoiceQueries::class, function ($mock) use (
            $purchaseOrderInvoice
        ): void {
            $mock->shouldReceive('getByIdForSent')
                ->once()
                ->andReturn($purchaseOrderInvoice);
        });

        $purchaseOrderInvoiceController = new PurchaseOrderInvoiceController($purchaseOrderInvoiceQueries);

        $response = $purchaseOrderInvoiceController->sent($request, $purchaseOrderInvoice->id);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals(
            'Invoice has been marked as Sent successfully',
            $response->getSession()->all()['success']
        );
        $this->assertStringContainsString('admin/purchase-order-invoices', $response->getTargetUrl());
    }
);

test(
    'paid method throws an exception when status is not received',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession($companyId);

        $request = new Request();

        $purchaseOrderInvoice = PurchaseOrderInvoice::factory()->make([
            'id' => 1,
            'purchase_order_id' => 1,
            'created_by_company_id' => null,
            'status' => InvoiceStatuses::DRAFT->value,
        ]);

        $purchaseOrderInvoiceQueries = $this->mock(PurchaseOrderInvoiceQueries::class, function ($mock) use (
            $purchaseOrderInvoice
        ): void {
            $mock->shouldReceive('getByIdForPaid')
                ->once()
                ->andReturn($purchaseOrderInvoice);
        });

        $purchaseOrderInvoiceController = new PurchaseOrderInvoiceController($purchaseOrderInvoiceQueries);

        $purchaseOrderInvoiceController->paid($request, $purchaseOrderInvoice->id);
    }
)->throws(
    HttpException::class,
    'Paying the purchase order invoice is not possible at the moment as it has not been marked as received.'
);

test(
    'It calls the paid method while change to status and returns a proper response',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession($companyId);

        $admin = Admin::factory()->make([
            'id' => 1,
            'employee_id' => 1,
        ]);

        $request = new Request();

        $request->setUserResolver(fn (): Admin => $admin);

        $purchaseOrderInvoice = PurchaseOrderInvoice::factory()->make([
            'id' => 1,
            'purchase_order_id' => 1,
            'created_by_company_id' => null,
            'status' => InvoiceStatuses::RECEIVED->value,
        ]);

        $this->mock(PurchaseOrderInvoiceService::class, function ($mock): void {
            $mock->shouldReceive('purchaseOrderInvoicePaid')
                ->once();
        });

        $purchaseOrderInvoiceQueries = $this->mock(PurchaseOrderInvoiceQueries::class, function ($mock) use (
            $purchaseOrderInvoice
        ): void {
            $mock->shouldReceive('getByIdForPaid')
                ->once()
                ->andReturn($purchaseOrderInvoice);
        });

        $purchaseOrderInvoiceController = new PurchaseOrderInvoiceController($purchaseOrderInvoiceQueries);

        $response = $purchaseOrderInvoiceController->paid($request, $purchaseOrderInvoice->id);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals(
            'Invoice has been marked as paid successfully',
            $response->getSession()->all()['success']
        );
        $this->assertStringContainsString('admin/purchase-order-invoices', $response->getTargetUrl());
    }
);

test(
    'cancel method throws an exception when status is not draft',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession($companyId);

        $request = new Request();

        $purchaseOrderInvoice = PurchaseOrderInvoice::factory()->make([
            'id' => 1,
            'purchase_order_id' => 1,
            'created_by_company_id' => null,
            'status' => InvoiceStatuses::PAID->value,
        ]);

        $this->mock(PurchaseOrderFulfillmentQueries::class, function ($mock): void {
            $mock->shouldReceive('getPurchaseOrderFulfillmentByInvoiceId')
                ->once();
        });

        $purchaseOrderInvoiceQueries = $this->mock(PurchaseOrderInvoiceQueries::class, function ($mock) use (
            $purchaseOrderInvoice
        ): void {
            $mock->shouldReceive('getById')
                ->once()
                ->andReturn($purchaseOrderInvoice);
        });

        $purchaseOrderInvoiceController = new PurchaseOrderInvoiceController($purchaseOrderInvoiceQueries);

        $purchaseOrderInvoiceController->cancel($request, $purchaseOrderInvoice->id);
    }
)->throws(
    HttpException::class,
    'Cancellation of the purchase order invoice is not possible at this moment, as it is not in a draft status.'
);

test(
    'It calls the cancel method while change to status and returns a proper response',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession($companyId);

        $admin = Admin::factory()->make([
            'id' => 1,
            'employee_id' => 1,
        ]);

        $request = new Request();

        $request->setUserResolver(fn (): Admin => $admin);

        $purchaseOrderInvoice = PurchaseOrderInvoice::factory()->make([
            'id' => 1,
            'purchase_order_id' => 1,
            'created_by_company_id' => null,
            'status' => InvoiceStatuses::DRAFT->value,
        ]);

        $fulfillments = PurchaseOrderFulfillment::factory()->make([
            'id' => 1,
            'purchase_order_id' => 1,
            'purchase_order_invoice_id' => $purchaseOrderInvoice->id,
            'delivery_order_number' => '1234567',
            'status' => FulfillmentStatuses::DRAFT->value,
        ]);

        $fulfillments = collect([$fulfillments]);

        $this->mock(PurchaseOrderFulfillmentQueries::class, function ($mock) use ($fulfillments): void {
            $mock->shouldReceive('getPurchaseOrderFulfillmentByInvoiceId')
                ->once()
                ->andReturn(collect($fulfillments));
        });

        $this->mock(PurchaseOrderInvoiceService::class, function ($mock): void {
            $mock->shouldReceive('purchaseOrderInvoiceCancel')
                ->once();
        });

        $purchaseOrderInvoiceQueries = $this->mock(PurchaseOrderInvoiceQueries::class, function ($mock) use (
            $purchaseOrderInvoice
        ): void {
            $mock->shouldReceive('getById')
                ->once()
                ->andReturn($purchaseOrderInvoice);
        });

        $purchaseOrderInvoiceController = new PurchaseOrderInvoiceController($purchaseOrderInvoiceQueries);

        $response = $purchaseOrderInvoiceController->cancel($request, $purchaseOrderInvoice->id);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals(
            'The specified purchase order has been marked as canceled successfully',
            $response->getSession()->all()['success']
        );
        $this->assertStringContainsString('admin/purchase-order-invoices', $response->getTargetUrl());
    }
);

test(
    'It calls the removeInvoiceId method purchase order invoice  queries class and returns a proper response',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession($companyId);

        $fulfillments = PurchaseOrderFulfillment::factory()->make([
            'id' => 1,
            'purchase_order_id' => 1,
            'purchase_order_invoice_id' => 1,
            'delivery_order_number' => '1234567',
            'status' => FulfillmentStatuses::DRAFT->value,
        ]);

        $this->mock(PurchaseOrderFulfillmentQueries::class, function ($mock) use ($fulfillments): void {
            $mock->shouldReceive('getById')
                ->once()
                ->andReturn($fulfillments);
            $mock->shouldReceive('updateRemoveInvoiceId')
                ->once();
        });

        $purchaseOrderInvoiceController = new PurchaseOrderInvoiceController(new PurchaseOrderInvoiceQueries());

        $response = $purchaseOrderInvoiceController->removeInvoiceId(1, 1);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals(
            'Successfully remove the delivery order to the invoice',
            $response->getSession()->all()['success']
        );
    }
);

test(
    'It calls the updateInvoiceId method purchase order invoice  queries class and returns a proper response',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession($companyId);

        $fulfillments = PurchaseOrderFulfillment::factory()->make([
            'id' => 1,
            'purchase_order_id' => 1,
            'purchase_order_invoice_id' => null,
            'delivery_order_number' => '1234567',
            'status' => FulfillmentStatuses::DRAFT->value,
        ]);

        $this->mock(PurchaseOrderFulfillmentQueries::class, function ($mock) use ($fulfillments): void {
            $mock->shouldReceive('getById')
                ->once()
                ->andReturn($fulfillments);
            $mock->shouldReceive('updateInvoiceId')
                ->once();
        });

        $purchaseOrderInvoiceController = new PurchaseOrderInvoiceController(new PurchaseOrderInvoiceQueries());

        $response = $purchaseOrderInvoiceController->updateInvoiceId(1, 1);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals(
            'Successfully added the delivery order to the invoice',
            $response->getSession()->all()['success']
        );
    }
);

test(
    'updateInvoiceId method throws an exception when include in another invoice',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession($companyId);

        $fulfillments = PurchaseOrderFulfillment::factory()->make([
            'id' => 1,
            'purchase_order_id' => 1,
            'purchase_order_invoice_id' => 1,
            'delivery_order_number' => '1234567',
            'status' => FulfillmentStatuses::DRAFT->value,
        ]);

        $this->mock(PurchaseOrderFulfillmentQueries::class, function ($mock) use ($fulfillments): void {
            $mock->shouldReceive('getById')
                ->once()
                ->andReturn($fulfillments);
        });

        $purchaseOrderInvoiceController = new PurchaseOrderInvoiceController(new PurchaseOrderInvoiceQueries());

        $purchaseOrderInvoiceController->updateInvoiceId(1, 1);
    }
)->throws(HttpException::class, 'Delivery Order already included in another invoice.');

test(
    'received method throws an exception when status is not sent',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession($companyId);

        $request = new Request();

        $purchaseOrderInvoice = PurchaseOrderInvoice::factory()->make([
            'id' => 1,
            'purchase_order_id' => 1,
            'created_by_company_id' => null,
            'status' => InvoiceStatuses::DRAFT->value,
        ]);

        $purchaseOrderInvoiceQueries = $this->mock(PurchaseOrderInvoiceQueries::class, function ($mock) use (
            $purchaseOrderInvoice
        ): void {
            $mock->shouldReceive('getByIdForPaid')
                ->once()
                ->andReturn($purchaseOrderInvoice);
        });

        $purchaseOrderInvoiceController = new PurchaseOrderInvoiceController($purchaseOrderInvoiceQueries);

        $purchaseOrderInvoiceController->markAsReceived($request, $purchaseOrderInvoice->id);
    }
)->throws(
    HttpException::class,
    'Marking the purchase order invoice as received is not possible until it has not been marked as sent.'
);

test(
    'It calls the received method while change to status and returns a proper response',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession($companyId);

        $admin = Admin::factory()->make([
            'id' => 1,
            'employee_id' => 1,
        ]);

        $request = new Request();

        $request->setUserResolver(fn (): Admin => $admin);

        $purchaseOrderInvoice = PurchaseOrderInvoice::factory()->make([
            'id' => 1,
            'purchase_order_id' => 1,
            'created_by_company_id' => null,
            'status' => InvoiceStatuses::SENT->value,
        ]);

        $this->mock(PurchaseOrderInvoiceService::class, function ($mock): void {
            $mock->shouldReceive('purchaseOrderInvoiceReceived')
                ->once();
        });

        $purchaseOrderInvoiceQueries = $this->mock(PurchaseOrderInvoiceQueries::class, function ($mock) use (
            $purchaseOrderInvoice
        ): void {
            $mock->shouldReceive('getByIdForPaid')
                ->once()
                ->andReturn($purchaseOrderInvoice);
        });

        $purchaseOrderInvoiceController = new PurchaseOrderInvoiceController($purchaseOrderInvoiceQueries);

        $response = $purchaseOrderInvoiceController->markAsReceived($request, $purchaseOrderInvoice->id);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals(
            'Invoice has been marked as received successfully',
            $response->getSession()->all()['success']
        );
        $this->assertStringContainsString('admin/purchase-order-invoices', $response->getTargetUrl());
    }
);

test('It calls the refreshPrice method while change to total cost and returns a proper response', function (): void {
    $purchaseOrderId = 2;

    $purchaseOrderInvoiceQueries = $this->mock(PurchaseOrderInvoiceQueries::class);

    $this->mock(PurchaseOrderItemQueries::class, function ($mock) use ($purchaseOrderId): void {
        $mock->shouldReceive('updatePurchaseCostOfDraftStatus')
        ->with($purchaseOrderId);
    });

    $purchaseOrderInvoiceController = new PurchaseOrderInvoiceController($purchaseOrderInvoiceQueries);
    $purchaseOrderInvoiceController->refreshPrice($purchaseOrderId);

    expect(DB::transactionLevel())->toBe(0);
});

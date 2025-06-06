<?php

declare(strict_types=1);

use App\Domains\PaymentType\PaymentTypeQueries;
use App\Http\Controllers\Admin\PaymentTypeReportController;
use App\Models\PaymentType;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test(
    'It calls the getPaymentTypeListForReport query method of the payment type queries class and returns proper response',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession();

        $requestParameter = [
            'search_text' => '',
            'per_page' => '',
            'location_ids' => [1],
            'payment_type_id' => '',
            'counter_ids' => [],
            'sort_by' => 'name',
            'sort_direction' => 'desc',
            'date' => '',
        ];

        $this->mock(PaymentTypeQueries::class, function ($mock) use ($requestParameter, $companyId): void {
            $mock->shouldReceive('getPaymentTypeListForReport')
                ->once()
                ->with($requestParameter, $companyId)
                ->andReturn(new LengthAwarePaginator([], 50, 15));

            $mock->shouldReceive('getBadgesTotal')
                ->once()
                ->with($requestParameter, $companyId)
                ->andReturn(collect([]));
        });

        $paymentTypeController = new PaymentTypeReportController(new PaymentTypeQueries());
        $response = $paymentTypeController->fetchPaymentTypeReport(new Request($requestParameter));
        $this->assertEquals(collect([]), $response['data']->resource);
    }
);

test(
    'It calls the fetchTransactions method and returns a proper response',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession();

        $requestParameter = [
            'id' => '',
            'location_ids' => '',
            'counter_ids' => '',
            'date' => '',
        ];

        $this->mock(PaymentTypeQueries::class, function ($mock) use ($requestParameter): void {
            $mock->shouldReceive('getPaymentTypeTransactionList')
                ->once()
                ->with($requestParameter)
                ->andReturn(collect([]));
        });

        $paymentTypeController = new PaymentTypeReportController(new PaymentTypeQueries());
        $response = $paymentTypeController->fetchTransactions(new Request($requestParameter));

        $this->assertEquals(collect([]), $response['transaction_details']->resource);
    }
);

test(
    'It calls the exportPaymentTypes method and returns a proper response',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession();

        $requestParameter = [
            'search_text' => '',
            'per_page' => '',
            'location_ids' => [1],
            'payment_type_id' => '',
            'counter_ids' => [],
            'sort_by' => 'name',
            'sort_direction' => 'desc',
            'date' => '',
        ];

        $this->mock(PaymentTypeQueries::class, function ($mock) use ($requestParameter, $companyId): void {
            $mock->shouldReceive('getPaymentTypeListExport')
                ->once()
                ->with($requestParameter, $companyId)
                ->andReturn(collect(new PaymentType()));
        });

        $paymentTypeController = new PaymentTypeReportController(new PaymentTypeQueries());
        $response = $paymentTypeController->exportPaymentTypes('filename.csv', new Request($requestParameter));

        $this->assertEquals(200, $response->getStatusCode());
        expect($response)->toBeInstanceOf(BinaryFileResponse::class);
    }
);

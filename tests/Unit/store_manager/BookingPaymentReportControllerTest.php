<?php

declare(strict_types=1);

use App\Domains\BookingPayment\BookingPaymentQueries;
use App\Domains\BookingPayment\Services\PrintBookingPaymentReportService;
use App\Http\Controllers\StoreManager\BookingPaymentReportController;
use App\Models\BookingPayment;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test(
    'It calls the getPaginatedBookingPaymentListForStoreManager method of the booking payment queries class and returns proper response',
    function (): void {
        $locationId = 1;
        setStoreIdInSession();
        $companyId = 1;
        setStoreManagerStoreCompanyIdInSession($companyId);

        $requestParameter = [
            'search_text' => 'test',
            'sort_by' => 'test',
            'sort_direction' => 'test',
            'per_page' => 'test',
            'date_range' => 'null',
            'member_id' => 'null',
            'status_id' => 'null',
            'receipt_id' => null,
        ];

        $bookingPaymentQueries = $this->mock(BookingPaymentQueries::class, function ($mock) use (
            $requestParameter,
            $companyId,
            $locationId
        ): void {
            $mock->shouldReceive('getPaginatedBookingPaymentListForStoreManager')
            ->once()
            ->with($requestParameter, $companyId, $locationId)
            ->andReturn(new LengthAwarePaginator([], 50, 15));
        });

        $bookingPaymentReportController = new BookingPaymentReportController($bookingPaymentQueries);

        $response = $bookingPaymentReportController->fetchBookingPayments(new Request($requestParameter));

        $this->assertEquals(50, $response['total_records']);
        $this->assertEquals(collect([]), $response['data']->resource);
    }
);

test(
    'It calls the exportBookingPayments method of the booking payment queries class and returns proper response',
    function (): void {
        $locationId = 1;

        setStoreIdInSession();

        $companyId = 1;
        setStoreManagerStoreCompanyIdInSession($companyId);

        $requestParameter = [
            'search_text' => 'test',
            'sort_by' => 'test',
            'sort_direction' => 'test',
            'date_range' => 'null',
            'member_id' => 'null',
            'status_id' => 'null',
            'receipt_id' => null,
            'export_columns' => null,
        ];

        $bookingPaymentQueries = $this->mock(BookingPaymentQueries::class, function ($mock) use (
            $requestParameter,
            $companyId,
            $locationId
        ): void {
            $mock->shouldReceive('getBookingPaymentForExportForStoreManager')
            ->once()
            ->with($requestParameter, $companyId, $locationId)
            ->andReturn(collect(new BookingPayment()));
        });

        $bookingPaymentReportController = new BookingPaymentReportController($bookingPaymentQueries);

        $response = $bookingPaymentReportController->exportBookingPayments(
            'filename.csv',
            new Request($requestParameter)
        );

        $this->assertEquals(200, $response->getStatusCode());

        expect($response)->toBeInstanceOf(BinaryFileResponse::class);
    }
);

test(
    'It calls the fetchBookingPaymentsDetailsById method and returns proper response',
    function (): void {
        $companyId = 1;

        setStoreManagerStoreCompanyIdInSession($companyId);

        $bookingPaymentQueries = $this->mock(BookingPaymentQueries::class, function ($mock) use (
            $companyId
        ): void {
            $mock->shouldReceive('getDetailsById')
                ->once()
                ->with(1, $companyId)
                ->andReturn(new BookingPayment());
        });

        $bookingPaymentReportController = new BookingPaymentReportController($bookingPaymentQueries);
        $response = $bookingPaymentReportController->fetchBookingPaymentsDetailsById(1);

        expect($response)
            ->toHaveKey('bookingPayment_details');
    }
);

test(
    'the printBookingPayment method and returns the string',
    function (): void {
        $companyId = 1;
        setStoreManagerStoreCompanyIdInSession($companyId);

        $this->mock(PrintBookingPaymentReportService::class, function ($mock): void {
            $mock->shouldReceive('print')
                ->once();
        });
        $bookingPaymentController = new BookingPaymentReportController(new BookingPaymentQueries());
        $response = $bookingPaymentController->printBookingPayment(1);
        expect($response)->toBeString();
    }
);

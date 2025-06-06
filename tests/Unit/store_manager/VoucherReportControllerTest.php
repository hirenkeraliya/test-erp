<?php

declare(strict_types=1);

use App\Domains\Voucher\VoucherQueries;
use App\Http\Controllers\StoreManager\VoucherReportController;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test(
    'It calls the getPaginatedVoucherListForStoreManager method of the voucher queries class and returns proper response',
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
            'status_type' => 'null',
        ];

        $voucherQueries = $this->mock(VoucherQueries::class, function ($mock) use (
            $requestParameter,
            $companyId,
            $locationId
        ): void {
            $mock->shouldReceive('getPaginatedVoucherListForStoreManager')
            ->once()
            ->with($requestParameter, $companyId, $locationId)
            ->andReturn(new LengthAwarePaginator([], 50, 15));
        });

        $voucherReportController = new VoucherReportController($voucherQueries);

        $response = $voucherReportController->fetchVouchers(new Request($requestParameter));

        $this->assertEquals(50, $response['total_records']);
        $this->assertEquals(collect([]), $response['data']->resource);
    }
);

test(
    'It calls the getVouchersForExportStoreManager method of the voucher queries class and returns proper response',
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
            'status_type' => 'null',
            'export_columns' => null,
        ];

        $voucherQueries = $this->mock(VoucherQueries::class, function ($mock) use (
            $requestParameter,
            $companyId,
            $locationId
        ): void {
            $mock->shouldReceive('getVouchersForExportStoreManager')
            ->once()
            ->with($requestParameter, $companyId, $locationId)
            ->andReturn(collect(new Voucher()));
        });

        $voucherReportController = new VoucherReportController($voucherQueries);

        $response = $voucherReportController->exportVouchers('filename.csv', new Request($requestParameter));

        $this->assertEquals(200, $response->getStatusCode());

        expect($response)->toBeInstanceOf(BinaryFileResponse::class);
    }
);

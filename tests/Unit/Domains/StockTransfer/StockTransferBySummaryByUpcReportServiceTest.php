<?php

declare(strict_types=1);

use App\Domains\Company\CompanyQueries;
use App\Domains\CustomReport\Services\CustomReportService;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\StockTransfer\Enums\StockTransferCustomReportDateTypes;
use App\Domains\StockTransfer\Enums\TransferReportType;
use App\Domains\StockTransfer\Enums\TransferTypeForReport;
use App\Domains\StockTransfer\Services\StockTransferBySummaryByUpcReportService;
use App\Domains\StockTransferItem\StockTransferItemQueries;
use App\Models\Company;
use App\Models\Location;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test(
    'can export stock transfer report by summary by upc',
    function (): void {
        $stockTransferBySummaryByUpcReportService = new StockTransferBySummaryByUpcReportService();

        $company = Company::factory()->make([
            'id' => 1,
            'default_country_id' => 1,
        ]);

        $this->mock(CompanyQueries::class, function ($mock) use ($company): void {
            $mock->shouldReceive('getNameAndCodeById')
                ->once()
                ->andReturn($company);
        });

        $this->mock(StockTransferItemQueries::class, function ($mock): void {
            $mock->shouldReceive('getByDateAndLocationWithProduct')
                ->once()
                ->andReturn(new Collection());
        });

        $this->mock(CustomReportService::class, function ($mock): void {
            $mock->shouldReceive('prepareDateRange')
                ->once()
                ->andReturn([now(), now()]);
        });

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => $company->id,
            'name' => 'Test Store',
            'type_id' => LocationTypes::STORE->value,
        ]);
        $response = $stockTransferBySummaryByUpcReportService->exportStockTransferReportBySummaryByUpcExport(
            1,
            [
                'location_ids' => [],
                'status_type' => null,
                'transfer_type' => TransferTypeForReport::TRANSFER_IN->value,
                'date_range' => '',
                'filter_by' => TransferReportType::BY_SUMMARY_UPC->value,
                'date_type' => StockTransferCustomReportDateTypes::CREATED_AT->value,
                'display_date_type' => StockTransferCustomReportDateTypes::CREATED_AT->value,
            ],
            'test.csv',
            collect([$location]),
            false
        );

        expect($response)->toBeInstanceOf(BinaryFileResponse::class);
    }
);

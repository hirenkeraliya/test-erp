<?php

declare(strict_types=1);

use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\StockTransfer\Enums\TransferReportType;
use App\Domains\StockTransfer\Enums\TransferTypeForReport;
use App\Domains\StockTransfer\Services\StockTransferByDocumentReportService;
use App\Domains\StockTransfer\Services\StockTransferBySummaryByUpcReportService;
use App\Domains\StockTransfer\Services\StockTransferBySummaryReportService;
use App\Domains\StockTransfer\Services\StockTransferCustomReportService;
use App\Models\Company;
use App\Models\Location;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test(
    'the export method calls and return the binary file response when filter by is StockTransferReportBySummary',
    function (): void {
        $stockTransferCustomReportService = new StockTransferCustomReportService();

        $company = Company::factory()->make([
            'id' => 1,
            'name' => 'Test Company',
            'default_country_id' => 1,
        ]);

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => $company->id,
            'name' => 'Test Warehouse',
            'type_id' => LocationTypes::WAREHOUSE->value,
        ]);

        $this->mock(LocationQueries::class, function ($mock) use ($location): void {
            $mock->shouldReceive('getByIdsWithNameAndCode')
                ->once()
                ->andReturn(collect([$location]));
        });

        $this->mock(StockTransferBySummaryReportService::class, function ($mock): void {
            $mock->shouldReceive('exportStockTransferReportBySummaryExport')
                ->once();
        });

        $response = $stockTransferCustomReportService->export(
            1,
            [
                'location_ids' => [1],
                'transfer_type' => TransferTypeForReport::TRANSFER_IN->value,
                'date_range' => '',
                'report_by' => TransferReportType::BY_SUMMARY->value,
                'filter_by' => null,
            ],
            'test',
            false
        );

        expect($response)->toBeInstanceOf(BinaryFileResponse::class);
    }
);

test(
    'the export method calls and return the binary file response when filter by is StockTransferReportBySummaryByUpc',
    function (): void {
        $stockTransferCustomReportService = new StockTransferCustomReportService();

        $company = Company::factory()->make([
            'id' => 1,
            'name' => 'Test Company',
            'default_country_id' => 1,
        ]);

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => $company->id,
            'name' => 'Test Warehouse',
            'type_id' => LocationTypes::WAREHOUSE->value,
        ]);

        $this->mock(LocationQueries::class, function ($mock) use ($location): void {
            $mock->shouldReceive('getByIdsWithNameAndCode')
                ->once()
                ->andReturn(collect([$location]));
        });

        $this->mock(StockTransferBySummaryByUpcReportService::class, function ($mock): void {
            $mock->shouldReceive('exportStockTransferReportBySummaryByUpcExport')
                ->once();
        });

        $response = $stockTransferCustomReportService->export(
            1,
            [
                'location_ids' => [],
                'transfer_type' => TransferTypeForReport::TRANSFER_IN->value,
                'date_range' => '',
                'report_by' => TransferReportType::BY_SUMMARY_UPC->value,
                'filter_by' => null,
            ],
            'test',
            false
        );

        expect($response)->toBeInstanceOf(BinaryFileResponse::class);
    }
);

test(
    'the export method calls and return the binary file response when filter by is StockTransferReportByDocument',
    function (): void {
        $stockTransferCustomReportService = new StockTransferCustomReportService();

        $this->mock(StockTransferByDocumentReportService::class, function ($mock): void {
            $mock->shouldReceive('exportStockTransferReportByDocumentExport')
                ->once();
        });

        $company = Company::factory()->make([
            'id' => 1,
            'name' => 'Test Company',
            'default_country_id' => 1,
        ]);

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => $company->id,
            'name' => 'Test Warehouse',
            'type_id' => LocationTypes::WAREHOUSE->value,
        ]);

        $this->mock(LocationQueries::class, function ($mock) use ($location): void {
            $mock->shouldReceive('getByIdsWithNameAndCode')
                ->once()
                ->andReturn(collect([$location]));
        });

        $response = $stockTransferCustomReportService->export(
            1,
            [
                'location_ids' => [],
                'transfer_type' => TransferTypeForReport::TRANSFER_IN->value,
                'date_range' => '',
                'report_by' => TransferReportType::BY_DOCUMENT->value,
                'filter_by' => null,
            ],
            'test',
            false
        );

        expect($response)->toBeInstanceOf(BinaryFileResponse::class);
    }
);

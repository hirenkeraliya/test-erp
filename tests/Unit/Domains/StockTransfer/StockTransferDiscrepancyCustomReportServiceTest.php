<?php

declare(strict_types=1);

use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\StockTransfer\Enums\TransferTypeDiscrepancyReport;
use App\Domains\StockTransfer\Enums\TransferTypeForReport;
use App\Domains\StockTransfer\Services\StockTransferDiscrepancyByDocumentReportService;
use App\Domains\StockTransfer\Services\StockTransferDiscrepancyBySummaryReportService;
use App\Domains\StockTransfer\Services\StockTransferDiscrepancyCustomReportService;
use App\Models\Company;
use App\Models\Location;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test(
    'the export method calls and return the binary file response when filter by is StockTransferReportBySummary',
    function (): void {
        $stockTransferCustomReportService = new StockTransferDiscrepancyCustomReportService();

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

        $this->mock(StockTransferDiscrepancyBySummaryReportService::class, function ($mock): void {
            $mock->shouldReceive('exportStockTransferReportBySummaryExport')
                ->once();
        });

        $response = $stockTransferCustomReportService->export(
            1,
            [
                'location_ids' => [$location->id],
                'transfer_type' => TransferTypeForReport::TRANSFER_IN->value,
                'date_range' => '',
                'report_by' => TransferTypeDiscrepancyReport::BY_SUMMARY->value,
                'filter_by' => null,
            ],
            'test',
        );

        expect($response)->toBeInstanceOf(BinaryFileResponse::class);
    }
);

test(
    'the export method calls and return the binary file response when filter by is StockTransferReportByDocument',
    function (): void {
        $stockTransferCustomReportService = new StockTransferDiscrepancyCustomReportService();

        $this->mock(StockTransferDiscrepancyByDocumentReportService::class, function ($mock): void {
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
                'location_ids' => [$location->id],
                'transfer_type' => TransferTypeForReport::TRANSFER_IN->value,
                'date_range' => '',
                'report_by' => TransferTypeDiscrepancyReport::BY_DOCUMENT->value,
                'filter_by' => null,
            ],
            'test',
        );

        expect($response)->toBeInstanceOf(BinaryFileResponse::class);
    }
);

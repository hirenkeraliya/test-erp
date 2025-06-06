<?php

declare(strict_types=1);

use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\PurchaseOrder\Enums\InterCompanyTransferReportType;
use App\Domains\PurchaseOrder\Enums\InterCompanyTransferType;
use App\Domains\PurchaseOrder\Services\InterCompanyByDocumentReportService;
use App\Domains\PurchaseOrder\Services\InterCompanyBySummaryByUpcReportService;
use App\Domains\PurchaseOrder\Services\InterCompanyBySummaryReportService;
use App\Domains\PurchaseOrder\Services\InterCompanyCustomReportService;
use App\Models\Company;
use App\Models\Location;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test(
    'the export method calls and return the binary file response when filter by is Inter Company StockTransferReportBySummary',
    function (): void {
        $interCompanyCustomReportService = new InterCompanyCustomReportService();

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
            $mock->shouldReceive('getByIdWithNameAndCode')
                ->once()
                ->andReturn($location);
        });

        $this->mock(InterCompanyBySummaryReportService::class, function ($mock): void {
            $mock->shouldReceive('exportStockTransferReportBySummaryExport')
                ->once();
        });

        $response = $interCompanyCustomReportService->export(
            1,
            [
                'location_id' => '',
                'location_type' => '',
                'transfer_type' => InterCompanyTransferType::TRANSFER_REQUEST->value,
                'date_range' => '',
                'report_by' => InterCompanyTransferReportType::SUMMARY_BY_ARTICLE->value,
                'filter_by' => null,
            ],
            'test',
            false
        );

        expect($response)->toBeInstanceOf(BinaryFileResponse::class);
    }
);

test(
    'the export method calls and return the binary file response when filter by is Inter Company StockTransferReportBySummaryByUpc',
    function (): void {
        $interCompanyCustomReportService = new InterCompanyCustomReportService();

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
            $mock->shouldReceive('getByIdWithNameAndCode')
                ->once()
                ->andReturn($location);
        });

        $this->mock(InterCompanyBySummaryByUpcReportService::class, function ($mock): void {
            $mock->shouldReceive('exportStockTransferReportBySummaryByUpcExport')
                ->once();
        });

        $response = $interCompanyCustomReportService->export(
            1,
            [
                'location_id' => '',
                'location_type' => '',
                'transfer_type' => InterCompanyTransferType::TRANSFER_REQUEST->value,
                'date_range' => '',
                'report_by' => InterCompanyTransferReportType::BY_SUMMARY_UPC->value,
                'filter_by' => null,
            ],
            'test',
            false
        );

        expect($response)->toBeInstanceOf(BinaryFileResponse::class);
    }
);

test(
    'the export method calls and return the binary file response when filter by is  Inter Company StockTransferReportByDocument',
    function (): void {
        $interCompanyCustomReportService = new InterCompanyCustomReportService();

        $this->mock(InterCompanyByDocumentReportService::class, function ($mock): void {
            $mock->shouldReceive('exportInterCompanyReportByDocumentExport')
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
            $mock->shouldReceive('getByIdWithNameAndCode')
                ->once()
                ->andReturn($location);
        });

        $response = $interCompanyCustomReportService->export(
            1,
            [
                'location_id' => '',
                'location_type' => '',
                'transfer_type' => InterCompanyTransferType::TRANSFER_REQUEST->value,
                'date_range' => '',
                'report_by' => InterCompanyTransferReportType::BY_DOCUMENT->value,
                'filter_by' => null,
            ],
            'test',
            false
        );

        expect($response)->toBeInstanceOf(BinaryFileResponse::class);
    }
);

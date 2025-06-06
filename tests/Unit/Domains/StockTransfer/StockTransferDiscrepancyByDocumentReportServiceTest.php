<?php

declare(strict_types=1);

use App\Domains\Company\CompanyQueries;
use App\Domains\CustomReport\Services\CustomReportService;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\StockTransfer\Enums\StockTransferCustomReportDateTypes;
use App\Domains\StockTransfer\Enums\TransferTypeDiscrepancyReport;
use App\Domains\StockTransfer\Enums\TransferTypeForReport;
use App\Domains\StockTransfer\Services\StockTransferDiscrepancyCustomReportService;
use App\Domains\StockTransfer\StockTransferQueries;
use App\Models\Company;
use App\Models\Location;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test(
    'can export stock transfer report by document',
    function (): void {
        $stockTransferByDocumentReportService = new StockTransferDiscrepancyCustomReportService();

        $company = Company::factory()->make([
            'id' => 1,
            'default_country_id' => 1,
        ]);

        $this->mock(CompanyQueries::class, function ($mock) use ($company): void {
            $mock->shouldReceive('getNameAndCodeById')
                ->once()
                ->andReturn($company);
        });

        $this->mock(StockTransferQueries::class, function ($mock): void {
            $mock->shouldReceive('getByDateAndLocationWithStockTransfer')
                ->once()
                ->andReturn(new Collection());
        });

        $this->mock(CustomReportService::class, function ($mock): void {
            $mock->shouldReceive('prepareDateRange')
                ->once()
                ->andReturn([now(), now()]);
        });

        Location::factory()->make([
            'id' => 1,
            'company_id' => $company->id,
            'name' => 'Test Store',
            'type_id' => LocationTypes::STORE->value,
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

        $response = $stockTransferByDocumentReportService->export(
            1,
            [
                'location_ids' => [$location->id],
                'status_type' => null,
                'transfer_type' => TransferTypeForReport::TRANSFER_IN->value,
                'date_range' => '',
                'date_type' => StockTransferCustomReportDateTypes::CREATED_AT->value,
                'display_date_type' => StockTransferCustomReportDateTypes::CREATED_AT->value,
                'report_by' => TransferTypeDiscrepancyReport::BY_DOCUMENT->value,
            ],
            'test.csv',
        );

        expect($response)->toBeInstanceOf(BinaryFileResponse::class);
    }
);

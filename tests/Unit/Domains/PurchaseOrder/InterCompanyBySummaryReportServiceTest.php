<?php

declare(strict_types=1);

use App\Domains\Company\CompanyQueries;
use App\Domains\Currency\CurrencyQueries;
use App\Domains\CustomReport\Services\CustomReportService;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\PurchaseOrder\Enums\InterCompanyTransferReportType;
use App\Domains\PurchaseOrder\Enums\InterCompanyTransferType;
use App\Domains\PurchaseOrder\Services\InterCompanyBySummaryReportService;
use App\Domains\PurchaseOrderItem\PurchaseOrderItemQueries;
use App\Models\Company;
use App\Models\Currency;
use App\Models\Location;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test(
    'can export inter company report by summary',
    function (): void {
        $interCompanyBySummaryReportService = new InterCompanyBySummaryReportService();

        $company = Company::factory()->make([
            'id' => 1,
            'default_country_id' => 1,
        ]);

        $this->mock(CompanyQueries::class, function ($mock) use ($company): void {
            $mock->shouldReceive('getNameAndCodeById')
                ->once()
                ->andReturn($company);
        });

        $this->mock(CurrencyQueries::class, function ($mock): void {
            $mock->shouldReceive('getByCompanyId')
            ->once()
            ->andReturn(new Currency([
                'symbol' => 'RS',
            ]));
        });

        $this->mock(PurchaseOrderItemQueries::class, function ($mock): void {
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
        $response = $interCompanyBySummaryReportService->exportStockTransferReportBySummaryExport(
            1,
            [
                'location_id' => '',
                'location_type' => '',
                'status_type' => null,
                'transfer_type' => InterCompanyTransferType::TRANSFER_REQUEST->value,
                'date_range' => '',
                'filter_by' => InterCompanyTransferReportType::SUMMARY_BY_ARTICLE->value,
            ],
            'test.csv',
            $location,
            false
        );

        expect($response)->toBeInstanceOf(BinaryFileResponse::class);
    }
);

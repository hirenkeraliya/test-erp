<?php

declare(strict_types=1);

use App\Domains\Company\CompanyQueries;
use App\Domains\CustomReport\Services\CustomReportService;
use App\Domains\PurchaseOrder\Services\InterCompanyCustomReportService;
use App\Domains\PurchaseOrderInvoice\PurchaseOrderInvoiceQueries;
use App\Domains\PurchaseOrderInvoice\Services\InterCompanyInvoiceCustomReportService;
use App\Models\Company;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test(
    'the export method calls and return the binary file response',
    function (): void {
        $interCompanyInvoiceCustomReportService = new InterCompanyInvoiceCustomReportService();

        $company = Company::factory()->make([
            'id' => 1,
            'name' => 'Test Company',
            'default_country_id' => 1,
        ]);

        $this->mock(CompanyQueries::class, function ($mock) use ($company): void {
            $mock->shouldReceive('getNameAndCodeById')
                ->once()
                ->andReturn($company);
        });

        $this->mock(PurchaseOrderInvoiceQueries::class, function ($mock): void {
            $mock->shouldReceive('getPurchaseOrderInvoicesForReport')
                ->once()
                ->andReturn(collect([]));
        });

        $this->mock(CustomReportService::class, function ($mock): void {
            $mock->shouldReceive('prepareDateRange')
                ->once()
                ->andReturn([now(), now()]);
        });

        $this->mock(InterCompanyCustomReportService::class, function ($mock): void {
            $mock->shouldReceive('filterBy')
                ->once()
                ->andReturn('');
        });

        $response = $interCompanyInvoiceCustomReportService->export(
            [
                'location_id' => '',
                'date_range' => '',
                'filter_by' => '',
            ],
            1,
            'test.csv'
        );

        expect($response)->toBeInstanceOf(BinaryFileResponse::class);
    }
);

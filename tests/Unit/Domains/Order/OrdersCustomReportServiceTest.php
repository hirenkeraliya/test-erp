<?php

declare(strict_types=1);

use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\Order\Enums\OrderReportTypes;
use App\Domains\Order\Services\OrdersByDetailsReportService;
use App\Domains\Order\Services\OrdersByDocumentReportService;
use App\Domains\Order\Services\OrdersBySummaryReportService;
use App\Domains\Order\Services\OrdersCustomReportService;
use App\Models\Company;
use App\Models\Location;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test(
    'the export method calls and return the binary file response when filter by is OrderReportBySummary',
    function (): void {
        $ordersCustomReportService = new OrdersCustomReportService();

        $company = Company::factory()->make([
            'id' => 1,
            'name' => 'Test Company',
            'default_country_id' => 1,
        ]);

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => $company->id,
            'name' => 'Test Store',
            'type_id' => LocationTypes::STORE->value,
        ]);

        $this->mock(LocationQueries::class, function ($mock) use ($location): void {
            $mock->shouldReceive('getByIdWithNameAndCode')
                ->once()
                ->andReturn($location);
        });

        $this->mock(OrdersBySummaryReportService::class, function ($mock): void {
            $mock->shouldReceive('export')
                ->once();
        });

        $response = $ordersCustomReportService->export(
            [
                'report_type' => OrderReportTypes::BY_SUMMARY->value,
                'location_id' => '',
                'store_manager' => null,
                'date_range' => '',
                'product_id' => '',
                'article_number' => '',
            ],
            1,
            'test',
        );

        expect($response)->toBeInstanceOf(BinaryFileResponse::class);
    }
);

test(
    'the export method calls and return the binary file response when filter by is OrdersReportByDocument',
    function (): void {
        $ordersCustomReportService = new OrdersCustomReportService();

        $this->mock(OrdersByDocumentReportService::class, function ($mock): void {
            $mock->shouldReceive('export')
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
            'name' => 'Test Store',
            'type_id' => LocationTypes::STORE->value,
        ]);

        $this->mock(LocationQueries::class, function ($mock) use ($location): void {
            $mock->shouldReceive('getByIdWithNameAndCode')
                ->once()
                ->andReturn($location);
        });

        $response = $ordersCustomReportService->export(
            [
                'report_type' => OrderReportTypes::BY_DOCUMENT->value,
                'location_id' => '',
                'store_manager' => null,
                'date_range' => '',
                'product_id' => '',
                'article_number' => '',
            ],
            1,
            'test',
        );

        expect($response)->toBeInstanceOf(BinaryFileResponse::class);
    }
);

test(
    'the export method calls and return the binary file response when filter by is OrdersReportByDetails',
    function (): void {
        $ordersCustomReportService = new OrdersCustomReportService();

        $this->mock(OrdersByDetailsReportService::class, function ($mock): void {
            $mock->shouldReceive('export')
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
            'name' => 'Test Store',
            'type_id' => LocationTypes::STORE->value,
        ]);

        $this->mock(LocationQueries::class, function ($mock) use ($location): void {
            $mock->shouldReceive('getByIdWithNameAndCode')
                ->once()
                ->andReturn($location);
        });

        $response = $ordersCustomReportService->export(
            [
                'report_type' => OrderReportTypes::BY_DETAILS->value,
                'location_id' => '',
                'store_manager' => null,
                'date_range' => '',
                'product_id' => '',
                'article_number' => '',
            ],
            1,
            'test',
        );

        expect($response)->toBeInstanceOf(BinaryFileResponse::class);
    }
);

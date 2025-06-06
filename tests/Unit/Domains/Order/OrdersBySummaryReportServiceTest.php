<?php

declare(strict_types=1);

use App\Domains\Company\CompanyQueries;
use App\Domains\CustomReport\Services\CustomReportService;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Order\Enums\OrderReportTypes;
use App\Domains\Order\Services\OrdersBySummaryReportService;
use App\Domains\OrderItem\OrderItemQueries;
use App\Models\Company;
use App\Models\Location;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test(
    'can export order report by summary',
    function (): void {
        $orderBySummaryReportService = new OrdersBySummaryReportService();

        $company = Company::factory()->make([
            'id' => 1,
            'default_country_id' => 1,
        ]);

        $this->mock(CompanyQueries::class, function ($mock) use ($company): void {
            $mock->shouldReceive('getNameAndCodeById')
                ->once()
                ->andReturn($company);
        });

        $this->mock(OrderItemQueries::class, function ($mock): void {
            $mock->shouldReceive('getOrderItemsForTheReport')
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

        $response = $orderBySummaryReportService->export(
            $location,
            [
                'report_type' => OrderReportTypes::BY_SUMMARY->value,
                'store_id' => '',
                'store_manager' => null,
                'date_range' => '',
                'product_id' => '',
                'article_number' => '',
            ],
            1,
            'test.csv',
        );

        expect($response)->toBeInstanceOf(BinaryFileResponse::class);
    }
);

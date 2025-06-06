<?php

declare(strict_types=1);

use App\Domains\Company\CompanyQueries;
use App\Domains\CustomReport\Services\CustomReportService;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Order\Enums\OrderReportTypes;
use App\Domains\Order\OrderQueries;
use App\Domains\Order\Services\OrdersByDetailsReportService;
use App\Models\Company;
use App\Models\Location;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test(
    'can export order report by details',
    function (): void {
        $orderByDetailReportService = new OrdersByDetailsReportService();

        $company = Company::factory()->make([
            'id' => 1,
            'default_country_id' => 1,
        ]);

        $this->mock(CompanyQueries::class, function ($mock) use ($company): void {
            $mock->shouldReceive('getNameAndCodeById')
                ->once()
                ->andReturn($company);
        });

        $this->mock(OrderQueries::class, function ($mock): void {
            $mock->shouldReceive('getOrderDetailsForReport')
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

        $response = $orderByDetailReportService->export(
            $location,
            [
                'report_type' => OrderReportTypes::BY_DETAILS->value,
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

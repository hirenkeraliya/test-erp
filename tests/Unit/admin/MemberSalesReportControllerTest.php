<?php

declare(strict_types=1);

use App\Domains\SaleItem\SaleItemQueries;
use App\Http\Controllers\Admin\MemberSalesReportController;
use App\Models\SaleItem;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test(
    'It calls the get paginated member sales report list method of the sale item queries class and returns proper response',
    function (): void {
        $companyId = 1;

        setCompanyIdInSession($companyId);

        $requestParameter = [
            'search_text' => 'test',
            'sort_by' => 'test',
            'sort_direction' => 'test',
            'per_page' => 'test',
            'member_id' => 'null',
            'product_id' => 'null',
            'date_range' => 'null',
            'location_id' => null,
            'product_collection_id' => null,
        ];

        $saleQueries = $this->mock(SaleItemQueries::class, function ($mock) use (
            $requestParameter,
            $companyId
        ): void {
            $mock->shouldReceive('getPaginatedMemberSalesReportList')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(new LengthAwarePaginator([], 50, 15));
        });

        $MemberSalesReportController = new MemberSalesReportController($saleQueries);

        $response = $MemberSalesReportController->fetchMemberSales(new Request($requestParameter));

        $this->assertEquals(50, $response['total_records']);
        $this->assertEquals(collect([]), $response['data']->resource);
    }
);

test(
    'It calls the exportMemberSales method of the sale item queries class and returns proper response',
    function (): void {
        $companyId = 1;

        setCompanyIdInSession($companyId);

        $requestParameter = [
            'search_text' => 'test',
            'sort_by' => 'test',
            'sort_direction' => 'test',
            'member_id' => 'null',
            'product_id' => 'null',
            'date_range' => 'null',
            'location_id' => null,
            'product_collection_id' => null,
            'export_columns' => null,
        ];

        $saleQueries = $this->mock(SaleItemQueries::class, function ($mock) use (
            $requestParameter,
            $companyId
        ): void {
            $mock->shouldReceive('getPaginatedMemberSalesListForExport')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(collect(new SaleItem()));
        });

        $MemberSalesReportController = new MemberSalesReportController($saleQueries);

        $response = $MemberSalesReportController->exportMemberSales('filename.csv', new Request($requestParameter));

        $this->assertEquals(200, $response->getStatusCode());

        expect($response)->toBeInstanceOf(BinaryFileResponse::class);
    }
);

test(
    'It calls the fetchSaleItemsBySaleId method of the saleQueries class and returns proper response',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession($companyId);

        $saleItemQueries = $this->mock(SaleItemQueries::class, function ($mock): void {
            $mock->shouldReceive('getSaleDetailsById')
                ->once()
                ->with(1)
                ->andReturn(new SaleItem());
        });

        $memberSalesReportController = new MemberSalesReportController($saleItemQueries);
        $response = $memberSalesReportController->fetchSaleDetailsBySaleItemId(1);

        expect($response)
            ->toHaveKey('sale_details');
    }
);

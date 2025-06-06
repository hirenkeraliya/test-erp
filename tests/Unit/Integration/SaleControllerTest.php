<?php

declare(strict_types=1);

use App\Domains\Sale\DataObjects\RetailPlanningRegularSaleByDateData;
use App\Domains\SaleItem\SaleItemQueries;
use App\Http\Controllers\Api\Integration\SaleController;
use App\Models\Integration;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

test('It calls the getRegularProductAggregateSales method of the SaleItemQueries class', function (): void {
    $integration = Integration::factory()->make([
        'id' => 1,
        'company_id' => 1,
    ]);

    $request = new Request();
    $request->setUserResolver(fn (): Integration => $integration);

    $this->mock(SaleItemQueries::class, function ($mock): void {
        $mock->shouldReceive('getRegularProductAggregateSales')
            ->once()
            ->andReturn(new LengthAwarePaginator([], 1, 15));
    });

    $saleController = new SaleController();
    $response = $saleController->getAllAggregatedSales($request);

    expect($response['sales'])->toBeObject();
});

test(
    'It calls the getRegularProductSalesAggregateForClosedCounter method of the SaleItemQueries class',
    function (): void {
        $integration = Integration::factory()->make([
            'id' => 1,
            'company_id' => 1,
        ]);

        $request = new Request();
        $request->setUserResolver(fn (): Integration => $integration);

        $this->mock(SaleItemQueries::class, function ($mock): void {
            $mock->shouldReceive('getRegularProductSalesAggregateForClosedCounter')
                ->once()
                ->andReturn(new LengthAwarePaginator([], 1, 15));
        });

        $RetailPlanningRegularSaleByDateData = new RetailPlanningRegularSaleByDateData([
            'dates' => [
                'start_date' => '2024-01-01',
                'end_date' => '2024-01-01',
            ],
        ]);

        $saleController = new SaleController();
        $response = $saleController->getAggregatedRegularSalesForSpecifiedDate(
            $RetailPlanningRegularSaleByDateData,
            $request
        );

        expect($response['sales'])->toBeObject();
    }
);

test(
    'It calls the getRegularProductCompleteCreditAndLayawaySalesAggregateForClosedCounter method of the SaleItemQueries class',
    function (): void {
        $integration = Integration::factory()->make([
            'id' => 1,
            'company_id' => 1,
        ]);

        $request = new Request();
        $request->setUserResolver(fn (): Integration => $integration);

        $this->mock(SaleItemQueries::class, function ($mock): void {
            $mock->shouldReceive('getRegularProductCompleteCreditAndLayawaySalesAggregateForClosedCounter')
                ->once()
                ->andReturn(collect());
        });

        $RetailPlanningRegularSaleByDateData = new RetailPlanningRegularSaleByDateData([
            'dates' => [
                'start_date' => '2024-01-01',
                'end_date' => '2024-01-01',
            ],
        ]);

        $saleController = new SaleController();
        $response = $saleController->getCompleteLayawayAndCreditAggregatedSalesForSpecifiedDate(
            $RetailPlanningRegularSaleByDateData,
            $request
        );

        expect($response['completeLayawayAndCreditSales'])->toBeCollection();
    }
);

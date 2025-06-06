<?php

declare(strict_types=1);

use App\Domains\Currency\CurrencyQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Promoter\PromoterQueries;
use App\Domains\Sale\DataObjects\PromoterHistorySaleData;
use App\Domains\SaleItem\SaleItemQueries;
use App\Domains\SaleReturnItem\SaleReturnItemQueries;
use App\Http\Controllers\Api\Promoter\SaleController;
use App\Models\Currency;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Promoter;
use Illuminate\Http\Request;

test(
    'It calls the getPromotersWiseSales and getPromoterWiseSalesReturnItems method of their respective class',
    function (): void {
        $promoter = Promoter::factory()->make([
            'id' => 1,
            'employee_id' => 1,
        ]);

        $promoter->employee = Employee::factory()->make([
            'company_id' => 1,
            'designation_id' => 1,
        ]);

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $filterData = [
            'store_id' => $location->id,
            'selected_date' => '2023-02-01',
            'per_page' => 10,
            'page' => 1,
            'location_id' => null,
        ];

        $request = new Request();
        $request->setUserResolver(fn (): Promoter => $promoter);

        $promoterHistorySaleData = new PromoterHistorySaleData(...$filterData);

        $this->mock(SaleItemQueries::class, function ($mock): void {
            $mock->shouldReceive('getPromotersWiseSales')
            ->once()
            ->andReturn(collect([]));
        });
        $this->mock(SaleReturnItemQueries::class, function ($mock): void {
            $mock->shouldReceive('getPromoterWiseSalesReturnItems')
            ->once()
            ->andReturn(collect([]));
        });

        $this->mock(CurrencyQueries::class, function ($mock): void {
            $mock->shouldReceive('getByCompanyId')
                ->once()
                ->andReturn(new Currency([
                    'symbol' => 'RS',
                ]));
        });

        $this->mock(PromoterQueries::class, function ($mock) use ($promoter): void {
            $mock->shouldReceive('loadEmployee')
                ->once()
                ->andReturn($promoter);
        });

        $saleController = new SaleController();
        $response = $saleController->getSaleHistoryBySingleDate($request, $promoterHistorySaleData);

        expect($response)->toBeArray();

        expect($response)->toHaveKeys(
            ['summary', 'summary.date', 'summary.items_sold', 'summary.items_returned', 'summary.net_sales', 'sales']
        );
    }
);

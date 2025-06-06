<?php

declare(strict_types=1);

use App\Domains\Cashier\CashierQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\SaleReturn\DataObjects\FilteredAndPaginatedSaleReturnsDataForPos;
use App\Domains\SaleReturn\SaleReturnQueries;
use App\Http\Controllers\Api\Pos\SaleReturnController;
use App\Models\Cashier;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

test(
    'it calls getPaginatedSaleReturnsWithAllRelations method of SaleReturnQueries class and returns the sale returns list',
    function (): void {
        $cashier = makeCashierAndEmployeeForPosWithCounterUpdateId()['cashier'];

        $filteredAndPaginatedSaleReturnsData = [
            'page' => 1,
            'member_id' => 1,
            'employee_id' => 1,
            'from_date' => '',
            'to_date' => '',
            'per_page' => 10,
            'sort_by' => 'id',
            'sort_direction' => 'desc',
            'search_text' => '',
            'after_updated_at' => null,
        ];

        $FilteredAndPaginatedSaleReturnsDataForPos = new FilteredAndPaginatedSaleReturnsDataForPos(
            ...$filteredAndPaginatedSaleReturnsData
        );

        $request = new Request();

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $request->setUserResolver(fn (): Cashier => $cashier);

        $this->mock(CashierQueries::class, function ($mock) use ($cashier): void {
            $mock->shouldReceive('getCashierCompanyId')
                ->once()
                ->with($cashier)
                ->andReturn(1);
        });

        $this->mock(LocationQueries::class, function ($mock) use ($cashier, $location): void {
            $mock->shouldReceive('getLocationByCountersCounterUpdateId')
                ->once()
                ->with($cashier->counter_update_id)
                ->andReturn($location);
        });

        $this->mock(SaleReturnQueries::class, function ($mock): void {
            $mock->shouldReceive('getPaginatedSaleReturnsWithAllRelations')
                ->once()
                ->andReturn(new LengthAwarePaginator([], 50, 15));
        });

        $saleReturnController = new SaleReturnController();
        $saleReturnController->getFilteredAndPaginatedSaleReturns($request, $FilteredAndPaginatedSaleReturnsDataForPos);
    }
);

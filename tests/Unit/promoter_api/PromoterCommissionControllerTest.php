<?php

declare(strict_types=1);

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Currency\CurrencyQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Promoter\PromoterQueries;
use App\Domains\PromoterCommission\DataObjects\PromoterCommissionData;
use App\Domains\PromoterCommissionUpdate\PromoterCommissionUpdateQueries;
use App\Http\Controllers\Api\Promoter\PromoterCommissionController;
use App\Models\Currency;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Promoter;
use App\Models\PromoterCommissionUpdate;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

test('getPaginatedPromoterCommissionHistory should return paginated promoter commission history', function (): void {
    $promoter = Promoter::factory()->make([
        'id' => 1,
        'employee_id' => 1,
    ]);

    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $filterData = [
        'per_page' => 10,
        'page' => 1,
        'store_id' => $location->id,
        'location_id' => $location->id,
        'start_date' => now()->subMonths(2)->format('Y-m-d'),
        'end_date' => now()->subMonths(2)->format('Y-m-d'),
    ];

    $promoterCommissionData = new PromoterCommissionData(...$filterData);
    $request = new Request();
    $request->setUserResolver(fn (): Promoter => $promoter);

    $this->mock(PromoterCommissionUpdateQueries::class, function ($mock): void {
        $mock->shouldReceive('promoterCommissionBasedOnPromoterAndLocation')
            ->once()
            ->andReturn(collect([]));
    });

    $promoterCommissionController = new PromoterCommissionController();
    $response = $promoterCommissionController->getPaginatedPromoterCommissionHistory($request, $promoterCommissionData);

    expect($response)->toHaveKeys(['commission_history', 'total_records']);

    expect($response['commission_history']->resource)->toBeInstanceOf(Collection::class);
});

test(
    'Call the getCommissionHistoryBySingleDate method for fetch promoter commission data with particular date.',
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
            'per_page' => 10,
            'store_id' => $location->id,
            'location_id' => $location->id,
            'selected_date' => (string) now()->format('Y-m-d'),
        ];

        $request = new Request($filterData);
        $request->setUserResolver(fn (): Promoter => $promoter);

        $this->mock(PromoterCommissionUpdateQueries::class, function ($mock): void {
            $mock->shouldReceive('getPromoterCommissionBySingleData')
            ->once()
            ->andReturn(new LengthAwarePaginator([], 10, 10));
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

        $promoterController = new PromoterCommissionController();
        $response = $promoterController->getCommissionHistoryBySingleDate($request);

        $this->assertEquals(10, $response['total_records']);
        $this->assertEquals(collect([]), collect($response['promoter_commission']));
    }
);

test('getPromoterCommissionDetails should return paginated promoter commission history', function (): void {
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
        'per_page' => 10,
        'location_id' => $location->id,
        'selected_date' => (string) now()->format('Y-m-d'),
    ];

    $promoterCommissionUpdate = PromoterCommissionUpdate::factory()->make([
        'promoter_commission_id' => 1,
        'affected_by_id' => 1,
        'affected_by_type' => ModelMapping::SALE_ITEM->name,
        'department_id' => 1,
    ]);

    $this->mock(PromoterCommissionUpdateQueries::class, function ($mock) use ($promoterCommissionUpdate): void {
        $mock->shouldReceive('fetchCommissionDetailsById')
            ->andReturn($promoterCommissionUpdate)
            ->once();
    });

    $request = new Request($filterData);
    $request->setUserResolver(fn (): Promoter => $promoter);

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

    $promoterCommissionController = new PromoterCommissionController();
    $response = $promoterCommissionController->getPromoterCommissionDetails(
        $promoterCommissionUpdate->promoter_commission_id,
        $request
    );

    expect($response['details']->resource)
        ->toHaveKey('promoter_commission_id', $promoterCommissionUpdate->promoter_commission_id)
        ->toHaveKey('department_id', $promoterCommissionUpdate->department_id)
        ->toHaveKey('commission_amount', $promoterCommissionUpdate->commission_amount);
});

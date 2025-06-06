<?php

declare(strict_types=1);

use App\Domains\Order\OrderQueries;
use App\Domains\Order\Services\OrderService;
use App\Domains\PaymentType\PaymentTypeQueries;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

test(
    'call getPaymentTypeList method get proper response',
    function (): void {
        $companyId = 1;

        $this->mock(PaymentTypeQueries::class, function ($mock) use ($companyId): void {
            $mock->shouldReceive('getActiveOnlyWithSubPaymentTypes')
                ->once()
                ->with($companyId)
                ->andReturn(collect([]));
        });

        $orderService = resolve(OrderService::class);
        $response = $orderService->getPaymentTypeList($companyId);

        expect($response)->toBeInstanceOf(Collection::class);
    }
);

test(
    'call getPaginateData method get proper response',
    function (): void {
        $filterData = [
            'search_text' => null,
            'sort_by' => null,
            'sort_direction' => null,
            'per_page' => null,
            'date_range' => null,
            'member_id' => null,
            'type_id' => null,
        ];

        $storeManagerId = 1;
        $locationId = 1;

        $this->mock(OrderQueries::class, function ($mock): void {
            $mock->shouldReceive('getPaginatedCompleteOrderWithRelations')
                ->once()
                ->andReturn(new LengthAwarePaginator([], 10, 5));
            $mock->shouldReceive('getFilteredTotalsForReport')
                ->once()
                ->andReturn(collect([]));
        });

        $orderService = resolve(OrderService::class);
        $response = $orderService->getPaginateData($filterData, $storeManagerId, $locationId, 1, true);

        expect($response)->toBeArray();
    }
);

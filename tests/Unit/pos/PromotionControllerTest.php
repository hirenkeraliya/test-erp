<?php

declare(strict_types=1);

use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\Promotion\DataObjects\PaginatedManualPromotionDataForPos;
use App\Domains\Promotion\PromotionQueries;
use App\Http\Controllers\Api\Pos\PromotionController;
use App\Models\Cashier;
use App\Models\Employee;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

test(
    'it calls the getListForPosAsPerTimeFrameWithRelatedData method of the PromotionQueries class and returns the list of promotions with related data',
    function (): void {
        $companyId = 1;

        $employee = Employee::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
            'designation_id' => 1,
        ]);

        $cashier = Cashier::factory()->make([
            'employee_id' => $employee->id,
            'cashier_group_id' => 1,
            'counter_update_id' => 1,
        ]);

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $request = new Request();

        $request->setUserResolver(fn (): Cashier => $cashier);

        $this->mock(LocationQueries::class, function ($mock) use ($cashier, $location): void {
            $mock->shouldReceive('getLocationByCountersCounterUpdateId')
            ->once()
            ->with($cashier->counter_update_id)
            ->andReturn($location);
        });

        $this->mock(PromotionQueries::class, function ($mock): void {
            $mock->shouldReceive('getListForPosAsPerTimeFrameWithRelatedData')
            ->once()
            ->andReturn(new Collection([]));
        });

        $promotionController = new PromotionController();
        $response = $promotionController->getList($request);
        expect($response)->toBeArray();
    }
);

test(
    'it calls the getListForPosAsPerTimeFrameWithRelatedDataAndManualPromotionOnly method of the PromotionQueries class and returns the list of promotions with related data',
    function (): void {
        $companyId = 1;

        $employee = Employee::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
            'designation_id' => 1,
        ]);

        $cashier = Cashier::factory()->make([
            'employee_id' => $employee->id,
            'cashier_group_id' => 1,
            'counter_update_id' => 1,
        ]);

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $paginatedManualPromotionData = [
            'per_page' => 10,
            'search_text' => '',
            'after_updated_at' => null,
        ];

        $paginatedManualPromotionDataForPos = new PaginatedManualPromotionDataForPos(...$paginatedManualPromotionData);

        $request = new Request();

        $request->setUserResolver(fn (): Cashier => $cashier);

        $this->mock(LocationQueries::class, function ($mock) use ($cashier, $location): void {
            $mock->shouldReceive('getLocationByCountersCounterUpdateId')
            ->once()
            ->with($cashier->counter_update_id)
            ->andReturn($location);
        });

        $this->mock(PromotionQueries::class, function ($mock): void {
            $mock->shouldReceive('getListForPosAsPerTimeFrameWithRelatedDataAndManualPromotionOnly')
            ->once()
            ->andReturn(new LengthAwarePaginator([], 50, 15));
        });

        $promotionController = new PromotionController();
        $response = $promotionController->getPaginatedManualPromotion($request, $paginatedManualPromotionDataForPos);
        expect($response)->toBeArray();
    }
);

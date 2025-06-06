<?php

declare(strict_types=1);

use App\Domains\Common\Enums\AuthorizerTypes;
use App\Domains\HappyHourDiscount\DataObjects\HappyHourDiscountDataForPos;
use App\Domains\HappyHourDiscount\Enums\ProductTypes;
use App\Domains\HappyHourDiscount\HappyHourDiscountQueries;
use App\Domains\HappyHourDiscount\Services\HappyHourDiscountService;
use App\Models\Cashier;
use App\Models\Employee;
use App\Models\HappyHourDiscount;
use App\Models\HappyHourDiscountTransaction;

function getHappyHourDiscountValidateData(): HappyHourDiscountDataForPos
{
    $happyHourDiscountData = [
        'offline_id' => '123456',
        'product_type_id' => ProductTypes::BRAND->value,
        'name' => 'abc',
        'new_price' => '500',
        'start_date' => '2024-01-04 04:25:50',
        'end_date' => '2024-01-04 04:50:50',
        'happened_at' => '2024-01-04 04:20:50',
        'store_manager_id' => 1,
        'store_manager_passcode' => '123456',
        'director_id' => null,
        'director_passcode' => null,
        'brand_ids' => [1],
    ];

    return new HappyHourDiscountDataForPos(...$happyHourDiscountData);
}

test(
    'addHappyHourDiscount method save happy hour discount data',
    function (): void {
        $companyId = 1;
        $counterUpdateId = 1;
        $locationId = 1;

        $employee = Employee::factory()->make([
            'company_id' => $companyId,
            'designation_id' => 1,
        ]);

        $cashier = Cashier::factory()->make([
            'employee_id' => $employee->id,
            'cashier_group_id' => 1,
        ]);

        $happyHourDiscount = HappyHourDiscount::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
            'location_id' => $locationId,
            'product_type_id' => ProductTypes::BRAND->value,
        ]);

        $happyHourDiscountTransaction = HappyHourDiscountTransaction::factory()->make([
            'happy_hour_discount_id' => $happyHourDiscount->id,
            'counter_update_id' => $counterUpdateId,
            'authorizer_id' => 1,
            'authorizer_type' => AuthorizerTypes::STORE_MANAGER->name,
        ]);

        $happyHourDiscount->happyHourDiscountTransaction = $happyHourDiscountTransaction;

        $happyHourDiscountDataForPos = getHappyHourDiscountValidateData();

        $this->mock(HappyHourDiscountQueries::class, function ($mock) use (
            $happyHourDiscountDataForPos,
            $happyHourDiscount,
            $cashier
        ): void {
            $mock->shouldReceive('addNew')
                ->once()
                ->with($happyHourDiscountDataForPos, 1, $cashier, 1, 1)
                ->andReturn($happyHourDiscount);
        });

        $happyHourDiscountService = new HappyHourDiscountService();
        $happyHourDiscount = $happyHourDiscountService->addHappyHourDiscount(
            $happyHourDiscountDataForPos,
            $companyId,
            $counterUpdateId,
            $locationId,
            $cashier
        );

        expect($happyHourDiscount)
            ->toHaveKeys([
                'location_id', 'company_id',  'product_type_id', 'name', 'new_price', 'start_date',  'end_date', 'happyHourDiscountTransaction',
            ]);
    }
);

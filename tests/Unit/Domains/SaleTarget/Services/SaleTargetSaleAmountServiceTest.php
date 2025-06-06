<?php

declare(strict_types=1);

use App\CommonFunctions;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Promoter\PromoterQueries;
use App\Domains\Sale\SaleQueries;
use App\Domains\SaleReturn\SaleReturnQueries;
use App\Domains\SaleTarget\DataObjects\SaleTargetData;
use App\Domains\SaleTarget\Enums\SaleTargetAmountTypes;
use App\Domains\SaleTarget\Enums\SaleTargetPromoterTypes;
use App\Domains\SaleTarget\Enums\SaleTargetStoreTypes;
use App\Domains\SaleTarget\Enums\TargetType;
use App\Domains\SaleTarget\Enums\TimeIntervalType;
use App\Domains\SaleTarget\Services\SaleTargetSaleAmountService;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\Location;
use App\Models\Promoter;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\SaleTarget;
use Carbon\Carbon;

beforeEach(function (): void {
    $this->companyId = 1;

    $this->saleTargetData = new SaleTargetData(
        'test',
        10,
        null,
        SaleTargetAmountTypes::AMOUNT->value,
        TargetType::COMPANY_WISE->value,
        TimeIntervalType::DAILY->value,
        true,
        [],
        [],
        [],
        SaleTargetStoreTypes::SELECT->value,
        SaleTargetPromoterTypes::SELECT->value,
        null,
        null
    );

    $this->saleTarget = SaleTarget::factory()->make([
        'id' => 1,
        'company_id' => $this->companyId,
        'name' => 'ABC',
    ]);

    $this->saleTargetSaleAmountService = new SaleTargetSaleAmountService();
});

test('handleSaleTargetData method returns the amount', function (): void {
    $response = $this->saleTargetSaleAmountService->handleSaleTargetData($this->saleTargetData, $this->companyId);

    expect($response)->toBe($this->saleTargetData->amount);
});

test('handleSaleTargetData calculates the company wise target amount based on daily sales data', function (): void {
    $date = Carbon::now()->format('Y-m-d');
    $this->saleTargetData->amount_type = SaleTargetAmountTypes::PERCENTAGE->value;
    $this->saleTargetData->amount = 0;
    $this->saleTargetData->percentage = 5;
    $this->saleTargetData->dates = [$date, $date];

    $sale = Sale::factory()->make([
        'id' => 1,
        'counter_update_id' => 1,
        'member_id' => null,
        'happened_at' => CommonFunctions::addStartTime($date),
        'total_sales_amount' => 100,
    ]);

    $saleReturn = SaleReturn::factory()->make([
        'id' => 1,
        'counter_update_id' => 1,
        'original_sale_id' => null,
        'member_id' => null,
        'happened_at' => CommonFunctions::addStartTime($date),
        'total_return_amount' => 10,
    ]);

    $this->mock(SaleQueries::class, function ($mock) use ($sale): void {
        $mock->shouldReceive('getTotalAmountForSaleCompanyTarget')
            ->once()
            ->andReturn($sale);
    });

    $this->mock(SaleReturnQueries::class, function ($mock) use ($saleReturn): void {
        $mock->shouldReceive('getTotalAmountForSaleCompanyTarget')
            ->once()
            ->andReturn($saleReturn);
    });

    $netSales = $sale->total_sales_amount - $saleReturn->total_return_amount;
    $amount = $netSales + ($netSales * $this->saleTargetData->percentage / 100);

    $response = $this->saleTargetSaleAmountService->handleSaleTargetData($this->saleTargetData, $this->companyId);

    expect($response)->toBe($amount);
});

test(
    'handleSaleTargetData calculates the company wise target amount based on custom period sales data',
    function (): void {
        $date = Carbon::now()->format('Y-m-d');
        $this->saleTargetData->amount_type = SaleTargetAmountTypes::PERCENTAGE->value;
        $this->saleTargetData->amount = 0;
        $this->saleTargetData->percentage = 5;
        $this->saleTargetData->dates = [$date, $date];
        $this->saleTargetData->time_interval_type = TimeIntervalType::CUSTOM_PERIOD->value;

        $sale = Sale::factory()->make([
            'id' => 1,
            'counter_update_id' => 1,
            'member_id' => null,
            'happened_at' => CommonFunctions::addStartTime($date),
            'total_sales_amount' => 100,
        ]);

        $saleReturn = SaleReturn::factory()->make([
            'id' => 1,
            'counter_update_id' => 1,
            'original_sale_id' => null,
            'member_id' => null,
            'happened_at' => CommonFunctions::addStartTime($date),
            'total_return_amount' => 10,
        ]);

        $this->mock(SaleQueries::class, function ($mock) use ($sale): void {
            $mock->shouldReceive('getTotalAmountForSaleCompanyTarget')
                ->once()
                ->andReturn($sale);
        });

        $this->mock(SaleReturnQueries::class, function ($mock) use ($saleReturn): void {
            $mock->shouldReceive('getTotalAmountForSaleCompanyTarget')
                ->once()
                ->andReturn($saleReturn);
        });

        $netSales = $sale->total_sales_amount - $saleReturn->total_return_amount;
        $amount = $netSales + ($netSales * $this->saleTargetData->percentage / 100);

        $response = $this->saleTargetSaleAmountService->handleSaleTargetData($this->saleTargetData, $this->companyId);

        expect($response)->toBe($amount);
    }
);

test('handleSaleTargetData calculates the company wise target amount based on weekly sales data', function (): void {
    $date = Carbon::now()->format('Y-m-d');
    $startWeek = Carbon::now()->startOfWeek()->format('Y-m-d');
    $endWeek = Carbon::now()->endOfWeek()->format('Y-m-d');

    $this->saleTargetData->amount_type = SaleTargetAmountTypes::PERCENTAGE->value;
    $this->saleTargetData->amount = 0;
    $this->saleTargetData->percentage = 5;
    $this->saleTargetData->week_tiers = [[
        'weeks' => [$startWeek, $endWeek],
    ]];
    $this->saleTargetData->time_interval_type = TimeIntervalType::WEEKLY->value;

    $sale = Sale::factory()->make([
        'id' => 1,
        'counter_update_id' => 1,
        'member_id' => null,
        'happened_at' => CommonFunctions::addStartTime($date),
        'total_sales_amount' => 100,
    ]);

    $saleReturn = SaleReturn::factory()->make([
        'id' => 1,
        'counter_update_id' => 1,
        'original_sale_id' => null,
        'member_id' => null,
        'happened_at' => CommonFunctions::addStartTime($date),
        'total_return_amount' => 10,
    ]);

    $this->mock(SaleQueries::class, function ($mock) use ($sale): void {
        $mock->shouldReceive('getTotalAmountForSaleCompanyTarget')
            ->once()
            ->andReturn($sale);
    });

    $this->mock(SaleReturnQueries::class, function ($mock) use ($saleReturn): void {
        $mock->shouldReceive('getTotalAmountForSaleCompanyTarget')
            ->once()
            ->andReturn($saleReturn);
    });

    $netSales = $sale->total_sales_amount - $saleReturn->total_return_amount;
    $amount = $netSales + ($netSales * $this->saleTargetData->percentage / 100);

    $response = $this->saleTargetSaleAmountService->handleSaleTargetData($this->saleTargetData, $this->companyId);

    expect($response)->toBe($amount);
});

test('handleSaleTargetData calculates the company wise target amount based on monthly sales data', function (): void {
    $date = Carbon::now()->format('Y-m-d');
    $month = Carbon::now()->startOfMonth()->format('m');
    $year = Carbon::now()->endOfMonth()->format('Y');

    $this->saleTargetData->amount_type = SaleTargetAmountTypes::PERCENTAGE->value;
    $this->saleTargetData->amount = 0;
    $this->saleTargetData->percentage = 5;
    $this->saleTargetData->month_tiers = [[
        'months' => [
            'month' => (int) $month,
            'year' => (int) $year,
        ],
    ]];
    $this->saleTargetData->time_interval_type = TimeIntervalType::MONTHLY->value;

    $sale = Sale::factory()->make([
        'id' => 1,
        'counter_update_id' => 1,
        'member_id' => null,
        'happened_at' => CommonFunctions::addStartTime($date),
        'total_sales_amount' => 100,
    ]);

    $saleReturn = SaleReturn::factory()->make([
        'id' => 1,
        'counter_update_id' => 1,
        'original_sale_id' => null,
        'member_id' => null,
        'happened_at' => CommonFunctions::addStartTime($date),
        'total_return_amount' => 10,
    ]);

    $this->mock(SaleQueries::class, function ($mock) use ($sale): void {
        $mock->shouldReceive('getTotalAmountForSaleCompanyTarget')
            ->once()
            ->andReturn($sale);
    });

    $this->mock(SaleReturnQueries::class, function ($mock) use ($saleReturn): void {
        $mock->shouldReceive('getTotalAmountForSaleCompanyTarget')
            ->once()
            ->andReturn($saleReturn);
    });

    $netSales = $sale->total_sales_amount - $saleReturn->total_return_amount;
    $amount = $netSales + ($netSales * $this->saleTargetData->percentage / 100);

    $response = $this->saleTargetSaleAmountService->handleSaleTargetData($this->saleTargetData, $this->companyId);

    expect($response)->toBe($amount);
});

test('handleSaleTargetData calculates the company wise target amount based on yearly sales data', function (): void {
    $date = Carbon::now()->format('Y-m-d');
    $year = Carbon::now()->format('Y');

    $this->saleTargetData->amount_type = SaleTargetAmountTypes::PERCENTAGE->value;
    $this->saleTargetData->amount = 0;
    $this->saleTargetData->percentage = 5;
    $this->saleTargetData->year = (int) $year;
    $this->saleTargetData->time_interval_type = TimeIntervalType::YEARLY->value;

    $sale = Sale::factory()->make([
        'id' => 1,
        'counter_update_id' => 1,
        'member_id' => null,
        'happened_at' => CommonFunctions::addStartTime($date),
        'total_sales_amount' => 100,
    ]);

    $saleReturn = SaleReturn::factory()->make([
        'id' => 1,
        'counter_update_id' => 1,
        'original_sale_id' => null,
        'member_id' => null,
        'happened_at' => CommonFunctions::addStartTime($date),
        'total_return_amount' => 10,
    ]);

    $this->mock(SaleQueries::class, function ($mock) use ($sale): void {
        $mock->shouldReceive('getTotalAmountForSaleCompanyTarget')
            ->once()
            ->andReturn($sale);
    });

    $this->mock(SaleReturnQueries::class, function ($mock) use ($saleReturn): void {
        $mock->shouldReceive('getTotalAmountForSaleCompanyTarget')
            ->once()
            ->andReturn($saleReturn);
    });

    $netSales = $sale->total_sales_amount - $saleReturn->total_return_amount;
    $amount = $netSales + ($netSales * $this->saleTargetData->percentage / 100);

    $response = $this->saleTargetSaleAmountService->handleSaleTargetData($this->saleTargetData, $this->companyId);

    expect($response)->toBe($amount);
});

test('handleSaleTargetData calculates the store wise target amount based on daily sales data', function (): void {
    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $date = Carbon::now()->format('Y-m-d');
    $this->saleTargetData->amount_type = SaleTargetAmountTypes::PERCENTAGE->value;
    $this->saleTargetData->target_type = TargetType::STORE_WISE->value;
    $this->saleTargetData->amount = 0;
    $this->saleTargetData->percentage = 5;
    $this->saleTargetData->dates = [$date, $date];
    $this->saleTargetData->location_ids = [$location->id];

    $counter = Counter::factory()->make([
        'id' => 1,
        'location_id' => $location->id,
    ]);

    $counterUpdate = CounterUpdate::factory()->make([
        'id' => 1,
        'counter_id' => $counter->id,
        'cashier_id' => 1,
    ]);

    $sale = Sale::factory()->make([
        'id' => 1,
        'counter_update_id' => $counterUpdate->id,
        'member_id' => null,
        'happened_at' => CommonFunctions::addStartTime($date),
        'total_sales_amount' => 100,
    ]);

    $saleReturn = SaleReturn::factory()->make([
        'id' => 1,
        'counter_update_id' => $counterUpdate->id,
        'original_sale_id' => null,
        'member_id' => null,
        'happened_at' => CommonFunctions::addStartTime($date),
        'total_return_amount' => 10,
    ]);

    $this->mock(SaleQueries::class, function ($mock) use ($sale): void {
        $mock->shouldReceive('getTotalAmountForSaleStoreTarget')
            ->once()
            ->andReturn(collect([$sale]));
    });

    $this->mock(SaleReturnQueries::class, function ($mock) use ($saleReturn): void {
        $mock->shouldReceive('getTotalAmountForSaleStoreTarget')
            ->once()
            ->andReturn(collect([$saleReturn]));
    });

    $netSales = $sale->total_sales_amount - $saleReturn->total_return_amount;
    $amount = $netSales + ($netSales * $this->saleTargetData->percentage / 100);

    $response = $this->saleTargetSaleAmountService->handleSaleTargetData($this->saleTargetData, $this->companyId);

    expect($response)->toBe($amount);
});

test(
    'handleSaleTargetData calculates the store wise target amount based on custom period sales data',
    function (): void {
        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $date = Carbon::now()->format('Y-m-d');
        $this->saleTargetData->amount_type = SaleTargetAmountTypes::PERCENTAGE->value;
        $this->saleTargetData->amount = 0;
        $this->saleTargetData->percentage = 5;
        $this->saleTargetData->dates = [$date, $date];
        $this->saleTargetData->time_interval_type = TimeIntervalType::CUSTOM_PERIOD->value;
        $this->saleTargetData->location_ids = [$location->id];
        $this->saleTargetData->target_type = TargetType::STORE_WISE->value;

        $counter = Counter::factory()->make([
            'id' => 1,
            'location_id' => $location->id,
        ]);

        $counterUpdate = CounterUpdate::factory()->make([
            'id' => 1,
            'counter_id' => $counter->id,
            'cashier_id' => 1,
        ]);

        $sale = Sale::factory()->make([
            'id' => 1,
            'counter_update_id' => $counterUpdate->id,
            'member_id' => null,
            'happened_at' => CommonFunctions::addStartTime($date),
            'total_sales_amount' => 100,
        ]);

        $saleReturn = SaleReturn::factory()->make([
            'id' => 1,
            'counter_update_id' => $counterUpdate->id,
            'original_sale_id' => null,
            'member_id' => null,
            'happened_at' => CommonFunctions::addStartTime($date),
            'total_return_amount' => 10,
        ]);

        $this->mock(SaleQueries::class, function ($mock) use ($sale): void {
            $mock->shouldReceive('getTotalAmountForSaleStoreTarget')
                ->once()
                ->andReturn(collect([$sale]));
        });

        $this->mock(SaleReturnQueries::class, function ($mock) use ($saleReturn): void {
            $mock->shouldReceive('getTotalAmountForSaleStoreTarget')
                ->once()
                ->andReturn(collect([$saleReturn]));
        });

        $netSales = $sale->total_sales_amount - $saleReturn->total_return_amount;
        $amount = $netSales + ($netSales * $this->saleTargetData->percentage / 100);

        $response = $this->saleTargetSaleAmountService->handleSaleTargetData($this->saleTargetData, $this->companyId);

        expect($response)->toBe($amount);
    }
);

test('handleSaleTargetData calculates the store wise target amount based on weekly sales data', function (): void {
    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'type_id' => LocationTypes::STORE->value,
    ]);
    $date = Carbon::now()->format('Y-m-d');
    $startWeek = Carbon::now()->startOfWeek()->format('Y-m-d');
    $endWeek = Carbon::now()->endOfWeek()->format('Y-m-d');

    $this->saleTargetData->amount_type = SaleTargetAmountTypes::PERCENTAGE->value;
    $this->saleTargetData->amount = 0;
    $this->saleTargetData->percentage = 5;
    $this->saleTargetData->week_tiers = [[
        'weeks' => [$startWeek, $endWeek],
    ]];
    $this->saleTargetData->time_interval_type = TimeIntervalType::WEEKLY->value;
    $this->saleTargetData->location_ids = [$location->id];
    $this->saleTargetData->target_type = TargetType::STORE_WISE->value;

    $counter = Counter::factory()->make([
        'id' => 1,
        'location_id' => $location->id,
    ]);

    $counterUpdate = CounterUpdate::factory()->make([
        'id' => 1,
        'counter_id' => $counter->id,
        'cashier_id' => 1,
    ]);

    $sale = Sale::factory()->make([
        'id' => 1,
        'counter_update_id' => $counterUpdate->id,
        'member_id' => null,
        'happened_at' => CommonFunctions::addStartTime($date),
        'total_sales_amount' => 100,
    ]);

    $saleReturn = SaleReturn::factory()->make([
        'id' => 1,
        'counter_update_id' => $counterUpdate->id,
        'original_sale_id' => null,
        'member_id' => null,
        'happened_at' => CommonFunctions::addStartTime($date),
        'total_return_amount' => 10,
    ]);

    $this->mock(SaleQueries::class, function ($mock) use ($sale): void {
        $mock->shouldReceive('getTotalAmountForSaleStoreTarget')
            ->once()
            ->andReturn(collect([$sale]));
    });

    $this->mock(SaleReturnQueries::class, function ($mock) use ($saleReturn): void {
        $mock->shouldReceive('getTotalAmountForSaleStoreTarget')
            ->once()
            ->andReturn(collect([$saleReturn]));
    });

    $netSales = $sale->total_sales_amount - $saleReturn->total_return_amount;
    $amount = $netSales + ($netSales * $this->saleTargetData->percentage / 100);

    $response = $this->saleTargetSaleAmountService->handleSaleTargetData($this->saleTargetData, $this->companyId);

    expect($response)->toBe($amount);
});

test('handleSaleTargetData calculates the store wise target amount based on monthly sales data', function (): void {
    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $date = Carbon::now()->format('Y-m-d');
    $month = Carbon::now()->startOfMonth()->format('m');
    $year = Carbon::now()->endOfMonth()->format('Y');

    $this->saleTargetData->amount_type = SaleTargetAmountTypes::PERCENTAGE->value;
    $this->saleTargetData->amount = 0;
    $this->saleTargetData->percentage = 5;
    $this->saleTargetData->month_tiers = [[
        'months' => [
            'month' => (int) $month,
            'year' => (int) $year,
        ],
    ]];
    $this->saleTargetData->time_interval_type = TimeIntervalType::MONTHLY->value;
    $this->saleTargetData->location_ids = [$location->id];
    $this->saleTargetData->target_type = TargetType::STORE_WISE->value;

    $counter = Counter::factory()->make([
        'id' => 1,
        'location_id' => $location->id,
    ]);

    $counterUpdate = CounterUpdate::factory()->make([
        'id' => 1,
        'counter_id' => $counter->id,
        'cashier_id' => 1,
    ]);

    $sale = Sale::factory()->make([
        'id' => 1,
        'counter_update_id' => $counterUpdate->id,
        'member_id' => null,
        'happened_at' => CommonFunctions::addStartTime($date),
        'total_sales_amount' => 100,
    ]);

    $saleReturn = SaleReturn::factory()->make([
        'id' => 1,
        'counter_update_id' => $counterUpdate->id,
        'original_sale_id' => null,
        'member_id' => null,
        'happened_at' => CommonFunctions::addStartTime($date),
        'total_return_amount' => 10,
    ]);

    $this->mock(SaleQueries::class, function ($mock) use ($sale): void {
        $mock->shouldReceive('getTotalAmountForSaleStoreTarget')
            ->once()
            ->andReturn(collect([$sale]));
    });

    $this->mock(SaleReturnQueries::class, function ($mock) use ($saleReturn): void {
        $mock->shouldReceive('getTotalAmountForSaleStoreTarget')
            ->once()
            ->andReturn(collect([$saleReturn]));
    });

    $netSales = $sale->total_sales_amount - $saleReturn->total_return_amount;
    $amount = $netSales + ($netSales * $this->saleTargetData->percentage / 100);

    $response = $this->saleTargetSaleAmountService->handleSaleTargetData($this->saleTargetData, $this->companyId);

    expect($response)->toBe($amount);
});

test('handleSaleTargetData calculates the store wise target amount based on yearly sales data', function (): void {
    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $date = Carbon::now()->format('Y-m-d');
    $year = Carbon::now()->format('Y');

    $this->saleTargetData->amount_type = SaleTargetAmountTypes::PERCENTAGE->value;
    $this->saleTargetData->amount = 0;
    $this->saleTargetData->percentage = 5;
    $this->saleTargetData->year = (int) $year;
    $this->saleTargetData->time_interval_type = TimeIntervalType::YEARLY->value;
    $this->saleTargetData->location_ids = [$location->id];
    $this->saleTargetData->target_type = TargetType::STORE_WISE->value;

    $counter = Counter::factory()->make([
        'id' => 1,
        'location_id' => $location->id,
    ]);

    $counterUpdate = CounterUpdate::factory()->make([
        'id' => 1,
        'counter_id' => $counter->id,
        'cashier_id' => 1,
    ]);

    $sale = Sale::factory()->make([
        'id' => 1,
        'counter_update_id' => $counterUpdate->id,
        'member_id' => null,
        'happened_at' => CommonFunctions::addStartTime($date),
        'total_sales_amount' => 100,
    ]);

    $saleReturn = SaleReturn::factory()->make([
        'id' => 1,
        'counter_update_id' => $counterUpdate->id,
        'original_sale_id' => null,
        'member_id' => null,
        'happened_at' => CommonFunctions::addStartTime($date),
        'total_return_amount' => 10,
    ]);

    $this->mock(SaleQueries::class, function ($mock) use ($sale): void {
        $mock->shouldReceive('getTotalAmountForSaleStoreTarget')
            ->once()
            ->andReturn(collect([$sale]));
    });

    $this->mock(SaleReturnQueries::class, function ($mock) use ($saleReturn): void {
        $mock->shouldReceive('getTotalAmountForSaleStoreTarget')
            ->once()
            ->andReturn(collect([$saleReturn]));
    });

    $netSales = $sale->total_sales_amount - $saleReturn->total_return_amount;
    $amount = $netSales + ($netSales * $this->saleTargetData->percentage / 100);

    $response = $this->saleTargetSaleAmountService->handleSaleTargetData($this->saleTargetData, $this->companyId);

    expect($response)->toBe($amount);
});

test('handleSaleTargetData calculates the promoter wise target amount based on daily sales data', function (): void {
    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $promoter = Promoter::factory()->make([
        'id' => 1,
        'employee_id' => 1,
    ]);

    $date = Carbon::now()->format('Y-m-d');
    $this->saleTargetData->amount_type = SaleTargetAmountTypes::PERCENTAGE->value;
    $this->saleTargetData->target_type = TargetType::PROMOTER_WISE->value;
    $this->saleTargetData->amount = 0;
    $this->saleTargetData->percentage = 5;
    $this->saleTargetData->dates = [$date, $date];
    $this->saleTargetData->promoter_ids = [$promoter->id];

    $counter = Counter::factory()->make([
        'id' => 1,
        'location_id' => $location->id,
    ]);

    $counterUpdate = CounterUpdate::factory()->make([
        'id' => 1,
        'counter_id' => $counter->id,
        'cashier_id' => 1,
    ]);

    $sale = Sale::factory()->make([
        'id' => 1,
        'counter_update_id' => $counterUpdate->id,
        'member_id' => null,
        'happened_at' => CommonFunctions::addStartTime($date),
        'total_sales_amount' => 100,
    ]);

    $saleReturn = SaleReturn::factory()->make([
        'id' => 1,
        'counter_update_id' => $counterUpdate->id,
        'original_sale_id' => null,
        'member_id' => null,
        'happened_at' => CommonFunctions::addStartTime($date),
        'total_return_amount' => 10,
    ]);

    $amountSold = $sale->total_sales_amount - $saleReturn->total_return_amount;

    $this->mock(PromoterQueries::class, function ($mock) use ($amountSold): void {
        $mock->shouldReceive('getTotalAmountForSalePromoterTarget')
            ->once()
            ->andReturn(collect([[
                'amount_sold' => $amountSold,
            ]]));
    });

    $netSales = $sale->total_sales_amount - $saleReturn->total_return_amount;
    $amount = $netSales + ($netSales * $this->saleTargetData->percentage / 100);

    $response = $this->saleTargetSaleAmountService->handleSaleTargetData($this->saleTargetData, $this->companyId);

    expect($response)->toBe($amount);
});

test(
    'handleSaleTargetData calculates the promoter wise target amount based on custom period sales data',
    function (): void {
        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $promoter = Promoter::factory()->make([
            'id' => 1,
            'employee_id' => 1,
        ]);

        $date = Carbon::now()->format('Y-m-d');
        $this->saleTargetData->amount_type = SaleTargetAmountTypes::PERCENTAGE->value;
        $this->saleTargetData->amount = 0;
        $this->saleTargetData->percentage = 5;
        $this->saleTargetData->dates = [$date, $date];
        $this->saleTargetData->time_interval_type = TimeIntervalType::CUSTOM_PERIOD->value;
        $this->saleTargetData->promoter_ids = [$promoter->id];
        $this->saleTargetData->target_type = TargetType::PROMOTER_WISE->value;

        $counter = Counter::factory()->make([
            'id' => 1,
            'location_id' => $location->id,
        ]);

        $counterUpdate = CounterUpdate::factory()->make([
            'id' => 1,
            'counter_id' => $counter->id,
            'cashier_id' => 1,
        ]);

        $sale = Sale::factory()->make([
            'id' => 1,
            'counter_update_id' => $counterUpdate->id,
            'member_id' => null,
            'happened_at' => CommonFunctions::addStartTime($date),
            'total_sales_amount' => 100,
        ]);

        $saleReturn = SaleReturn::factory()->make([
            'id' => 1,
            'counter_update_id' => $counterUpdate->id,
            'original_sale_id' => null,
            'member_id' => null,
            'happened_at' => CommonFunctions::addStartTime($date),
            'total_return_amount' => 10,
        ]);

        $amountSold = $sale->total_sales_amount - $saleReturn->total_return_amount;

        $this->mock(PromoterQueries::class, function ($mock) use ($amountSold): void {
            $mock->shouldReceive('getTotalAmountForSalePromoterTarget')
                ->once()
                ->andReturn(collect([[
                    'amount_sold' => $amountSold,
                ]]));
        });

        $netSales = $sale->total_sales_amount - $saleReturn->total_return_amount;
        $amount = $netSales + ($netSales * $this->saleTargetData->percentage / 100);

        $response = $this->saleTargetSaleAmountService->handleSaleTargetData($this->saleTargetData, $this->companyId);

        expect($response)->toBe($amount);
    }
);

test('handleSaleTargetData calculates the promoter wise target amount based on weekly sales data', function (): void {
    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $promoter = Promoter::factory()->make([
        'id' => 1,
        'employee_id' => 1,
    ]);
    $date = Carbon::now()->format('Y-m-d');
    $startWeek = Carbon::now()->startOfWeek()->format('Y-m-d');
    $endWeek = Carbon::now()->endOfWeek()->format('Y-m-d');

    $this->saleTargetData->amount_type = SaleTargetAmountTypes::PERCENTAGE->value;
    $this->saleTargetData->amount = 0;
    $this->saleTargetData->percentage = 5;
    $this->saleTargetData->week_tiers = [[
        'weeks' => [$startWeek, $endWeek],
    ]];
    $this->saleTargetData->time_interval_type = TimeIntervalType::WEEKLY->value;
    $this->saleTargetData->promoter_ids = [$promoter->id];
    $this->saleTargetData->target_type = TargetType::PROMOTER_WISE->value;

    $counter = Counter::factory()->make([
        'id' => 1,
        'location_id' => $location->id,
    ]);

    $counterUpdate = CounterUpdate::factory()->make([
        'id' => 1,
        'counter_id' => $counter->id,
        'cashier_id' => 1,
    ]);

    $sale = Sale::factory()->make([
        'id' => 1,
        'counter_update_id' => $counterUpdate->id,
        'member_id' => null,
        'happened_at' => CommonFunctions::addStartTime($date),
        'total_sales_amount' => 100,
    ]);

    $saleReturn = SaleReturn::factory()->make([
        'id' => 1,
        'counter_update_id' => $counterUpdate->id,
        'original_sale_id' => null,
        'member_id' => null,
        'happened_at' => CommonFunctions::addStartTime($date),
        'total_return_amount' => 10,
    ]);

    $amountSold = $sale->total_sales_amount - $saleReturn->total_return_amount;

    $this->mock(PromoterQueries::class, function ($mock) use ($amountSold): void {
        $mock->shouldReceive('getTotalAmountForSalePromoterTarget')
            ->once()
            ->andReturn(collect([[
                'amount_sold' => $amountSold,
            ]]));
    });

    $netSales = $sale->total_sales_amount - $saleReturn->total_return_amount;
    $amount = $netSales + ($netSales * $this->saleTargetData->percentage / 100);

    $response = $this->saleTargetSaleAmountService->handleSaleTargetData($this->saleTargetData, $this->companyId);

    expect($response)->toBe($amount);
});

test('handleSaleTargetData calculates the promoter wise target amount based on monthly sales data', function (): void {
    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $promoter = Promoter::factory()->make([
        'id' => 1,
        'employee_id' => 1,
    ]);

    $date = Carbon::now()->format('Y-m-d');
    $month = Carbon::now()->startOfMonth()->format('m');
    $year = Carbon::now()->endOfMonth()->format('Y');

    $this->saleTargetData->amount_type = SaleTargetAmountTypes::PERCENTAGE->value;
    $this->saleTargetData->amount = 0;
    $this->saleTargetData->percentage = 5;
    $this->saleTargetData->month_tiers = [[
        'months' => [
            'month' => (int) $month,
            'year' => (int) $year,
        ],
    ]];
    $this->saleTargetData->time_interval_type = TimeIntervalType::MONTHLY->value;
    $this->saleTargetData->promoter_ids = [$promoter->id];
    $this->saleTargetData->target_type = TargetType::PROMOTER_WISE->value;

    $counter = Counter::factory()->make([
        'id' => 1,
        'location_id' => $location->id,
    ]);

    $counterUpdate = CounterUpdate::factory()->make([
        'id' => 1,
        'counter_id' => $counter->id,
        'cashier_id' => 1,
    ]);

    $sale = Sale::factory()->make([
        'id' => 1,
        'counter_update_id' => $counterUpdate->id,
        'member_id' => null,
        'happened_at' => CommonFunctions::addStartTime($date),
        'total_sales_amount' => 100,
    ]);

    $saleReturn = SaleReturn::factory()->make([
        'id' => 1,
        'counter_update_id' => $counterUpdate->id,
        'original_sale_id' => null,
        'member_id' => null,
        'happened_at' => CommonFunctions::addStartTime($date),
        'total_return_amount' => 10,
    ]);

    $amountSold = $sale->total_sales_amount - $saleReturn->total_return_amount;

    $this->mock(PromoterQueries::class, function ($mock) use ($amountSold): void {
        $mock->shouldReceive('getTotalAmountForSalePromoterTarget')
            ->once()
            ->andReturn(collect([[
                'amount_sold' => $amountSold,
            ]]));
    });

    $netSales = $sale->total_sales_amount - $saleReturn->total_return_amount;
    $amount = $netSales + ($netSales * $this->saleTargetData->percentage / 100);

    $response = $this->saleTargetSaleAmountService->handleSaleTargetData($this->saleTargetData, $this->companyId);

    expect($response)->toBe($amount);
});

test('handleSaleTargetData calculates the promoter wise target amount based on yearly sales data', function (): void {
    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $promoter = Promoter::factory()->make([
        'id' => 1,
        'employee_id' => 1,
    ]);

    $date = Carbon::now()->format('Y-m-d');
    $year = Carbon::now()->format('Y');

    $this->saleTargetData->amount_type = SaleTargetAmountTypes::PERCENTAGE->value;
    $this->saleTargetData->amount = 0;
    $this->saleTargetData->percentage = 5;
    $this->saleTargetData->year = (int) $year;
    $this->saleTargetData->time_interval_type = TimeIntervalType::YEARLY->value;
    $this->saleTargetData->promoter_ids = [$promoter->id];
    $this->saleTargetData->target_type = TargetType::PROMOTER_WISE->value;

    $counter = Counter::factory()->make([
        'id' => 1,
        'location_id' => $location->id,
    ]);

    $counterUpdate = CounterUpdate::factory()->make([
        'id' => 1,
        'counter_id' => $counter->id,
        'cashier_id' => 1,
    ]);

    $sale = Sale::factory()->make([
        'id' => 1,
        'counter_update_id' => $counterUpdate->id,
        'member_id' => null,
        'happened_at' => CommonFunctions::addStartTime($date),
        'total_sales_amount' => 100,
    ]);

    $saleReturn = SaleReturn::factory()->make([
        'id' => 1,
        'counter_update_id' => $counterUpdate->id,
        'original_sale_id' => null,
        'member_id' => null,
        'happened_at' => CommonFunctions::addStartTime($date),
        'total_return_amount' => 10,
    ]);

    $amountSold = $sale->total_sales_amount - $saleReturn->total_return_amount;

    $this->mock(PromoterQueries::class, function ($mock) use ($amountSold): void {
        $mock->shouldReceive('getTotalAmountForSalePromoterTarget')
            ->once()
            ->andReturn(collect([[
                'amount_sold' => $amountSold,
            ]]));
    });

    $netSales = $sale->total_sales_amount - $saleReturn->total_return_amount;
    $amount = $netSales + ($netSales * $this->saleTargetData->percentage / 100);

    $response = $this->saleTargetSaleAmountService->handleSaleTargetData($this->saleTargetData, $this->companyId);

    expect($response)->toBe($amount);
});

test('it calculates the store-wise daily and custom sale target amount', function (): void {
    $dates = ['2024-01-01', '2024-01-10'];
    $locationIds = [1, 2, 3];
    $percentage = 5;

    $sale = Sale::factory()->make([
        'id' => 1,
        'counter_update_id' => 1,
        'member_id' => null,
        'happened_at' => CommonFunctions::addStartTime($dates[0]),
        'total_sales_amount' => 100,
    ]);

    $saleReturn = SaleReturn::factory()->make([
        'id' => 1,
        'counter_update_id' => 1,
        'original_sale_id' => null,
        'member_id' => null,
        'happened_at' => CommonFunctions::addStartTime($dates[0]),
        'total_return_amount' => 10,
    ]);

    $this->mock(SaleQueries::class, function ($mock) use ($sale): void {
        $mock->shouldReceive('getTotalAmountForSaleStoreTarget')
            ->once()
            ->andReturn(collect([$sale]));
    });

    $this->mock(SaleReturnQueries::class, function ($mock) use ($saleReturn): void {
        $mock->shouldReceive('getTotalAmountForSaleStoreTarget')
            ->once()
            ->andReturn(collect([$saleReturn]));
    });

    $result = $this->saleTargetSaleAmountService->storeWiseDailyAndCustomSaleTargetAmount(
        $dates,
        $locationIds,
        $percentage
    );

    $netSales = $sale->total_sales_amount - $saleReturn->total_return_amount;
    $amount = $netSales + ($netSales * $percentage / 100);

    expect($result)->toBe($amount);
});

test('it calculates the store-wise monthly sale target amount', function (): void {
    $date = Carbon::now();
    $dates = [[
        'months' => [
            'month' => (int) $date->format('m'),
            'year' => (int) $date->format('Y'),
        ],
    ]];
    $locationIds = [1, 2, 3];
    $percentage = 5;

    $sale = Sale::factory()->make([
        'id' => 1,
        'counter_update_id' => 1,
        'member_id' => null,
        'happened_at' => CommonFunctions::addStartTime($date->format('Y-m-d')),
        'total_sales_amount' => 100,
    ]);

    $saleReturn = SaleReturn::factory()->make([
        'id' => 1,
        'counter_update_id' => 1,
        'original_sale_id' => null,
        'member_id' => null,
        'happened_at' => CommonFunctions::addStartTime($date->format('Y-m-d')),
        'total_return_amount' => 10,
    ]);

    $this->mock(SaleQueries::class, function ($mock) use ($sale): void {
        $mock->shouldReceive('getTotalAmountForSaleStoreTarget')
            ->once()
            ->andReturn(collect([$sale]));
    });

    $this->mock(SaleReturnQueries::class, function ($mock) use ($saleReturn): void {
        $mock->shouldReceive('getTotalAmountForSaleStoreTarget')
            ->once()
            ->andReturn(collect([$saleReturn]));
    });

    $result = $this->saleTargetSaleAmountService->storeWiseMonthlySaleTargetAmount($dates, $locationIds, $percentage);

    $netSales = $sale->total_sales_amount - $saleReturn->total_return_amount;
    $amount = $netSales + ($netSales * $percentage / 100);

    expect($result)->toBe($amount);
});

test('it calculates the store-wise weekly sale target amount', function (): void {
    $date = Carbon::now();
    $startWeek = $date->clone()->startOfWeek()->format('Y-m-d');
    $endWeek = $date->clone()->endOfWeek()->format('Y-m-d');
    $dates = [[
        'weeks' => [$startWeek, $endWeek],
    ]];
    $locationIds = [1, 2, 3];
    $percentage = 5;

    $sale = Sale::factory()->make([
        'id' => 1,
        'counter_update_id' => 1,
        'member_id' => null,
        'happened_at' => CommonFunctions::addStartTime($date->format('Y-m-d')),
        'total_sales_amount' => 100,
    ]);

    $saleReturn = SaleReturn::factory()->make([
        'id' => 1,
        'counter_update_id' => 1,
        'original_sale_id' => null,
        'member_id' => null,
        'happened_at' => CommonFunctions::addStartTime($date->format('Y-m-d')),
        'total_return_amount' => 10,
    ]);

    $this->mock(SaleQueries::class, function ($mock) use ($sale): void {
        $mock->shouldReceive('getTotalAmountForSaleStoreTarget')
            ->once()
            ->andReturn(collect([$sale]));
    });

    $this->mock(SaleReturnQueries::class, function ($mock) use ($saleReturn): void {
        $mock->shouldReceive('getTotalAmountForSaleStoreTarget')
            ->once()
            ->andReturn(collect([$saleReturn]));
    });

    $result = $this->saleTargetSaleAmountService->storeWiseWeeklySaleTargetAmount($dates, $locationIds, $percentage);

    $netSales = $sale->total_sales_amount - $saleReturn->total_return_amount;
    $amount = $netSales + ($netSales * $percentage / 100);

    expect($result)->toBe($amount);
});

test('it calculates the store-wise yearly sale target amount', function (): void {
    $date = Carbon::now();
    $locationIds = [1, 2, 3];
    $percentage = 5;

    $sale = Sale::factory()->make([
        'id' => 1,
        'counter_update_id' => 1,
        'member_id' => null,
        'happened_at' => CommonFunctions::addStartTime($date->format('Y-m-d')),
        'total_sales_amount' => 100,
    ]);

    $saleReturn = SaleReturn::factory()->make([
        'id' => 1,
        'counter_update_id' => 1,
        'original_sale_id' => null,
        'member_id' => null,
        'happened_at' => CommonFunctions::addStartTime($date->format('Y-m-d')),
        'total_return_amount' => 10,
    ]);

    $this->mock(SaleQueries::class, function ($mock) use ($sale): void {
        $mock->shouldReceive('getTotalAmountForSaleStoreTarget')
            ->once()
            ->andReturn(collect([$sale]));
    });

    $this->mock(SaleReturnQueries::class, function ($mock) use ($saleReturn): void {
        $mock->shouldReceive('getTotalAmountForSaleStoreTarget')
            ->once()
            ->andReturn(collect([$saleReturn]));
    });

    $result = $this->saleTargetSaleAmountService->storeWiseYearlySaleTargetAmount(
        (int) $date->format('Y'),
        $locationIds,
        $percentage
    );

    $netSales = $sale->total_sales_amount - $saleReturn->total_return_amount;
    $amount = $netSales + ($netSales * $percentage / 100);

    expect($result)->toBe($amount);
});

test('it calculates the promoter-wise daily and custom sale target amount', function (): void {
    $dates = ['2024-01-01', '2024-01-10'];
    $promoterIds = [1, 2, 3];
    $percentage = 5;

    $sale = Sale::factory()->make([
        'id' => 1,
        'counter_update_id' => 1,
        'member_id' => null,
        'happened_at' => CommonFunctions::addStartTime($dates[0]),
        'total_sales_amount' => 100,
    ]);

    $saleReturn = SaleReturn::factory()->make([
        'id' => 1,
        'counter_update_id' => 1,
        'original_sale_id' => null,
        'member_id' => null,
        'happened_at' => CommonFunctions::addStartTime($dates[0]),
        'total_return_amount' => 10,
    ]);

    $amountSold = $sale->total_sales_amount - $saleReturn->total_return_amount;

    $this->mock(PromoterQueries::class, function ($mock) use ($amountSold): void {
        $mock->shouldReceive('getTotalAmountForSalePromoterTarget')
            ->once()
            ->andReturn(collect([[
                'amount_sold' => $amountSold,
            ]]));
    });

    $result = $this->saleTargetSaleAmountService->promoterWiseDailyAndCustomSaleTargetAmount(
        $dates,
        $promoterIds,
        $percentage
    );

    $netSales = $sale->total_sales_amount - $saleReturn->total_return_amount;
    $amount = $netSales + ($netSales * $percentage / 100);

    expect($result)->toBe($amount);
});

test('it calculates the promoter-wise monthly sale target amount', function (): void {
    $date = Carbon::now();
    $dates = [[
        'months' => [
            'month' => (int) $date->format('m'),
            'year' => (int) $date->format('Y'),
        ],
    ]];
    $promoterIds = [1, 2, 3];
    $percentage = 5;

    $sale = Sale::factory()->make([
        'id' => 1,
        'counter_update_id' => 1,
        'member_id' => null,
        'happened_at' => CommonFunctions::addStartTime($date->format('Y-m-d')),
        'total_sales_amount' => 100,
    ]);

    $saleReturn = SaleReturn::factory()->make([
        'id' => 1,
        'counter_update_id' => 1,
        'original_sale_id' => null,
        'member_id' => null,
        'happened_at' => CommonFunctions::addStartTime($date->format('Y-m-d')),
        'total_return_amount' => 10,
    ]);

    $amountSold = $sale->total_sales_amount - $saleReturn->total_return_amount;

    $this->mock(PromoterQueries::class, function ($mock) use ($amountSold): void {
        $mock->shouldReceive('getTotalAmountForSalePromoterTarget')
            ->once()
            ->andReturn(collect([[
                'amount_sold' => $amountSold,
            ]]));
    });

    $result = $this->saleTargetSaleAmountService->promoterWiseMonthlySaleTargetAmount(
        $dates,
        $promoterIds,
        $percentage
    );

    $netSales = $sale->total_sales_amount - $saleReturn->total_return_amount;
    $amount = $netSales + ($netSales * $percentage / 100);

    expect($result)->toBe($amount);
});

test('it calculates the promoter-wise weekly sale target amount', function (): void {
    $date = Carbon::now();
    $startWeek = $date->clone()->startOfWeek()->format('Y-m-d');
    $endWeek = $date->clone()->endOfWeek()->format('Y-m-d');
    $dates = [[
        'weeks' => [$startWeek, $endWeek],
    ]];
    $promoterIds = [1, 2, 3];
    $percentage = 5;

    $sale = Sale::factory()->make([
        'id' => 1,
        'counter_update_id' => 1,
        'member_id' => null,
        'happened_at' => CommonFunctions::addStartTime($date->format('Y-m-d')),
        'total_sales_amount' => 100,
    ]);

    $saleReturn = SaleReturn::factory()->make([
        'id' => 1,
        'counter_update_id' => 1,
        'original_sale_id' => null,
        'member_id' => null,
        'happened_at' => CommonFunctions::addStartTime($date->format('Y-m-d')),
        'total_return_amount' => 10,
    ]);

    $amountSold = $sale->total_sales_amount - $saleReturn->total_return_amount;

    $this->mock(PromoterQueries::class, function ($mock) use ($amountSold): void {
        $mock->shouldReceive('getTotalAmountForSalePromoterTarget')
            ->once()
            ->andReturn(collect([[
                'amount_sold' => $amountSold,
            ]]));
    });

    $result = $this->saleTargetSaleAmountService->promoterWiseWeeklySaleTargetAmount($dates, $promoterIds, $percentage);

    $netSales = $sale->total_sales_amount - $saleReturn->total_return_amount;
    $amount = $netSales + ($netSales * $percentage / 100);

    expect($result)->toBe($amount);
});

test('it calculates the promoter-wise yearly sale target amount', function (): void {
    $date = Carbon::now();
    $promoterIds = [1, 2, 3];
    $percentage = 5;

    $sale = Sale::factory()->make([
        'id' => 1,
        'counter_update_id' => 1,
        'member_id' => null,
        'happened_at' => CommonFunctions::addStartTime($date->format('Y-m-d')),
        'total_sales_amount' => 100,
    ]);

    $saleReturn = SaleReturn::factory()->make([
        'id' => 1,
        'counter_update_id' => 1,
        'original_sale_id' => null,
        'member_id' => null,
        'happened_at' => CommonFunctions::addStartTime($date->format('Y-m-d')),
        'total_return_amount' => 10,
    ]);

    $amountSold = $sale->total_sales_amount - $saleReturn->total_return_amount;

    $this->mock(PromoterQueries::class, function ($mock) use ($amountSold): void {
        $mock->shouldReceive('getTotalAmountForSalePromoterTarget')
            ->once()
            ->andReturn(collect([[
                'amount_sold' => $amountSold,
            ]]));
    });

    $result = $this->saleTargetSaleAmountService->promoterWiseYearlySaleTargetAmount(
        (int) $date->format('Y'),
        $promoterIds,
        $percentage
    );

    $netSales = $sale->total_sales_amount - $saleReturn->total_return_amount;
    $amount = $netSales + ($netSales * $percentage / 100);

    expect($result)->toBe($amount);
});

test('it calculates the company daily and custom sale target amount', function (): void {
    $dates = ['2024-01-01', '2024-01-10'];
    $companyId = 1;
    $percentage = 5;

    $sale = Sale::factory()->make([
        'id' => 1,
        'counter_update_id' => 1,
        'member_id' => null,
        'happened_at' => CommonFunctions::addStartTime($dates[0]),
        'total_sales_amount' => 100,
    ]);

    $saleReturn = SaleReturn::factory()->make([
        'id' => 1,
        'counter_update_id' => 1,
        'original_sale_id' => null,
        'member_id' => null,
        'happened_at' => CommonFunctions::addStartTime($dates[0]),
        'total_return_amount' => 10,
    ]);

    $this->mock(SaleQueries::class, function ($mock) use ($sale): void {
        $mock->shouldReceive('getTotalAmountForSaleCompanyTarget')
            ->once()
            ->andReturn($sale);
    });

    $this->mock(SaleReturnQueries::class, function ($mock) use ($saleReturn): void {
        $mock->shouldReceive('getTotalAmountForSaleCompanyTarget')
            ->once()
            ->andReturn($saleReturn);
    });

    $result = $this->saleTargetSaleAmountService->companyWiseDailyAndCustomSaleTargetAmount(
        $dates,
        $companyId,
        $percentage
    );

    $netSales = $sale->total_sales_amount - $saleReturn->total_return_amount;
    $amount = $netSales + ($netSales * $percentage / 100);

    expect($result)->toBe($amount);
});

test('it calculates the company monthly sale target amount', function (): void {
    $date = Carbon::now();
    $dates = [[
        'months' => [
            'month' => (int) $date->format('m'),
            'year' => (int) $date->format('Y'),
        ],
    ]];
    $companyId = 1;
    $percentage = 5;

    $sale = Sale::factory()->make([
        'id' => 1,
        'counter_update_id' => 1,
        'member_id' => null,
        'happened_at' => CommonFunctions::addStartTime($date->format('Y-m-d')),
        'total_sales_amount' => 100,
    ]);

    $saleReturn = SaleReturn::factory()->make([
        'id' => 1,
        'counter_update_id' => 1,
        'original_sale_id' => null,
        'member_id' => null,
        'happened_at' => CommonFunctions::addStartTime($date->format('Y-m-d')),
        'total_return_amount' => 10,
    ]);

    $this->mock(SaleQueries::class, function ($mock) use ($sale): void {
        $mock->shouldReceive('getTotalAmountForSaleCompanyTarget')
            ->once()
            ->andReturn($sale);
    });

    $this->mock(SaleReturnQueries::class, function ($mock) use ($saleReturn): void {
        $mock->shouldReceive('getTotalAmountForSaleCompanyTarget')
            ->once()
            ->andReturn($saleReturn);
    });

    $result = $this->saleTargetSaleAmountService->companyWiseMonthlySaleTargetAmount($dates, $companyId, $percentage);

    $netSales = $sale->total_sales_amount - $saleReturn->total_return_amount;
    $amount = $netSales + ($netSales * $percentage / 100);

    expect($result)->toBe($amount);
});

test('it calculates the company weekly sale target amount', function (): void {
    $date = Carbon::now();
    $startWeek = $date->clone()->startOfWeek()->format('Y-m-d');
    $endWeek = $date->clone()->endOfWeek()->format('Y-m-d');
    $dates = [[
        'weeks' => [$startWeek, $endWeek],
    ]];
    $companyId = 1;
    $percentage = 5;

    $sale = Sale::factory()->make([
        'id' => 1,
        'counter_update_id' => 1,
        'member_id' => null,
        'happened_at' => CommonFunctions::addStartTime($date->format('Y-m-d')),
        'total_sales_amount' => 100,
    ]);

    $saleReturn = SaleReturn::factory()->make([
        'id' => 1,
        'counter_update_id' => 1,
        'original_sale_id' => null,
        'member_id' => null,
        'happened_at' => CommonFunctions::addStartTime($date->format('Y-m-d')),
        'total_return_amount' => 10,
    ]);

    $this->mock(SaleQueries::class, function ($mock) use ($sale): void {
        $mock->shouldReceive('getTotalAmountForSaleCompanyTarget')
            ->once()
            ->andReturn($sale);
    });

    $this->mock(SaleReturnQueries::class, function ($mock) use ($saleReturn): void {
        $mock->shouldReceive('getTotalAmountForSaleCompanyTarget')
            ->once()
            ->andReturn($saleReturn);
    });

    $result = $this->saleTargetSaleAmountService->companyWiseWeeklySaleTargetAmount($dates, $companyId, $percentage);

    $netSales = $sale->total_sales_amount - $saleReturn->total_return_amount;
    $amount = $netSales + ($netSales * $percentage / 100);

    expect($result)->toBe($amount);
});

test('it calculates the company yearly sale target amount', function (): void {
    $date = Carbon::now();
    $companyId = 1;
    $percentage = 5;

    $sale = Sale::factory()->make([
        'id' => 1,
        'counter_update_id' => 1,
        'member_id' => null,
        'happened_at' => CommonFunctions::addStartTime($date->format('Y-m-d')),
        'total_sales_amount' => 100,
    ]);

    $saleReturn = SaleReturn::factory()->make([
        'id' => 1,
        'counter_update_id' => 1,
        'original_sale_id' => null,
        'member_id' => null,
        'happened_at' => CommonFunctions::addStartTime($date->format('Y-m-d')),
        'total_return_amount' => 10,
    ]);

    $this->mock(SaleQueries::class, function ($mock) use ($sale): void {
        $mock->shouldReceive('getTotalAmountForSaleCompanyTarget')
            ->once()
            ->andReturn($sale);
    });

    $this->mock(SaleReturnQueries::class, function ($mock) use ($saleReturn): void {
        $mock->shouldReceive('getTotalAmountForSaleCompanyTarget')
            ->once()
            ->andReturn($saleReturn);
    });

    $result = $this->saleTargetSaleAmountService->companyWiseYearlySaleTargetAmount(
        (int) $date->format('Y'),
        $companyId,
        $percentage
    );

    $netSales = $sale->total_sales_amount - $saleReturn->total_return_amount;
    $amount = $netSales + ($netSales * $percentage / 100);

    expect($result)->toBe($amount);
});

test('it calculates the total sale amount for store target within a specific date range', function (): void {
    $dates = ['2024-01-01', '2024-01-10'];
    $promoterIds = [1, 2, 3];

    $sale = Sale::factory()->make([
        'id' => 1,
        'counter_update_id' => 1,
        'member_id' => null,
        'happened_at' => CommonFunctions::addStartTime($dates[0]),
        'total_sales_amount' => 100,
    ]);

    $this->mock(SaleQueries::class, function ($mock) use ($sale): void {
        $mock->shouldReceive('getTotalAmountForSaleStoreTarget')
            ->once()
            ->andReturn(collect([$sale]));
    });

    $result = $this->saleTargetSaleAmountService->getTotalSaleAmount($dates[0], $dates[1], $promoterIds);

    expect($result)->toBe((float) $sale->total_sales_amount);
});

test('it calculates the total sale return amount for store target within a specific date range', function (): void {
    $dates = ['2024-01-01', '2024-01-10'];
    $promoterIds = [1, 2, 3];

    $saleReturn = SaleReturn::factory()->make([
        'id' => 1,
        'counter_update_id' => 1,
        'original_sale_id' => null,
        'member_id' => null,
        'happened_at' => CommonFunctions::addStartTime($dates[0]),
        'total_return_amount' => 10,
    ]);

    $this->mock(SaleReturnQueries::class, function ($mock) use ($saleReturn): void {
        $mock->shouldReceive('getTotalAmountForSaleStoreTarget')
            ->once()
            ->andReturn(collect([$saleReturn]));
    });

    $result = $this->saleTargetSaleAmountService->getTotalSaleReturnAmount($dates[0], $dates[1], $promoterIds);

    expect($result)->toBe((float) $saleReturn->total_return_amount);
});

test('it calculates the amount_sold for promoter target within a specific date range', function (): void {
    $date = Carbon::now()->format('Y-m-d');
    $promoterIds = [1, 2, 3];

    $sale = Sale::factory()->make([
        'id' => 1,
        'counter_update_id' => 1,
        'member_id' => null,
        'happened_at' => CommonFunctions::addStartTime($date),
        'total_sales_amount' => 100,
    ]);

    $saleReturn = SaleReturn::factory()->make([
        'id' => 1,
        'counter_update_id' => 1,
        'original_sale_id' => null,
        'member_id' => null,
        'happened_at' => CommonFunctions::addStartTime($date),
        'total_return_amount' => 10,
    ]);

    $amountSold = $sale->total_sales_amount - $saleReturn->total_return_amount;

    $this->mock(PromoterQueries::class, function ($mock) use ($amountSold): void {
        $mock->shouldReceive('getTotalAmountForSalePromoterTarget')
            ->once()
            ->andReturn(collect([[
                'amount_sold' => $amountSold,
            ]]));
    });

    $result = $this->saleTargetSaleAmountService->getPromoterTotalSaleAmount($date, $date, $promoterIds);

    expect($result)->toBe((float) $amountSold);
});

test('it calculates the total sale amount for company target within a specific date range', function (): void {
    $dates = ['2024-01-01', '2024-01-10'];
    $companyId = 1;

    $sale = Sale::factory()->make([
        'id' => 1,
        'counter_update_id' => 1,
        'member_id' => null,
        'happened_at' => CommonFunctions::addStartTime($dates[0]),
        'total_sales_amount' => 100,
    ]);

    $this->mock(SaleQueries::class, function ($mock) use ($sale): void {
        $mock->shouldReceive('getTotalAmountForSaleCompanyTarget')
            ->once()
            ->andReturn($sale);
    });

    $result = $this->saleTargetSaleAmountService->getTotalSaleAmountForCompany($dates[0], $dates[1], $companyId);

    expect($result)->toBe((float) $sale->total_sales_amount);
});

test('it calculates the total sale return amount for company target within a specific date range', function (): void {
    $dates = ['2024-01-01', '2024-01-10'];
    $companyId = 1;

    $saleReturn = SaleReturn::factory()->make([
        'id' => 1,
        'counter_update_id' => 1,
        'original_sale_id' => null,
        'member_id' => null,
        'happened_at' => CommonFunctions::addStartTime($dates[0]),
        'total_return_amount' => 10,
    ]);

    $this->mock(SaleReturnQueries::class, function ($mock) use ($saleReturn): void {
        $mock->shouldReceive('getTotalAmountForSaleCompanyTarget')
            ->once()
            ->andReturn($saleReturn);
    });

    $result = $this->saleTargetSaleAmountService->getTotalSaleReturnAmountForCompany($dates[0], $dates[1], $companyId);

    expect($result)->toBe((float) $saleReturn->total_return_amount);
});

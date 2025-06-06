<?php

declare(strict_types=1);

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Notification\Jobs\SendSaleTargetNotificationsJob;
use App\Domains\Promoter\PromoterQueries;
use App\Domains\Sale\SaleQueries;
use App\Domains\SaleAchievedTarget\SaleAchievedTargetQueries;
use App\Domains\SaleReturn\SaleReturnQueries;
use App\Domains\SaleTarget\Enums\TargetType;
use App\Domains\SaleTarget\Jobs\SaleAchievedTargetJob;
use App\Domains\SaleTarget\SaleTargetQueries;
use App\Domains\SaleTargetTimeframe\SaleTargetTimeframeQueries;
use App\Models\Location;
use App\Models\Promoter;
use App\Models\Sale;
use App\Models\SaleAchievedTarget;
use App\Models\SaleReturn;
use App\Models\SaleTarget;
use App\Models\SaleTargetTimeframe;
use Illuminate\Support\Facades\Queue;

test(
    'SaleAchievedTargetJob job calls addNew methods of saleAchievedTargetQueries when target type company wise',
    function (): void {
        Queue::fake()->except([SaleAchievedTargetJob::class]);

        $saleTarget = SaleTarget::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'target_type' => TargetType::COMPANY_WISE->value,
        ]);

        $saleTargetTimeframe = SaleTargetTimeframe::factory()->make([
            'id' => 1,
            'sale_target_id' => 1,
        ]);

        $saleTargetTimeframe->saleTarget = $saleTarget;
        $saleTargetTimeframe->saleAchievedTargets = collect([]);

        $this->mock(SaleTargetTimeframeQueries::class, function ($mock) use ($saleTargetTimeframe): void {
            $mock->shouldReceive('getByStartAndEndDate')
                ->times(1)
                ->andReturn(collect([$saleTargetTimeframe]));
        });

        $sale = Sale::factory()->make([
            'id' => 1,
            'member_id' => 1,
            'counter_update_id' => 1,
        ]);

        $sale->total_sales_amount = 100;

        $this->mock(SaleQueries::class, function ($mock) use ($sale): void {
            $mock->shouldReceive('getTotalAmountForSaleCompanyTarget')
                ->times(1)
                ->andReturn($sale);
        });

        $saleReturn = SaleReturn::factory()->make([
            'id' => 1,
            'member_id' => 1,
            'counter_update_id' => 1,
            'original_sale_id' => 1,
        ]);

        $saleReturn->total_return_amount = 50;

        $this->mock(SaleReturnQueries::class, function ($mock) use ($saleReturn): void {
            $mock->shouldReceive('getTotalAmountForSaleCompanyTarget')
                ->times(1)
                ->andReturn($saleReturn);
        });

        $this->mock(SaleAchievedTargetQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->times(1);
        });

        SaleAchievedTargetJob::dispatch()->onQueue(config('horizon.default_queue_name'));
        Queue::assertPushed(SendSaleTargetNotificationsJob::class);
    }
);

test(
    'SaleAchievedTargetJob job calls updateAchievedValue methods of saleAchievedTargetQueries when target type company wise',
    function (): void {
        Queue::fake()->except([SaleAchievedTargetJob::class]);

        $saleTarget = SaleTarget::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'target_type' => TargetType::COMPANY_WISE->value,
        ]);

        $saleTargetTimeframe = SaleTargetTimeframe::factory()->make([
            'id' => 1,
            'sale_target_id' => 1,
        ]);

        $saleTargetTimeframe->saleTarget = $saleTarget;

        $saleAchievedTarget = SaleAchievedTarget::factory()->make([
            'id' => 1,
            'sale_target_timeframe_id' => 1,
            'targetable_id' => 1,
        ]);

        $saleTargetTimeframe->saleAchievedTargets = collect([$saleAchievedTarget]);

        $this->mock(SaleTargetTimeframeQueries::class, function ($mock) use ($saleTargetTimeframe): void {
            $mock->shouldReceive('getByStartAndEndDate')
                ->times(1)
                ->andReturn(collect([$saleTargetTimeframe]));
        });

        $sale = Sale::factory()->make([
            'id' => 1,
            'member_id' => 1,
            'counter_update_id' => 1,
        ]);

        $sale->total_sales_amount = 100;

        $this->mock(SaleQueries::class, function ($mock) use ($sale): void {
            $mock->shouldReceive('getTotalAmountForSaleCompanyTarget')
                ->times(1)
                ->andReturn($sale);
        });

        $saleReturn = SaleReturn::factory()->make([
            'id' => 1,
            'member_id' => 1,
            'counter_update_id' => 1,
            'original_sale_id' => 1,
        ]);

        $saleReturn->total_return_amount = 50;

        $this->mock(SaleReturnQueries::class, function ($mock) use ($saleReturn): void {
            $mock->shouldReceive('getTotalAmountForSaleCompanyTarget')
                ->times(1)
                ->andReturn($saleReturn);
        });

        $this->mock(SaleAchievedTargetQueries::class, function ($mock): void {
            $mock->shouldReceive('updateAchievedValue')
                ->times(1);
        });

        SaleAchievedTargetJob::dispatch()->onQueue(config('horizon.default_queue_name'));
        Queue::assertPushed(SendSaleTargetNotificationsJob::class);
    }
);

test(
    'SaleAchievedTargetJob job calls addNew methods of saleAchievedTargetQueries when target type store wise',
    function (): void {
        Queue::fake()->except([SaleAchievedTargetJob::class]);

        $saleTarget = SaleTarget::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'target_type' => TargetType::STORE_WISE->value,
        ]);

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $saleTarget->locations = collect([$location]);

        $saleTargetTimeframe = SaleTargetTimeframe::factory()->make([
            'id' => 1,
            'sale_target_id' => 1,
        ]);

        $saleTargetTimeframe->saleTarget = $saleTarget;
        $saleTargetTimeframe->saleAchievedTargets = collect([]);

        $this->mock(SaleTargetTimeframeQueries::class, function ($mock) use ($saleTargetTimeframe): void {
            $mock->shouldReceive('getByStartAndEndDate')
                ->times(1)
                ->andReturn(collect([$saleTargetTimeframe]));

            $mock->shouldReceive('refresh')
                ->times(2)
                ->andReturn($saleTargetTimeframe);
        });

        $sale = Sale::factory()->make([
            'id' => 1,
            'member_id' => 1,
            'counter_update_id' => 1,
        ]);

        $sale['location_id'] = $location->id;

        $sale->total_sales_amount = 100;

        $this->mock(SaleQueries::class, function ($mock) use ($sale): void {
            $mock->shouldReceive('getTotalAmountForSaleStoreTarget')
                ->times(1)
                ->andReturn(collect([$sale]));
        });

        $saleReturn = SaleReturn::factory()->make([
            'id' => 1,
            'member_id' => 1,
            'counter_update_id' => 1,
            'original_sale_id' => 1,
        ]);

        $saleReturn['location_id'] = $location->id;

        $saleReturn->total_return_amount = 50;

        $this->mock(SaleReturnQueries::class, function ($mock) use ($saleReturn): void {
            $mock->shouldReceive('getTotalAmountForSaleStoreTarget')
                ->times(1)
                ->andReturn(collect([$saleReturn]));
        });

        $this->mock(SaleAchievedTargetQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->times(2);
        });

        SaleAchievedTargetJob::dispatch()->onQueue(config('horizon.default_queue_name'));
        Queue::assertPushed(SendSaleTargetNotificationsJob::class);
    }
);

test(
    'SaleAchievedTargetJob job calls updateAchievedValue methods of saleAchievedTargetQueries when target type store wise',
    function (): void {
        Queue::fake()->except([SaleAchievedTargetJob::class]);

        $saleTarget = SaleTarget::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'target_type' => TargetType::STORE_WISE->value,
        ]);

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $saleTarget->locations = collect([$location]);

        $saleTargetTimeframe = SaleTargetTimeframe::factory()->make([
            'id' => 1,
            'sale_target_id' => 1,
        ]);

        $saleTargetTimeframe->saleTarget = $saleTarget;
        $saleAchievedTarget = SaleAchievedTarget::factory()->make([
            'id' => 1,
            'sale_target_timeframe_id' => 1,
            'targetable_id' => 1,
            'targetable_type' => ModelMapping::LOCATION->name,
        ]);

        $saleTargetTimeframe->saleAchievedTargets = collect([$saleAchievedTarget]);

        $this->mock(SaleTargetTimeframeQueries::class, function ($mock) use ($saleTargetTimeframe): void {
            $mock->shouldReceive('getByStartAndEndDate')
                ->times(1)
                ->andReturn(collect([$saleTargetTimeframe]));

            $mock->shouldReceive('refresh')
                ->times(2)
                ->andReturn($saleTargetTimeframe);
        });

        $sale = Sale::factory()->make([
            'id' => 1,
            'member_id' => 1,
            'counter_update_id' => 1,
        ]);
        $sale->location_id = 1;
        $sale->total_sales_amount = 100;

        $this->mock(SaleQueries::class, function ($mock) use ($sale): void {
            $mock->shouldReceive('getTotalAmountForSaleStoreTarget')
                ->times(1)
                ->andReturn(collect([$sale]));
        });

        $saleReturn = SaleReturn::factory()->make([
            'id' => 1,
            'member_id' => 1,
            'counter_update_id' => 1,
            'original_sale_id' => 1,
        ]);

        $saleReturn->location_id = 1;
        $saleReturn->total_return_amount = 50;

        $this->mock(SaleReturnQueries::class, function ($mock) use ($saleReturn): void {
            $mock->shouldReceive('getTotalAmountForSaleStoreTarget')
                ->times(1)
                ->andReturn(collect([$saleReturn]));
        });

        $this->mock(SaleAchievedTargetQueries::class, function ($mock): void {
            $mock->shouldReceive('updateAchievedValue')
                ->times(2);
        });

        SaleAchievedTargetJob::dispatch()->onQueue(config('horizon.default_queue_name'));
        Queue::assertPushed(SendSaleTargetNotificationsJob::class);
    }
);

test(
    'SaleAchievedTargetJob job calls addNew methods of saleAchievedTargetQueries when target type promoter wise',
    function (): void {
        Queue::fake()->except([SaleAchievedTargetJob::class]);

        $saleTarget = SaleTarget::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'target_type' => TargetType::PROMOTER_WISE->value,
        ]);

        $promoter = Promoter::factory()->make([
            'id' => 1,
            'employee_id' => 1,
        ]);

        $saleTarget->promoters = collect([$promoter]);

        $saleTargetTimeframe = SaleTargetTimeframe::factory()->make([
            'id' => 1,
            'sale_target_id' => 1,
        ]);

        $saleTargetTimeframe->saleTarget = $saleTarget;
        $saleTargetTimeframe->saleAchievedTargets = collect([]);

        $this->mock(SaleTargetTimeframeQueries::class, function ($mock) use ($saleTargetTimeframe): void {
            $mock->shouldReceive('getByStartAndEndDate')
                ->times(1)
                ->andReturn(collect([$saleTargetTimeframe]));
        });

        $promoter->amount_sold = 100;
        $promoter->promoter_id = 1;

        $this->mock(PromoterQueries::class, function ($mock) use ($promoter): void {
            $mock->shouldReceive('getTotalAmountForSalePromoterTarget')
                ->times(1)
                ->andReturn(collect([$promoter]));
        });

        $this->mock(SaleAchievedTargetQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->times(1);
        });

        SaleAchievedTargetJob::dispatch()->onQueue(config('horizon.default_queue_name'));
        Queue::assertPushed(SendSaleTargetNotificationsJob::class);
    }
);

test(
    'SaleAchievedTargetJob job calls updateAchievedValue methods of saleAchievedTargetQueries when target type promoter wise',
    function (): void {
        Queue::fake()->except([SaleAchievedTargetJob::class]);

        $saleTarget = SaleTarget::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'target_type' => TargetType::PROMOTER_WISE->value,
        ]);

        $promoter = Promoter::factory()->make([
            'id' => 1,
            'employee_id' => 1,
        ]);

        $saleTarget->promoters = collect([$promoter]);

        $saleTargetTimeframe = SaleTargetTimeframe::factory()->make([
            'id' => 1,
            'sale_target_id' => 1,
        ]);

        $saleTargetTimeframe->saleTarget = $saleTarget;
        $saleAchievedTarget = SaleAchievedTarget::factory()->make([
            'id' => 1,
            'sale_target_timeframe_id' => 1,
            'targetable_id' => 1,
            'targetable_type' => ModelMapping::PROMOTER->name,
        ]);

        $saleTargetTimeframe->saleAchievedTargets = collect([$saleAchievedTarget]);

        $this->mock(SaleTargetTimeframeQueries::class, function ($mock) use ($saleTargetTimeframe): void {
            $mock->shouldReceive('getByStartAndEndDate')
                ->times(1)
                ->andReturn(collect([$saleTargetTimeframe]));
        });

        $promoter->amount_sold = 100;
        $promoter->promoter_id = 1;

        $this->mock(PromoterQueries::class, function ($mock) use ($promoter): void {
            $mock->shouldReceive('getTotalAmountForSalePromoterTarget')
                ->times(1)
                ->andReturn(collect([$promoter]));
        });

        $this->mock(SaleAchievedTargetQueries::class, function ($mock): void {
            $mock->shouldReceive('updateAchievedValue')
                ->times(1);
        });

        SaleAchievedTargetJob::dispatch()->onQueue(config('horizon.default_queue_name'));
        Queue::assertPushed(SendSaleTargetNotificationsJob::class);
    }
);

test(
    'SaleAchievedTargetJob job calls markAsRegenerateCompete methods of SaleTargetQueries class',
    function (): void {
        Queue::fake()->except([SaleAchievedTargetJob::class]);

        $saleTarget = SaleTarget::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'target_type' => TargetType::PROMOTER_WISE->value,
        ]);

        $promoter = Promoter::factory()->make([
            'id' => 1,
            'employee_id' => 1,
        ]);

        $saleTarget->promoters = collect([$promoter]);

        $saleTargetTimeframe = SaleTargetTimeframe::factory()->make([
            'id' => 1,
            'sale_target_id' => 1,
        ]);

        $saleTargetTimeframe->saleTarget = $saleTarget;
        $saleAchievedTarget = SaleAchievedTarget::factory()->make([
            'id' => 1,
            'sale_target_timeframe_id' => 1,
            'targetable_id' => 1,
            'targetable_type' => ModelMapping::PROMOTER->name,
        ]);

        $saleTargetTimeframe->saleAchievedTargets = collect([$saleAchievedTarget]);

        $this->mock(SaleTargetTimeframeQueries::class, function ($mock) use ($saleTargetTimeframe): void {
            $mock->shouldReceive('getBySaleTargetId')
                ->times(1)
                ->andReturn(collect([$saleTargetTimeframe]));
        });

        $this->mock(SaleTargetQueries::class, function ($mock): void {
            $mock->shouldReceive('markAsRegenerateCompete')
                ->times(1);
        });

        $promoter->amount_sold = 100;
        $promoter->promoter_id = 1;

        $this->mock(PromoterQueries::class, function ($mock) use ($promoter): void {
            $mock->shouldReceive('getTotalAmountForSalePromoterTarget')
                ->times(1)
                ->andReturn(collect([$promoter]));
        });

        $this->mock(SaleAchievedTargetQueries::class, function ($mock): void {
            $mock->shouldReceive('updateAchievedValue')
                ->times(1);
        });

        SaleAchievedTargetJob::dispatch(1)->onQueue(config('horizon.default_queue_name'));
        Queue::assertPushed(SendSaleTargetNotificationsJob::class);
    }
);

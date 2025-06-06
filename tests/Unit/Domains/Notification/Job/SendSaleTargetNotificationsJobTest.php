<?php

declare(strict_types=1);

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Notification\Jobs\SendSaleTargetNotificationsJob;
use App\Domains\Notification\NotificationQueries;
use App\Domains\SaleTarget\Enums\TargetType;
use App\Domains\SaleTarget\SaleTargetQueries;
use App\Domains\StoreManager\StoreManagerQueries;
use App\Models\Location;
use App\Models\Promoter;
use App\Models\SaleAchievedTarget;
use App\Models\SaleTarget;
use App\Models\SaleTargetTimeframe;
use App\Models\StoreManager;

test('sends the notification to store manager', function (): void {
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
        'target_value' => 100,
        'achieved_value' => 200,
    ]);

    $saleTargetTimeframe->saleAchievedTargets = collect([$saleAchievedTarget]);
    $saleTarget->saleTargetTimeframes = collect([$saleTargetTimeframe]);

    $storeManager = StoreManager::factory()->make([
        'id' => 1,
        'employee_id' => 1,
    ]);

    $storeManager->locations = collect([$location]);

    $this->mock(SaleTargetQueries::class, function ($mock) use ($saleTarget): void {
        $mock->shouldReceive('getByIds')
            ->once()
            ->andReturn(collect([$saleTarget]));
    });

    $this->mock(StoreManagerQueries::class, function ($mock) use ($storeManager): void {
        $mock->shouldReceive('getAllStoreManagerWithLocations')
            ->once()
            ->andReturn(collect([$storeManager]));
    });

    $this->mock(NotificationQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    SendSaleTargetNotificationsJob::dispatch([$saleTarget->id])->onQueue(config('horizon.default_queue_name'));
});

test('sends the notification to promoter', function (): void {
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
        'target_value' => 100,
        'achieved_value' => 200,
    ]);

    $saleTargetTimeframe->saleAchievedTargets = collect([$saleAchievedTarget]);
    $saleTarget->saleTargetTimeframes = collect([$saleTargetTimeframe]);

    $this->mock(SaleTargetQueries::class, function ($mock) use ($saleTarget): void {
        $mock->shouldReceive('getByIds')
            ->once()
            ->andReturn(collect([$saleTarget]));
    });

    $this->mock(NotificationQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    SendSaleTargetNotificationsJob::dispatch([$saleTarget->id])->onQueue(config('horizon.default_queue_name'));
});

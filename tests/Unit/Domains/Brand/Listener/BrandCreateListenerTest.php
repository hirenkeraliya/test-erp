<?php

declare(strict_types=1);

use App\Domains\Brand\Events\BrandCreateEvent;
use App\Domains\Brand\Listeners\BrandCreateListener;
use App\Domains\Brand\Services\BrandRetailPlanningIntegrationService;
use App\Domains\Brand\Services\BrandSaleChannelService;
use App\Models\Brand;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

test(
    'Brand Create Listener Handles Event Gracefully',
    function (): void {
        Http::fake();
        Queue::fake();

        $brand = Brand::factory()->make([
            'company_id' => 1,
        ]);

        $brandCreateListener = new BrandCreateListener();
        $brandCreateEvent = new BrandCreateEvent($brand);

        $this->mock(BrandSaleChannelService::class, static function ($mock): void {
            $mock->shouldReceive('createBrand')
                ->once();
        });

        $this->mock(BrandRetailPlanningIntegrationService::class, static function ($mock): void {
            $mock->shouldReceive('manageBrand')
                ->once();
        });

        $brandCreateListener->handle($brandCreateEvent);
    }
);

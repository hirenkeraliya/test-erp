<?php

declare(strict_types=1);

use App\Domains\Brand\Events\BrandUpdateEvent;
use App\Domains\Brand\Listeners\BrandUpdateListener;
use App\Domains\Brand\Services\BrandRetailPlanningIntegrationService;
use App\Domains\Brand\Services\BrandSaleChannelService;
use App\Models\Brand;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

test(
    'Brand Update Listener Handles Event Gracefully',
    function (): void {
        Http::fake();
        Queue::fake();

        $brand = Brand::factory()->make();

        $brandUpdateListener = new BrandUpdateListener();
        $brandUpdateEvent = new BrandUpdateEvent($brand);

        $this->mock(BrandSaleChannelService::class, static function ($mock): void {
            $mock->shouldReceive('updateBrand')
                ->once();
        });

        $this->mock(BrandRetailPlanningIntegrationService::class, static function ($mock): void {
            $mock->shouldReceive('manageBrand')
                ->once();
        });

        $brandUpdateListener->handle($brandUpdateEvent);
    }
);

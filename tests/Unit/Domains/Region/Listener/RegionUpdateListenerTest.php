<?php

declare(strict_types=1);

use App\Domains\Region\Events\RegionUpdateEvent;
use App\Domains\Region\Listeners\RegionUpdateListener;
use App\Domains\Region\Services\RegionRetailPlanningIntegrationService;
use App\Models\Region;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

test(
    'Region Update Listener Handles Event Gracefully',
    function (): void {
        Http::fake();
        Queue::fake();

        $region = Region::factory()->make([
            'company_id' => 1,
        ]);

        $regionUpdateListener = new RegionUpdateListener();
        $regionUpdateEvent = new RegionUpdateEvent($region);

        $this->mock(RegionRetailPlanningIntegrationService::class, static function ($mock): void {
            $mock->shouldReceive('manageRegion')
                ->once();
        });

        $regionUpdateListener->handle($regionUpdateEvent);
    }
);

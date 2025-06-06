<?php

declare(strict_types=1);

use App\Domains\Region\Events\RegionCreateEvent;
use App\Domains\Region\Listeners\RegionCreateListener;
use App\Domains\Region\Services\RegionRetailPlanningIntegrationService;
use App\Models\Region;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

test(
    'Region Create Listener Handles Event Gracefully',
    function (): void {
        Http::fake();
        Queue::fake();

        $region = Region::factory()->make([
            'company_id' => 1,
        ]);

        $regionCreateListener = new RegionCreateListener();
        $regionCreateEvent = new RegionCreateEvent($region);

        $this->mock(RegionRetailPlanningIntegrationService::class, static function ($mock): void {
            $mock->shouldReceive('manageRegion')
                ->once();
        });

        $regionCreateListener->handle($regionCreateEvent);
    }
);

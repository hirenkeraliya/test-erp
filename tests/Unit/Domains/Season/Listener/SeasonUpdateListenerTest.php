<?php

declare(strict_types=1);

use App\Domains\Season\Events\SeasonUpdateEvent;
use App\Domains\Season\Listeners\SeasonUpdateListener;
use App\Domains\Season\Services\SeasonRetailPlanningIntegrationService;
use App\Models\Season;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

test('Season update Listener Handles Event Gracefully', function (): void {
    Http::fake();
    Queue::fake();

    $season = Season::factory()->make([
        'name' => 'Test Season',
        'company_id' => 1,
    ]);

    $seasonUpdateListener = new SeasonUpdateListener();
    $seasonUpdateEvent = new SeasonUpdateEvent($season);

    $this->mock(SeasonRetailPlanningIntegrationService::class, static function ($mock): void {
        $mock->shouldReceive('manageSeason')
            ->once();
    });

    $seasonUpdateListener->handle($seasonUpdateEvent);
});

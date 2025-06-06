<?php

declare(strict_types=1);

use App\Domains\Season\Events\SeasonCreateEvent;
use App\Domains\Season\Listeners\SeasonCreateListener;
use App\Domains\Season\Services\SeasonRetailPlanningIntegrationService;
use App\Models\Season;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

test('Season Create Listener Handles Event Gracefully', function (): void {
    Http::fake();
    Queue::fake();

    $season = Season::factory()->make([
        'name' => 'Test Season',
        'company_id' => 1,
    ]);

    $seasonCreateListener = new SeasonCreateListener();
    $seasonCreateEvent = new SeasonCreateEvent($season);

    $this->mock(SeasonRetailPlanningIntegrationService::class, static function ($mock): void {
        $mock->shouldReceive('manageSeason')
            ->once();
    });

    $seasonCreateListener->handle($seasonCreateEvent);
});

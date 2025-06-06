<?php

declare(strict_types=1);

use App\Domains\State\Events\StateUpdateEvent;
use App\Domains\State\Listeners\StateUpdateListener;
use App\Domains\State\Services\StateRetailPlanningIntegrationService;
use App\Models\State;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

test(
    'State Update Listener Handles Event Gracefully',
    function (): void {
        Http::fake();
        Queue::fake();

        $state = State::factory()->make([
            'name' => 'Test State',
            'country_id' => 1,
        ]);

        $stateUpdateListener = new StateUpdateListener();
        $stateUpdateEvent = new StateUpdateEvent($state);

        $this->mock(StateRetailPlanningIntegrationService::class, static function ($mock): void {
            $mock->shouldReceive('manageState')
                ->once();
        });

        $stateUpdateListener->handle($stateUpdateEvent);
    }
);

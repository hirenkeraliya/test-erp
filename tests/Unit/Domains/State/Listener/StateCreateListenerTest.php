<?php

declare(strict_types=1);

use App\Domains\State\Events\StateCreateEvent;
use App\Domains\State\Listeners\StateCreateListener;
use App\Domains\State\Services\StateRetailPlanningIntegrationService;
use App\Models\State;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

test(
    'State Create Listener Handles Event Gracefully',
    function (): void {
        Http::fake();
        Queue::fake();

        $state = State::factory()->make([
            'name' => 'Test State',
            'country_id' => 1,
        ]);

        $stateCreateListener = new StateCreateListener();
        $stateCreateEvent = new StateCreateEvent($state);

        $this->mock(StateRetailPlanningIntegrationService::class, static function ($mock): void {
            $mock->shouldReceive('manageState')
                ->once();
        });

        $stateCreateListener->handle($stateCreateEvent);
    }
);

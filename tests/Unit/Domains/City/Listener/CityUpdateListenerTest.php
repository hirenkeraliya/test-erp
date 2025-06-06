<?php

declare(strict_types=1);

use App\Domains\City\Events\CityUpdateEvent;
use App\Domains\City\Listeners\CityUpdateListener;
use App\Domains\City\Services\CityRetailPlanningIntegrationService;
use App\Models\City;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

test('City Update Listener Handles Event Gracefully', function (): void {
    Http::fake();
    Queue::fake();

    $city = City::factory()->make([
        'name' => 'Test City',
        'state_id' => 1,
        'country_id' => 1,
    ]);

    $cityUpdateListener = new CityUpdateListener();
    $cityUpdateEvent = new CityUpdateEvent($city);

    $this->mock(CityRetailPlanningIntegrationService::class, static function ($mock): void {
        $mock->shouldReceive('manageCity')
            ->once();
    });

    $cityUpdateListener->handle($cityUpdateEvent);
});

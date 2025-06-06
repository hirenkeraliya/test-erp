<?php

declare(strict_types=1);

use App\Domains\City\Events\CityCreateEvent;
use App\Domains\City\Listeners\CityCreateListener;
use App\Domains\City\Services\CityRetailPlanningIntegrationService;
use App\Models\City;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

test('City Create Listener Handles Event Gracefully', function (): void {
    Http::fake();
    Queue::fake();

    $city = City::factory()->make([
        'name' => 'Test City',
        'state_id' => 1,
        'country_id' => 1,
    ]);

    $cityCreateListener = new CityCreateListener();
    $cityCreateEvent = new CityCreateEvent($city);

    $this->mock(CityRetailPlanningIntegrationService::class, static function ($mock): void {
        $mock->shouldReceive('manageCity')
            ->once();
    });

    $cityCreateListener->handle($cityCreateEvent);
});

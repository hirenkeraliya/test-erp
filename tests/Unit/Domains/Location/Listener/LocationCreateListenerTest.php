<?php

declare(strict_types=1);

use App\Domains\Location\Events\LocationCreateEvent;
use App\Domains\Location\Listeners\LocationCreateListener;
use App\Domains\Location\LocationQueries;
use App\Domains\Location\Services\LocationRetailPlanningIntegrationService;
use App\Models\Location;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

test('Location Create Listener Handles Event Gracefully', function (): void {
    Http::fake();
    Queue::fake();

    $location = Location::factory()->make([
        'id' => 1,
        'name' => 'Test',
        'company_id' => 1,
        'type_id' => 1,
    ]);

    $locationCreateListener = new LocationCreateListener();
    $locationCreateEvent = new LocationCreateEvent($location);

    $this->mock(LocationQueries::class, static function ($mock) use ($location): void {
        $mock->shouldReceive('getByIdWithRelation')
            ->once()
            ->andReturn($location);
    });

    $this->mock(LocationRetailPlanningIntegrationService::class, static function ($mock): void {
        $mock->shouldReceive('manageLocation')
            ->once();
    });

    $locationCreateListener->handle($locationCreateEvent);
});

test('Location Create Listener Fails When type_id is Warehouse', function (): void {
    Http::fake();
    Queue::fake();

    $location = Location::factory()->make([
        'id' => 1,
        'name' => 'Test',
        'company_id' => 1,
        'type_id' => 2,
    ]);

    $locationCreateListener = new LocationCreateListener();
    $locationCreateEvent = new LocationCreateEvent($location);

    $this->mock(LocationQueries::class, static function ($mock): void {
        $mock->shouldNotReceive('getByIdWithRelation');
    });

    $this->mock(LocationRetailPlanningIntegrationService::class, static function ($mock): void {
        $mock->shouldNotReceive('manageLocation');
    });

    $locationCreateListener->handle($locationCreateEvent);
});

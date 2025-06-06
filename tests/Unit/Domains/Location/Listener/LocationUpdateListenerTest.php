<?php

declare(strict_types=1);

use App\Domains\Location\Events\LocationUpdateEvent;
use App\Domains\Location\Listeners\LocationUpdateListener;
use App\Domains\Location\LocationQueries;
use App\Domains\Location\Services\LocationRetailPlanningIntegrationService;
use App\Models\Location;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

test('Location update Listener Handles Event Gracefully', function (): void {
    Http::fake();
    Queue::fake();

    $location = Location::factory()->make([
        'id' => 1,
        'name' => 'Test',
        'company_id' => 1,
        'type_id' => 1,
    ]);

    $locationUpdateListener = new LocationUpdateListener();
    $locationUpdateEvent = new LocationUpdateEvent($location);

    $this->mock(LocationQueries::class, static function ($mock) use ($location): void {
        $mock->shouldReceive('getByIdWithRelation')
            ->once()
            ->andReturn($location);
    });

    $this->mock(LocationRetailPlanningIntegrationService::class, static function ($mock): void {
        $mock->shouldReceive('manageLocation')
            ->once();
    });

    $locationUpdateListener->handle($locationUpdateEvent);
});

test('Location update Listener Fails When type_id is Warehouse', function (): void {
    Http::fake();
    Queue::fake();

    $location = Location::factory()->make([
        'id' => 1,
        'name' => 'Test',
        'company_id' => 1,
        'type_id' => 2,
    ]);

    $locationUpdateListener = new LocationUpdateListener();
    $locationUpdateEvent = new LocationUpdateEvent($location);

    $this->mock(LocationQueries::class, static function ($mock): void {
        $mock->shouldNotReceive('getByIdWithRelation');
    });

    $this->mock(LocationRetailPlanningIntegrationService::class, static function ($mock): void {
        $mock->shouldNotReceive('manageLocation');
    });

    $locationUpdateListener->handle($locationUpdateEvent);
});

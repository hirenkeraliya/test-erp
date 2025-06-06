<?php

declare(strict_types=1);

use App\Domains\Country\Events\CountryUpdateEvent;
use App\Domains\Country\Listeners\CountryUpdateListener;
use App\Domains\Country\Services\CountryRetailPlanningIntegrationService;
use App\Models\Country;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

test(
    'Country Update Listener Handles Event Gracefully',
    function (): void {
        Http::fake();
        Queue::fake();

        $country = Country::factory()->make([
            'name' => 'Test Country',
        ]);

        $countryUpdateListener = new CountryUpdateListener();
        $countryUpdateEvent = new CountryUpdateEvent($country);

        $this->mock(CountryRetailPlanningIntegrationService::class, static function ($mock): void {
            $mock->shouldReceive('manageCountry')
                ->once();
        });

        $countryUpdateListener->handle($countryUpdateEvent);
    }
);

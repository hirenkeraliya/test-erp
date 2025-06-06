<?php

declare(strict_types=1);

use App\Domains\Country\Events\CountryCreateEvent;
use App\Domains\Country\Listeners\CountryCreateListener;
use App\Domains\Country\Services\CountryRetailPlanningIntegrationService;
use App\Models\Country;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

test(
    'Country Create Listener Handles Event Gracefully',
    function (): void {
        Http::fake();
        Queue::fake();

        $country = Country::factory()->make([
            'name' => 'Test Country',
        ]);

        $countryCreateListener = new CountryCreateListener();
        $countryCreateEvent = new CountryCreateEvent($country);

        $this->mock(CountryRetailPlanningIntegrationService::class, static function ($mock): void {
            $mock->shouldReceive('manageCountry')
                ->once();
        });

        $countryCreateListener->handle($countryCreateEvent);
    }
);

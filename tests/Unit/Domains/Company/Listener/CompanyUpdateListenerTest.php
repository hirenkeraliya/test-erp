<?php

declare(strict_types=1);

use App\Domains\Company\Events\CompanyUpdateEvent;
use App\Domains\Company\Listeners\CompanyUpdateListener;
use App\Domains\Company\Services\CompanyRetailPlanningIntegrationService;
use App\Models\Company;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

test(
    'Company Update Listener Handles Event Gracefully',
    function (): void {
        Http::fake();
        Queue::fake();

        $company = Company::factory()->make([
            'default_country_id' => 1,
        ]);

        $companyUpdateListener = new CompanyUpdateListener();
        $companyUpdateEvent = new CompanyUpdateEvent($company);

        $this->mock(CompanyRetailPlanningIntegrationService::class, static function ($mock): void {
            $mock->shouldReceive('manageCompany')
                ->once();
        });

        $companyUpdateListener->handle($companyUpdateEvent);
    }
);

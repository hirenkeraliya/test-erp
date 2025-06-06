<?php

declare(strict_types=1);

use App\Domains\Company\Events\CompanyCreateEvent;
use App\Domains\Company\Listeners\CompanyCreateListener;
use App\Domains\Company\Services\CompanyRetailPlanningIntegrationService;
use App\Models\Company;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

test(
    'Company Create Listener Handles Event Gracefully',
    function (): void {
        Http::fake();
        Queue::fake();

        $company = Company::factory()->make([
            'default_country_id' => 1,
        ]);

        $companyCreateListener = new CompanyCreateListener();
        $companyCreateEvent = new CompanyCreateEvent($company);

        $this->mock(CompanyRetailPlanningIntegrationService::class, static function ($mock): void {
            $mock->shouldReceive('manageCompany')
                ->once();
        });

        $companyCreateListener->handle($companyCreateEvent);
    }
);

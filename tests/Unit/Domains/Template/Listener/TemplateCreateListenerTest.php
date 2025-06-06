<?php

declare(strict_types=1);

use App\Domains\Template\Events\TemplateCreateEvent;
use App\Domains\Template\Listeners\TemplateCreateListener;
use App\Domains\Template\Services\TemplateRetailPlanningIntegrationService;
use App\Models\Template;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

test(
    'Template Create Listener Handles Event Gracefully',
    function (): void {
        Http::fake();
        Queue::fake();

        $template = Template::factory()->make([
            'company_id' => 1,
        ]);

        $templateCreateListener = new TemplateCreateListener();
        $templateCreateEvent = new TemplateCreateEvent($template);

        $this->mock(TemplateRetailPlanningIntegrationService::class, static function ($mock): void {
            $mock->shouldReceive('manageTemplate')
                ->once();
        });

        $templateCreateListener->handle($templateCreateEvent);
    }
);

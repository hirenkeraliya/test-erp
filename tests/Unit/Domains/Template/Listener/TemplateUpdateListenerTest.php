<?php

declare(strict_types=1);

use App\Domains\Template\Events\TemplateUpdateEvent;
use App\Domains\Template\Listeners\TemplateUpdateListener;
use App\Domains\Template\Services\TemplateRetailPlanningIntegrationService;
use App\Models\Template;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

test(
    'Template Update Listener Handles Event Gracefully',
    function (): void {
        Http::fake();
        Queue::fake();

        $template = Template::factory()->make([
            'company_id' => 1,
        ]);

        $templateUpdateListener = new TemplateUpdateListener();
        $templateUpdateEvent = new TemplateUpdateEvent($template);

        $this->mock(TemplateRetailPlanningIntegrationService::class, static function ($mock): void {
            $mock->shouldReceive('manageTemplate')
                ->once();
        });

        $templateUpdateListener->handle($templateUpdateEvent);
    }
);

<?php

declare(strict_types=1);

use App\Domains\Style\Events\StyleUpdateEvent;
use App\Domains\Style\Listeners\StyleUpdateListener;
use App\Domains\Style\Services\StyleRetailPlanningIntegrationService;
use App\Models\Style;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

test(
    'Style Update Listener Handles Event Gracefully',
    function (): void {
        Http::fake();
        Queue::fake();

        $style = Style::factory()->make([
            'company_id' => 1,
        ]);

        $styleUpdateListener = new StyleUpdateListener();
        $styleUpdateEvent = new StyleUpdateEvent($style);

        $this->mock(StyleRetailPlanningIntegrationService::class, static function ($mock): void {
            $mock->shouldReceive('manageStyle')
                ->once();
        });

        $styleUpdateListener->handle($styleUpdateEvent);
    }
);

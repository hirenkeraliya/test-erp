<?php

declare(strict_types=1);

use App\Domains\Style\Events\StyleCreateEvent;
use App\Domains\Style\Listeners\StyleCreateListener;
use App\Domains\Style\Services\StyleRetailPlanningIntegrationService;
use App\Models\Style;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

test(
    'Style Create Listener Handles Event Gracefully',
    function (): void {
        Http::fake();
        Queue::fake();

        $style = Style::factory()->make([
            'company_id' => 1,
        ]);

        $styleCreateListener = new StyleCreateListener();
        $styleCreateEvent = new StyleCreateEvent($style);

        $this->mock(StyleRetailPlanningIntegrationService::class, static function ($mock): void {
            $mock->shouldReceive('manageStyle')
                ->once();
        });

        $styleCreateListener->handle($styleCreateEvent);
    }
);

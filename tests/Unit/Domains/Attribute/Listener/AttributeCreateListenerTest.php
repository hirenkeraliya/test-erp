<?php

declare(strict_types=1);

use App\Domains\Attribute\Events\AttributeCreateEvent;
use App\Domains\Attribute\Listeners\AttributeCreateListener;
use App\Domains\Attribute\Services\AttributeRetailPlanningIntegrationService;
use App\Domains\SaleChannel\SaleChannelQueries;
use App\Models\Attribute;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

test(
    'Attribute Create Listener Handles Event Gracefully',
    function (): void {
        Http::fake();
        Queue::fake();

        $attribute = Attribute::factory()->make([
            'company_id' => 1,
        ]);

        $attributeCreateListener = new AttributeCreateListener();
        $attributeCreateEvent = new AttributeCreateEvent($attribute);

        $this->mock(SaleChannelQueries::class, static function ($mock): void {
            $mock->shouldReceive('getSaleChannelsByCompany')
                ->once()
                ->andReturn(collect());
        });

        $this->mock(AttributeRetailPlanningIntegrationService::class, static function ($mock): void {
            $mock->shouldReceive('manageAttribute')
                ->once();
        });

        $attributeCreateListener->handle($attributeCreateEvent);
    }
);

<?php

declare(strict_types=1);

use App\Domains\MasterProduct\Events\MasterProductUpdateEvent;
use App\Domains\MasterProduct\Listeners\MasterProductRetailPlanningUpdateListener;
use App\Domains\MasterProduct\Services\MasterProductRetailPlanningIntegrationService;
use App\Domains\Product\Enums\ProductTypes;
use App\Domains\Product\Enums\Statuses;
use App\Models\MasterProduct;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

test('Master Product Create Listener Handles Regular Product Event', function (): void {
    Http::fake();
    Queue::fake();

    $masterProduct = MasterProduct::factory()->make([
        'company_id' => 1,
        'brand_id' => 1,
        'vendor_id' => 1,
        'variant_template_id' => 1,
        'department_id' => 1,
        'unit_of_measure_id' => 1,
        'type_id' => ProductTypes::REGULAR_PRODUCT->value,
        'status' => Statuses::ACTIVE->value,
    ]);

    $masterProductRetailPlanningUpdateListener = new MasterProductRetailPlanningUpdateListener();
    $masterProductUpdateEvent = new MasterProductUpdateEvent($masterProduct, false);

    $this->mock(MasterProductRetailPlanningIntegrationService::class, function ($mock): void {
        $mock->shouldReceive('manageMasterProduct')
            ->once();
    });

    $masterProductRetailPlanningUpdateListener->handle($masterProductUpdateEvent);
});

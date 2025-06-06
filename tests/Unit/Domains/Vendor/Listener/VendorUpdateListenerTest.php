<?php

declare(strict_types=1);

use App\Domains\Vendor\Events\VendorUpdateEvent;
use App\Domains\Vendor\Listeners\VendorUpdateListener;
use App\Domains\Vendor\Services\VendorRetailPlanningIntegrationService;
use App\Models\Vendor;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

test('Vendor Update Listener Handles Event Gracefully', function (): void {
    Http::fake();
    Queue::fake();

    $vendor = Vendor::factory()->make([
        'name' => 'Test Vendor',
        'company_id' => 1,
    ]);

    $vendorUpdateListener = new VendorUpdateListener();
    $vendorUpdateEvent = new VendorUpdateEvent($vendor);

    $this->mock(VendorRetailPlanningIntegrationService::class, function ($mock): void {
        $mock->shouldReceive('manageVendor')
            ->once();
    });

    $vendorUpdateListener->handle($vendorUpdateEvent);
});

<?php

declare(strict_types=1);

use App\Domains\Vendor\Events\VendorCreateEvent;
use App\Domains\Vendor\Listeners\VendorCreateListener;
use App\Domains\Vendor\Services\VendorRetailPlanningIntegrationService;
use App\Models\Vendor;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

test('Vendor Create Listener Handles Event Gracefully', function (): void {
    Http::fake();
    Queue::fake();

    $vendor = Vendor::factory()->make([
        'name' => 'Test Vendor',
        'company_id' => 1,
    ]);

    $vendorCreateListener = new VendorCreateListener();
    $vendorCreateEvent = new VendorCreateEvent($vendor);

    $this->mock(VendorRetailPlanningIntegrationService::class, static function ($mock): void {
        $mock->shouldReceive('manageVendor')
            ->once();
    });

    $vendorCreateListener->handle($vendorCreateEvent);
});

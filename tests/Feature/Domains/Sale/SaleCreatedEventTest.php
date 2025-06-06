<?php

declare(strict_types=1);

use App\Domains\Sale\Events\SaleCreatedEvent;
use App\Domains\Sale\Listeners\PriceFallDownNotificationListener;
use App\Models\Sale;
use Illuminate\Support\Facades\Event;

test('sale created then the event dispatched as expected', function (): void {
    Event::fake([SaleCreatedEvent::class]);

    $sale = Sale::factory()->create();

    Event::assertDispatched(fn (SaleCreatedEvent $event): bool => $event->sale->id === $sale->id);

    Event::assertListening(SaleCreatedEvent::class, PriceFallDownNotificationListener::class);
});

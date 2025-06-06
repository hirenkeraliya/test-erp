<?php

declare(strict_types=1);

use App\Domains\CounterUpdateEvent\CounterUpdateEventQueries;
use App\Domains\CounterUpdateEvent\DataObjects\CounterUpdateEventData;
use App\Domains\CounterUpdateEvent\Enums\CounterUpdateEventTypes;
use App\Domains\Location\Enums\LocationTypes;
use App\Models\Cashier;
use App\Models\Company;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\CounterUpdateEvent;
use App\Models\Location;
use App\Models\Product;
use Carbon\Carbon;

beforeEach(function (): void {
    $this->companyId = Company::factory()->create()->id;
    $this->cashier = Cashier::factory()->create();
    $this->location = Location::factory()->create([
        'company_id' => $this->companyId,
        'type_id' => LocationTypes::STORE->value,
    ]);
    $this->counter = Counter::factory()->create([
        'location_id' => $this->location->id,
    ]);

    $this->counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $this->counter->id,
        'cashier_id' => $this->cashier->id,
    ]);

    $this->counterUpdateEvent = CounterUpdateEvent::factory()->create([
        'counter_update_id' => $this->counterUpdate->id,
        'type_id' => CounterUpdateEventTypes::TAKE_A_BREAK->value,
    ]);

    $this->counterUpdateEventQueries = new CounterUpdateEventQueries();
});

test(
    'the get List method returns the counter update event list',
    function (): void {
        $response = $this->counterUpdateEventQueries->getList($this->counterUpdate->id);

        $this->assertEquals(1, $response->count());

        expect($response->first()->toArray())
            ->toHaveKey('id', $this->counterUpdateEvent->id)
            ->toHaveKey('offline_id', $this->counterUpdateEvent->offline_id);
    }
);

test('New Counter Update Event can be added', function (): void {
    $counterUpdateEventData = new CounterUpdateEventData(
        offline_id: 'a123',
        happened_at: Carbon::now()->format('Y-m-d H:i:s'),
        type_id: CounterUpdateEventTypes::TAKE_A_BREAK->value,
        product_id: null,
    );

    $counterUpdate = CounterUpdate::factory()->create();
    $this->counterUpdateEventQueries->addNew($counterUpdateEventData, $counterUpdate->id);

    $this->assertDatabaseHas('counter_update_events', [
        'offline_id' => $counterUpdateEventData->offline_id,
        'happened_at' => $counterUpdateEventData->happened_at,
        'counter_update_id' => $counterUpdate->id,
    ]);
});

test('New Counter Update Event can be added with product', function (): void {
    $product = Product::factory()->create([
        'company_id' => $this->companyId,
    ]);

    $counterUpdateEventData = new CounterUpdateEventData(
        offline_id: 'a123',
        happened_at: Carbon::now()->format('Y-m-d H:i:s'),
        type_id: CounterUpdateEventTypes::PRODUCT_ADDED_TO_CART->value,
        product_id: $product->id,
    );

    $counterUpdateEvent = $this->counterUpdateEventQueries->addNew($counterUpdateEventData, $this->counterUpdate->id);

    $this->assertDatabaseHas('counter_update_events', [
        'offline_id' => $counterUpdateEventData->offline_id,
        'happened_at' => $counterUpdateEventData->happened_at,
        'counter_update_id' => $this->counterUpdate->id,
    ]);

    $this->assertDatabaseHas('counter_update_event_product', [
        'counter_update_event_id' => $counterUpdateEvent->id,
        'product_id' => $product->id,
    ]);
});

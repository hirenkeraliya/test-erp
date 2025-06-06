<?php

declare(strict_types=1);

use App\Domains\CounterUpdateEvent\DataObjects\CounterUpdateEventData;
use App\Domains\CounterUpdateEvent\Enums\CounterUpdateEventTypes;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

test('Counter update event validation fails as expected', function (): void {
    $request = new Request([
        'offline_id' => '',
        'type_id' => '',
        'happened_at' => '',
        'product_id' => '',
    ]);

    $request->validate(CounterUpdateEventData::rules($request));
})->throws(ValidationException::class);

test('Counter update event validation fails when product id not passed in request', function (): void {
    $request = new Request([
        'offline_id' => 'ewe22',
        'type_id' => CounterUpdateEventTypes::PRODUCT_ADDED_TO_CART->value,
        'happened_at' => now()->format('Y-m-d H:i:s'),
        'product_id' => '',
    ]);

    $request->validate(CounterUpdateEventData::rules($request));
})->throws(ValidationException::class);

test('Counter update event validation pass', function (): void {
    $request = new Request([
        'offline_id' => 'ewe22',
        'type_id' => CounterUpdateEventTypes::PRODUCT_ADDED_TO_CART->value,
        'happened_at' => now()->format('Y-m-d H:i:s'),
        'product_id' => 1,
    ]);

    $request->validate(CounterUpdateEventData::rules($request));
    $this->assertTrue(true);
});

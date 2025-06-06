<?php

declare(strict_types=1);

use App\Domains\HoldSale\Enums\HoldSaleTypes;
use App\Domains\HoldSale\HoldSaleQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Models\Company;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\HoldBookingPaymentItem;
use App\Models\HoldSale;
use App\Models\HoldSaleDetail;
use App\Models\HoldSaleItem;
use App\Models\HoldSaleReturnItem;
use App\Models\Location;
use App\Models\Sale;

beforeEach(function (): void {
    $this->companyId = Company::factory()->create([
        'name' => 'Test Company',
        'email' => 'abc@company.test',
        'code' => 'ABC',
    ])->id;

    $this->holdSaleQueries = new HoldSaleQueries();
});

test('new hold sale can be added', function (): void {
    $counterUpdate = CounterUpdate::factory()->create();

    $this->holdSaleQueries->addNew('13245', $counterUpdate->id, HoldSaleTypes::REGULAR_SALE->value);

    $this->assertDatabaseHas('hold_sales', [
        'offline_id' => '13245',
        'counter_update_id' => $counterUpdate->id,
    ]);
});

test('loadRelations return hold sale data', function (): void {
    $holdSale = HoldSale::factory()->create();

    $holdSaleDetail = HoldSaleDetail::factory()->create([
        'hold_sale_id' => $holdSale->id,
    ]);

    $holdSaleItem = HoldSaleItem::factory()->create([
        'hold_sale_detail_id' => $holdSaleDetail->id,
    ]);

    $holdSaleReturnItem = HoldSaleReturnItem::factory()->create([
        'hold_sale_detail_id' => $holdSaleDetail->id,
    ]);

    $holdBookingPaymentItem = HoldBookingPaymentItem::factory()->create([
        'hold_sale_detail_id' => $holdSaleDetail->id,
    ]);

    $response = $this->holdSaleQueries->loadRelations($holdSale);

    expect($response->toArray())
        ->toHaveKey('id', $holdSale->id)
        ->toHaveKey('offline_id', $holdSale->offline_id)
        ->toHaveKey('counter_update_id', $holdSale->counter_update_id)
        ->toHaveKey('hold_sale_details.0.id', $holdSaleDetail->id)
        ->toHaveKey('hold_sale_details.0.hold_sale_id', $holdSale->id)
        ->toHaveKey('hold_sale_details.0.hold_sale_item.id', $holdSaleItem->id)
        ->toHaveKey('hold_sale_details.0.hold_sale_item.hold_sale_detail_id', $holdSaleItem->hold_sale_detail_id)
        ->toHaveKey('hold_sale_details.0.hold_sale_return_item.id', $holdSaleReturnItem->id)
        ->toHaveKey(
            'hold_sale_details.0.hold_sale_return_item.hold_sale_detail_id',
            $holdSaleReturnItem->hold_sale_detail_id
        )
        ->toHaveKey('hold_sale_details.0.hold_booking_payment_item.id', $holdBookingPaymentItem->id)
        ->toHaveKey(
            'hold_sale_details.0.hold_booking_payment_item.hold_sale_detail_id',
            $holdBookingPaymentItem->hold_sale_detail_id
        );
});

test('get hold sale not cancel', function (): void {
    $holdSale = HoldSale::factory()->create([
        'cancelled_at' => null,
    ]);

    $response = $this->holdSaleQueries->getNotCancelByOfflineId($holdSale->offline_id);

    expect($response->toArray())
        ->toHaveKey('id', $holdSale->id)
        ->toHaveKey('offline_id', $holdSale->offline_id);
});

test('doesOfflineIdExist returns boolean as expected', function (): void {
    $holdSale = HoldSale::factory()->create([
        'offline_id' => '12345',
        'cancelled_at' => null,
    ]);

    $response = $this->holdSaleQueries->doesOfflineIdExist($holdSale->offline_id);
    $this->assertTrue($response);

    $response = $this->holdSaleQueries->doesOfflineIdExist('Test');
    $this->assertFalse($response);
});

test('hold sale mark as cancelled', function (): void {
    $holdSale = HoldSale::factory()->create([
        'cancelled_at' => null,
    ]);

    $this->holdSaleQueries->markAsCancel($holdSale, '2022-01-22 10:10:00');

    $this->assertDatabaseHas('hold_sales', [
        'offline_id' => $holdSale->offline_id,
        'cancelled_at' => '2022-01-22 10:10:00',
    ]);
});

test('get hold sale not complete', function (): void {
    $holdSale = HoldSale::factory()->create([
        'complete_at' => null,
    ]);

    $response = $this->holdSaleQueries->getNotCompleteByOfflineId($holdSale->offline_id);

    expect($response->toArray())
        ->toHaveKey('id', $holdSale->id)
        ->toHaveKey('offline_id', $holdSale->offline_id);
});

test('hold sale mark as complete', function (): void {
    $holdSale = HoldSale::factory()->create([
        'complete_at' => null,
        'complete_sale_id' => null,
    ]);

    $sale = Sale::factory()->create();

    $this->holdSaleQueries->markAsComplete($holdSale, '2022-01-22 10:10:00', $sale->offline_sale_id, $sale->id, null);

    $this->assertDatabaseHas('hold_sales', [
        'offline_id' => $holdSale->offline_id,
        'complete_at' => '2022-01-22 10:10:00',
        'complete_offline_id' => $sale->offline_sale_id,
        'complete_sale_id' => $sale->id,
    ]);
});

test('get hold sale by offline_id', function (): void {
    $holdSale = HoldSale::factory()->create([
        'complete_at' => null,
        'cancelled_at' => null,
    ]);

    $response = $this->holdSaleQueries->getNotCompleteAndNotCancelByOfflineId($holdSale->offline_id);

    expect($response->toArray())
        ->toHaveKey('id', $holdSale->id)
        ->toHaveKey('offline_id', $holdSale->offline_id);
});

test('isCancelledHoldSale returns boolean as expected', function (): void {
    $holdSale = HoldSale::factory()->create([
        'offline_id' => '12345',
        'cancelled_at' => null,
    ]);

    $response = $this->holdSaleQueries->isCancelledHoldSale($holdSale->offline_id);
    $this->assertFalse($response);

    $holdSale = HoldSale::factory()->create([
        'offline_id' => '123456',
        'cancelled_at' => now(),
    ]);

    $response = $this->holdSaleQueries->isCancelledHoldSale($holdSale->offline_id);
    $this->assertTrue($response);
});

test('isCompletedHoldSale returns boolean as expected', function (): void {
    $holdSale = HoldSale::factory()->create([
        'offline_id' => '12345',
        'complete_at' => null,
    ]);

    $response = $this->holdSaleQueries->isCompletedHoldSale($holdSale->offline_id);
    $this->assertFalse($response);

    $holdSale = HoldSale::factory()->create([
        'offline_id' => '123456',
        'complete_at' => now(),
    ]);

    $response = $this->holdSaleQueries->isCompletedHoldSale($holdSale->offline_id);
    $this->assertTrue($response);
});

test('getSuspendAndResumeReport method  can be return proper response', function (): void {
    $location = Location::factory()->create([
        'company_id' => $this->companyId,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $counter = Counter::factory()->create([
        'location_id' => $location->id,
    ]);

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->id,
    ]);

    $holdSale = HoldSale::factory()->create([
        'counter_update_id' => $counterUpdate->id,
    ]);

    $response = $this->holdSaleQueries->getSuspendAndResumeReport([
        'location_ids' => [$location->id],
        'counter_ids' => null,
        'cashier_ids' => null,
        'date_range' => null,
    ]);

    expect($response->first()->toArray())
        ->toHaveKey('id', $holdSale->id)
        ->toHaveKey('counter_update_id', $holdSale->counter_update_id)
        ->toHaveKey('offline_id', $holdSale->offline_id)
        ->toHaveKeys(['counter_update', 'counter_update.counter', 'counter_update.counter.location']);
});

<?php

declare(strict_types=1);

use App\Domains\HoldBookingPaymentItem\HoldBookingPaymentItemQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Models\Company;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\HoldBookingPaymentItem;
use App\Models\HoldSale;
use App\Models\HoldSaleDetail;
use App\Models\Location;
use App\Models\Product;
use App\Models\Sale;

beforeEach(function (): void {
    $this->holdBookingPaymentItemQueries = new HoldBookingPaymentItemQueries();
});

test('new hold booking payment item can be added', function (): void {
    $holdSaleDetail = HoldSaleDetail::factory()->create();
    $product = Product::factory()->create();

    $item = [
        'id' => $product->id,
        'quantity' => '1',
    ];

    $this->holdBookingPaymentItemQueries->addNew($holdSaleDetail->id, $item);

    $this->assertDatabaseHas('hold_booking_payment_items', [
        'hold_sale_detail_id' => $holdSaleDetail->id,
    ]);
});

test('if product is merged then the product id is updated', function (): void {
    $companyId = Company::factory()->create()->id;
    $locationId = Location::factory()->create([
        'company_id' => $companyId,
        'type_id' => LocationTypes::STORE->value,
    ]);
    $counterId = Counter::factory()->create([
        'location_id' => $locationId,
    ])->id;
    $counterUpdateId = CounterUpdate::factory()->create([
        'counter_id' => $counterId,
    ])->id;
    $sale = Sale::factory()->create([
        'counter_update_id' => $counterUpdateId,
    ]);

    $productAId = Product::factory()->create()->id;
    $productBId = Product::factory()->create()->id;

    $holdSale = HoldSale::factory()->create([
        'counter_update_id' => $counterUpdateId,
    ]);

    $holdSaleDetails = HoldSaleDetail::factory()->create([
        'hold_sale_id' => $holdSale->id,
    ]);

    HoldBookingPaymentItem::factory()->create([
        'hold_sale_detail_id' => $holdSaleDetails->id,
        'product_id' => $productBId,
    ]);

    $this->holdBookingPaymentItemQueries->updateProductId($companyId, $productBId, $productAId);

    $this->assertDatabaseHas(HoldBookingPaymentItem::class, [
        'hold_sale_detail_id' => $holdSaleDetails->id,
        'product_id' => $productAId,
    ]);
});

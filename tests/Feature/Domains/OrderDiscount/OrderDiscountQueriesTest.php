<?php

declare(strict_types=1);

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\OrderDiscount\OrderDiscountQueries;
use App\Models\Company;
use App\Models\Location;
use App\Models\Order;
use App\Models\OrderDiscount;
use App\Models\Voucher;

test('order Discount can be added', function (): void {
    $companyId = Company::factory()->create()->id;

    $location = Location::factory()->create([
        'company_id' => $companyId,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $order = Order::factory()->create([
        'location_id' => $location->id,
        'store_manager_id' => null,
        'member_id' => null,
        'order_return_id' => null,
        'cancel_order_reason_id' => null,
    ]);

    $orderDiscount = OrderDiscount::factory()->make([
        'order_id' => $order->id,
    ]);

    $orderDiscountQueries = new OrderDiscountQueries();
    $orderDiscountQueries->addNew(
        $orderDiscount->order_id,
        $orderDiscount->discountable_id,
        $orderDiscount->discountable_type,
        10.20
    );

    $this->assertDatabaseHas('order_discounts', [
        'order_id' => $orderDiscount->order_id,
        'discountable_id' => $orderDiscount->discountable_id,
        'discountable_type' => $orderDiscount->discountable_type,
        'amount' => 10.20,
    ]);
});

test('getVoucherIdByOrder method returns the records by order id', function (): void {
    $companyId = Company::factory()->create()->id;

    $location = Location::factory()->create([
        'company_id' => $companyId,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $order = Order::factory()->create([
        'location_id' => $location->id,
        'store_manager_id' => null,
        'member_id' => null,
        'order_return_id' => null,
        'cancel_order_reason_id' => null,
    ]);

    $voucher = Voucher::factory()->create();

    OrderDiscount::factory()->create([
        'order_id' => $order->id,
        'discountable_type' => ModelMapping::VOUCHER->name,
        'discountable_id' => $voucher->id,
    ]);

    $orderDiscountQueries = new OrderDiscountQueries();
    $response = $orderDiscountQueries->getVoucherIdByOrder($order->id);

    $this->assertEquals($voucher->id, $response);
});

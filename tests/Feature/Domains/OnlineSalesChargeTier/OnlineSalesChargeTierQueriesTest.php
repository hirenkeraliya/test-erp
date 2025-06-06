<?php

declare(strict_types=1);

use App\Domains\OnlineSalesCharges\Enums\ShippingChargeTypes;
use App\Domains\OnlineSalesChargeTier\OnlineSalesChargeTierQueries;
use App\Models\OnlineSalesCharges;
use App\Models\OnlineSalesChargeTier;

beforeEach(function (): void {
    $this->onlineSalesCharge = OnlineSalesCharges::factory()->create([
        'shipping_charge_type_id' => ShippingChargeTypes::WEIGHT->value,
    ]);

    $this->onlineSalesChargeTierQueries = new OnlineSalesChargeTierQueries();
});

test('new record can be added', function (): void {
    $data = [
        'online_sales_charges_id' => $this->onlineSalesCharge->id,
        'min_weight' => 1,
        'max_weight' => 5,
        'amount' => 50,
    ];

    $this->onlineSalesChargeTierQueries->addNew($data);

    $this->assertDatabaseHas('online_sales_charge_tiers', $data);
});

test('record can be delete', function (): void {
    $onlineSalesChargeTier = OnlineSalesChargeTier::factory()->create([
        'online_sales_charges_id' => $this->onlineSalesCharge->id,
    ]);

    $this->onlineSalesChargeTierQueries->remove($this->onlineSalesCharge);

    $this->assertDatabaseMissing('online_sales_charge_tiers', [
        'id' => $onlineSalesChargeTier->id,
    ]);
});

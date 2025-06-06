<?php

declare(strict_types=1);

use App\Domains\OnlineSalesCharges\DataObjects\OnlineSalesChargesData;
use App\Domains\OnlineSalesCharges\Enums\ShippingChargeTypes;
use App\Domains\OnlineSalesCharges\OnlineSalesChargesQueries;
use App\Models\Company;
use App\Models\OnlineSalesCharges;
use App\Models\SaleChannel;
use App\Models\ShippingZone;

beforeEach(function (): void {
    $this->company = Company::factory()->create();
    $this->companyId = $this->company->id;

    $this->onlineSalesCharge = OnlineSalesCharges::factory()->create([
        'company_id' => $this->companyId,
        'name' => 'TEST',
        'shipping_charge_type_id' => ShippingChargeTypes::NUMBER_OF_UNITS->value,
        'shipping_zone_id' => ShippingZone::factory()->create()->id,
        'is_available_in_ecommerce' => false,
    ]);

    $this->onlineSalesChargesQueries = new OnlineSalesChargesQueries();
});

test('Record can be searched', function (): void {
    $response = $this->onlineSalesChargesQueries->listQuery([
        'search_text' => 'TEST',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ], $this->companyId);

    $this->assertEquals(1, $response->total());

    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('name', $this->onlineSalesCharge->name);
});

test('new record can be added', function (): void {
    $saleChannel = SaleChannel::factory()->create();

    $data = [
        'name' => 'TEST',
        'shipping_zone_id' => ShippingZone::factory()->create()->id,
        'minimum_value' => 10,
        'maximum_value' => 20,
        'amount' => 30,
        'is_available_in_ecommerce' => true,
        'sale_channel_ids' => [$saleChannel->id],
        'shipping_charge_type_id' => ShippingChargeTypes::NUMBER_OF_ITEMS->value,
        'online_sales_charge_tiers' => [],
    ];

    $this->onlineSalesChargesQueries->addNew(new OnlineSalesChargesData(...$data), $this->companyId);

    unset($data['sale_channel_ids']);
    unset($data['online_sales_charge_tiers']);

    $this->assertDatabaseHas('online_sales_charges', $data);
    $this->assertDatabaseHas('online_sales_charges_sale_channel', [
        'sale_channel_id' => $saleChannel->id,
    ]);
});

test('new record can be added with weight shipping charge type', function (): void {
    $saleChannel = SaleChannel::factory()->create();

    $onlineSalesChargeTiers = [
        'min_weight' => 1,
        'max_weight' => 5,
        'amount' => 10,
    ];

    $data = [
        'name' => 'TEST',
        'shipping_zone_id' => ShippingZone::factory()->create()->id,
        'minimum_value' => 10,
        'maximum_value' => 20,
        'amount' => 30,
        'is_available_in_ecommerce' => true,
        'sale_channel_ids' => [$saleChannel->id],
        'shipping_charge_type_id' => ShippingChargeTypes::WEIGHT->value,
        'online_sales_charge_tiers' => [$onlineSalesChargeTiers],
    ];

    $this->onlineSalesChargesQueries->addNew(new OnlineSalesChargesData(...$data), $this->companyId);

    unset($data['sale_channel_ids']);
    unset($data['online_sales_charge_tiers']);

    $this->assertDatabaseHas('online_sales_charges', $data);
    $this->assertDatabaseHas('online_sales_charges_sale_channel', [
        'sale_channel_id' => $saleChannel->id,
    ]);
    $this->assertDatabaseHas('online_sales_charge_tiers', $onlineSalesChargeTiers);
});

test('Record can be fetched', function (): void {
    $response = $this->onlineSalesChargesQueries->getById($this->onlineSalesCharge->id, $this->companyId);
    expect($response->toArray())
        ->toHaveKey('name', $this->onlineSalesCharge->name)
        ->toHaveKey('minimum_value', $this->onlineSalesCharge->minimum_value)
        ->toHaveKey('maximum_value', $this->onlineSalesCharge->maximum_value)
        ->toHaveKey('amount', $this->onlineSalesCharge->amount)
        ->toHaveKey('shipping_charge_type_id', $this->onlineSalesCharge->shipping_charge_type_id);
});

test('A record can be updated', function (): void {
    $saleChannel = SaleChannel::factory()->create();
    $data = [
        'name' => 'TEST',
        'shipping_zone_id' => ShippingZone::factory()->create()->id,
        'minimum_value' => 10,
        'maximum_value' => 20,
        'amount' => 30,
        'is_available_in_ecommerce' => true,
        'sale_channel_ids' => [$saleChannel->id],
        'shipping_charge_type_id' => ShippingChargeTypes::NUMBER_OF_ITEMS->value,
        'online_sales_charge_tiers' => [],
    ];

    $this->onlineSalesChargesQueries->update(
        new OnlineSalesChargesData(...$data),
        $this->onlineSalesCharge->id,
        $this->companyId
    );

    $data['id'] = $this->onlineSalesCharge->id;
    $data['company_id'] = $this->onlineSalesCharge->company_id;
    unset($data['sale_channel_ids']);
    unset($data['online_sales_charge_tiers']);
    $this->assertDatabaseHas('online_sales_charges', $data);

    $this->assertDatabaseHas('online_sales_charges_sale_channel', [
        'online_sales_charges_id' => $this->onlineSalesCharge->id,
        'sale_channel_id' => $saleChannel->id,
    ]);
});

test('A record can be soft delete', function (): void {
    $this->onlineSalesChargesQueries->delete($this->onlineSalesCharge->id, $this->companyId);

    $this->assertDatabaseMissing('online_sales_charges', [
        'id' => $this->onlineSalesCharge->id,
        'deleted_at' => null,
    ]);
});

test('toggleStatus is change the status', function (): void {
    $this->onlineSalesChargesQueries->toggleStatus($this->onlineSalesCharge->id, $this->companyId);

    $this->assertDatabaseHas('online_sales_charges', [
        'id' => $this->onlineSalesCharge->id,
        'name' => $this->onlineSalesCharge->name,
        'status' => ! $this->onlineSalesCharge->status,
    ]);
});

test('onlineSalesChargesForEcommerce is return the collection by company', function (): void {
    $response = $this->onlineSalesChargesQueries->onlineSalesChargesForEcommerce($this->companyId);
    expect($response->first()->toArray())->toHaveKey('id', $this->onlineSalesCharge->id)
        ->toHaveKey('name', $this->onlineSalesCharge->name)
        ->toHaveKey('status', $this->onlineSalesCharge->status)
        ->toHaveKey('minimum_value', $this->onlineSalesCharge->minimum_value)
        ->toHaveKey('maximum_value', $this->onlineSalesCharge->maximum_value)
        ->toHaveKey('amount', $this->onlineSalesCharge->amount);
});

test('validateOnlineSalesChargeSaleChannelMatch returns true when sale channel is linked', function (): void {
    $saleChannel = SaleChannel::factory()->create([
        'company_id' => $this->companyId,
    ]);

    $this->onlineSalesCharge->saleChannels()->attach($saleChannel->id);

    $result = $this->onlineSalesChargesQueries->validateOnlineSalesChargeSaleChannelMatch(
        $this->onlineSalesCharge,
        $saleChannel
    );

    expect($result)->toBeTrue();
});

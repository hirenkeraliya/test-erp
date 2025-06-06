<?php

declare(strict_types=1);

use App\Domains\OnlineSalesChargesChannelReference\OnlineSalesChargeChannelReferenceQueries;
use App\Models\OnlineSalesChargeChannelReference;
use App\Models\OnlineSalesCharges;
use App\Models\SaleChannel;

beforeEach(function (): void {
    $this->onlineSalesChargeChannelReferenceQueries = new OnlineSalesChargeChannelReferenceQueries();
});

test('a online sales charge channel reference can be added', function (): void {
    $onlineSalesCharge = OnlineSalesCharges::factory()->create()->id;
    $saleChannelId = SaleChannel::factory()->create()->id;

    $onlineSalesChargeChannelReferenceRecord = OnlineSalesChargeChannelReference::factory()->make([
        'online_sales_charges_id' => $onlineSalesCharge,
        'sale_channel_id' => $saleChannelId,
        'external_online_sales_charges_id' => $onlineSalesCharge,
    ]);

    $this->onlineSalesChargeChannelReferenceQueries->addNew($onlineSalesChargeChannelReferenceRecord->toArray());

    $this->assertDatabaseHas(
        OnlineSalesChargeChannelReference::class,
        $onlineSalesChargeChannelReferenceRecord->toArray()
    );
});

test(
    'it calls the getByOnlineSalesChargeIdAndSaleChannelId to get the external online sales charge',
    function (): void {
        $onlineSalesChargeId = OnlineSalesCharges::factory()->create()->getKey();
        $saleChannelId = SaleChannel::factory()->create()->getKey();

        $onlineSalesChargeChannelReference = OnlineSalesChargeChannelReference::factory()->create([
            'sale_channel_id' => $saleChannelId,
            'online_sales_charges_id' => $onlineSalesChargeId,
            'external_online_sales_charges_id' => 1,
        ]);

        $response = $this->onlineSalesChargeChannelReferenceQueries->getByOnlineSalesChargeIdAndSaleChannelId(
            $onlineSalesChargeId,
            $saleChannelId
        );

        expect($response)
            ->toHaveKey('id', $onlineSalesChargeChannelReference->getKey())
            ->toHaveKey('online_sales_charges_id', $onlineSalesChargeId)
            ->toHaveKey('external_online_sales_charges_id', 1);
    }
);

test('a product collection channel reference can be deleted', function (): void {
    $onlineSalesChargeReference = OnlineSalesChargeChannelReference::factory()->create([
        'external_online_sales_charges_id' => 1,
    ]);

    $this->assertDatabaseHas('online_sales_charge_channel_references', [
        'id' => $onlineSalesChargeReference->id,
    ]);

    $this->onlineSalesChargeChannelReferenceQueries->deleteById(1, $onlineSalesChargeReference->sale_channel_id);

    $this->assertDatabaseMissing('online_sales_charge_channel_references', [
        'id' => $onlineSalesChargeReference->id,
    ]);
});

<?php

declare(strict_types=1);

use App\Domains\MasterProductChannelReference\MasterProductChannelReferenceQueries;
use App\Models\MasterProduct;
use App\Models\MasterProductChannelReference;
use App\Models\SaleChannel;

beforeEach(function (): void {
    $this->masterProductChannelReferenceQueries = new MasterProductChannelReferenceQueries();
});

test('a master product channel reference can be added', function (): void {
    $masterProductId = MasterProduct::factory()->create()->id;
    $saleChannelId = SaleChannel::factory()->create()->id;

    $masterProductChannelReferenceRecord = MasterProductChannelReference::factory()->make([
        'master_product_id' => $masterProductId,
        'sale_channel_id' => $saleChannelId,
        'external_master_product_id' => $masterProductId,
    ]);

    $this->masterProductChannelReferenceQueries->addNew($masterProductChannelReferenceRecord->toArray());

    $this->assertDatabaseHas(MasterProductChannelReference::class, $masterProductChannelReferenceRecord->toArray());
});

test('it calls the getByMasterProductIdAndSaleChannelId to get the external Master product id', function (): void {
    $masterProductId = MasterProduct::factory()->create()->getKey();
    $saleChannelId = SaleChannel::factory()->create()->getKey();

    $masterProductChannelReferenceRecord = MasterProductChannelReference::factory()->create([
        'sale_channel_id' => $saleChannelId,
        'master_product_id' => $masterProductId,
        'external_master_product_id' => 1,
    ]);

    $response = $this->masterProductChannelReferenceQueries->getByMasterProductIdAndSaleChannelId(
        $masterProductId,
        $saleChannelId
    );

    expect($response)
        ->toHaveKey('id', $masterProductChannelReferenceRecord->getKey())
        ->toHaveKey('master_product_id', $masterProductId)
        ->toHaveKey('external_master_product_id', 1);
});

<?php

declare(strict_types=1);

use App\Domains\DreamPriceChannelReference\DreamPriceChannelReferenceQueries;
use App\Models\DreamPrice;
use App\Models\DreamPriceChannelReference;
use App\Models\SaleChannel;

beforeEach(function (): void {
    $this->dreamPriceChannelReferenceQueries = new DreamPriceChannelReferenceQueries();
});

test('a dream price channel reference can be added', function (): void {
    $dreamPrice = DreamPrice::factory()->create()->id;
    $saleChannelId = SaleChannel::factory()->create()->id;

    $dreamPriceChannelReferenceRecord = DreamPriceChannelReference::factory()->make([
        'dream_price_id' => $dreamPrice,
        'sale_channel_id' => $saleChannelId,
        'external_dream_price_id' => $dreamPrice,
    ]);

    $this->dreamPriceChannelReferenceQueries->addNew($dreamPriceChannelReferenceRecord->toArray());

    $this->assertDatabaseHas(DreamPriceChannelReference::class, $dreamPriceChannelReferenceRecord->toArray());
});

test('it calls the getByDreamPriceIdAndSaleChannelId to get the external DreamPrice', function (): void {
    $dreamPriceId = DreamPrice::factory()->create()->getKey();
    $saleChannelId = SaleChannel::factory()->create()->getKey();

    $dreamPriceChannelReference = DreamPriceChannelReference::factory()->create([
        'sale_channel_id' => $saleChannelId,
        'dream_price_id' => $dreamPriceId,
        'external_dream_price_id' => 1,
    ]);

    $response = $this->dreamPriceChannelReferenceQueries->getByDreamPriceIdAndSaleChannelId(
        $dreamPriceId,
        $saleChannelId
    );

    expect($response)
        ->toHaveKey('id', $dreamPriceChannelReference->getKey())
        ->toHaveKey('dream_price_id', $dreamPriceId)
        ->toHaveKey('external_dream_price_id', 1);
});

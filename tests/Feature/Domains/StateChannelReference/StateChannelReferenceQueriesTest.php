<?php

declare(strict_types=1);

use App\Domains\StateChannelReference\StateChannelReferenceQueries;
use App\Models\SaleChannel;
use App\Models\State;
use App\Models\StateChannelReference;

beforeEach(function (): void {
    $this->stateChannelReferenceQueries = new StateChannelReferenceQueries();
});

test('a State channel reference can be added', function (): void {
    $state = State::factory()->create()->id;
    $saleChannelId = SaleChannel::factory()->create()->id;

    $stateChannelReferenceRecord = StateChannelReference::factory()->make([
        'state_id' => $state,
        'sale_channel_id' => $saleChannelId,
        'external_state_id' => $state,
    ]);

    $this->stateChannelReferenceQueries->addNew($stateChannelReferenceRecord->toArray());

    $this->assertDatabaseHas(StateChannelReference::class, $stateChannelReferenceRecord->toArray());
});

test('it calls the getByStateIdAndSaleChannelId to get the external state', function (): void {
    $stateId = State::factory()->create()->id;
    $saleChannelId = SaleChannel::factory()->create()->id;

    $stateChannelReference = StateChannelReference::factory()->create([
        'sale_channel_id' => $saleChannelId,
        'state_id' => $stateId,
        'external_state_id' => 1,
    ]);

    $response = $this->stateChannelReferenceQueries->getByStateIdAndSaleChannelId($stateId, $saleChannelId);

    expect($response)
        ->toHaveKey('id', $stateChannelReference->getKey())
        ->toHaveKey('state_id', $stateId)
        ->toHaveKey('external_state_id', 1);
});

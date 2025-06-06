<?php

declare(strict_types=1);

use App\Domains\CloseCounterDenomination\CloseCounterDenominationQueries;
use App\Domains\Counter\DataObjects\CloseCounterDenominationData;
use App\Models\CounterUpdate;

test('New close counter denomination can be added', function (): void {
    $counterUpdate = CounterUpdate::factory()->create();

    $denomination = [
        'denomination' => 10,
        'quantity' => 1,
    ];

    $closeCounterDenominationData = new CloseCounterDenominationData(...$denomination);

    $closeCounterDenominationQueries = resolve(CloseCounterDenominationQueries::class);
    $closeCounterDenominationQueries->addNew($counterUpdate->id, $closeCounterDenominationData);

    $this->assertDatabaseHas('close_counter_denominations', [
        'counter_update_id' => $counterUpdate->id,
        'denomination' => $closeCounterDenominationData->denomination,
        'quantity' => $closeCounterDenominationData->quantity,
    ]);
});

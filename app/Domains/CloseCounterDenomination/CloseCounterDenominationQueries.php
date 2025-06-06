<?php

declare(strict_types=1);

namespace App\Domains\CloseCounterDenomination;

use App\Domains\Counter\DataObjects\CloseCounterDenominationData;
use App\Models\CloseCounterDenomination;

class CloseCounterDenominationQueries
{
    public function addNew(int $counterUpdateId, CloseCounterDenominationData $closeCounterDenominationData): void
    {
        CloseCounterDenomination::create([
            'counter_update_id' => $counterUpdateId,
            'denomination' => $closeCounterDenominationData->denomination,
            'quantity' => $closeCounterDenominationData->quantity,
        ]);
    }

    public function getBasicColumnNames(): string
    {
        return 'id,counter_update_id,denomination,quantity';
    }
}

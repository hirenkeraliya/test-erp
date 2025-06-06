<?php

declare(strict_types=1);

namespace App\Domains\OnlineSalesChargeTier;

use App\Models\OnlineSalesCharges;
use App\Models\OnlineSalesChargeTier;

class OnlineSalesChargeTierQueries
{
    public function addNew(array $record): void
    {
        OnlineSalesChargeTier::create($record);
    }

    public function remove(OnlineSalesCharges $onlineSalesCharge): void
    {
        $onlineSalesCharge->onlineSalesChargeTiers()->delete();
    }
}

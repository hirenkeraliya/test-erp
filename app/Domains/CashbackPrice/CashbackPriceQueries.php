<?php

declare(strict_types=1);

namespace App\Domains\CashbackPrice;

use App\Models\Cashback;
use App\Models\CashbackPrice;

class CashbackPriceQueries
{
    public function addNew(array $data): void
    {
        CashbackPrice::create($data);
    }

    public function delete(Cashback $cashback): void
    {
        foreach ($cashback->cashbackPrices as $cashbackPrice) {
            $cashbackPrice->delete();
        }
    }

    public function getBasicColumnNames(): string
    {
        return 'id,cashback_id,condition_operator_type_id,amount';
    }
}

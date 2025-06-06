<?php

declare(strict_types=1);

namespace App\Domains\SaleItemExchange;

use App\Models\SaleItemExchange;

class SaleItemExchangeQueries
{
    public function addNew(array $saleItemExchangeData): int
    {
        return SaleItemExchange::create($saleItemExchangeData)->id;
    }
}

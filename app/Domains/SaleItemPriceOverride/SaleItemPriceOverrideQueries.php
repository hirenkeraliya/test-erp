<?php

declare(strict_types=1);

namespace App\Domains\SaleItemPriceOverride;

use App\Models\SaleItemPriceOverride;
use Closure;

class SaleItemPriceOverrideQueries
{
    public function addNew(
        int $saleItemId,
        int $negotiatorId,
        string $negotiatorType,
        float $overridePrice
    ): SaleItemPriceOverride {
        return SaleItemPriceOverride::create([
            'sale_item_id' => $saleItemId,
            'negotiator_id' => $negotiatorId,
            'negotiator_type' => $negotiatorType,
            'override_price' => $overridePrice,
        ]);
    }

    public function getBasicColumnNames(): string
    {
        return 'id,sale_item_id,negotiator_id,negotiator_type,override_price';
    }

    public function getNegotiatorBasicColumnNames(): string
    {
        return 'id,employee_id';
    }

    public function getSeasonalSalesBasicColumns(): Closure
    {
        return fn ($query) => $query->select('id', 'negotiator_id', 'negotiator_type');
    }
}

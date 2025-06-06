<?php

declare(strict_types=1);

namespace App\Domains\SalePriceOverride;

use App\Models\SalePriceOverride;
use Closure;

class SalePriceOverrideQueries
{
    public function addNew(
        int $saleId,
        int $negotiatorId,
        string $negotiatorType,
        float $overridePrice
    ): SalePriceOverride {
        return SalePriceOverride::create([
            'sale_id' => $saleId,
            'negotiator_id' => $negotiatorId,
            'negotiator_type' => $negotiatorType,
            'override_price' => $overridePrice,
        ]);
    }

    public function getBasicColumnNames(): string
    {
        return 'id,sale_id,negotiator_id,negotiator_type,override_price';
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

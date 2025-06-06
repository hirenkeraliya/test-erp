<?php

declare(strict_types=1);

namespace App\Domains\SaleItemComplimentary;

use App\Models\SaleItemComplimentary;

class SaleItemComplimentaryQueries
{
    public function addNew(int $saleItemId, int $authorizerId, string $authorizerType, float $amount): void
    {
        SaleItemComplimentary::create([
            'sale_item_id' => $saleItemId,
            'authorizer_id' => $authorizerId,
            'authorizer_type' => $authorizerType,
            'amount' => $amount,
        ]);
    }

    public function getBasicColumnNames(): string
    {
        return 'id,sale_item_id,authorizer_id,authorizer_type,amount';
    }
}

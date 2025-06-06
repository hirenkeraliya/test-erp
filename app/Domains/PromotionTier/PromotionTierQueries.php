<?php

declare(strict_types=1);

namespace App\Domains\PromotionTier;

class PromotionTierQueries
{
    public function getBasicColumnNames(): string
    {
        return 'promotion_id,buy_value,get_value,get_quantity,max_value';
    }
}

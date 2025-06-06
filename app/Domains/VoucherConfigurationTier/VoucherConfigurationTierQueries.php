<?php

declare(strict_types=1);

namespace App\Domains\VoucherConfigurationTier;

class VoucherConfigurationTierQueries
{
    public static function getBasicColumnNames(): string
    {
        return 'id,voucher_configuration_id,minimum_spend_amount,maximum_spend_amount,get_value';
    }
}

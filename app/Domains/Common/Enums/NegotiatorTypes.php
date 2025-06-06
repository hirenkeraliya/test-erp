<?php

declare(strict_types=1);

namespace App\Domains\Common\Enums;

use App\Http\Traits\PrepareEnumDataMethods;
use App\Models\Cashier;
use App\Models\Director;
use App\Models\StoreManager;

enum NegotiatorTypes: string
{
    use PrepareEnumDataMethods;

    case CASHIER = 'Cashier';
    case DIRECTOR = 'Director';
    case STORE_MANAGER = 'Store Manager';

    public static function getNegotiatorClass(string $negotiatorType): string
    {
        if ($negotiatorType === self::CASHIER->value) {
            return Cashier::class;
        }

        if ($negotiatorType === self::DIRECTOR->value) {
            return Director::class;
        }

        return StoreManager::class;
    }
}

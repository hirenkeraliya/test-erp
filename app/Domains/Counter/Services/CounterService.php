<?php

declare(strict_types=1);

namespace App\Domains\Counter\Services;

use App\Domains\Counter\DataObjects\CounterData;
use Illuminate\Support\Str;

class CounterService
{
    public function getCounterData(array $counterDetails, int $locationId): CounterData
    {
        $isSelfCheckout = array_key_exists('is_self_checkout', $counterDetails) && 'yes' === Str::lower(
            $counterDetails['is_self_checkout']
        );

        return new CounterData(
            name: (string) $counterDetails['name'],
            location_id: $locationId,
            is_locked: 'yes' === Str::lower($counterDetails['is_locked']),
            is_self_checkout: $isSelfCheckout,
        );
    }
}

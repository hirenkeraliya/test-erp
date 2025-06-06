<?php

declare(strict_types=1);

namespace App\Domains\Company;

use App\CommonFunctions;

class RoundOffConfiguration
{
    /**
     * @var array<string, string>
     */
    final public const DECIMAL_PLACES = [
        '.01' => '-0.01',
        '.02' => '-0.02',
        '.03' => '0.02',
        '.04' => '0.01',
        '.05' => '0.00',
        '.06' => '-0.01',
        '.07' => '-0.02',
        '.08' => '0.02',
        '.09' => '0.01',
        '.00' => '0.00',
    ];

    public static function roundOffCalculationFor(string $amount): float
    {
        $amount = CommonFunctions::numberFormatString((float) $amount);

        $decimalPlacesOfAmount = '.0' . substr($amount, -1);

        return (float) self::DECIMAL_PLACES[$decimalPlacesOfAmount];
    }

    /**
     * @return array<int, array{decimal_place: string, value: string}>
     */
    public function getList(): array
    {
        $roundData = [];
        foreach (self::DECIMAL_PLACES as $key => $value) {
            $roundData[] = [
                'decimal_place' => $key,
                'value' => $value,
            ];
        }

        return $roundData;
    }
}

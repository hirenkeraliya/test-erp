<?php

declare(strict_types=1);

namespace App\Domains\Company;

use App\CommonFunctions;

class RoundOffConfigurationToSixDigits
{
    /**
     * @var array<string, string>
     */
    final public const DECIMAL_PLACES = [
        '.000001' => '-0.000001',
        '.000002' => '-0.000002',
        '.000003' => '0.000002',
        '.000004' => '0.000001',
        '.000005' => '0.000000',
        '.000006' => '-0.000001',
        '.000007' => '-0.000002',
        '.000008' => '0.000002',
        '.000009' => '0.000001',
        '.000000' => '0.000000',
    ];

    public static function roundOffCalculationFor(string $amount): float
    {
        $amount = CommonFunctions::numberFormatString((float) $amount, 6);

        $decimalPlacesOfAmount = '.00000' . substr($amount, -1);

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

<?php

declare(strict_types=1);

namespace App\Domains\Denomination\DataObjects;

use Spatie\LaravelData\Data;

class DenominationData extends Data
{
    public function __construct(
        public float $denomination,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(): array
    {
        return [
            'denomination' => ['required', 'numeric', 'min:0.01'],
        ];
    }
}

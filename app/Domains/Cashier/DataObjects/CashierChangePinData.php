<?php

declare(strict_types=1);

namespace App\Domains\Cashier\DataObjects;

use Spatie\LaravelData\Attributes\Validation\Unique;
use Spatie\LaravelData\Data;

class CashierChangePinData extends Data
{
    public function __construct(
        public string $new_pin
    ) {
    }

    /**
     * @return array<string, array<string|Unique>>
     */
    public static function rules(): array
    {
        return [
            'new_pin' => ['required', 'string', 'confirmed', 'min:4', 'max:4'],
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Domains\Cashier\DataObjects;

use Spatie\LaravelData\Data;

class CashierLoginData extends Data
{
    public function __construct(
        public string $username,
        public string $pin,
        public string $device_type,
    ) {
    }

    /**
     * @return array<string, array<string>>
     */
    public static function rules(): array
    {
        return [
            'username' => ['required', 'string', 'exists:cashiers,username'],
            'pin' => ['required', 'numeric'],
            'device_type' => ['required', 'in:mobile'],
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Domains\WarehouseManager\DataObjects;

use Illuminate\Http\Request;
use Spatie\LaravelData\Data;

class WarehouseManagerApplicationLoginData extends Data
{
    public function __construct(
        public string $username,
        public string $password,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(Request $request): array
    {
        return [
            'username' => ['required', 'string', 'min:4', 'max:255'],
            'password' => ['required', 'string', 'max:20'],
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Domains\Sale\DataObjects;

use Spatie\LaravelData\Data;

class CancelLayawaySaleData extends Data
{
    public function __construct(
        public int $store_manager_id,
        public string $passcode,
        public string $happened_at,
        public string $reason,
        public ?string $store_manager_authorization_code = null,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(): array
    {
        return [
            'store_manager_id' => ['required', 'integer'],
            'passcode' => ['required', 'string'],
            'happened_at' => ['required', 'date_format:Y-m-d H:i:s'],
            'reason' => ['required', 'string'],
            'store_manager_authorization_code' => ['sometimes', 'nullable', 'string'],
        ];
    }
}

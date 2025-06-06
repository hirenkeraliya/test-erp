<?php

declare(strict_types=1);

namespace App\Domains\Counter\DataObjects;

use Spatie\LaravelData\Data;

class OpenCounterData extends Data
{
    public function __construct(
        public string $counter_id,
        public float $opening_balance,
        public ?string $opened_by_pos_at,
    ) {
    }

    /**
     * @return array<string, array<string>>
     */
    public static function rules(): array
    {
        return [
            'counter_id' => ['required', 'integer'],
            'opening_balance' => ['required', 'numeric', 'min:0'],
            'opened_by_pos_at' => ['sometimes', 'date_format:Y-m-d H:i:s'],
        ];
    }
}

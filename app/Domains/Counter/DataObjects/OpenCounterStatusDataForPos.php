<?php

declare(strict_types=1);

namespace App\Domains\Counter\DataObjects;

use App\Models\Counter;
use App\Models\CounterUpdate;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;

class OpenCounterStatusDataForPos extends Data
{
    public function __construct(
        public ?int $counter_id,
        public ?string $opened_by_pos_at,
        public ?int $counter_update_id,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'counter_id' => ['sometimes', 'integer', Rule::exists(Counter::class, 'id')],
            'opened_by_pos_at' => ['sometimes', 'string'],
            'counter_update_id' => ['sometimes', 'integer', Rule::exists(CounterUpdate::class, 'id')],
        ];
    }
}

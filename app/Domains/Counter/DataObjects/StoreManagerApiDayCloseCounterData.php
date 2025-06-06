<?php

declare(strict_types=1);

namespace App\Domains\Counter\DataObjects;

use App\Domains\CounterUpdate\Enums\CounterStatus;
use Illuminate\Validation\Rules\Enum;
use Spatie\LaravelData\Data;

class StoreManagerApiDayCloseCounterData extends Data
{
    public function __construct(
        public ?int $store_id,
        public ?int $location_id,
        public int $status,
        public ?string $search_text = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'store_id' => ['required_without_all:location_id', 'integer'],
            'location_id' => ['required_without_all:store_id', 'integer'],
            'status' => ['required', 'integer', new Enum(CounterStatus::class)],
            'search_text' => ['sometimes', 'nullable', 'string'],
        ];
    }
}

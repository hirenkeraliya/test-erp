<?php

declare(strict_types=1);

namespace App\Domains\CounterUpdateEvent\DataObjects;

use App\Domains\CounterUpdateEvent\Enums\CounterUpdateEventTypes;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;

class CounterUpdateEventData extends Data
{
    public function __construct(
        public string $offline_id,
        public int $type_id,
        public string $happened_at,
        public ?int $product_id,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(Request $request): array
    {
        return [
            'offline_id' => ['required', 'string', 'unique:counter_update_events,offline_id'],
            'type_id' => ['required', 'integer', 'in:' . CounterUpdateEventTypes::getValues()],
            'happened_at' => ['required', 'date_format:Y-m-d H:i:s'],
            'product_id' => [
                Rule::requiredIf(
                    fn (): bool => (int) $request->input(
                        'type_id'
                    ) === CounterUpdateEventTypes::PRODUCT_ADDED_TO_CART->value ||
                        (int) $request->input('type_id') ===
                        CounterUpdateEventTypes::PRODUCT_REMOVED_FROM_CART->value
                ),
                'nullable',
                'integer',
            ],
        ];
    }
}

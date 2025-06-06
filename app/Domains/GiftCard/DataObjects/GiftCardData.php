<?php

declare(strict_types=1);

namespace App\Domains\GiftCard\DataObjects;

use App\Domains\GiftCard\Enums\GiftCardTypes;
use Spatie\LaravelData\Data;

class GiftCardData extends Data
{
    public function __construct(
        public int $type_id,
        public array $gift_cards,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(): array
    {
        return [
            'type_id' => ['required', 'integer', 'in:' . GiftCardTypes::getValues()],

            'gift_cards' => ['required', 'array'],
            'gift_cards.*.number' => ['required', 'alpha_num'],
            'gift_cards.*.expiry_date' => [
                'nullable',
                'date',
                'date_format:Y-m-d',
                'after_or_equal:' . now()->format('Y-m-d'),
            ],
            'gift_cards.*.amount' => ['required', 'numeric'],
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Domains\Counter\DataObjects;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

class CloseCounterDataForStoreManager extends Data
{
    public function __construct(
        public float $closing_balance,
        public ?string $mismatch_amount_reason,
        #[DataCollectionOf(CloseCounterDenominationData::class)]
        public ?DataCollection $denominations,
    ) {
    }

    /**
     * @return array<string, array<string>>
     */
    public static function rules(): array
    {
        return [
            'closing_balance' => ['required', 'numeric', 'min:0'],
            'mismatch_amount_reason' => ['nullable', 'string'],
            'denominations' => ['sometimes', 'array'],
            'denominations.*.denomination' => ['required', 'numeric', 'min:0.01'],
            'denominations.*.quantity' => ['required', 'integer', 'min:0'],
        ];
    }
}

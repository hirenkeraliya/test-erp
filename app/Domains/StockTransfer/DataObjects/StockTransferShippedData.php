<?php

declare(strict_types=1);

namespace App\Domains\StockTransfer\DataObjects;

use App\Domains\StockTransfer\Enums\ShippedTypes;
use Spatie\LaravelData\Data;

class StockTransferShippedData extends Data
{
    public function __construct(
        public int $shipped_type,
        public ?string $location_id,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(): array
    {
        return [
            'shipped_type' => ['required', 'integer', 'in:' . ShippedTypes::getValues()],
            'location_id' => ['required_if:shipped_type,' . ShippedTypes::TRANSIT->value, 'integer'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'location_id.required_if' => 'Location is required for the stock transfer ship to transit.',
        ];
    }
}

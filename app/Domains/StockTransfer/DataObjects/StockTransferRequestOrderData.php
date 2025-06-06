<?php

declare(strict_types=1);

namespace App\Domains\StockTransfer\DataObjects;

use Spatie\LaravelData\Data;

class StockTransferRequestOrderData extends Data
{
    public function __construct(
        public int $source_location_id,
        public int $destination_location_id,
        public ?string $attention,
        public ?string $reference_number,
        public ?string $remarks,
        public array $transfer_items,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(): array
    {
        return [
            'source_location_id' => ['required', 'integer'],
            'destination_location_id' => ['required', 'integer'],
            'attention' => ['nullable', 'string', 'max:255'],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'remarks' => ['nullable', 'string'],
            'transfer_items' => ['required', 'array'],
            'transfer_items.*.product_id' => ['required', 'integer', 'distinct:strict'],
            'transfer_items.*.transfer_stock' => ['required', 'numeric', 'min:0.01'],
            'transfer_items.*.unit_of_measure_derivative_id' => ['nullable', 'integer'],
            'transfer_items.*.remarks' => ['nullable', 'string'],
            'transfer_items.*.batch_details' => ['nullable', 'array'],
            'transfer_items.*.batch_details.*.batch_number' => ['nullable', 'string'],
            'transfer_items.*.batch_details.*.quantity' => ['nullable', 'numeric', 'min:0.01'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'transfer_items.*.product_id.distinct' => 'Please remove duplicate products.',
        ];
    }
}

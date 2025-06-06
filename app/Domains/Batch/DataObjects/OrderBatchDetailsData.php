<?php

declare(strict_types=1);

namespace App\Domains\Batch\DataObjects;

use Spatie\LaravelData\Data;

class OrderBatchDetailsData extends Data
{
    public function __construct(
        public int $product_id,
        public array $batch_details,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(): array
    {
        return [
            'product_id' => ['required', 'integer'],
            'batch_details' => ['required', 'array'],
            'batch_details.*.batch_number' => ['required', 'string'],
            'batch_details.*.batch_expiry_date' => ['required', 'date'],
        ];
    }
}

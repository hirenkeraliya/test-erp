<?php

declare(strict_types=1);

namespace App\Domains\BookingPaymentProduct\DataObjects;

use Illuminate\Validation\Rules\Exists;
use Spatie\LaravelData\Data;

class BookingPaymentProductData extends Data
{
    public function __construct(
        public array $products,
        public ?array $promoter_ids = [],
    ) {
    }

    /**
     * @return array<string, array<int, Exists|string>>
     */
    public static function rules(): array
    {
        return [
            'promoter_ids' => ['sometimes', 'nullable', 'array'],
            'promoter_ids.*' => ['required', 'integer'],

            'products' => ['required', 'array'],
            'products.*.product_id' => ['required', 'integer'],
            'products.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'products.*.price' => ['nullable', 'numeric', 'between:0,99999999.99'],
            'products.*.box_product_id' => ['sometimes', 'nullable', 'integer'],
            'products.*.product_bundle_id' => ['sometimes', 'nullable', 'integer'],
            'products.*.promoter_ids' => ['sometimes', 'nullable', 'array'],
            'products.*.promoter_ids.*' => ['required', 'integer'],
        ];
    }
}

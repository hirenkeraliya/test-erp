<?php

declare(strict_types=1);

namespace App\Domains\DreamPriceProduct\DataObjects;

use Spatie\LaravelData\Data;

class DreamPriceProductData extends Data
{
    public function __construct(
        public int $dream_price_id,
        public int $product_id,
        public float $price,
    ) {
    }

    /**
     * @return array<string, array<string>>
     */
    public static function rules(): array
    {
        return [
            'dream_price_id' => ['required', 'integer'],
            'product_id' => ['required', 'integer'],
            'price' => ['required', 'numeric'],
        ];
    }
}

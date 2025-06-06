<?php

declare(strict_types=1);

namespace App\Domains\Product\DataObjects;

use App\Models\Product;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;

class ProductStockForAllStoreDataForPos extends Data
{
    public function __construct(
        public int $product_id,
        public ?string $after_updated_at,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', Rule::exists(Product::class, 'id')],
            'after_updated_at' => ['sometimes', 'nullable', 'string', 'date_format:Y-m-d H:i:s'],
        ];
    }
}

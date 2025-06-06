<?php

declare(strict_types=1);

namespace App\Domains\Product\DataObjects;

use Spatie\LaravelData\Data;

class ProductWithLocationStockData extends Data
{
    public function __construct(
        public string $article_number,
        public int $location_id,
        public int $external_location_id,
    ) {
    }

    /**
     * @return array<string, array<string>>
     */
    public static function rules(): array
    {
        if (! config('app.product_variant')) {
            $rule = [
                'article_number' => ['required', 'string', 'exists:products,article_number'],
            ];
        } else {
            $rule = [
                'article_number' => ['required', 'string', 'exists:master_products,article_number'],
            ];
        }

        return array_merge($rule, [
            'location_id' => ['required', 'integer'],
            'external_location_id' => ['required', 'integer'],
        ]);
    }
}

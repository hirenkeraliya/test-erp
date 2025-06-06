<?php

declare(strict_types=1);

namespace App\Domains\Product\DataObjects;

use Spatie\LaravelData\Data;

class ProductStockPurchasePlanData extends Data
{
    public function __construct(
        public string $article_number,
        public int $location_id,
    ) {
    }

    /**
     * @return array<string, array<string>>
     */
    public static function rules(): array
    {
        return [
            'article_number' => ['required', 'string', 'exists:products,article_number'],
            'location_id' => ['required', 'integer'],
        ];
    }
}

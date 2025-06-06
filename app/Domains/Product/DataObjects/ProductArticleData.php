<?php

declare(strict_types=1);

namespace App\Domains\Product\DataObjects;

use Spatie\LaravelData\Data;

class ProductArticleData extends Data
{
    public function __construct(
        public string $article_number,
        public string $source_location_id,
        public string $destination_location_id,
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
            'source_location_id' => ['required', 'integer'],
            'destination_location_id' => ['required', 'integer'],
        ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Domains\CustomReport\DataObjects;

use Spatie\LaravelData\Data;

class StockCardCustomReportData extends Data
{
    public function __construct(
        public ?string $product_id = null,
        public ?int $location_id = null,
        public ?int $product_collection_id = null,
        public ?array $date_range = [],
        public ?string $article_number = null,
        public ?int $filter_by = null,
        public ?int $brand_id = null,
        public ?int $category_id = null,
        public ?int $department_id = null,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(): array
    {
        return [
            'location_id' => ['nullable', 'integer'],
            'product_collection_id' => ['nullable', 'integer'],
            'product_id' => ['nullable'],
            'brand_id' => ['nullable'],
            'category_id' => ['nullable'],
            'department_id' => ['nullable'],
            'date_range' => ['required', 'array'],
            'article_number' => ['nullable', 'string'],
            'filter_by' => ['nullable', 'integer'],
        ];
    }
}

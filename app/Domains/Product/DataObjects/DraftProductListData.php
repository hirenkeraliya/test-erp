<?php

declare(strict_types=1);

namespace App\Domains\Product\DataObjects;

use Spatie\LaravelData\Data;

class DraftProductListData extends Data
{
    public function __construct(
        public ?int $per_page,
        public ?int $page,
        public ?string $search_text,
        public ?string $sort_by,
        public ?string $sort_direction,
        public ?string $batch,
        public ?int $product_type_id,
        public ?int $employee_id,
        public ?array $date_range = [],
        public ?array $category_ids = [],
        public ?array $brand_ids = [],
        public ?array $color_ids = [],
        public ?array $size_ids = [],
        public ?array $department_ids = [],
        public ?array $article_numbers = [],
        public ?array $tag_ids = [],
        public ?array $style_ids = [],
        public ?array $attributes = [],
    ) {
    }

    /**
     * @return array<string, array<string>>
     */
    public static function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer'],
            'page' => ['sometimes', 'integer'],
            'search_text' => ['nullable', 'string'],
            'sort_by' => ['sometimes', 'string'],
            'sort_direction' => ['sometimes', 'string'],
            'batch' => ['sometimes', 'string'],
            'date_range' => ['sometimes', 'array'],
            'product_type_id' => ['sometimes', 'integer'],
            'category_ids' => ['sometimes', 'array'],
            'brand_ids' => ['sometimes', 'array'],
            'color_ids' => ['sometimes', 'array'],
            'size_ids' => ['sometimes', 'array'],
            'department_ids' => ['sometimes', 'array'],
            'article_numbers' => ['sometimes', 'array'],
            'tag_ids' => ['sometimes', 'array'],
            'style_ids' => ['sometimes', 'array'],
            'attributes' => ['sometimes', 'array'],
            'employee_id' => ['sometimes', 'integer'],
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Domains\HappyHourDiscount\DataObjects;

use App\Domains\HappyHourDiscount\Enums\ProductTypes;
use Illuminate\Validation\Rules\Enum;
use Spatie\LaravelData\Data;

class HappyHourDiscountListDataForPos extends Data
{
    public function __construct(
        public ?int $product_type_id,
        public int $per_page,
        public int $page,
        public ?string $search_text,
        public ?string $sort_by,
        public ?string $sort_direction,
        public ?string $after_updated_at,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(): array
    {
        return [
            'product_type_id' => ['sometimes', 'integer', new Enum(ProductTypes::class)],
            'per_page' => ['required', 'integer'],
            'page' => ['required', 'integer'],
            'search_text' => ['sometimes', 'string'],
            'sort_by' => ['sometimes', 'string', 'in:id,offline_id,start_date,end_date,happened_at'],
            'sort_direction' => ['required_with:sort_by', 'string', 'in:asc,desc'],
            'after_updated_at' => ['sometimes', 'nullable', 'string', 'date_format:Y-m-d H:i:s'],
        ];
    }
}

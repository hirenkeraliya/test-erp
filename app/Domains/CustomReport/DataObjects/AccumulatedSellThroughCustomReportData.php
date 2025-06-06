<?php

declare(strict_types=1);

namespace App\Domains\CustomReport\DataObjects;

use Spatie\LaravelData\Data;

class AccumulatedSellThroughCustomReportData extends Data
{
    public function __construct(
        public string $date,
        public int $report_type,
        public int $filter_by,
        public ?int $product_id,
        public ?int $product_collection_id,
        public ?int $category_id,
        public ?int $brand_id,
        public ?int $size_id,
        public ?array $location_ids = [],
        public ?array $color_ids = [],
        public ?array $department_ids = [],
        public ?array $tag_ids = [],
        public ?array $article_numbers = [],
        public ?array $style_ids = [],
        public array $accumulated_sale_through_include_types = [],
        public ?array $includes_by_goods_receive_note_in_location_ids = [],
        public ?array $includes_by_goods_receive_note_out_location_ids = [],
        public ?array $includes_by_stock_adjustment_in_location_ids = [],
        public ?array $includes_by_stock_adjustment_out_location_ids = [],
        public ?array $includes_by_stock_transfer_in_location_ids = [],
        public ?array $includes_by_stock_transfer_out_location_ids = [],
        public ?array $includes_by_delivery_order_in_location_ids = [],
        public ?array $includes_by_delivery_order_out_location_ids = [],
        public ?array $attributes = [],
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(): array
    {
        return [
            'date' => ['required', 'string'],
            'location_ids' => ['sometimes', 'array'],
            'report_type' => ['required', 'integer'],
            'filter_by' => ['required', 'integer'],
            'product_id' => ['sometimes', 'integer'],
            'product_collection_id' => ['sometimes', 'integer'],
            'category_id' => ['sometimes', 'integer'],
            'brand_id' => ['sometimes', 'integer'],
            'size_id' => ['sometimes', 'integer'],
            'color_ids' => ['sometimes', 'nullable', 'array'],
            'department_ids' => ['sometimes', 'array'],
            'tag_ids' => ['sometimes', 'array'],
            'article_numbers' => ['sometimes', 'array'],
            'style_ids' => ['sometimes', 'array'],
            'accumulated_sale_through_include_types' => ['sometimes', 'array'],
            'includes_by_goods_receive_note_in_location_ids' => ['sometimes', 'array'],
            'includes_by_goods_receive_note_out_location_ids' => ['sometimes', 'array'],
            'includes_by_stock_adjustment_in_location_ids' => ['sometimes', 'array'],
            'includes_by_stock_adjustment_out_location_ids' => ['sometimes', 'array'],
            'includes_by_stock_transfer_in_location_ids' => ['sometimes', 'array'],
            'includes_by_stock_transfer_out_location_ids' => ['sometimes', 'array'],
            'includes_by_delivery_order_in_location_ids' => ['sometimes', 'array'],
            'includes_by_delivery_order_out_location_ids' => ['sometimes', 'array'],
            'attributes' => ['sometimes', 'array'],
        ];
    }
}

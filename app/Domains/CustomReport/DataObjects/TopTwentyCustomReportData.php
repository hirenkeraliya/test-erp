<?php

declare(strict_types=1);

namespace App\Domains\CustomReport\DataObjects;

use Spatie\LaravelData\Data;

class TopTwentyCustomReportData extends Data
{
    public function __construct(
        public bool $combine_stock_by_selected_location,
        public ?array $location_ids = null,
        public ?array $date_range = [],
        public ?array $counter_ids = null,
        public ?array $cashier_ids = null,
        public ?string $check_article_number = null,
        public ?int $report_type = null,
        public ?int $report_view_type = null,
        public ?int $filter_by = null,
        public ?int $attribute_type = null,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(): array
    {
        return [
            'location_ids' => ['nullable', 'array'],
            'counter_ids' => ['nullable', 'array'],
            'cashier_ids' => ['nullable', 'array'],
            'report_view_type' => ['required', 'integer'],
            'check_article_number' => ['nullable', 'string'],
            'combine_stock_by_selected_location' => ['required', 'nullable', 'boolean'],
            'date_range' => ['required', 'array'],
            'report_type' => ['required', 'integer'],
            'filter_by' => ['nullable', 'integer'],
            'attribute_type' => ['nullable', 'integer'],
        ];
    }
}

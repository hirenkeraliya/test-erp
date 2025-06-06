<?php

declare(strict_types=1);

namespace App\Domains\CustomReport\DataObjects;

use Spatie\LaravelData\Data;

class GoodReceivedNotesCustomReportData extends Data
{
    public function __construct(
        public ?array $location_ids = [],
        public ?array $vendor_ids = null,
        public ?int $product_id = null,
        public ?int $product_collection_id = null,
        public ?array $date_range = [],
        public ?string $article_number = null,
        public ?array $brand_ids = null,
        public ?array $department_ids = null,
        public ?int $report_type = null,
        public ?int $filter_by = null,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(): array
    {
        return [
            'location_ids' => ['nullable', 'array'],
            'date_range' => ['required', 'array'],
            'filter_by' => ['sometimes', 'nullable'],
            'report_type' => ['required', 'integer'],
            'brand_ids' => ['sometimes', 'nullable', 'array'],
            'department_ids' => ['sometimes', 'nullable', 'array'],
            'product_id' => ['sometimes', 'nullable', 'integer'],
            'product_collection_id' => ['sometimes', 'nullable', 'integer'],
            'vendor_ids' => ['sometimes', 'nullable', 'array'],
            'article_number' => ['sometimes', 'nullable', 'string'],
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Domains\CustomReport\DataObjects;

use Spatie\LaravelData\Data;

class OrderCustomReportData extends Data
{
    public function __construct(
        public int $report_type,
        public ?int $location_id = null,
        public ?int $store_manager_id = null,
        public ?int $product_id = null,
        public ?int $product_collection_id = null,
        public ?array $date_range = [],
        public ?string $article_number = null,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(): array
    {
        return [
            'location_id' => ['nullable', 'integer'],
            'store_manager_id' => ['nullable', 'integer'],
            'date_range' => ['required', 'array'],
            'report_type' => ['required', 'integer'],
            'product_id' => ['nullable', 'integer'],
            'product_collection_id' => ['nullable', 'integer'],
            'article_number' => ['nullable', 'string'],
        ];
    }
}

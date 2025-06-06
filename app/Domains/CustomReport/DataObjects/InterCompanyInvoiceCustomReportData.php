<?php

declare(strict_types=1);

namespace App\Domains\CustomReport\DataObjects;

use Spatie\LaravelData\Data;

class InterCompanyInvoiceCustomReportData extends Data
{
    public function __construct(
        public array $date_range,
        public ?int $location_id = null,
        public ?int $product_id = null,
        public ?int $product_collection_id = null,
        public ?string $article_number = null,
        public ?int $external_location_id = null,
        public ?int $external_company_id = null,
        public ?int $filter_by = null,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(): array
    {
        return [
            'location_id' => ['nullable', 'integer'],
            'date_range' => ['required', 'array'],
            'product_id' => ['nullable', 'integer'],
            'product_collection_id' => ['nullable', 'integer'],
            'article_number' => ['nullable', 'string'],
            'external_location_id' => ['nullable', 'integer'],
            'external_company_id' => ['nullable', 'integer'],
            'filter_by' => ['nullable', 'integer'],
        ];
    }
}

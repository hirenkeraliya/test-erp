<?php

declare(strict_types=1);

namespace App\Domains\CustomReport\DataObjects;

use App\Domains\DigitalInvoice\Enums\EInvoiceFilter;
use Spatie\LaravelData\Data;

class GeneralSalesCustomReportData extends Data
{
    public function __construct(
        public string $exclude_products_with_no_price,
        public ?array $date_range = [],
        public ?string $date = null,
        public ?array $location_ids = [],
        public ?array $department_ids = null,
        public ?array $brand_ids = null,
        public ?array $promoter_ids = null,
        public ?array $counter_ids = null,
        public ?int $report_type = null,
        public ?int $filter_by = null,
        public ?int $e_invoice_submitted = null,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(): array
    {
        return [
            'location_ids' => ['nullable', 'array'],
            'department_ids' => ['nullable', 'array'],
            'date_range' => ['nullable', 'array'],
            'date' => ['nullable', 'string'],
            'filter_by' => ['nullable', 'integer'],
            'e_invoice_submitted' => ['nullable', 'integer', 'in:'. EInvoiceFilter::getValues()],
            'report_type' => ['required', 'integer'],
            'brand_ids' => ['nullable', 'array'],
            'promoter_ids' => ['nullable', 'array'],
            'counter_ids' => ['nullable', 'array'],
            'exclude_products_with_no_price' => ['required', 'string'],
        ];
    }
}

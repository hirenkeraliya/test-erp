<?php

declare(strict_types=1);

namespace App\Domains\CustomReport\DataObjects;

use App\Domains\DigitalInvoice\Enums\EInvoiceFilter;
use Spatie\LaravelData\Data;

class SalesCollectionCustomReportData extends Data
{
    public function __construct(
        public ?int $e_invoice_submitted,
        public ?array $date_range = [],
        public ?string $date = null,
        public ?array $location_ids = null,
        public ?array $counter_ids = null,
        public ?array $cashier_ids = null,
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
            'counter_ids' => ['nullable', 'array'],
            'cashier_ids' => ['nullable', 'array'],
            'date_range' => ['sometimes', 'array'],
            'date' => ['sometimes', 'string'],
            'report_type' => ['required', 'integer'],
            'filter_by' => ['nullable', 'integer'],
            'e_invoice_submitted' => ['nullable', 'integer', 'in:'. EInvoiceFilter::getValues()],
        ];
    }
}

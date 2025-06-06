<?php

declare(strict_types=1);

namespace App\Domains\PurchaseOrder\DataObjects;

use App\Domains\PurchaseOrder\Enums\OrderTypes;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;

class PurchaseOrderData extends Data
{
    public function __construct(
        public int $external_company_id,
        public int $external_location_id,
        public int $location_id,
        public ?string $reference_number,
        public ?string $remarks,
        public ?string $attention,
        public ?string $require_date,
        public int $order_type,
        public array $transfer_items,
    ) {
    }

    public static function rules(): array
    {
        return [
            'external_company_id' => ['required', 'integer', Rule::exists('external_companies', 'id')],
            'external_location_id' => ['required', 'integer', Rule::exists('external_locations', 'id')],
            'location_id' => ['required', 'integer'],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'remarks' => ['nullable', 'string'],
            'attention' => ['nullable', 'string', 'max:255'],
            'require_date' => ['nullable', 'date', 'max:255', 'date_format:Y-m-d'],
            'order_type' => ['required', 'integer', 'in:' . OrderTypes::getValues()],
            'transfer_items' => ['required', 'nullable', 'array'],
            'transfer_items.*.product_id' => ['required', 'integer', 'distinct:strict'],
            'transfer_items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'transfer_items.*.unit_of_measure_derivative_id' => ['nullable', 'integer'],
            'transfer_items.*.remarks' => ['nullable', 'string'],
        ];
    }
}

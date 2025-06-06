<?php

declare(strict_types=1);

namespace App\Domains\PurchaseOrder\DataObjects;

use App\Domains\PurchaseOrder\Enums\OrderTypes;
use App\Domains\PurchaseOrder\Enums\Statuses;
use Illuminate\Validation\Rules\Unique;
use Spatie\LaravelData\Data;

class PurchaseOrderApiData extends Data
{
    public function __construct(
        public int $external_purchase_order_id,
        public int $external_company_id,
        public int $external_location_id,
        public int $location_id,
        public int $company_id,
        public int $order_type,
        public int $status,
        public ?string $reference_number,
        public string $external_order_number,
        public ?string $remarks,
        public ?string $attention,
        public ?string $require_date,
        public ?array $items,
        public ?int $created_by_company_id = null,
        public ?int $parent_purchase_order_id = null,
        public ?string $external_username = null,
    ) {
    }

    /**
     * @return array<string, array<string|Unique>>
     */
    public static function rules(): array
    {
        return [
            'external_purchase_order_id' => ['required', 'integer'],
            'external_company_id' => ['required', 'integer'],
            'created_by_company_id' => ['nullable', 'integer'],
            'parent_purchase_order_id' => ['nullable', 'integer'],
            'external_location_id' => ['required', 'integer'],
            'location_id' => ['required', 'integer'],
            'company_id' => ['required', 'integer'],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'external_order_number' => ['required', 'string', 'max:255'],
            'remarks' => ['nullable', 'string'],
            'attention' => ['nullable', 'string', 'max:255'],
            'require_date' => ['nullable', 'date', 'max:255', 'date_format:Y-m-d'],
            'order_type' => ['required', 'integer', 'in:' . OrderTypes::getValues()],
            'status' => ['required', 'integer', 'in:' . Statuses::getValues()],
            'items' => ['required', 'array'],
            'items.*.external_purchase_order_item_id' => ['required', 'integer'],
            'items.*.price_per_unit' => ['nullable', 'numeric'],
            'items.*.upc' => ['required', 'string'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.rejected_quantity' => ['nullable', 'numeric'],
            'items.*.transferred_quantity' => ['nullable', 'numeric'],
            'items.*.unit_of_measure_derivative' => ['nullable', 'string'],
            'items.*.remarks' => ['nullable', 'string'],
        ];
    }
}

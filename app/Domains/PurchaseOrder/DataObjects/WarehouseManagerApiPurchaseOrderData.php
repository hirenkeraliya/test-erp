<?php

declare(strict_types=1);

namespace App\Domains\PurchaseOrder\DataObjects;

use App\Domains\PurchaseOrder\Enums\OrderTypes;
use App\Domains\PurchaseOrder\Enums\Statuses;
use Illuminate\Validation\Rules\Enum;
use Spatie\LaravelData\Data;

class WarehouseManagerApiPurchaseOrderData extends Data
{
    public function __construct(
        public int $id,
        public int $per_page,
        public int $page,
        public string $start_date,
        public string $end_date,
        public ?string $search_text = null,
        public ?string $sort_by = null,
        public ?string $sort_direction = null,
        public ?int $order_type = null,
        public ?int $status = null,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(): array
    {
        return [
            'id' => ['required', 'integer'],
            'per_page' => ['required', 'integer'],
            'page' => ['required', 'integer'],
            'start_date' => ['required', 'date', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'search_text' => ['sometimes', 'nullable', 'string'],
            'sort_by' => ['sometimes', 'string', 'in:created_at,reference_number'],
            'sort_direction' => ['required_with:sort_by', 'string', 'in:asc,desc'],
            'order_type' => ['sometimes', 'integer', new Enum(OrderTypes::class)],
            'status' => ['sometimes', 'integer', new Enum(Statuses::class)],
        ];
    }
}

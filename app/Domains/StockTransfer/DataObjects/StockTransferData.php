<?php

declare(strict_types=1);

namespace App\Domains\StockTransfer\DataObjects;

use App\Domains\StockTransfer\Enums\StockTransferTypes;
use App\Domains\StockTransferReason\StockTransferReasonQueries;
use App\Models\Admin;
use App\Models\StoreManager;
use App\Models\WarehouseManager;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;

class StockTransferData extends Data
{
    public function __construct(
        public int $source_location_id,
        public int $destination_location_id,
        public ?string $transfer_date,
        public ?string $require_date,
        public ?string $attention,
        public ?string $reference_number,
        public ?string $remarks,
        public ?int $stock_transfer_reason_id,
        public array $transfer_items,
        public ?string $transfer_type = null,
        public ?int $aggregate_average_days = 0,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(Request $request): array
    {
        /** @var Admin|StoreManager|WarehouseManager $user */
        $user = $request->user();

        $stockTransferReasonQueries = resolve(StockTransferReasonQueries::class);

        $rules = [
            'source_location_id' => ['required', 'integer'],
            'destination_location_id' => ['required', 'integer'],
            'transfer_date' => ['nullable', 'date', 'date_format:Y-m-d'],
            'require_date' => ['nullable', 'date', 'date_format:Y-m-d'],
            'attention' => ['nullable', 'string', 'max:255'],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'remarks' => ['nullable', 'string'],
            'transfer_items' => ['required', 'array'],
            'transfer_items.*.product_id' => ['required', 'integer', 'distinct:strict'],
            'transfer_items.*.transfer_stock' => ['required', 'numeric', 'min:0.01'],
            'transfer_items.*.package_type_id' => ['nullable', 'integer'],
            'transfer_items.*.unit_of_measure_derivative_id' => ['nullable', 'integer'],
            'transfer_items.*.package_quantity' => ['nullable', 'numeric', 'min:0'],
            'transfer_items.*.package_total_quantity' => ['nullable', 'numeric', 'min:0'],
            'transfer_items.*.remarks' => ['nullable', 'string'],
            'transfer_items.*.batch_details' => ['nullable', 'array'],
            'transfer_items.*.batch_details.*.batch_number' => ['nullable', 'string'],
            'transfer_items.*.batch_details.*.quantity' => ['nullable', 'numeric', 'min:0.01'],
            'transfer_type' => ['required', 'string', 'in:' . StockTransferTypes::getNames()],
            'aggregate_average_days' => ['nullable', 'integer'],
        ];

        if (StoreManager::class === $user::class) {
            $rules['stock_transfer_reason_id'] = [
                'nullable',
                'integer',
                Rule::exists('stock_transfer_reasons', 'id')
                    ->where($stockTransferReasonQueries->filterByCompany(
                        session('store_manager_selected_location_company_id')
                    )),
            ];

            return $rules;
        }

        if (WarehouseManager::class === $user::class) {
            $rules['stock_transfer_reason_id'] = [
                'nullable',
                'integer',
                Rule::exists('stock_transfer_reasons', 'id')
                    ->where($stockTransferReasonQueries->filterByCompany(
                        session('warehouse_manager_selected_location_company_id')
                    )),
            ];

            return $rules;
        }

        $rules['stock_transfer_reason_id'] = [
            'nullable',
            'integer',
            Rule::exists('stock_transfer_reasons', 'id')
                ->where($stockTransferReasonQueries->filterByCompany(session('admin_company_id'))),
        ];

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'transfer_items.*.product_id.distinct' => 'Please remove duplicate products.',
            'transfer_items.*.transfer_stock.min' => 'Transfer stock must be at least 0.01',
            'transfer_items.*.package_quantity.min' => 'Package quantity must be at least 0',
            'transfer_items.*.package_total_quantity.min' => 'Package total quantity must be at least 0',
            'transfer_items.*.batch_details.*.quantity.min' => 'Transfer stock must be at least 0.01',
        ];
    }
}

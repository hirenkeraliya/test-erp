<?php

declare(strict_types=1);

namespace App\Domains\CashierGroup\DataObjects;

use App\Domains\CashierGroup\CashierGroupQueries;
use App\Domains\CashierGroup\Enums\PermissionTypes;
use App\Domains\Common\Enums\PriceOverrideTypes;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use Spatie\LaravelData\Data;

class CashierGroupData extends Data
{
    public function __construct(
        public string $name,
        public int $price_override_type,
        public ?float $price_override_limit_percentage_for_item,
        public array $permission_ids,
        public ?float $price_override_limit_percentage_for_cart,
    ) {
    }

    /**
     * @return array<string, array<string|Unique>>
     */
    public static function rules(Request $request): array
    {
        $cashierGroupId = null;
        $cashierGroupQueries = new CashierGroupQueries();
        $companyId = session('admin_company_id');

        if ('admin.cashier_groups.update' === $request->route()?->getName()) {
            $cashierGroupId = $request->route()->parameter('cashierGroupId');
        }

        if ('store_manager.cashier_groups.store' === $request->route()?->getName()) {
            $companyId = session('store_manager_selected_location_company_id');
        }

        if ('store_manager.cashier_groups.update' === $request->route()?->getName()) {
            $cashierGroupId = $request->route()->parameter('cashierGroupId');
            $companyId = session('store_manager_selected_location_company_id');
        }

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('cashier_groups', 'name')->ignore($cashierGroupId)
                    ->where($cashierGroupQueries->filterByCompany($companyId)),
            ],
            'permission_ids' => ['required', 'array', 'in:' . PermissionTypes::getValues()],
            'price_override_type' => ['required', 'integer', 'in:' . PriceOverrideTypes::getValues()],
            'price_override_limit_percentage_for_item' => [
                'required_if:price_override_type,' . PriceOverrideTypes::PERCENTAGE->value,
                'nullable',
                'numeric',
                'between:0,100.00',
            ],
            'price_override_limit_percentage_for_cart' => ['sometimes', 'numeric', 'between:0,100.00'],
        ];
    }
}

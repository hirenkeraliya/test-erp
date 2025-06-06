<?php

declare(strict_types=1);

namespace App\Domains\Cashier\DataObjects;

use App\Domains\CashierGroup\CashierGroupQueries;
use App\Domains\Employee\EmployeeQueries;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Attributes\Validation\Unique;
use Spatie\LaravelData\Data;

class CashierData extends Data
{
    public function __construct(
        public string $username,
        public ?string $pin,
        public int $employee_id,
        public int $cashier_group_id,
        public array $location_ids,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(Request $request): array
    {
        $cashierId = null;
        $employeeQueries = new EmployeeQueries();
        $cashierGroupQueries = new CashierGroupQueries();
        $companyId = session('admin_company_id');

        if ('admin.cashiers.update' === $request->route()?->getName()) {
            /** @var string $cashierId */
            $cashierId = $request->route()->parameter('cashierId');
        }

        if ('store_manager.cashiers.store' === $request->route()?->getName()) {
            $companyId = session('store_manager_selected_location_company_id');
        }

        if ('store_manager.cashiers.update' === $request->route()?->getName()) {
            /** @var string $cashierId */
            $cashierId = $request->route()->parameter('cashierId');
            $companyId = session('store_manager_selected_location_company_id');
        }

        $rules = [
            'username' => ['required', 'string', 'max:255', new Unique('cashiers', 'username', ignore: $cashierId)],
            'employee_id' => [
                'required',
                'integer',
                new Unique('cashiers', 'employee_id', ignore: $cashierId),
                Rule::exists('employees', 'id')
                    ->where($employeeQueries->filterByCompany($companyId)),
            ],
            'cashier_group_id' => [
                'required',
                'integer',
                Rule::exists('cashier_groups', 'id')
                    ->where($cashierGroupQueries->filterByCompany($companyId)),
            ],
            'location_ids' => ['required', 'array'],
            'location_ids.*' => ['required', 'integer'],
        ];

        if ('admin.cashiers.store' === $request->route()?->getName()
            || 'store_manager.cashiers.store' === $request->route()?->getName()
        ) {
            $rules['pin'] = ['required', 'confirmed', 'string', 'min:4', 'max:4'];
        }

        return $rules;
    }
}

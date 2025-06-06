<?php

declare(strict_types=1);

namespace App\Domains\EmployeeGroup\DataObjects;

use App\Domains\EmployeeGroup\EmployeeGroupQueries;
use App\Domains\EmployeeGroup\Enums\LimitResetTypes;
use App\Domains\EmployeeGroup\Enums\PurchaseLimitTypes;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use Spatie\LaravelData\Data;

class SuperAdminEmployeeGroupData extends Data
{
    public function __construct(
        public int $company_id,
        public string $name,
        public ?string $code,
        public int $item_purchase_limit,
        public int $purchase_limit_type_id,
        public int $limit_reset_type_id,
        public int $limit_reset,
    ) {
    }

    /**
     * @return array<string, array<string|Unique>>
     */
    public static function rules(Request $request): array
    {
        $employeeGroupId = null;
        $employeeGroupQueries = new EmployeeGroupQueries();

        if ('super_admin.employee_groups.update' === $request->route()?->getName()) {
            $employeeGroupId = $request->route()->parameter('employeeGroupId');
        }

        $rules = [
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('employee_groups', 'name')->ignore($employeeGroupId)
                    ->where($employeeGroupQueries->filterByCompany((int) $request->input('company_id'))),
            ],
            'code' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('employee_groups', 'code')->ignore($employeeGroupId)
                    ->where($employeeGroupQueries->filterByCompany((int) $request->input('company_id'))),
            ],
            'item_purchase_limit' => ['required', 'integer'],
            'purchase_limit_type_id' => ['required', 'integer', 'in:' . PurchaseLimitTypes::getValues()],
            'limit_reset_type_id' => ['required', 'integer', 'in:' . LimitResetTypes::getValues()],
            'limit_reset' => ['required', 'integer', 'min:1'],
        ];

        if ($request->input('limit_reset_type_id') === LimitResetTypes::BY_MONTH->value) {
            $rules['limit_reset'] = ['required', 'integer', 'min:1', 'between:1,31'];
        }

        if ($request->input('limit_reset_type_id') === LimitResetTypes::BY_WEEK->value) {
            $rules['limit_reset'] = ['required', 'integer', 'min:1', 'between:1,7'];
        }

        return $rules;
    }
}

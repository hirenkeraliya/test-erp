<?php

declare(strict_types=1);

namespace App\Domains\Department\DataObjects;

use App\Domains\Common\Enums\DiscountTypes;
use App\Domains\Department\DepartmentQueries;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;

class DepartmentData extends Data
{
    public function __construct(
        public string $name,
        public ?string $code,
        public ?float $commission_percentage,
        public ?float $flat_commission,
        public int $discount_type
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(Request $request): array
    {
        $departmentId = null;
        $departmentQueries = new DepartmentQueries();

        if ('admin.departments.update' === $request->route()?->getName()) {
            $departmentId = $request->route()->parameter('departmentId');
        }

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('departments', 'name')->ignore($departmentId)
                    ->where($departmentQueries->filterByCompany(session('admin_company_id'))),
            ],
            'code' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('departments', 'code')->ignore($departmentId)
                    ->where($departmentQueries->filterByCompany(session('admin_company_id'))),
            ],
            'discount_type' => ['required', 'integer', 'in:' . DiscountTypes::getValues()],
            'commission_percentage' => ['nullable', 'numeric', 'min:0.0', 'max:100.00'],
            'flat_commission' => ['nullable', 'numeric', 'min:0.0', 'max:100.00'],
        ];
    }
}

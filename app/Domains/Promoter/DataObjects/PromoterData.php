<?php

declare(strict_types=1);

namespace App\Domains\Promoter\DataObjects;

use App\Domains\Employee\EmployeeQueries;
use App\Domains\PromoterGroup\PromoterGroupQueries;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Spatie\LaravelData\Attributes\Validation\Unique;
use Spatie\LaravelData\Data;

class PromoterData extends Data
{
    public function __construct(
        public int $employee_id,
        public string $username,
        public ?string $password,
        public ?float $monthly_sales_target,
        public ?string $code,
        public array $location_ids,
        public ?float $default_commission_amount_percentage,
        public ?float $monthly_target_commission_percentage,
        public ?int $group_id = null,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(Request $request): array
    {
        $employeeQueries = resolve(EmployeeQueries::class);
        $uniqueRule = Rule::unique('promoters');
        $promoterId = null;
        $promoterGroupQueries = new PromoterGroupQueries();
        $companyId = session('admin_company_id');

        if ('admin.promoters.update' === $request->route()?->getName()) {
            $uniqueRule->ignore(Route::current()?->originalParameter('promoterId'));

            /** @var string $promoterId */
            $promoterId = $request->route()->parameter('promoterId');
        }

        if ('store_manager.promoters.update' === $request->route()?->getName()) {
            $uniqueRule->ignore(Route::current()?->originalParameter('promoterId'));

            /** @var string $promoterId */
            $promoterId = $request->route()->parameter('promoterId');
            $companyId = session('store_manager_selected_location_company_id');
        }

        if ('store_manager.promoters.store' === $request->route()?->getName()) {
            $companyId = session('store_manager_selected_location_company_id');
        }

        $rules = [
            'employee_id' => [
                'required',
                'integer',
                $uniqueRule,
                Rule::exists('employees', 'id')->where($employeeQueries->filterByCompany($companyId)),
            ],
            'username' => [
                'required',
                'string',
                'min:4',
                'max:255',
                new Unique('promoters', 'username', ignore: $promoterId),
            ],
            'code' => ['nullable', 'string'],
            'location_ids' => ['required', 'array'],
            'monthly_sales_target' => ['nullable', 'numeric'],
            'default_commission_amount_percentage' => ['nullable', 'numeric', 'min:0.00', 'max:100.00'],
            'monthly_target_commission_percentage' => ['nullable', 'numeric', 'min:0.00', 'max:100.00'],
            'location_ids.*' => ['required', 'integer'],
            'group_id' => [
                'nullable',
                'integer',
                Rule::exists('promoter_groups', 'id')
                    ->where($promoterGroupQueries->filterByCompany($companyId)),
            ],
        ];

        if ('admin.promoters.store' === $request->route()?->getName()
            || 'store_manager.promoters.store' === $request->route()?->getName()
        ) {
            $rules['password'] = ['required', 'confirmed', 'string', 'max:20', Password::defaults()];
        }

        return $rules;
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'password' => 'A password must be at least 8 characters long and include a combination of uppercase and lowercase letters, numbers, and symbols.',
            'password.confirmed' => 'The confirmed password does not match the original password. Please re-enter your password and confirm it.',
        ];
    }
}

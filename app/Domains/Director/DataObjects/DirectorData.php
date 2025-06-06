<?php

declare(strict_types=1);

namespace App\Domains\Director\DataObjects;

use App\Domains\Common\Enums\PriceOverrideTypes;
use App\Domains\Employee\EmployeeQueries;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;

class DirectorData extends Data
{
    public function __construct(
        public int $employee_id,
        public array $location_ids,
        public ?string $passcode,
        public int $price_override_type,
        public ?float $price_override_limit_percentage_for_item,
        public ?float $price_override_limit_percentage_for_cart,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(Request $request): array
    {
        $employeeQueries = resolve(EmployeeQueries::class);
        $uniqueRule = Rule::unique('directors');
        $companyId = session('admin_company_id');

        if ('admin.directors.update' === $request->route()?->getName()) {
            $uniqueRule->ignore(Route::current()?->originalParameter('directorId'));
        }

        if ('store_manager.directors.store' === $request->route()?->getName()) {
            $companyId = session('store_manager_selected_location_company_id');
        }

        if ('store_manager.directors.update' === $request->route()?->getName()) {
            $uniqueRule->ignore(Route::current()?->originalParameter('directorId'));
            $companyId = session('store_manager_selected_location_company_id');
        }

        $rules = [
            'employee_id' => [
                'required',
                'integer',
                $uniqueRule,
                Rule::exists('employees', 'id')->where($employeeQueries->filterByCompany($companyId)),
            ],
            'location_ids' => ['required', 'array'],
            'location_ids.*' => ['required', 'integer'],
            'price_override_type' => ['required', 'integer', 'in:' . PriceOverrideTypes::getValues()],
            'price_override_limit_percentage_for_item' => [
                'required_if:price_override_type,' . PriceOverrideTypes::PERCENTAGE->value,
                'nullable',
                'numeric',
                'between:0,100.00',
            ],
            'price_override_limit_percentage_for_cart' => ['sometimes', 'numeric', 'between:0,100.00'],
        ];

        if ('admin.directors.store' === $request->route()?->getName() || 'store_manager.directors.store' === $request->route()?->getName()) {
            $rules['passcode'] = ['required', 'string', 'max:255'];
        }

        return $rules;
    }
}

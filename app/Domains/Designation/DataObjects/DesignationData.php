<?php

declare(strict_types=1);

namespace App\Domains\Designation\DataObjects;

use App\Domains\Designation\DesignationQueries;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use Spatie\LaravelData\Data;

class DesignationData extends Data
{
    public function __construct(
        public string $name,
        public ?string $code,
    ) {
    }

    /**
     * @return array<string, array<string|Unique>>
     */
    public static function rules(Request $request): array
    {
        $designationId = null;
        $designationQueries = new DesignationQueries();
        $companyId = session('admin_company_id');

        if ('admin.designations.update' === $request->route()?->getName()) {
            $designationId = $request->route()->parameter('designationId');
        }

        if ('store_manager.designations.store' === $request->route()?->getName()) {
            $companyId = session('store_manager_selected_location_company_id');
        }

        if ('store_manager.designations.update' === $request->route()?->getName()) {
            $designationId = $request->route()->parameter('designationId');
            $companyId = session('store_manager_selected_location_company_id');
        }

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('designations', 'name')->ignore($designationId)
                    ->where($designationQueries->filterByCompany($companyId)),
            ],
            'code' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('designations', 'code')->ignore($designationId)
                    ->where($designationQueries->filterByCompany($companyId)),
            ],
        ];
    }
}

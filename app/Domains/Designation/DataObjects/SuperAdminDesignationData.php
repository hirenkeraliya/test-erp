<?php

declare(strict_types=1);

namespace App\Domains\Designation\DataObjects;

use App\Domains\Designation\DesignationQueries;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use Spatie\LaravelData\Data;

class SuperAdminDesignationData extends Data
{
    public function __construct(
        public int $company_id,
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

        if (
            'super_admin.designations.update' === $request->route()?->getName()
        ) {
            $designationId = $request->route()->parameter('designationId');
        }

        return [
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('designations', 'name')->ignore($designationId)
                    ->where($designationQueries->filterByCompany((int) $request->input('company_id'))),
            ],
            'code' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('designations', 'code')->ignore($designationId)
                    ->where($designationQueries->filterByCompany((int) $request->input('company_id'))),
            ],
        ];
    }
}

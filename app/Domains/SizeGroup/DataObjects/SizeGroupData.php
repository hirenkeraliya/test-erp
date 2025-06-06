<?php

declare(strict_types=1);

namespace App\Domains\SizeGroup\DataObjects;

use App\Domains\SizeGroup\SizeGroupQueries;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use Spatie\LaravelData\Data;

class SizeGroupData extends Data
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
        $sizeGroupId = null;
        $sizeGroupQueries = new SizeGroupQueries();

        if ('admin.size_groups.update' === $request->route()?->getName()) {
            $sizeGroupId = $request->route()->parameter('sizeGroupId');
        }

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('size_groups', 'name')->ignore($sizeGroupId)
                    ->where($sizeGroupQueries->filterByCompany(session('admin_company_id'))),
            ],
            'code' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('size_groups', 'code')->ignore($sizeGroupId)
                    ->where($sizeGroupQueries->filterByCompany(session('admin_company_id'))),
            ],
        ];
    }
}

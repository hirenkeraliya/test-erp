<?php

declare(strict_types=1);

namespace App\Domains\Region\DataObjects;

use App\Domains\Region\RegionQueries;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use Spatie\LaravelData\Data;

class RegionData extends Data
{
    public function __construct(
        public string $name,
        public ?string $code,
        public ?string $manager_name,
        public ?string $manager_email,
    ) {
    }

    /**
     * @return array<string, array<string|Unique>>
     */
    public static function rules(Request $request): array
    {
        $regionId = null;
        $regionQueries = new RegionQueries();

        if ('admin.regions.update' === $request->route()?->getName()) {
            $regionId = $request->route()->parameter('regionId');
        }

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('regions', 'name')->ignore($regionId)
                    ->where($regionQueries->filterByCompany(session('admin_company_id'))),
            ],
            'code' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('regions', 'code')->ignore($regionId)
                    ->where($regionQueries->filterByCompany(session('admin_company_id'))),
            ],
            'manager_name' => ['nullable', 'string'],
            'manager_email' => ['nullable', 'email:rfc,dns'],
        ];
    }
}

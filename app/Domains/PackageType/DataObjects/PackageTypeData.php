<?php

declare(strict_types=1);

namespace App\Domains\PackageType\DataObjects;

use App\Domains\PackageType\PackageTypeQueries;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use Spatie\LaravelData\Data;

class PackageTypeData extends Data
{
    public function __construct(
        public string $name,
    ) {
    }

    /**
     * @return array<string, array<int, string|Unique>>
     */
    public static function rules(Request $request): array
    {
        $packageTypeId = null;
        $packageTypeQueries = new PackageTypeQueries();

        if ('admin.package_types.update' === $request->route()?->getName()) {
            $packageTypeId = $request->route()->parameter('packageTypeId');
        }

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('package_types', 'name')->ignore($packageTypeId)
                    ->where($packageTypeQueries->filterByCompany(session('admin_company_id'))),
            ],
        ];
    }
}

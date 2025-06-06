<?php

declare(strict_types=1);

namespace App\Domains\Size\DataObjects;

use App\Domains\Size\SizeQueries;
use App\Domains\SizeGroup\SizeGroupQueries;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;

class SizeData extends Data
{
    public function __construct(
        public string $name,
        public ?string $code,
        public int $sort_order,
        public ?int $group_id = null,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(Request $request): array
    {
        $sizeId = null;
        $sizeQueries = new SizeQueries();
        $sizeGroupQueries = new SizeGroupQueries();

        if ('admin.sizes.update' === $request->route()?->getName()) {
            $sizeId = $request->route()->parameter('sizeId');
        }

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('sizes', 'name')->ignore($sizeId)
                    ->where($sizeQueries->filterByCompany(session('admin_company_id'))),
            ],
            'code' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('sizes', 'code')->ignore($sizeId)
                    ->where($sizeQueries->filterByCompany(session('admin_company_id'))),
            ],
            'sort_order' => ['required', 'integer'],
            'group_id' => [
                'nullable',
                'integer',
                Rule::exists('size_groups', 'id')
                    ->where($sizeGroupQueries->filterByCompany(session('admin_company_id'))),
            ],
        ];
    }
}

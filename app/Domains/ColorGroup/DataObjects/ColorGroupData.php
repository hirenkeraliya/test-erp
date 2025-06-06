<?php

declare(strict_types=1);

namespace App\Domains\ColorGroup\DataObjects;

use App\Domains\ColorGroup\ColorGroupQueries;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use Spatie\LaravelData\Data;

class ColorGroupData extends Data
{
    public function __construct(
        public string $name,
        public ?string $code,
        public ?string $color_code,
    ) {
    }

    /**
     * @return array<string, array<string|Unique>>
     */
    public static function rules(Request $request): array
    {
        $colorGroupId = null;
        $colorGroupQueries = new ColorGroupQueries();

        if ('admin.color_groups.update' === $request->route()?->getName()) {
            $colorGroupId = $request->route()->parameter('colorGroupId');
        }

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('color_groups', 'name')->ignore($colorGroupId)
                    ->where($colorGroupQueries->filterByCompany(session('admin_company_id'))),
            ],
            'code' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('color_groups', 'code')->ignore($colorGroupId)
                    ->where($colorGroupQueries->filterByCompany(session('admin_company_id'))),
            ],
            'color_code' => ['nullable', 'string', 'max:255'],
        ];
    }
}

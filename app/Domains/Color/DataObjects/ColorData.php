<?php

declare(strict_types=1);

namespace App\Domains\Color\DataObjects;

use App\Domains\Color\ColorQueries;
use App\Domains\ColorGroup\ColorGroupQueries;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;

class ColorData extends Data
{
    public function __construct(
        public string $name,
        public ?string $code,
        public ?string $color_code,
        public ?int $group_id = null,
    ) {
    }

    public static function rules(Request $request): array
    {
        $colorId = null;
        $colorQueries = new ColorQueries();
        $colorGroupQueries = new ColorGroupQueries();

        if ('admin.colors.update' === $request->route()?->getName()) {
            $colorId = $request->route()->parameter('colorId');
        }

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('colors', 'name')->ignore($colorId)
                    ->where($colorQueries->filterByCompany(session('admin_company_id'))),
            ],
            'code' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('colors', 'code')->ignore($colorId)
                    ->where($colorQueries->filterByCompany(session('admin_company_id'))),
            ],
            'group_id' => [
                'nullable',
                'integer',
                Rule::exists('color_groups', 'id')
                    ->where($colorGroupQueries->filterByCompany(session('admin_company_id'))),
            ],
            'color_code' => ['nullable', 'string', 'max:255'],
        ];
    }
}

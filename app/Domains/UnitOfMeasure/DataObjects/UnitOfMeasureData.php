<?php

declare(strict_types=1);

namespace App\Domains\UnitOfMeasure\DataObjects;

use App\Domains\UnitOfMeasure\UnitOfMeasureQueries;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use Spatie\LaravelData\Data;

class UnitOfMeasureData extends Data
{
    public function __construct(
        public string $name,
        public bool $allow_decimal_qty,
    ) {
    }

    /**
     * @return array<string, array<int, string|Unique>>
     */
    public static function rules(Request $request): array
    {
        $unitOfMeasureId = null;
        $unitOfMeasureQueries = new UnitOfMeasureQueries();

        if ('admin.unit_of_measures.update' === $request->route()?->getName()) {
            $unitOfMeasureId = $request->route()->parameter('unitOfMeasureId');
        }

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('unit_of_measures', 'name')->ignore($unitOfMeasureId)
                    ->where($unitOfMeasureQueries->filterByCompany(session('admin_company_id'))),
            ],
            'allow_decimal_qty' => ['required', 'boolean'],
        ];
    }
}

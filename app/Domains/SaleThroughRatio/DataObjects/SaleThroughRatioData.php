<?php

declare(strict_types=1);

namespace App\Domains\SaleThroughRatio\DataObjects;

use App\Domains\SaleThroughRatio\SaleThroughRatioQueries;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use Spatie\LaravelData\Data;

class SaleThroughRatioData extends Data
{
    public function __construct(
        public string $name,
        public float $percentage,
        public string $description,
    ) {
    }

    /**
     * @return array<string, array<string|Unique>>
     */
    public static function rules(Request $request): array
    {
        $saleThroughRatioId = null;
        $saleThroughRatioQueries = new SaleThroughRatioQueries();

        if ('admin.sale_through_ratios.update' === $request->route()?->getName()) {
            $saleThroughRatioId = $request->route()->parameter('saleThroughRatioId');
        }

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('sale_through_ratios', 'name')->ignore($saleThroughRatioId)
                    ->where($saleThroughRatioQueries->filterByCompany(session('admin_company_id'))),
            ],
            'percentage' => ['required', 'numeric', 'between:0,100.00'],
            'description' => ['required', 'string', 'max:255'],
        ];
    }
}

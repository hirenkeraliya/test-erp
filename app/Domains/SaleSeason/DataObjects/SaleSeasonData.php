<?php

declare(strict_types=1);

namespace App\Domains\SaleSeason\DataObjects;

use App\Domains\SaleSeason\SaleSeasonQueries;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;

class SaleSeasonData extends Data
{
    public function __construct(
        public string $name,
        public string $start_date,
        public string $end_date,
    ) {
    }

    public static function rules(Request $request): array
    {
        $saleSeasonId = null;
        $saleSeasonQueries = new SaleSeasonQueries();

        if ('admin.sale_seasons.update' === $request->route()?->getName()) {
            $saleSeasonId = $request->route()->parameter('saleSeasonId');
        }

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('sale_seasons', 'name')->ignore($saleSeasonId)
                    ->withoutTrashed()
                    ->where($saleSeasonQueries->filterByCompany(session('admin_company_id'))),
            ],
            'start_date' => ['required', 'date', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date', 'date_format:Y-m-d', 'after:start_date'],
        ];
    }
}

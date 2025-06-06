<?php

declare(strict_types=1);

namespace App\Domains\Season\DataObjects;

use App\Domains\Season\SeasonQueries;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use Spatie\LaravelData\Data;

class SeasonData extends Data
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
        $seasonId = null;
        $seasonQueries = new SeasonQueries();

        if ('admin.seasons.update' === $request->route()?->getName()) {
            $seasonId = $request->route()->parameter('seasonId');
        }

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('seasons', 'name')->ignore($seasonId)
                    ->where($seasonQueries->filterByCompany(session('admin_company_id'))),
            ],
            'code' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('seasons', 'code')->ignore($seasonId)
                    ->where($seasonQueries->filterByCompany(session('admin_company_id'))),
            ],
        ];
    }
}

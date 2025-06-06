<?php

declare(strict_types=1);

namespace App\Domains\Style\DataObjects;

use App\Domains\Style\StyleQueries;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use Spatie\LaravelData\Data;

class StyleData extends Data
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
        $styleId = null;
        $styleQueries = new StyleQueries();

        if ('admin.styles.update' === $request->route()?->getName()) {
            $styleId = $request->route()->parameter('styleId');
        }

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('styles', 'name')->ignore($styleId)
                    ->where($styleQueries->filterByCompany(session('admin_company_id'))),
            ],
            'code' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('styles', 'code')->ignore($styleId)
                    ->where($styleQueries->filterByCompany(session('admin_company_id'))),
            ],
        ];
    }
}

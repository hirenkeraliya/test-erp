<?php

declare(strict_types=1);

namespace App\Domains\Template\DataObjects;

use App\Domains\Template\TemplateQueries;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;

class TemplateData extends Data
{
    public function __construct(
        public string $name,
        public ?string $description,
        public bool $is_variant,
    ) {
    }

    public static function rules(Request $request): array
    {
        $templateId = null;
        $templateQueries = new TemplateQueries();

        if ('admin.templates.update' === $request->route()?->getName()) {
            $templateId = $request->route()->parameter('templateId');
        }

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('templates', 'name')
                    ->ignore($templateId)
                    ->where($templateQueries->filterByCompany(session('admin_company_id'))),
            ],
            'description' => ['nullable', 'string', 'max:255'],
            'is_variant' => ['required', 'boolean'],
        ];
    }
}

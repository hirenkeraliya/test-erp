<?php

declare(strict_types=1);

namespace App\Domains\DynamicMenus\DataObjects;

use App\Domains\DynamicMenus\DynamicMenuQueries;
use App\Domains\DynamicMenus\Enums\DynamicMenuTypesEnum;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use Spatie\LaravelData\Data;

class DynamicMenuData extends Data
{
    public function __construct(
        public string $title,
        public ?int $parent_id,
        public int $type,
        public ?int $module_id,
        public ?string $content,
        public bool $status
    ) {
    }

    /**
     * @return array<string, array<string|Unique>>
     */
    public static function rules(Request $request): array
    {
        $dynamicMenuId = null;
        $dynamicMenuQueries = new DynamicMenuQueries();
        $companyId = session('admin_company_id');

        if ('admin.dynamic_menus.update' === $request->route()?->getName()) {
            $dynamicMenuId = $request->route()->parameter('dynamicMenuId');
        }

        return [
            'title' => [
                'required',
                'nullable',
                'string',
                'max:255',
                Rule::unique('dynamic_menus', 'title')->ignore($dynamicMenuId)
                    ->where($dynamicMenuQueries->filterByCompany($companyId)),
            ],
            'parent_id' => ['nullable', 'integer', 'exists:dynamic_menus,id'],
            'type' => ['required', 'integer', 'in:' . DynamicMenuTypesEnum::getValues()],
            'module_id' => [
                'nullable',
                'required_if:type,!' . DynamicMenuTypesEnum::STATIC_PAGE->value,
                'integer',
            ],
            'content' => ['required_if:type,' . DynamicMenuTypesEnum::STATIC_PAGE->value, 'nullable', 'string'],
            'status' => ['required', 'boolean'],
        ];
    }

    public static function messages(): array
    {
        return [
            'content.required_if' => 'Content field is required.',
        ];
    }
}

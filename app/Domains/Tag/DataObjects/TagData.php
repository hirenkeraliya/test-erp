<?php

declare(strict_types=1);

namespace App\Domains\Tag\DataObjects;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;

class TagData extends Data
{
    public function __construct(
        public string $name,
    ) {
    }

    /**
     * @return array<string, array<(string)>>
     */
    public static function rules(Request $request): array
    {
        $companyId = session('admin_company_id');
        $tagId = null;
        if ('admin.tags.update' === $request->route()?->getName()) {
            $tagId = $request->route()->parameter('tagId');
        }

        /* @phpstan-ignore-next-line */
        return [
            'name' => [
                'required',
                'string',
                Rule::unique('tags', 'name')->ignore($tagId)->where(
                    fn ($query) => $query->where('company_id', $companyId)
                ),
            ],
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Domains\Category\DataObjects;

use App\Domains\Category\CategoryQueries;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Spatie\LaravelData\Data;

class CategoryData extends Data
{
    public function __construct(
        public string $name,
        public ?string $code,
        public ?int $parent_category_id,
        public ?string $description,
        public bool $status,
        public bool $is_available_in_ecommerce,
        public bool $is_display_on_menu,
        public ?UploadedFile $square_image,
        public ?array $portrait_images = [],
        public ?array $landscape_images = [],
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(Request $request): array
    {
        $categoryId = null;
        $categoryQueries = new CategoryQueries();

        if ('admin.categories.update' === $request->route()?->getName()) {
            $categoryId = $request->route()->parameter('categoryId');
        }

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'name')->ignore($categoryId)
                    ->where($categoryQueries->filterByCompany(session('admin_company_id'))),
            ],
            'code' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('categories', 'code')->ignore($categoryId)
                    ->where($categoryQueries->filterByCompany(session('admin_company_id'))),
            ],
            'parent_category_id' => ['sometimes', 'nullable', 'integer', 'exists:categories,id'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'boolean'],
            'is_available_in_ecommerce' => ['required', 'boolean'],
            'is_display_on_menu' => ['required', 'boolean'],
            'square_image' => [
                'nullable',
                'file',
                'mimetypes:image/jpeg,image/gif,image/png',
                File::image()->dimensions(Rule::dimensions()->maxWidth(600)->maxHeight(600)),
                'max:' . config('services.max_upload_size'),
            ],
            'portrait_images' => ['array', 'nullable'],
            'portrait_images.*' => [
                'required',
                'file',
                'mimetypes:image/jpeg,image/gif,image/png',
                File::image()->dimensions(Rule::dimensions()->maxWidth(900)->maxHeight(1200)),
                'max:' . config('services.max_upload_size'),
            ],
            'landscape_images' => ['array', 'nullable'],
            'landscape_images.*' => [
                'required',
                'file',
                'mimetypes:image/jpeg,image/gif,image/png',
                File::image()->dimensions(Rule::dimensions()->maxWidth(1920)->maxHeight(1080)),
                'max:' . config('services.max_upload_size'),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'portrait_images.*.mimetypes' => 'The portrait image must be a file of type: jpeg, gif, png.',
            'portrait_images.*.dimensions' => 'The portrait image must have a maximum width of 900 pixels and a maximum height of 1200 pixels.',
            'landscape_images.*.mimetypes' => 'The landscape image must be a file of type: jpeg, gif, png.',
            'landscape_images.*.dimensions' => 'The landscape image must have a maximum width of 1920 pixels and a maximum height of 1080 pixels.',
        ];
    }
}

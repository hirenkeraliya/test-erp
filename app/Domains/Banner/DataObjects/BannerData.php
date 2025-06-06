<?php

declare(strict_types=1);

namespace App\Domains\Banner\DataObjects;

use App\Domains\Banner\Enums\ActionTypes;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Rules\Unique;
use Spatie\LaravelData\Data;

class BannerData extends Data
{
    public function __construct(
        public string $name,
        public string $description,
        public int $action_type_id,
        public ?UploadedFile $image,
        public bool $status,
        public ?string $custom_url = null
    ) {
    }

    /**
     * @return array<string, array<string|Unique>>
     */
    public static function rules(Request $request): array
    {
        $requiredRule = 'required';

        if ('admin.banners.update' === $request->route()?->getName()) {
            $requiredRule = 'nullable';
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:255'],
            'action_type_id' => ['required', 'integer', 'in:' . ActionTypes::getValues()],
            'custom_url' => [
                'required_if:action_type_id,' . ActionTypes::CUSTOM_URL->value,
                'nullable',
                'string',
                'max:255',
            ],
            'image' => [
                $requiredRule,
                'file',
                'mimetypes:image/jpeg,image/png',
                File::image()->dimensions(Rule::dimensions()->maxWidth(1200)->maxHeight(628)),
                'max:' . config('services.max_upload_size'),
            ],
            'status' => ['required', 'boolean'],
        ];
    }
}

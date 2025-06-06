<?php

declare(strict_types=1);

namespace App\Domains\ProductCollection\DataObjects;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Rules\In;
use Illuminate\Validation\Rules\Unique;
use Spatie\LaravelData\Data;

class ProductCollectionImagesData extends Data
{
    public function __construct(
        public ?UploadedFile $square_image,
        public ?array $portrait_images = [],
        public ?array $landscape_images = [],
    ) {
    }

    /**
     * @return array<string, array<(Exists|In|Unique|string)>>
     */
    public static function rules(Request $request): array
    {
        return [
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
            'square_image.mimetypes' => 'The square image must be a file of type: jpeg, gif, png.',
            'square_image.dimensions' => 'The square image must have a maximum width of 600 pixels and a maximum height of 600 pixels.',
            'portrait_images.*.mimetypes' => 'The portrait image must be a file of type: jpeg, gif, png.',
            'portrait_images.*.dimensions' => 'The portrait image must have a maximum width of 900 pixels and a maximum height of 1200 pixels.',
            'landscape_images.*.mimetypes' => 'The landscape image must be a file of type: jpeg, gif, png.',
            'landscape_images.*.dimensions' => 'The landscape image must have a maximum width of 1920 pixels and a maximum height of 1080 pixels.',
        ];
    }
}

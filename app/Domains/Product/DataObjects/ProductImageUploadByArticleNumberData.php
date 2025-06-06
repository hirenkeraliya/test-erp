<?php

declare(strict_types=1);

namespace App\Domains\Product\DataObjects;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Spatie\LaravelData\Data;

class ProductImageUploadByArticleNumberData extends Data
{
    public function __construct(
        public string $article_number,
        public ?UploadedFile $thumbnail,
        public ?array $images = [],
        public ?array $videos = [],
        public bool $delete_old_videos = false,
        public bool $delete_old_images = false,
    ) {
    }

    public static function rules(Request $request): array
    {
        return [
            'article_number' => ['required', 'string'],
            'thumbnail' => [
                'nullable',
                'file',
                'mimetypes:image/jpeg,image/gif,image/png',
                File::image()->dimensions(Rule::dimensions()->maxWidth(343)->maxHeight(260)),
                'max:' . config('services.max_upload_size'),
            ],
            'images' => ['array', 'nullable'],
            'images.*' => [
                'required',
                'file',
                'mimetypes:image/jpeg,image/png',
                File::image()->dimensions(Rule::dimensions()->maxWidth(500)->maxHeight(500)),
                'max:' . config('services.max_upload_size'),
            ],
            'videos' => ['array', 'nullable'],
            'videos.*' => [
                'required',
                'file',
                'mimetypes:video/mp4,video/avi,video/mpeg',
                'max:' . config('services.max_upload_size'),
            ],
            'delete_old_videos' => ['required', 'boolean'],
            'delete_old_images' => ['required', 'boolean'],
        ];
    }
}

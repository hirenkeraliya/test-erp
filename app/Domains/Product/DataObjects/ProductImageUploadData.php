<?php

declare(strict_types=1);

namespace App\Domains\Product\DataObjects;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Spatie\LaravelData\Data;

class ProductImageUploadData extends Data
{
    public function __construct(
        public int $product_id,
        public UploadedFile $image,
    ) {
    }

    public static function rules(Request $request): array
    {
        return [
            'product_id' => ['required', 'integer'],
            'image' => [
                'required',
                'file',
                'mimetypes:image/jpeg,image/png',
                File::image()->dimensions(Rule::dimensions()->maxWidth(343)->maxHeight(260)),
                'max:' . config('services.max_upload_size'),
            ],
        ];
    }
}

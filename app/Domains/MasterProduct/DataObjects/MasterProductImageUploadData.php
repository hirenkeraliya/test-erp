<?php

declare(strict_types=1);

namespace App\Domains\MasterProduct\DataObjects;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Spatie\LaravelData\Data;

class MasterProductImageUploadData extends Data
{
    public function __construct(
        public int $master_product_id,
        public UploadedFile $image,
    ) {
    }

    public static function rules(Request $request): array
    {
        return [
            'master_product_id' => ['required', 'integer'],
            'image' => [
                'required',
                'file',
                'mimetypes:image/jpeg,image/png',
                File::image()->dimensions(Rule::dimensions()->maxWidth(343)->maxHeight(260)),
            ],
        ];
    }
}

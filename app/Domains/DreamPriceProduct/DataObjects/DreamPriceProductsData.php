<?php

declare(strict_types=1);

namespace App\Domains\DreamPriceProduct\DataObjects;

use Illuminate\Http\UploadedFile;
use Spatie\LaravelData\Data;

class DreamPriceProductsData extends Data
{
    public function __construct(
        public UploadedFile $dream_price_products,
    ) {
    }

    /**
     * @return array<string, array<string>>
     */
    public static function rules(): array
    {
        return [
            'dream_price_products' => [
                'required',
                'file',
                'mimes:xlsx, ods, xls',
                'max:' . config('services.max_upload_size'),
            ],
        ];
    }
}

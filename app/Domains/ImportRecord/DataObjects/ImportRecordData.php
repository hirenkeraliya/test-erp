<?php

declare(strict_types=1);

namespace App\Domains\ImportRecord\DataObjects;

use App\Domains\ImportRecord\Enums\ImportTypes;
use App\Domains\Product\Enums\ProductUploadTypes;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Spatie\LaravelData\Data;

class ImportRecordData extends Data
{
    public function __construct(
        public int $type_id,
        public UploadedFile $upload_file,
        public ?int $product_upload_type_id = null,
    ) {
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function rules(Request $request): array
    {
        $mimes = 'mimes:xlsx, ods, xls';
        if ((int) $request->type_id === ImportTypes::PRODUCT_BULK_IMAGE_UPLOAD->value) {
            $mimes = 'mimes:zip';
        }

        return [
            'type_id' => ['required', 'integer', 'in:' . ImportTypes::getValues()],
            'upload_file' => ['required', 'file', $mimes, 'max:' . config('services.max_upload_size')],
            'product_upload_type_id' => [
                'required_if:type_id,' . ImportTypes::PRODUCT_BULK_IMAGE_UPLOAD->value,
                'nullable',
                'integer',
                'in:' . ProductUploadTypes::getValues()],
        ];
    }
}

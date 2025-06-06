<?php

declare(strict_types=1);

namespace App\Domains\StockAdjustment\DataObjects;

use Illuminate\Http\UploadedFile;
use Spatie\LaravelData\Data;

class StockAdjustmentFileData extends Data
{
    public function __construct(
        public UploadedFile $uploaded_file,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(): array
    {
        return [
            'uploaded_file' => [
                'required',
                'file',
                'mimes:xlsx, ods, xls',
                'max:' . config('services.max_upload_size'),
            ],
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Domains\GoodsReceivedNote\DataObjects;

use Illuminate\Http\UploadedFile;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;

class GoodsReceivedNoteStoreForStoreManagerAppData extends Data
{
    public function __construct(
        #[MapName('store_id')]
        public int $location_id,
        public ?string $purchase_order_reference,
        public ?string $delivery_order_reference,
        public ?string $notes,
        public UploadedFile $uploaded_file,
        public int $vendor_id,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(): array
    {
        return [
            'location_id' => ['required', 'integer'],
            'purchase_order_reference' => ['sometimes', 'string'],
            'delivery_order_reference' => ['sometimes', 'string'],
            'notes' => ['sometimes', 'string'],
            'uploaded_file' => ['required', 'file', 'mimes:xlsx', 'max:' . config('services.max_upload_size')],
            'vendor_id' => ['required', 'integer'],
        ];
    }
}

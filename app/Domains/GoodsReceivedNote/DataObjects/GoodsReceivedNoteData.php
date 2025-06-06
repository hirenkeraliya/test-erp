<?php

declare(strict_types=1);

namespace App\Domains\GoodsReceivedNote\DataObjects;

use App\Domains\Vendor\VendorQueries;
use App\Models\Admin;
use App\Models\StoreManager;
use App\Models\WarehouseManager;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;

class GoodsReceivedNoteData extends Data
{
    public function __construct(
        public ?string $purchase_order_reference,
        public ?string $delivery_order_reference,
        public ?string $notes,
        public UploadedFile $uploaded_file,
        public int $vendor_id,
        public int $location_id,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(Request $request): array
    {
        $vendorQueries = new VendorQueries();

        /** @var Admin|StoreManager|WarehouseManager $user */
        $user = $request->user();

        $companyId = (int) session('admin_company_id');

        if (StoreManager::class === $user::class) {
            $companyId = (int) session('store_manager_selected_location_company_id');
        }

        if (WarehouseManager::class === $user::class) {
            $companyId = (int) session('warehouse_manager_selected_location_company_id');
        }

        return [
            'purchase_order_reference' => ['nullable', 'string'],
            'delivery_order_reference' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'location_id' => ['required', 'integer'],
            'uploaded_file' => [
                'required',
                'file',
                'mimes:xlsx, ods, xls',
                'max:' . config('services.max_upload_size'),
            ],
            'vendor_id' => [
                'required',
                'integer',
                Rule::exists('vendors', 'id')
                    ->where($vendorQueries->filterByCompany($companyId)),
            ],
        ];
    }
}

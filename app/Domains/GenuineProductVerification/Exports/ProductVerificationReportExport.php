<?php

declare(strict_types=1);

namespace App\Domains\GenuineProductVerification\Exports;

use App\Domains\Common\Services\ExportService;
use App\Domains\Product\Services\ProductService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProductVerificationReportExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function __construct(
        protected Collection $genuineProductVerifications,
        protected Collection $filteredColumns
    ) {
    }

    public function collection(): Collection
    {
        $productService = resolve(ProductService::class);

        return $this->genuineProductVerifications->map(function ($genuineProductVerification) use (
            $productService
        ): array {
            $product = $genuineProductVerification->product;
            $genuineProductVerificationData = [
                'name' => $genuineProductVerification->name,
                'mobile_number' => $genuineProductVerification->mobile_number,
                'email' => $genuineProductVerification->email,
                'product_name' => $product ? $product->name : 'N/A',
                'upc' => $product ? $product->upc : 'N/A',
                'color' => config('app.product_variant') ? 'N/A' : $product->color?->name ?? 'N/A',
                'size' => config('app.product_variant') ? 'N/A' : $product->size?->name ?? 'N/A',
                'is_genuine' => $genuineProductVerification->is_genuine ? 'Genuine' : 'Fake',
                'qr_code' => $genuineProductVerification->qr_code,
                'receipt_number' => $genuineProductVerification->receipt_number,
                'created_at' => $genuineProductVerification->created_at->format('d-m-Y D h:s:i A'),
                'remarks' => $genuineProductVerification->remarks,
                'attributes' => $productService->getAttributesForPrint($product),
            ];

            $exportService = resolve(ExportService::class);

            return $exportService->exportData($genuineProductVerificationData, $this->filteredColumns);
        });
    }

    public function headings(): array
    {
        $exportService = resolve(ExportService::class);

        return $exportService->getHeadings($this->filteredColumns);
    }
}

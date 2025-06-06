<?php

declare(strict_types=1);

namespace App\Domains\SaleItem\Exports;

use App\CommonFunctions;
use App\Domains\Common\Services\ExportService;
use App\Domains\Product\Services\ProductService;
use App\Models\Member;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class MemberSalesExport implements FromCollection, WithHeadings
{
    public function __construct(
        protected Collection $saleItems,
        protected Collection $filteredColumns
    ) {
    }

    public function collection(): Collection
    {
        $productService = resolve(ProductService::class);

        return $this->saleItems->map(function (SaleItem $saleItem) use ($productService): array {
            /** @var Product $product */
            $product = $saleItem->product;

            /** @var Sale $sale */
            $sale = $saleItem->sale;

            /** @var Member $member */
            $member = $sale->member;

            $colorSizeOrAttributeData = [];
            if (config('app.product_variant')) {
                $colorSizeOrAttributeData['attributes'] = $productService->getAttributesForPrint($product);
            } else {
                $colorSizeOrAttributeData = [
                    'color' => $product->color?->name ?? 'N/A',
                    'size' => $product->size?->name ?? 'N/A',
                ];
            }

            $memberSaleData = [
                'id' => $saleItem->getKey(),
                'member' => $member->getFullName(),
                'mobile_number' => $member->getMobileNumber(),
                'product' => $product->getName(),
                ...$colorSizeOrAttributeData,
                'upc' => $product->getUpc(),
                'units_sold' => $saleItem->getQuantity(),
                'units_returned' => $saleItem->getReturnedQuantity(),
                'price' => CommonFunctions::currencyFormat($product->getRetailPrice()),
            ];

            $exportService = resolve(ExportService::class);

            return $exportService->exportData($memberSaleData, $this->filteredColumns);
        });
    }

    public function headings(): array
    {
        $exportService = resolve(ExportService::class);

        return $exportService->getHeadings($this->filteredColumns);
    }
}

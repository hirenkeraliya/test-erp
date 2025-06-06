<?php

declare(strict_types=1);

namespace App\Domains\PromoterCommission\Exports;

use App\CommonFunctions;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Product\Services\ProductService;
use App\Models\Brand;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\Department;
use App\Models\Location;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PromoterCommissionDetailsExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function __construct(
        protected Collection $promoterCommissionsDetails,
    ) {
    }

    public function collection(): Collection
    {
        $productService = resolve(ProductService::class);
        $promoterCommissionData = collect();
        $totalAmounts = 0;
        $totalCommissionAmount = 0;

        foreach ($this->promoterCommissionsDetails as $promoterCommissionDetail) {
            $saleOrSaleReturn = null;
            $saleItemOrSaleReturnItem = $promoterCommissionDetail->affected_by;
            if ($promoterCommissionDetail->affected_by_type === ModelMapping::SALE_RETURN_ITEM->name) {
                $saleOrSaleReturn = $promoterCommissionDetail->affected_by->saleReturn;
            }

            if ($promoterCommissionDetail->affected_by_type === ModelMapping::SALE_ITEM->name) {
                $saleOrSaleReturn = $promoterCommissionDetail->affected_by->sale;
            }

            /** @var Department $department */
            $department = $promoterCommissionDetail->department;

            /** @var CounterUpdate $counterUpdate */
            $counterUpdate = $saleOrSaleReturn->counterUpdate;

            /** @var Counter $counter */
            $counter = $counterUpdate->counter;

            /** @var Location $location */
            $location = $counter->location;

            $product = $saleItemOrSaleReturnItem->product;

            /** @var Brand|null $brand */
            $brand = config('app.product_variant') ? $product?->masterProduct?->brand : $product->brand;

            $totalAmounts += $promoterCommissionDetail->amount;
            $totalCommissionAmount += $promoterCommissionDetail->commission_amount;

            $colorSizeOrAttributeData = [];
            if (config('app.product_variant')) {
                $colorSizeOrAttributeData['attributes'] = $productService->getAttributesForPrint($product);
            } else {
                $colorSizeOrAttributeData = [
                    'color' => $product->color?->name ?? 'N/A',
                    'size' => $product->size?->name ?? 'N/A',
                ];
            }

            $promoterCommissionData->push([
                'offline_id' => $promoterCommissionDetail->getOfflineId(
                    $promoterCommissionDetail->affected_by_type
                ),
                'product' => $product->name,
                'brand' => $brand ? $brand->name : 'N/A',
                ...$colorSizeOrAttributeData,
                'department' => $department->name ?? 'N/A',
                'location_name' => $location->name,
                'units' => $saleItemOrSaleReturnItem->quantity,
                'commission_percentage' => $promoterCommissionDetail->commission_percentage,
                'amount' => CommonFunctions::currencyFormat((float) $promoterCommissionDetail->amount),
                'commission_amount' => CommonFunctions::currencyFormat(
                    (float) $promoterCommissionDetail->commission_amount,
                    2
                ),
            ]);
        }

        $promoterCommissionDetails = $promoterCommissionData->toArray();

        $totalsRow = [];

        if (config('app.product_variant')) {
            $totalsRow['Attributes'] = 'N/A';
        } else {
            $totalsRow['Color'] = 'N/A';
            $totalsRow['Size'] = 'N/A';
        }

        $promoterCommissionDetails[] = [
            'Offline Id' => '',
            'Product' => '',
            'Brand' => '',
            ...$totalsRow,
            'Department' => '',
            'Location Name' => '',
            'Units' => '',
            'Commission Percentage' => 'Totals',
            'Amount' => CommonFunctions::currencyFormat($totalAmounts),
            'Commission Amount' => CommonFunctions::currencyFormat($totalCommissionAmount),
        ];

        return collect($promoterCommissionDetails);
    }

    public function headings(): array
    {
        if (config('app.product_variant')) {
            $headings[] = 'Attributes';
        } else {
            $headings[] = 'Color';
            $headings[] = 'Size';
        }

        return [
            'Offline Id',
            'Product',
            'Brand',
            ...$headings,
            'Department',
            'Location Name',
            'Units',
            'Commission Percentage',
            'Amount',
            'Commission',
        ];
    }
}

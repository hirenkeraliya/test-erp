<?php

declare(strict_types=1);

namespace App\Domains\Product\Exports;

use App\Domains\Common\Services\ExportService;
use App\Domains\Product\Services\ProductService;
use App\Domains\Product\SubDepartment\Enums\SubDepartments;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class OnlineProductReportExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function __construct(
        protected Collection $onlineProducts,
        protected Collection $filteredColumns
    ) {
    }

    public function collection(): Collection
    {
        $productService = resolve(ProductService::class);

        return $this->onlineProducts->map(function ($product) use ($productService): array {
            $colorSizeOrAttributeData = [];
            if (config('app.product_variant')) {
                $colorSizeOrAttributeData['attributes'] = $productService->getJsonAttributeToString(
                    $product->product_variants
                );
            } else {
                $colorSizeOrAttributeData = [
                    'color' => $product->color_name ?? 'N/A',
                    'size' => $product->size_name ?? 'N/A',
                ];
            }

            $productReportData = [
                'id' => $product->id,
                'product' => $product->name,
                'upc' => $product->upc,
                'article_number' => $product->article_number ?: 'N/A',
                'categories' => $product->category_names ?: 'N/A',
                'brand' => $product->brand_name,
                'season' => $product->season_name ?? 'N/A',
                'department' => $product->department_name ?? 'N/A',
                ...$colorSizeOrAttributeData,
                'sub_department' => $product->sub_department_id ? SubDepartments::getFormattedCaseName(
                    $product->sub_department_id
                ) : 'N/A',
                'unit_of_measure' => $product->unit_of_measure_name ?? 'N/A',
                'location' => $product->location_name,
                'units_sold' => $product->sum_order_quantity ?? 0,
                'total_orders' => $product->sum_order_amount ?? 0,
                'units_returned' => $product->sum_order_return_quantity ?? 0,
                'total_order_returns' => $product->sum_order_return_amount ?? 0,
                'tags' => $product->tag_names,
            ];

            $exportService = resolve(ExportService::class);

            return $exportService->exportData($productReportData, $this->filteredColumns);
        });
    }

    public function headings(): array
    {
        $exportService = resolve(ExportService::class);

        return $exportService->getHeadings($this->filteredColumns);
    }
}

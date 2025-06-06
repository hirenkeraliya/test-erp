<?php

declare(strict_types=1);

namespace App\Domains\Sale\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class GeneralSalesReportByColorAndSizeExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function __construct(
        protected array $saleCollections,
        protected array $columns,
    ) {
    }

    public function collection(): Collection
    {
        $saleCollections = [];

        foreach ($this->saleCollections as $saleCollection) {
            foreach ($saleCollection['data'] as $collection) {
                $saleData = [
                    'location_name' => $saleCollection['location_name'],
                    'counter_name' => $collection['product']['counter_name'],
                    'product_no' => $collection['product']['product_no'],
                    'promoters' => $collection['product']['promoters'],
                    'description' => $collection['product']['description'],
                    'qty' => $collection['product']['qty'],
                    'gross_sales' => $collection['product']['gross_sales'],
                    'discount' => $collection['product']['discount'],
                    'net_sales' => $collection['product']['net_sales'],
                ];

                if (isset($collection['sales'])) {
                    foreach ($collection['sales'] as $sales) {
                        foreach ($sales as $sale) {
                            $saleCollections[] = array_merge($saleData, [
                                'sale_product_no' => $sale['product_no'],
                                'sale_color' => $sale['color'],
                                'sale_size' => $sale['size'],
                                'sale_qty' => $sale['qty'],
                            ]);
                        }
                    }
                } else {
                    $saleCollections[] = $saleData;
                }
            }
        }

        return collect($saleCollections);
    }

    /**
     * @return mixed[]
     */
    public function headings(): array
    {
        return ['Location Name', ...$this->columns];
    }
}

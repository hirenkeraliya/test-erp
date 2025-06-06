<?php

declare(strict_types=1);

namespace App\Domains\Sale\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class VoidReportByReceiptExport implements FromCollection, WithHeadings, ShouldAutoSize
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
            foreach ($saleCollection['sales'] as $locationSale) {
                foreach ($locationSale['products'] as $product) {
                    $saleCollections[] = [
                        'location_name' => $saleCollection['location_name'],
                        'receipt_date' => $locationSale['receipt_date'],
                        'receipt_no' => $locationSale['receipt_no'],
                        'product_upc' => $product['product_upc'],
                        'product_name' => $product['product_name'],
                        'total' => $product['total'],
                        'void_reason' => $locationSale['void_reason'],
                        'voided_by' => $locationSale['voided_by'],
                        'void_sale_number' => $locationSale['void_sale_number'],
                        'promoters' => $product['promoters'],
                    ];
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

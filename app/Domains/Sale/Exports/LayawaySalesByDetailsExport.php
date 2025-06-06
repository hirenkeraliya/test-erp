<?php

declare(strict_types=1);

namespace App\Domains\Sale\Exports;

use App\CommonFunctions;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class LayawaySalesByDetailsExport implements FromCollection, ShouldAutoSize, WithEvents
{
    public function __construct(
        protected array $layawaySalesData,
        protected array $grandTotal,
        protected array $columns,
    ) {
    }

    public function collection(): Collection
    {
        $layawaySalesCollection = collect([$this->getTitleColumns()]);

        foreach ($this->layawaySalesData as $seasonalSaleData) {
            $layawaySalesCollection->push([$seasonalSaleData['location_name']]);
            foreach ($seasonalSaleData['products'] as $saleData) {
                $saleDataArray = $this->getSaleData($saleData);

                if (array_key_exists('items', $saleData)) {
                    foreach ($saleData['items'] as $item) {
                        $itemDataArray = $this->getItemData($item, $saleData['currency_symbol']);

                        $arrayMerge = array_merge($saleDataArray, $itemDataArray);
                        $layawaySalesCollection->push([$arrayMerge]);
                    }
                }
            }

            if (array_key_exists('totals', $seasonalSaleData)) {
                $layawaySalesCollection->push([$this->getTotalColumns($seasonalSaleData)]);
                $layawaySalesCollection->push(['']);
            }
        }

        $layawaySalesCollection->push([$this->getGrandTotalColumns()]);

        return $layawaySalesCollection;
    }

    public function getTitleColumns(): array
    {
        $this->columns[] = 'Product Name';
        $this->columns[] = 'Product UPC';
        if (config('app.product_variant')) {
            $this->columns[] = 'Attributes';
        } else {
            $this->columns[] = 'Color';
            $this->columns[] = 'Size';
        }

        $this->columns[] = 'Quantity';
        $this->columns[] = 'Unit Price';
        $this->columns[] = 'SubTotal';
        $this->columns[] = 'Discount';
        $this->columns[] = 'Tax';
        $this->columns[] = 'Paid';
        $this->columns[] = 'Pending';

        return $this->columns;
    }

    public function getSaleData(array $saleData): array
    {
        return [
            $saleData['receipt_number'],
            $saleData['status'],
            $saleData['counter'],
            $saleData['cashier'],
            $saleData['layaway_authorizer'],
            CommonFunctions::currencySymbolDisplayWithAmount($saleData['currency_symbol'], $saleData['total_amount']),
            CommonFunctions::currencySymbolDisplayWithAmount(
                $saleData['currency_symbol'],
                $saleData['total_amount_paid']
            ),
            CommonFunctions::currencySymbolDisplayWithAmount(
                $saleData['currency_symbol'],
                $saleData['layaway_pending_amount'] ?? 0
            ),
        ];
    }

    public function getItemData(array $item, string $currencySymbol): array
    {
        return [
            $item['product_name'],
            $item['product_upc'],
            ...config('app.product_variant') ? [$item['attributes']] : [$item['color'], $item['size']],
            $item['quantity'],
            $item['unit_price'],
            CommonFunctions::currencySymbolDisplayWithAmount($currencySymbol, $item['subtotal']),
            CommonFunctions::currencySymbolDisplayWithAmount($currencySymbol, $item['total_discount_amount']),
            CommonFunctions::currencySymbolDisplayWithAmount($currencySymbol, $item['total_tax_amount']),
            CommonFunctions::currencySymbolDisplayWithAmount($currencySymbol, $item['total_amount_paid']),
            CommonFunctions::currencySymbolDisplayWithAmount($currencySymbol, $item['total_pending_amount']),
        ];
    }

    public function getTotalColumns(array $seasonalSaleData): array
    {
        return [
            'Total',
            '',
            '',
            '',
            '',
            CommonFunctions::currencySymbolDisplayWithAmount(
                $seasonalSaleData['totals']['currency_symbol'],
                $seasonalSaleData['totals']['total_amount']
            ),
            CommonFunctions::currencySymbolDisplayWithAmount(
                $seasonalSaleData['totals']['currency_symbol'],
                $seasonalSaleData['totals']['total_amount_paid']
            ),
            CommonFunctions::currencySymbolDisplayWithAmount(
                $seasonalSaleData['totals']['currency_symbol'],
                $seasonalSaleData['totals']['total_layaway_pending_amount']
            ),
        ];
    }

    public function getGrandTotalColumns(): array
    {
        return [
            'Grand Total',
            '',
            '',
            '',
            '',
            CommonFunctions::currencySymbolDisplayWithAmount(
                $this->grandTotal['currency_symbol'],
                $this->grandTotal['total_amount']
            ),
            CommonFunctions::currencySymbolDisplayWithAmount(
                $this->grandTotal['currency_symbol'],
                $this->grandTotal['total_amount_paid']
            ),
            CommonFunctions::currencySymbolDisplayWithAmount(
                $this->grandTotal['currency_symbol'],
                $this->grandTotal['total_layaway_pending_amount']
            ),
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $event->sheet->freezePane('A2');
                $event->sheet->getStyle('1')->getFont()->setBold(true);
            },
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Domains\Sale\Exports;

use App\CommonFunctions;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class SaleExchangeReportExport implements FromCollection, ShouldAutoSize, WithEvents
{
    protected array $rowCountArray = [];

    public function __construct(
        public array $locationsSales
    ) {
    }

    public function collection(): Collection
    {
        $saleAndSaleReturnCollection = collect([$this->getHeaderRow()]);

        $rowCount = 2;
        foreach ($this->locationsSales as $locationSale) {
            $rowCount++;
            $saleAndSaleReturnCollection->push($this->getRowWith(''));
            $this->rowCountArray[] = $rowCount++;
            $saleAndSaleReturnCollection->push($this->getRowWith($locationSale['location_name']));
            $this->rowCountArray[] = $rowCount++;
            $saleAndSaleReturnCollection->push($this->getTitleColumns());

            foreach ($locationSale['sale'] as $sale) {
                $lastSectionKey = null;

                foreach ($sale['sale_products'] as $key => $saleProduct) {
                    $exchangeData = null;
                    $saleAndExchangeData = null;

                    if (array_key_exists($key, $sale['return_sale_products'])) {
                        $product = $sale['return_sale_products'][$key];
                        $exchangeData = [
                            'return_sale_happened_at' => $sale['return_sale_happened_at'],
                            'return_sale_offline_id' => $sale['return_sale_offline_id'],
                            'upc' => $product['upc'],
                            'name' => $product['name'],
                            'reason' => $product['reason'],
                            'quantity' => $product['quantity'],
                            'price' => CommonFunctions::currencySymbolDisplayWithAmount(
                                $sale['currency_symbol'],
                                $product['price'],
                                true
                            ),
                            'promoters' => $sale['promoters'],
                        ];

                        $saleAndExchangeData = [
                            'new_sale_happened_at' => $sale['return_sale_happened_at'],
                            'new_sale_offline_id' => $sale['return_sale_offline_id'],
                            'upc' => $product['upc'],
                            'name' => $product['name'],
                            'quantity' => $product['quantity'],
                            'price' => CommonFunctions::currencySymbolDisplayWithAmount(
                                $sale['currency_symbol'],
                                $product['price'],
                                true
                            ),
                            'promoters' => $sale['promoters'],
                        ];
                    } elseif (null === $lastSectionKey) {
                        $lastSectionKey = 0;
                    }

                    if (null !== $lastSectionKey && ($sale['new_sale_offline_id'] && array_key_exists(
                        $lastSectionKey,
                        $sale['new_sale_products']
                    ))) {
                        $product = $sale['new_sale_products'][$lastSectionKey];
                        $saleAndExchangeData = [
                            'new_sale_happened_at' => $sale['new_sale_happened_at'],
                            'new_sale_offline_id' => $sale['new_sale_offline_id'],
                            'upc' => $product['upc'],
                            'name' => $product['name'],
                            'quantity' => $product['quantity'],
                            'price' => CommonFunctions::currencySymbolDisplayWithAmount(
                                $sale['currency_symbol'],
                                $product['price']
                            ),
                            'promoters' => $sale['promoters'],
                        ];
                        $lastSectionKey += 1;
                    }

                    $rowCount++;
                    $saleAndSaleReturnCollection->push([
                        'column_1' => $sale['sale_happened_at'],
                        'title_1' => $sale['sale_offline_id'],
                        'column_2' => 'UPC:' . $saleProduct['upc'],
                        'column_3' => 'Name: ' . $saleProduct['name'],
                        'column_4' => $saleProduct['quantity'],
                        'column_5' => CommonFunctions::currencySymbolDisplayWithAmount(
                            $sale['currency_symbol'],
                            $saleProduct['price']
                        ),
                        'column_6' => $sale['promoters'],
                        'column_7' => '',
                        'column_8' => $exchangeData ? $exchangeData['return_sale_happened_at'] : '',
                        'title_2' => $exchangeData ? $exchangeData['return_sale_offline_id'] : '',
                        'column_9' => $exchangeData ? 'UPC: ' . $exchangeData['upc'] : '',
                        'column_10' => $exchangeData ? 'Name: ' . $exchangeData['name'] . ' Reason: ' . $exchangeData['reason'] : '',
                        'column_11' => $exchangeData ? $exchangeData['quantity'] : '',
                        'column_12' => $exchangeData ? $exchangeData['price'] : '',
                        'column_13' => $sale['promoters'],
                        'column_14' => '',
                        'column_15' => $saleAndExchangeData ? $saleAndExchangeData['new_sale_happened_at'] : '',
                        'title_3' => $saleAndExchangeData ? $saleAndExchangeData['new_sale_offline_id'] : '',
                        'column_16' => $saleAndExchangeData ? 'UPC: ' . $saleAndExchangeData['upc'] : '',
                        'column_17' => $saleAndExchangeData ? 'Name: ' . $saleAndExchangeData['name'] : '',
                        'column_18' => $saleAndExchangeData ? $saleAndExchangeData['quantity'] : '',
                        'column_19' => $saleAndExchangeData ? $saleAndExchangeData['price'] : '',
                        'column_20' => $sale['promoters'],
                    ]);
                }

                if (null !== $lastSectionKey && (is_countable($sale['new_sale_products']) ? count(
                    $sale['new_sale_products']
                ) : 0) > 0
                    && $lastSectionKey !== (is_countable($sale['new_sale_products']) ? count(
                        $sale['new_sale_products']
                    ) : 0)
                ) {
                    $itemsCount = is_countable($sale['new_sale_products']) ? count($sale['new_sale_products']) : 0;
                    for ($i = $lastSectionKey; $i < $itemsCount; $i++) {
                        $product = $sale['new_sale_products'][$i];
                        $saleAndExchangeData = [
                            'new_sale_happened_at' => $sale['return_sale_happened_at'] ?? '',
                            'new_sale_offline_id' => $sale['return_sale_offline_id'] ?? '',
                            'upc' => $product['upc'] ?? '',
                            'name' => $product['name'] ?? '',
                            'quantity' => $product['quantity'] ?? '',
                            'price' => CommonFunctions::currencySymbolDisplayWithAmount(
                                $sale['currency_symbol'],
                                $product['price']
                            ),
                            'promoters' => $sale['promoters'],
                        ];

                        $rowCount++;
                        $saleAndSaleReturnCollection->push([
                            'column_1' => '',
                            'title_1' => '',
                            'column_2' => '',
                            'column_3' => '',
                            'column_4' => '',
                            'column_5' => '',
                            'column_6' => '',
                            'column_7' => '',
                            'column_8' => '',
                            'title_2' => '',
                            'column_9' => '',
                            'column_10' => '',
                            'column_11' => '',
                            'column_12' => '',
                            'column_13' => '',
                            'column_14' => '',
                            'column_15' => $saleAndExchangeData['new_sale_happened_at'],
                            'title_3' => $saleAndExchangeData['new_sale_offline_id'],
                            'column_16' => 'UPC: ' . $saleAndExchangeData['upc'],
                            'column_17' => 'Name: ' . $saleAndExchangeData['name'],
                            'column_18' => $saleAndExchangeData['quantity'],
                            'column_19' => $saleAndExchangeData['price'],
                            'column_20' => $saleAndExchangeData['promoters'],
                        ]);
                    }
                }

                if (
                    $lastSectionKey !== (is_countable($sale['new_sale_products']) ? count(
                        $sale['new_sale_products']
                    ) : 0)
                ) {
                    $itemsCount = is_countable($sale['new_sale_products']) ? count($sale['new_sale_products']) : 0;
                    for ($i = 0; $i < $itemsCount; $i++) {
                        $product = $sale['new_sale_products'][$i];
                        $saleAndReturnData = [
                            'new_sale_happened_at' => $sale['return_sale_happened_at'] ?? '',
                            'new_sale_offline_id' => $sale['return_sale_offline_id'] ?? '',
                            'upc' => $product['upc'] ?? '',
                            'name' => $product['name'] ?? '',
                            'quantity' => $product['quantity'] ?? '',
                            'price' => CommonFunctions::currencySymbolDisplayWithAmount(
                                $sale['currency_symbol'],
                                $product['price']
                            ),
                            'promoters' => $sale['promoters'],
                        ];

                        $rowCount++;
                        $saleAndSaleReturnCollection->push([
                            'column_1' => '',
                            'title_1' => '',
                            'column_2' => '',
                            'column_3' => '',
                            'column_4' => '',
                            'column_5' => '',
                            'column_6' => '',
                            'column_7' => '',
                            'column_8' => '',
                            'title_2' => '',
                            'column_9' => '',
                            'column_10' => '',
                            'column_11' => '',
                            'column_12' => '',
                            'column_13' => '',
                            'column_14' => '',
                            'column_15' => $saleAndReturnData['new_sale_happened_at'],
                            'title_3' => $saleAndReturnData['new_sale_offline_id'],
                            'column_16' => 'UPC: ' . $saleAndReturnData['upc'],
                            'column_17' => 'Name: ' . $saleAndReturnData['name'],
                            'column_18' => $saleAndReturnData['quantity'],
                            'column_19' => $saleAndReturnData['price'],
                            'column_20' => $saleAndReturnData['promoters'],
                        ]);
                    }
                }

                $rowCount++;
                $saleAndSaleReturnCollection->push($this->getRowWith(''));
            }
        }

        return $saleAndSaleReturnCollection;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $event->sheet->freezePane('A2');
                $event->sheet->getStyle('1')->getFont()->setBold(true);
                foreach ($this->rowCountArray as $value) {
                    $event->sheet->getStyle($value)->getFont()->setBold(true);
                }
            },
        ];
    }

    public function getTitleColumns(): array
    {
        return [
            'column_1' => 'Date',
            'title_1' => 'Receipt No.',
            'column_2' => 'Upc',
            'column_3' => 'Description',
            'column_4' => 'Quantity',
            'column_5' => 'Total',
            'column_6' => 'Promoters',
            'column_7' => '',
            'column_8' => 'Date',
            'title_2' => 'Receipt No.',
            'column_9' => 'Upc',
            'column_10' => 'Description',
            'column_11' => 'Quantity',
            'column_12' => 'Total',
            'column_13' => 'Promoters',
            'column_14' => '',
            'column_15' => 'Date',
            'title_3' => 'Receipt No.',
            'column_16' => 'Upc',
            'column_17' => 'Description',
            'column_18' => 'Quantity',
            'column_19' => 'Total',
            'column_20' => 'Promoters',
        ];
    }

    public function getRowWith(string $locationName): array
    {
        return [
            'column_1' => $locationName,
            'title_1' => '',
            'column_2' => '',
            'column_3' => '',
            'column_4' => '',
            'column_5' => '',
            'column_6' => '',
            'column_7' => '',
            'column_8' => '',
            'title_2' => '',
            'column_9' => '',
            'column_10' => '',
            'column_11' => '',
            'column_12' => '',
            'column_13' => '',
            'column_14' => '',
            'column_15' => '',
            'title_3' => '',
            'column_16' => '',
            'column_17' => '',
            'column_18' => '',
            'column_19' => '',
            'column_20' => '',
        ];
    }

    private function getHeaderRow(): array
    {
        return [
            'column_1' => '',
            'title_1' => 'Receipt Before Exchange',
            'column_2' => '',
            'column_3' => '',
            'column_4' => '',
            'column_5' => '',
            'column_6' => '',
            'column_7' => '',
            'column_8' => '',
            'title_2' => 'Exchange',
            'column_9' => '',
            'column_10' => '',
            'column_11' => '',
            'column_12' => '',
            'column_13' => '',
            'column_14' => '',
            'column_15' => '',
            'title_3' => 'Receipt After Exchange',
            'column_16' => '',
            'column_17' => '',
            'column_18' => '',
            'column_19' => '',
            'column_20' => '',
        ];
    }
}

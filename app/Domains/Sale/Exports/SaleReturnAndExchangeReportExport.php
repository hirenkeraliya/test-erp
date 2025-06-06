<?php

declare(strict_types=1);

namespace App\Domains\Sale\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class SaleReturnAndExchangeReportExport implements FromCollection, ShouldAutoSize, WithEvents
{
    protected array $rowCountArray = [];

    public function __construct(
        public array $locationsSales,
    ) {
    }

    public function collection(): Collection
    {
        $rowCount = 2;
        $saleAndSaleReturnCollection = collect([$this->getHeaderRow()]);

        foreach ($this->locationsSales as $locationSale) {
            $rowCount++;
            $saleAndSaleReturnCollection->push($this->getRowWith(''));
            $this->rowCountArray[] = $rowCount++;
            $saleAndSaleReturnCollection->push($this->getRowWith($locationSale['location_name']));
            $this->rowCountArray[] = $rowCount++;
            $saleAndSaleReturnCollection->push($this->getTitleColumns());

            foreach ($locationSale['sale'] as $sale) {
                $saleAndSaleReturnArray = [];
                foreach ($sale['sale_products'] as $key => $saleProduct) {
                    $saleAndSaleReturnArray[$key] = [
                        'column_1' => $sale['sale_happened_at'],
                        'title_1' => $sale['sale_offline_id'],
                        'column_2' => 'UPC:' . $saleProduct['upc'],
                        'column_3' => 'Name: ' . $saleProduct['name'],
                        'column_4' => $saleProduct['quantity'],
                        'column_5' => $sale['currency_symbol'] . $saleProduct['price'],
                        'column_6' => $sale['promoters'],
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

                $lastSectionKey = 0;

                foreach ($sale['return_sale_products'] as $key => $returnSaleProducts) {
                    if (isset($saleAndSaleReturnArray[$key])) {
                        $saleAndSaleReturnArray[$key]['column_8'] = $sale['new_sale_happened_at'];
                        $saleAndSaleReturnArray[$key]['title_2'] = $sale['new_sale_offline_id'];
                        $saleAndSaleReturnArray[$key]['column_9'] = 'UPC: ' . $returnSaleProducts['upc'];
                        $saleAndSaleReturnArray[$key]['column_10'] = 'Name: ' . $returnSaleProducts['name'] . ' Reason: ' . $returnSaleProducts['reason'];
                        $saleAndSaleReturnArray[$key]['column_11'] = $returnSaleProducts['quantity'];
                        $saleAndSaleReturnArray[$key]['column_12'] = '-' .$sale['currency_symbol'] . $returnSaleProducts['price'];
                        $saleAndSaleReturnArray[$key]['column_13'] = $sale['promoters'];

                        $saleAndSaleReturnArray[$key]['column_15'] = $sale['new_sale_happened_at'];
                        $saleAndSaleReturnArray[$key]['title_3'] = $sale['new_sale_offline_id'];
                        $saleAndSaleReturnArray[$key]['column_16'] = 'UPC: ' . $returnSaleProducts['upc'];
                        $saleAndSaleReturnArray[$key]['column_17'] = 'Name: ' . $returnSaleProducts['name'];
                        $saleAndSaleReturnArray[$key]['column_18'] = $returnSaleProducts['quantity'];
                        $saleAndSaleReturnArray[$key]['column_19'] = '-' . $sale['currency_symbol'] . $returnSaleProducts['price'];
                        $saleAndSaleReturnArray[$key]['column_20'] = $sale['promoters'];

                        $lastSectionKey += 1;
                    }
                }

                foreach ($sale['new_sale_products'] as $key => $newSaleProducts) {
                    $actualKey = $lastSectionKey + $key;
                    if (isset($saleAndSaleReturnArray[$actualKey])) {
                        $saleAndSaleReturnArray[$actualKey]['column_15'] = $sale['new_sale_happened_at'];
                        $saleAndSaleReturnArray[$actualKey]['title_3'] = $sale['new_sale_offline_id'].'BBB';
                        $saleAndSaleReturnArray[$actualKey]['column_16'] = 'UPC: ' . $newSaleProducts['upc'];
                        $saleAndSaleReturnArray[$actualKey]['column_17'] = 'Name: ' . $newSaleProducts['name'];
                        $saleAndSaleReturnArray[$actualKey]['column_18'] = $newSaleProducts['quantity'];
                        $saleAndSaleReturnArray[$actualKey]['column_19'] = $sale['currency_symbol'] . $newSaleProducts['price'];
                        $saleAndSaleReturnArray[$actualKey]['column_20'] = $sale['promoters'];
                    } else {
                        $saleAndSaleReturnArray[] = [
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
                            'column_15' => $sale['new_sale_happened_at'],
                            'title_3' => $sale['new_sale_offline_id'],
                            'column_16' => 'UPC: ' . $newSaleProducts['upc'],
                            'column_17' => 'Name: ' . $newSaleProducts['name'],
                            'column_18' => $newSaleProducts['quantity'],
                            'column_19' => $sale['currency_symbol'] . $newSaleProducts['price'],
                            'column_20' => $sale['promoters'],
                        ];
                    }
                }

                $saleAndSaleReturnCollection->push($saleAndSaleReturnArray);

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
            'title_1' => 'Receipt Before Exchange/Return',
            'column_2' => '',
            'column_3' => '',
            'column_4' => '',
            'column_5' => '',
            'column_6' => '',
            'column_7' => '',
            'column_8' => '',
            'title_2' => 'Exchange/Return',
            'column_9' => '',
            'column_10' => '',
            'column_11' => '',
            'column_12' => '',
            'column_13' => '',
            'column_14' => '',
            'column_15' => '',
            'title_3' => 'Receipt After Exchange/Return',
            'column_16' => '',
            'column_17' => '',
            'column_18' => '',
            'column_19' => '',
            'column_20' => '',
        ];
    }
}

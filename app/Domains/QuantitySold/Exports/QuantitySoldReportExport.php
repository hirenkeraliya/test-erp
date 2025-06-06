<?php

declare(strict_types=1);

namespace App\Domains\QuantitySold\Exports;

use App\Models\Company;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class QuantitySoldReportExport implements FromCollection, ShouldAutoSize
{
    public function __construct(
        protected array $records,
        protected ?string $location,
        protected ?string $compareStore,
        protected ?string $region,
        protected ?string $compareRegion,
        protected array $dateRange,
        protected Company $company,
        protected bool $sortWithDifferentColumn,
    ) {
    }

    public function collection(): Collection
    {
        $locationOrRegion = $this->location ?? $this->region;
        $compareLocationOrRegion = $this->compareStore ?? $this->compareRegion;

        $quantitySoldRecords = collect([$this->getHeaderRow($locationOrRegion, $compareLocationOrRegion)]);

        $quantitySoldRecords->push($this->getTitleColumns());

        $records = $this->sortWithDifferentColumn ? $this->records['records'] ?? [] : $this->records ?? [];
        $comparedRecords = $this->sortWithDifferentColumn ? $this->records['compareRecords'] ?? [] : $this->records ?? [];

        $recordCount = count($records);
        $comparedRecordCount = count($comparedRecords);

        $maxIterations = max($recordCount, $comparedRecordCount);

        for ($i = 0; $i < $maxIterations; $i++) {
            $record = $i < $recordCount ? $records[$i] : null;
            $compareRecord = $i < $comparedRecordCount ? $comparedRecords[$i] : null;

            if (config('app.product_variant')) {
                $quantitySoldRecords->push([
                    'title_1' => $record['product'] ?? '',
                    'column_2' => $record['upc'] ?? '',
                    'column_3' => $record['article_number'] ?? '',
                    'column_4' => $record['product_variant_values'] ?? '',
                    'column_5' => $record['qty_sold'] ?? '',
                    'column_6' => $record['amount_sold'] ?? '',
                    'column_7' => '',
                    'title_2' => $compareRecord['product'] ?? '',
                    'column_8' => $compareRecord['upc'] ?? '',
                    'column_9' => $compareRecord['article_number'] ?? '',
                    'column_10' => $compareRecord['product_variant_values'] ?? '',
                    'column_11' => $compareRecord['compare_qty_sold'] ?? '',
                    'column_12' => $compareRecord['compare_sold_amount'] ?? '',
                ]);
            } else {
                $quantitySoldRecords->push([
                    'title_1' => $record['product'] ?? '',
                    'column_2' => $record['upc'] ?? '',
                    'column_3' => $record['article_number'] ?? '',
                    'column_4' => $record['color'] ?? '',
                    'column_5' => $record['size'] ?? '',
                    'column_6' => $record['qty_sold'] ?? '',
                    'column_7' => $record['amount_sold'] ?? '',
                    'column_8' => '',
                    'title_2' => $compareRecord['product'] ?? '',
                    'column_9' => $compareRecord['upc'] ?? '',
                    'column_10' => $compareRecord['article_number'] ?? '',
                    'column_11' => $compareRecord['color'] ?? '',
                    'column_12' => $compareRecord['size'] ?? '',
                    'column_13' => $compareRecord['compare_qty_sold'] ?? '',
                    'column_14' => $compareRecord['compare_sold_amount'] ?? '',
                ]);
            }
        }

        return $quantitySoldRecords;
    }

    public function getTitleColumns(): array
    {
        if (config('app.product_variant')) {
            return [
                'title_1' => 'Name',
                'column_2' => 'Upc',
                'column_3' => 'Article Number',
                'column_4' => 'Attributes',
                'column_5' => 'Qty Sold',
                'column_6' => 'Amount Sold',
                'column_7' => '',
                'title_2' => 'Name',
                'column_8' => 'Upc',
                'column_9' => 'Article Number',
                'column_10' => 'Attributes',
                'column_11' => 'Qty Sold',
                'column_12' => 'Amount Sold',
            ];
        }

        return [
            'title_1' => 'Name',
            'column_2' => 'Upc',
            'column_3' => 'Article Number',
            'column_4' => 'Color',
            'column_5' => 'Size',
            'column_6' => 'Qty Sold',
            'column_7' => 'Amount Sold',
            'column_8' => '',
            'title_2' => 'Name',
            'column_9' => 'Upc',
            'column_10' => 'Article Number',
            'column_11' => 'Color',
            'column_12' => 'Size',
            'column_13' => 'Qty Sold',
            'column_14' => 'Amount Sold',
        ];
    }

    private function getHeaderRow(?string $locationOrRegion, ?string $compareLocationOrRegion): array
    {
        if (config('app.product_variant')) {
            return [
                'title_1' => $locationOrRegion,
                'column_1' => '',
                'column_2' => '',
                'column_3' => '',
                'column_4' => '',
                'column_5' => '',
                'column_6' => '',
                'title_2' => $compareLocationOrRegion,
                'column_7' => '',
                'column_8' => '',
                'column_9' => '',
                'column_10' => '',
                'column_11' => '',
                'column_12' => '',
            ];
        }

        return [
            'title_1' => $locationOrRegion,
            'column_1' => '',
            'column_2' => '',
            'column_3' => '',
            'column_4' => '',
            'column_5' => '',
            'column_6' => '',
            'column_7' => '',
            'title_2' => $compareLocationOrRegion,
            'column_8' => '',
            'column_09' => '',
            'column_10' => '',
            'column_11' => '',
            'column_12' => '',
            'column_13' => '',
        ];
    }
}

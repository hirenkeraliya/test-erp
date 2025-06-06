<?php

declare(strict_types=1);

namespace App\Domains\Promoter\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SalesByPromoterWithSummaryReportExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function __construct(
        protected array $promoters,
        protected array $columns,
        protected array $totals,
    ) {
    }

    public function collection(): Collection
    {
        $salesByPromoterData = [];

        foreach ($this->promoters as $promoter) {
            foreach ($promoter['promoter_sales'] as $promoterSale) {
                $salesByPromoterData[] = array_merge([
                    'location_name' => $promoter['location_name'],
                ], $promoterSale);
            }

            $salesByPromoterData[] = [
                '',
                '',
                'Grand Totals',
                $this->totals[$promoter['location_id']]['units_sold'],
                $this->totals[$promoter['location_id']]['units_returned'],
                $this->totals[$promoter['location_id']]['total_units_returned_amount'],
                $this->totals[$promoter['location_id']]['gross_amount'],
                $this->totals[$promoter['location_id']]['discount_amount'],
                $this->totals[$promoter['location_id']]['tax_amount'],
                $this->totals[$promoter['location_id']]['net_amount'],
            ];
        }

        return collect($salesByPromoterData);
    }

    public function headings(): array
    {
        return ['Location Name', ...$this->columns];
    }
}

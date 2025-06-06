<?php

declare(strict_types=1);

namespace App\Domains\LoyaltyCampaign\Exports;

use App\Models\LoyaltyCampaign;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class LoyaltyCampaignExport implements FromCollection, WithHeadings
{
    public function __construct(
        protected Collection $loyaltyCampaigns
    ) {
    }

    public function collection(): Collection
    {
        return $this->loyaltyCampaigns->map(function (LoyaltyCampaign $loyaltyCampaign): array {
            $endDate = Carbon::createFromFormat('Y-m-d', $loyaltyCampaign->end_date);
            $startDate = Carbon::createFromFormat('Y-m-d', $loyaltyCampaign->start_date);

            return [
                'name' => $loyaltyCampaign->name,
                'minimum_spend_amount' => $loyaltyCampaign->minimum_spend_amount,
                'loyalty_points' => $loyaltyCampaign->loyalty_points,
                'start_date' => $startDate ? $startDate->format('d-m-Y') : '',
                'end_date' => $endDate ? $endDate->format('d-m-Y') : '',
            ];
        });
    }

    public function headings(): array
    {
        return ['Name', 'Minimum Spend', 'Loyalty Points', 'Start Date', 'End Date'];
    }
}

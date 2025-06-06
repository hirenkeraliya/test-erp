<?php

declare(strict_types=1);

namespace App\Domains\LoyaltyCampaignConfiguration\Exports;

use App\Domains\LoyaltyCampaignConfiguration\Enums\ExpirationTypes;
use App\Domains\LoyaltyCampaignConfiguration\Enums\LoyaltyCampaignTypes;
use App\Models\LoyaltyCampaignConfiguration;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class LoyaltyCampaignConfigurationExport implements FromCollection, WithHeadings
{
    public function __construct(
        protected Collection $loyaltyCampaignConfigurations
    ) {
    }

    public function collection(): Collection
    {
        return $this->loyaltyCampaignConfigurations->map(
            fn (LoyaltyCampaignConfiguration $loyaltyCampaignConfiguration): array => [
                'description' => $loyaltyCampaignConfiguration->description,
                'loyalty_campaign_type' => LoyaltyCampaignTypes::getFormattedCaseName(
                    $loyaltyCampaignConfiguration->loyalty_campaign_type
                ),
                'point_earned' => $loyaltyCampaignConfiguration->point_earned,
                'minimum_purchase_amount' => $loyaltyCampaignConfiguration->minimum_purchase_amount,
                'expiration_type' => ExpirationTypes::getFormattedCaseName(
                    $loyaltyCampaignConfiguration->expiration_type
                ),
                'include_tax' => $loyaltyCampaignConfiguration->include_tax ? 'Yes' : 'No',
                'status' => $loyaltyCampaignConfiguration->status ? 'Active' : 'Inactive',
            ]
        );
    }

    public function headings(): array
    {
        return [
            'Title',
            'Campaign Type',
            'Points Earned',
            'Minimum Purchase',
            'Expire By',
            'Include Tax',
            'Status',
        ];
    }
}

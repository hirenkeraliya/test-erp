<?php

declare(strict_types=1);

namespace App\Domains\LoyaltyCampaignConfiguration\Resources;

use App\Domains\LoyaltyCampaignConfiguration\Enums\ExpirationTypes;
use App\Domains\LoyaltyCampaignConfiguration\Enums\LoyaltyCampaignTypes;
use App\Models\LoyaltyCampaignConfiguration;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoyaltyCampaignConfigurationListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var LoyaltyCampaignConfiguration $loyaltyCampaignConfiguration */
        $loyaltyCampaignConfiguration = $this;

        return [
            'id' => $loyaltyCampaignConfiguration->id,
            'description' => $loyaltyCampaignConfiguration->description,
            'loyalty_campaign_type' => LoyaltyCampaignTypes::getFormattedCaseName(
                $loyaltyCampaignConfiguration->loyalty_campaign_type
            ),
            'point_earned' => $loyaltyCampaignConfiguration->point_earned,
            'minimum_purchase_amount' => $loyaltyCampaignConfiguration->minimum_purchase_amount,
            'expiration_type' => ExpirationTypes::getFormattedCaseName($loyaltyCampaignConfiguration->expiration_type),
            'include_tax' => $loyaltyCampaignConfiguration->include_tax ? 'Yes' : 'No',
            'status' => $loyaltyCampaignConfiguration->status ? 'Active' : 'Inactive',
        ];
    }
}

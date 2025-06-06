<?php

declare(strict_types=1);

namespace App\Domains\LoyaltyCampaign\Resources;

use App\Models\LoyaltyCampaign;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminLoyaltyCampaignListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var LoyaltyCampaign $loyaltyCampaign */
        $loyaltyCampaign = $this;

        $endDate = Carbon::createFromFormat('Y-m-d', $loyaltyCampaign->end_date);
        $startDate = Carbon::createFromFormat('Y-m-d', $loyaltyCampaign->start_date);

        return [
            'id' => $loyaltyCampaign->id,
            'name' => $loyaltyCampaign->name,
            'minimum_spend_amount' => $loyaltyCampaign->minimum_spend_amount,
            'loyalty_points' => $loyaltyCampaign->loyalty_points,
            'loyalty_point_expiration_days' => $loyaltyCampaign->loyalty_point_expiration_days,
            'start_date' => $startDate ? $startDate->format('d-m-Y') : '',
            'end_date' => $endDate ? $endDate->format('d-m-Y') : '',
        ];
    }
}

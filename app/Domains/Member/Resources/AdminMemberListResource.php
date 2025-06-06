<?php

declare(strict_types=1);

namespace App\Domains\Member\Resources;

use App\Domains\Member\Enums\Status;
use App\Domains\Member\Enums\Titles;
use App\Domains\Member\Enums\Types;
use App\Models\Member;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminMemberListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var Member $member */
        $member = $this;

        /** @var Carbon $createdAt */
        $createdAt = $member->created_at;

        /** @var Carbon $updatedAt */
        $updatedAt = $member->updated_at;

        $lastManualUpdateLoyaltyPoint = null;
        $lastUpdateLoyaltyPointDate = null;
        if ($member->lastManualUpdateLoyaltyPoint) {
            $lastManualUpdateLoyaltyPoint = $member->lastManualUpdateLoyaltyPoint;
            if ($lastManualUpdateLoyaltyPoint->happened_at) {
                /** @var Carbon $lastUpdateLoyaltyPointDateFormate */
                $lastUpdateLoyaltyPointDateFormate = Carbon::createFromFormat(
                    'Y-m-d H:i:s',
                    $lastManualUpdateLoyaltyPoint->happened_at
                );
                $lastUpdateLoyaltyPointDate = $lastUpdateLoyaltyPointDateFormate->format('d-m-Y h:i:s A');
            }
        }

        return [
            'id' => $member->id,
            'type' => $member->type_id ? Types::getFormattedCaseName($member->type_id) : 'N/A',
            'title' => $member->title_id ? Titles::getFormattedCaseName($member->title_id) : 'N/A',
            'employee_id' => $member->employee_id,
            'first_name' => $member->first_name,
            'mobile_number' => $member->mobile_number,
            'email' => $member->email ?? 'N/A',
            'card_number' => $member->card_number,
            'loyalty_points' => $member->loyalty_points,
            'membership_id' => $member->membership_id,
            'membership' => $member->membership,
            'status' => $member->status === Status::ACTIVE->value,
            'last_purchase_date' => $member->last_purchase_date ? $member->last_purchase_date->format(
                'd-m-Y h:i:s A'
            ) . ' </br> (' . $member->last_purchase_date->diffForHumans() . ')' : 'N/A',
            'created_at' => $createdAt->format('d-m-Y h:i:s A'),
            'updated_at' => $updatedAt->format('d-m-Y h:i:s A'),
            'last_update_loyalty_points' => [
                'date' => $lastUpdateLoyaltyPointDate,
                'point' => $lastManualUpdateLoyaltyPoint?->points,
                'reason' => $lastManualUpdateLoyaltyPoint?->remarks,
            ],
            'is_email_verified' => $member->is_email_verified,
        ];
    }
}

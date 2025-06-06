<?php

declare(strict_types=1);

namespace App\Domains\Member\Resources;

use App\Domains\Member\Enums\MemberChannelEnum;
use App\Models\MemberAddress;
use App\Models\Membership;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class MemberDetailListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $member = $this->resource;

        /** @var Collection $sales */
        $sales = $member->sales;

        /** @var Carbon $createdAt */
        $createdAt = $member->created_at;

        /** @var MemberAddress|null $primaryAddress */
        $primaryAddress = $member->primaryMemberAddress;

        /** @var ?Membership $membership */
        $membership = $member->membership;

        return [
            'id' => $member->id,
            'first_name' => $member->first_name,
            'mobile_number' => $member->mobile_number,
            'loyalty_points' => $member->loyalty_points,
            'redeemed_points' => $member->total_redeemed_points,
            'membership_name' => $membership ? $membership->name : 'N/A',
            'membership_date' => $membership ? $membership->created_at?->format('d-m-Y') : 'N/A',
            'created_at' => $createdAt->format('d-m-Y'),
            'address_line_1' => $primaryAddress ? $primaryAddress->address_line_1 : null,
            'location' => $member->createdInLocation?->name,
            'spent_till_now' => $member->spent_till_now,
            'total_discount_amount' => $sales->sum('total_discount_amount'),
            'channel' => MemberChannelEnum::getFormattedCaseName($member->channel_id),
        ];
    }
}

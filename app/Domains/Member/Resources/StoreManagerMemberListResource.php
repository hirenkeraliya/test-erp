<?php

declare(strict_types=1);

namespace App\Domains\Member\Resources;

use App\Domains\Member\Enums\Titles;
use App\Domains\Member\Enums\Types;
use App\Models\Member;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StoreManagerMemberListResource extends JsonResource
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

        return [
            'id' => $member->id,
            'type' => $member->type_id ? Types::getFormattedCaseName($member->type_id) : 'N/A',
            'title' => $member->title_id ? Titles::getFormattedCaseName($member->title_id) : 'N/A',
            'first_name' => $member->first_name,
            'mobile_number' => $member->mobile_number,
            'email' => $member->email ?? 'N/A',
            'card_number' => $member->card_number,
            'membership_id' => $member->membership_id,
            'membership' => $member->membership,
            'last_purchase_date' => $member->last_purchase_date ? $member->last_purchase_date->format(
                'd-m-Y h:i:s A'
            ) . ' </br> (' . $member->last_purchase_date->diffForHumans() . ')' : 'N/A',
            'created_at' => $createdAt->format('d-m-Y h:i:s A'),
            'updated_at' => $updatedAt->format('d-m-Y h:i:s A'),
        ];
    }
}

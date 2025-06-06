<?php

declare(strict_types=1);

namespace App\Domains\MemberGroup\Resources;

use App\CommonFunctions;
use App\Domains\ImportRecord\Enums\Status;
use App\Domains\MemberGroup\Enums\GroupTypes;
use App\Models\ImportRecord;
use App\Models\Member;
use App\Models\MemberGroup;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberGroupListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var MemberGroup $memberGroup */
        $memberGroup = $this;

        /** @var ?ImportRecord $importRecord */
        $importRecord = $memberGroup->importRecord;

        $lifetimeValueOfMembers = 0;
        if ($memberGroup->memberGroupMembers->count()) {
            foreach ($memberGroup->memberGroupMembers as $memberGroupMember) {
                /** @var Member $member */
                $member = $memberGroupMember->member;
                $lifetimeValueOfMembers += $member->spent_till_now;
            }
        }

        return [
            'id' => $memberGroup->getKey(),
            'name' => $memberGroup->name,
            'type_id' => $memberGroup->type_id,
            'pending_members' => $memberGroup->memberGroupMembers->where('is_synced', false)->count(),
            'members' => $memberGroup->memberGroupMembers->where('is_synced', true)->count(),
            'lifetime_value_of_members' => CommonFunctions::numberFormat((float) $lifetimeValueOfMembers, 2),
            'code' => $memberGroup->code,
            'type' => GroupTypes::getFormattedCaseName($memberGroup->type_id),
            'upload_status' => $importRecord instanceof ImportRecord ? Status::getFormattedCaseName(
                $importRecord->status
            ) : 'N/A',
        ];
    }
}

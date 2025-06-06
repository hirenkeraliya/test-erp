<?php

declare(strict_types=1);

namespace App\Domains\MemberGroup\Resources;

use App\Models\MemberGroup;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EcommerceMemberGroupListResource extends JsonResource
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

        /** @var Carbon $createdAt */
        $createdAt = $memberGroup->created_at;

        /** @var Carbon $updatedAt */
        $updatedAt = $memberGroup->updated_at;

        return [
            'id' => $memberGroup->getKey(),
            'name' => $memberGroup->name,
            'code' => $memberGroup->code,
            'member_ids' => $memberGroup->memberGroupMembers->pluck('member_id')->toArray(),
            'created_at' => $createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Domains\Member\Resources;

use App\Models\Location;
use App\Models\Member;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberReportResource extends JsonResource
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

        /** @var ?Location $location */
        $location = $member->createdInLocation;

        return [
            /* @phpstan-ignore-next-line */
            'date' => Carbon::createFromFormat('Y-m-d', $member->date)->format('d-m-Y'),
            'location' => null !== $location ? $location->name : 'N/A',
            /* @phpstan-ignore-next-line */
            'members_count' => $member->members_count,
            'created_location_id' => $member->created_location_id,
        ];
    }
}

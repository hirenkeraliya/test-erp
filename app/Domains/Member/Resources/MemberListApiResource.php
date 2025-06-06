<?php

declare(strict_types=1);

namespace App\Domains\Member\Resources;

use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberListApiResource extends JsonResource
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

        return [
            'id' => $member->id,
            'first_name' => $member->first_name,
            'last_name' => $member->last_name,
            'mobile_number' => $member->mobile_number,
            'email' => $member->email,
            'loyalty_points' => $member->loyalty_points,
            'vouchers_count' => $member->vouchers_count,
        ];
    }
}

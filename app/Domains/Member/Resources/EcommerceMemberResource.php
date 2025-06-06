<?php

declare(strict_types=1);

namespace App\Domains\Member\Resources;

use App\Domains\Member\Enums\Status;
use App\Domains\Sale\DataPreparer\UserDataPreparer;
use App\Models\Member;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class EcommerceMemberResource extends JsonResource
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

        /** @var Collection $memberAddresses */
        $memberAddresses = $member->memberAddresses;

        $userDataPreparer = resolve(UserDataPreparer::class);

        return [
            'id' => $member->id,
            'first_name' => $member->first_name,
            'mobile_number' => $member->mobile_number,
            'email' => $member->email ?? 'N/A',
            'date_of_birth' => $member->date_of_birth,
            'member_addresses' => $userDataPreparer->getMemberAddressDetails($memberAddresses),
            'status' => Status::getFormattedCaseName($member->status),
            'created_at' => $createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Member;

use App\Domains\MemberAddress\DataObjects\AppMemberAddressData;
use App\Domains\MemberAddress\MemberAddressQueries;
use App\Domains\MemberAddress\Resources\MemberAddressResource;
use App\Http\Controllers\Controller;
use App\Models\Member;
use Illuminate\Http\Request;

class MemberAddressController extends Controller
{
    public function store(AppMemberAddressData $memberAddressData, Request $request): array
    {
        /** @var Member $member */
        $member = $request->user();

        $memberAddressQueries = resolve(MemberAddressQueries::class);
        $memberAddress = $memberAddressQueries->isPrimary($member->id);

        if ($memberAddress && $memberAddressData->is_primary) {
            $memberAddressQueries->updatePrimaryKey($memberAddress->id, $member->id);
        }

        $memberAddress = $memberAddressQueries->addAddressForMemberApp($memberAddressData, $member->id);

        return [
            'member_address' => new MemberAddressResource($memberAddress),
        ];
    }

    public function update(AppMemberAddressData $memberAddressData, int $memberAddressId, Request $request): void
    {
        /** @var Member $member */
        $member = $request->user();

        $memberAddressQueries = resolve(MemberAddressQueries::class);
        $memberAddress = $memberAddressQueries->getById($memberAddressId, $member->id);
        $memberAddressQueries->updateForMemberApp($memberAddressData, $memberAddress, $member->id);
    }

    public function removeAddress(int $memberAddressId): void
    {
        $memberAddressQueries = resolve(MemberAddressQueries::class);
        $memberAddressQueries->delete($memberAddressId);
    }
}

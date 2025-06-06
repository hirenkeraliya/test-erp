<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Pos;

use App\Domains\MemberAddress\DataObjects\MemberAddressData;
use App\Domains\MemberAddress\MemberAddressQueries;
use App\Domains\MemberAddress\Resources\MemberAddressResource;
use App\Http\Controllers\Controller;

class MemberAddressController extends Controller
{
    public function store(MemberAddressData $memberAddressData): array
    {
        $memberAddressQueries = resolve(MemberAddressQueries::class);
        $memberAddress = $memberAddressQueries->isPrimary($memberAddressData->member_id);

        if ($memberAddress && $memberAddressData->is_primary) {
            $memberAddressQueries->updatePrimaryKey($memberAddress->id, $memberAddressData->member_id);
        }

        $memberAddress = $memberAddressQueries->addAddress($memberAddressData);

        return [
            'member_address' => new MemberAddressResource($memberAddress),
        ];
    }

    public function update(MemberAddressData $memberAddressData, int $memberAddressId): void
    {
        $memberAddressQueries = resolve(MemberAddressQueries::class);
        $memberAddress = $memberAddressQueries->getById($memberAddressId, $memberAddressData->member_id);
        $memberAddressQueries->update($memberAddressData, $memberAddress);
    }

    public function removeAddress(int $memberAddressId): void
    {
        $memberAddressQueries = resolve(MemberAddressQueries::class);
        $memberAddressQueries->delete($memberAddressId);
    }
}

<?php

declare(strict_types=1);

namespace App\Domains\MemberAddress;

use App\Domains\Member\DataObjects\UpdateMemberAddressData;
use App\Domains\MemberAddress\DataObjects\AppMemberAddressData;
use App\Domains\MemberAddress\DataObjects\EcommerceMemberAddressData;
use App\Domains\MemberAddress\DataObjects\MemberAddressData;
use App\Models\City;
use App\Models\Member;
use App\Models\MemberAddress;
use Illuminate\Support\Collection;

class MemberAddressQueries
{
    public function addNew(array $memberAddressRecord): void
    {
        // If we have a city name but no city_id, try to find the matching city
        if (isset($memberAddressRecord['city']) && ! isset($memberAddressRecord['city_id'])) {
            $city = City::where('name', 'like', '%' . $memberAddressRecord['city'] . '%')->first();
            if ($city) {
                $memberAddressRecord['city_id'] = $city->id;
                $memberAddressRecord['state_id'] = $city->state_id;
                $memberAddressRecord['country_id'] = $city->country_id;
            }

            // Store the city name in city_name field
            $memberAddressRecord['city_name'] = $memberAddressRecord['city'];
            unset($memberAddressRecord['city']);
        }

        MemberAddress::create($memberAddressRecord);
    }

    public function deleteMemberAddress(Member $member): void
    {
        $member->memberAddresses()->delete();
    }

    public function getMemberAddressDetails(int $memberId): Collection
    {
        return MemberAddress::query()
            ->select(
                'id',
                'member_id',
                'name',
                'first_name',
                'last_name',
                'contact_mobile_number',
                'contact_email',
                'address_line_1',
                'address_line_2',
                'city_name',
                'area_code',
                'is_primary',
                'country_id',
                'state_id',
                'city_id',
                'created_at',
                'updated_at'
            )
            ->with(['country:id,name', 'state:id,name', 'city:id,name'])
            ->where('member_id', $memberId)
            ->get();
    }

    public function getById(int $memberAddressId, int $memberId): MemberAddress
    {
        return MemberAddress::where('member_id', $memberId)
            ->with(['country:id,name', 'state:id,name', 'city:id,name'])
            ->findOrFail($memberAddressId);
    }

    public function getByIdAndIsPrimary(int $memberId): ?MemberAddress
    {
        return MemberAddress::where('member_id', $memberId)->where('is_primary', true)->first();
    }

    public function updateForPos(int $memberId, array $memberAddressRecord): void
    {
        $memberAddress = MemberAddress::select(
            'id',
            'member_id',
            'address_line_1',
            'address_line_2',
            'city_name',
            'area_code'
        )
            ->where('member_id', $memberId)
            ->where('is_primary', true)
            ->first();

        if ($memberAddress) {
            // Handle city data
            if (isset($memberAddressRecord['city'])) {
                $city = City::where('name', 'like', '%' . $memberAddressRecord['city'] . '%')->first();
                if ($city) {
                    $memberAddressRecord['city_id'] = $city->id;
                    $memberAddressRecord['state_id'] = $city->state_id;
                    $memberAddressRecord['country_id'] = $city->country_id;
                }

                $memberAddressRecord['city_name'] = $memberAddressRecord['city'];
                unset($memberAddressRecord['city']);
            }

            $memberAddress->update($memberAddressRecord);
        }
    }

    public function updateMemberAddress(UpdateMemberAddressData $updateMemberAddressData, int $memberId): void
    {
        foreach ($updateMemberAddressData->member_addresses as $memberAddress) {
            MemberAddress::create([
                'member_id' => $memberId,
                'name' => $memberAddress['name'],
                'contact_mobile_number' => $memberAddress['contact_mobile_number'],
                'contact_email' => $memberAddress['contact_email'],
                'address_line_1' => $memberAddress['address_line_1'],
                'address_line_2' => $memberAddress['address_line_2'],
                'city' => $memberAddress['city'],
                'area_code' => $memberAddress['area_code'],
                'is_primary' => $memberAddress['is_primary'],
            ]);
        }
    }

    public function getBasicColumnNames(): string
    {
        return 'id,member_id,name,first_name,last_name,contact_mobile_number,contact_email,address_line_1,address_line_2,city_name,area_code,is_primary,country_id,state_id,city_id';
    }

    public function isPrimary(int $memberId): ?MemberAddress
    {
        return MemberAddress::select('id', 'member_id', 'is_primary')
        ->where('member_id', $memberId)
        ->where('is_primary', true)
        ->first();
    }

    public function updatePrimaryKey(int $memberAddressId, int $memberId): void
    {
        $memberAddress = $this->getById($memberAddressId, $memberId);
        $memberAddress->is_primary = false;
        $memberAddress->save();
    }

    public function addAddress(MemberAddressData $memberAddressData): MemberAddress
    {
        $memberAddressRecord = $memberAddressData->all();

        return MemberAddress::create($memberAddressRecord);
    }

    public function update(MemberAddressData $memberAddressData, MemberAddress $memberAddress): void
    {
        $memberAddressRecord = $memberAddressData->all();

        // Handle city data
        if (isset($memberAddressRecord['city']) && ! isset($memberAddressRecord['city_id'])) {
            $city = City::where('name', 'like', '%' . $memberAddressRecord['city'] . '%')->first();
            if ($city) {
                $memberAddressRecord['city_id'] = $city->id;
                $memberAddressRecord['state_id'] = $city->state_id;
                $memberAddressRecord['country_id'] = $city->country_id;
            }

            $memberAddressRecord['city_name'] = $memberAddressRecord['city'];
            unset($memberAddressRecord['city']);
        }

        $memberAddress->update($memberAddressRecord);
    }

    public function updateAddress(int $memberAddressId, array $memberAddressRecord): void
    {
        $memberAddress = MemberAddress::find($memberAddressId);

        if (! $memberAddress) {
            return;
        }

        $memberAddress->update($memberAddressRecord);
    }

    public function delete(int $memberAddressId): void
    {
        $memberAddress = MemberAddress::select('id', 'member_id', 'is_primary')->findOrFail($memberAddressId);
        $memberAddress->delete();
    }

    public function addAddressForMemberApp(AppMemberAddressData $memberAddressData, int $memberId): MemberAddress
    {
        $memberAddressRecord = $memberAddressData->all();
        $memberAddressRecord['member_id'] = $memberId;
        $memberAddressRecord['city_name'] = $memberAddressData->city;

        return MemberAddress::create($memberAddressRecord);
    }

    public function updateForMemberApp(
        AppMemberAddressData $memberAddressData,
        MemberAddress $memberAddress,
        int $memberId
    ): void {
        $memberAddressRecord = $memberAddressData->all();
        $memberAddressRecord['member_id'] = $memberId;
        $memberAddress->update($memberAddressRecord);
    }

    public function deleteOldMember(int $oldMemberId): void
    {
        MemberAddress::query()
            ->where('member_id', $oldMemberId)
            ->delete();
    }

    public function getByOnlyId(int $memberAddressId): MemberAddress
    {
        return MemberAddress::select(
            'id',
            'member_id',
            'name',
            'first_name',
            'last_name',
            'contact_mobile_number',
            'contact_email',
            'address_line_1',
            'address_line_2',
            'city_name',
            'area_code',
            'is_primary',
            'country_id',
            'state_id',
        )
        ->findOrFail($memberAddressId);
    }

    public function addAddressForEcommerce(
        EcommerceMemberAddressData $ecommerceMemberAddressData,
        int $memberId
    ): MemberAddress {
        $memberAddressRecord = $ecommerceMemberAddressData->all();
        $memberAddressRecord['member_id'] = $memberId;
        unset($memberAddressRecord['external_member_id'], $memberAddressRecord['external_member_address_id']);

        return MemberAddress::create($memberAddressRecord);
    }

    public function updateForEcommerce(
        EcommerceMemberAddressData $ecommerceMemberAddressData,
        int $memberAddressId
    ): void {
        $memberAddress = MemberAddress::findOrFail($memberAddressId);
        $memberAddressRecord = $ecommerceMemberAddressData->all();

        unset($memberAddressRecord['external_member_id'], $memberAddressRecord['external_member_address_id']);

        $memberAddress->update($memberAddressRecord);
    }

    public function deleteAddressById(int $memberAddressId): void
    {
        $memberAddresses = MemberAddress::select('id')->find($memberAddressId);

        if ($memberAddresses) {
            $memberAddresses->delete();
        }
    }

    public function refresh(MemberAddress $memberAddresses): MemberAddress
    {
        return $memberAddresses->refresh();
    }
}

<?php

declare(strict_types=1);

namespace App\Domains\Member\Resources;

use App\Domains\Member\Enums\Genders;
use App\Domains\Member\Enums\Races;
use App\Domains\Member\Enums\Titles;
use App\Domains\Member\Enums\Types;
use App\Domains\MemberGroup\DataPreparer\MemberGroupDataPreparer;
use App\Domains\Sale\DataPreparer\UserDataPreparer;
use App\Models\Company;
use App\Models\Location;
use App\Models\Member;
use App\Models\MemberAddress;
use App\Models\Membership;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class MemberAppListApiResource extends JsonResource
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
        $member = $this->resource;

        /** @var Carbon $registeredAt */
        $registeredAt = $member->created_at;

        /** @var ?Location $location */
        $location = $member->createdInLocation;

        /** @var Membership $membership */
        $membership = $member->membership;

        /** @var Company $company */
        $company = $member->company;

        /** @var Collection $memberAddresses */
        $memberAddresses = $member->memberAddresses;

        /** @var MemberAddress|null $primaryAddress */
        $primaryAddress = $member->primaryMemberAddress;

        $userDataPreparer = resolve(UserDataPreparer::class);
        $memberGroupDataPreparer = resolve(MemberGroupDataPreparer::class);

        return [
            'id' => $member->id,
            'employee_id' => $member->employee_id,
            'type_details' => $member->type_id ? [
                'id' => $member->type_id,
                'name' => Types::getFormattedCaseName($member->type_id),
                'key' => Types::getCaseNameByValue($member->type_id),
            ] : null,
            'title_details' => $member->title_id ? [
                'id' => $member->title_id,
                'name' => Titles::getFormattedCaseName($member->title_id),
                'key' => Titles::getCaseNameByValue($member->title_id),
            ] : null,
            'gender_details' => $member->gender_id ? [
                'id' => $member->gender_id,
                'name' => Genders::getFormattedCaseName($member->gender_id),
                'key' => Genders::getCaseNameByValue($member->gender_id),
            ] : null,
            'race_details' => $member->race_id ? [
                'id' => $member->race_id,
                'name' => Races::getFormattedCaseName($member->race_id),
                'key' => Races::getCaseNameByValue($member->race_id),
            ] : null,
            'group' => $memberGroupDataPreparer->getMemberGroup($member),
            'groups' => $memberGroupDataPreparer->getMemberGroups($member),
            'first_name' => $member->first_name,
            'last_name' => $member->last_name,
            'mobile_number' => $member->mobile_number,
            'email' => $member->email,
            'address_line_1' => $primaryAddress ? $primaryAddress->address_line_1 : null,
            'address_line_2' => $primaryAddress ? $primaryAddress->address_line_2 : null,
            'city' => $primaryAddress ? $primaryAddress->city : null,
            'area_code' => $primaryAddress ? $primaryAddress->area_code : null,
            'member_addresses' => $userDataPreparer->getMemberAddressDetails($memberAddresses),
            'date_of_birth' => $member->date_of_birth,
            'total_orders' => (float) $member->total_orders,
            'spent_till_now' => (float) $member->spent_till_now,
            'last_purchase_date' => $member->last_purchase_date ? $member->last_purchase_date->format(
                'Y-m-d h:i:s'
            ) : null,
            'company_name' => $member->company_name,
            'company_registration_number' => $member->company_registration_number,
            'company_tax_number' => $member->company_tax_number,
            'company_phone' => $member->company_phone,
            'notes' => $member->notes,
            'photo_url' => $member->getDiskBasedFirstMediaUrl('photo'),
            'available_loyalty_points' => (int) $member->loyalty_points,
            'membership_id' => $member->membership_id,
            'registered_at' => $registeredAt->format('Y-m-d H:i:s'),
            'card_number' => $member->card_number,
            'store' => $location?->name,
            'location' => $location?->name,
            'membership' => $membership->name ?? null,
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'code' => $company->code,
            ],
        ];
    }
}

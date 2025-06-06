<?php

declare(strict_types=1);

namespace App\Domains\Member\Resources;

use App\Domains\Member\Enums\Genders;
use App\Domains\Member\Enums\MemberChannelEnum;
use App\Domains\Member\Enums\Races;
use App\Domains\Member\Enums\Titles;
use App\Domains\Member\Enums\Types;
use App\Domains\MemberGroup\DataPreparer\MemberGroupDataPreparer;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Member;
use App\Models\MemberAddress;
use App\Models\Membership;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberListForMergeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Member $member */
        $member = $this->resource;

        /** @var ?Membership $membership */
        $membership = $member->membership;

        /** @var ?Location $location */
        $location = $member->createdInLocation;

        /** @var MemberAddress $memberAddresses */
        $memberAddresses = $member->memberAddresses;

        /** @var ?Employee $employee */
        $employee = $member->employee;

        /** @var ?Voucher $birthdayVoucher */
        $birthdayVoucher = $member->birthdayVoucher;

        $memberGroupDataPreparer = resolve(MemberGroupDataPreparer::class);

        return [
            'id' => $member->id,
            'type' => $member->type_id ? Types::getFormattedCaseName($member->type_id) : null,
            'title' => $member->title_id ? Titles::getFormattedCaseName($member->title_id) : null,
            'race' => $member->race_id ? Races::getFormattedCaseName($member->race_id) : null,
            'channel' => $member->channel_id ? MemberChannelEnum::getFormattedCaseName($member->channel_id) : null,
            'first_name' => $member->first_name,
            'last_name' => $member->last_name,
            'gender' => $member->gender_id ? Genders::getFormattedCaseName($member->gender_id) : null,
            'group' => $member->memberGroupMembers->first()?->memberGroup?->name,
            'groups' => $memberGroupDataPreparer->getMemberGroups($member),
            'date_of_birth' => $member->date_of_birth,
            'mobile_number' => $member->mobile_number,
            'email' => $member->email,
            'company_name' => $member->company_name,
            'company_registration_number' => $member->company_registration_number,
            'company_tax_number' => $member->company_tax_number,
            'company_address' => $member->company_address,
            'company_phone' => $member->company_phone,
            'created_by_type' => $member->created_by_type,
            'created_location' => $location?->getNameWithCode(),
            'last_purchase_date' => $member->last_purchase_date,
            'notes' => $member->notes,
            'spent_till_now' => $member->spent_till_now,
            'loyalty_points' => $member->loyalty_points,
            'membership' => $membership ? $membership->name : null,
            'card_number' => $member->card_number,
            'birthday_voucher_last_generated_at' => $member->birthday_voucher_last_generated_at,
            'last_birthday_voucher' => $birthdayVoucher ? $birthdayVoucher->number : null,
            'welcome_member_voucher_generated_at' => $member->welcome_member_voucher_generated_at,
            'welcome_member_voucher' => $this->getWelcomeMemberVoucher($member),
            'total_redeemed_points' => $member->total_redeemed_points,
            'total_earned_points' => $member->total_earned_points,
            'total_expired_points' => $member->total_expired_points,
            'total_sales' => $member->total_sales,
            'employee' => $employee ? $employee->getFullName() : null,
            'pic_name' => $member->pic_name,
            'pic_contact' => $member->pic_contact,
            'address' => $memberAddresses,
        ];
    }

    public function getWelcomeMemberVoucher(Member $member): ?string
    {
        $vouchers = $member->vouchers;

        if ($vouchers->isEmpty()) {
            return null;
        }

        $welcomeMemberVoucher = $vouchers->firstWhere('id', $member->welcome_member_voucher_id);

        if (! $welcomeMemberVoucher) {
            return null;
        }

        return $welcomeMemberVoucher->number;
    }
}

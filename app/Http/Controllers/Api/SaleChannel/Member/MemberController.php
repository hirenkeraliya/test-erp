<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\SaleChannel\Member;

use App\Domains\Member\DataObjects\FirstOrCreateMemberData;
use App\Domains\Member\DataObjects\PaginatedMemberListDataForEcommerce;
use App\Domains\Member\DataObjects\SaleChannelMemberData;
use App\Domains\Member\DataObjects\SaleChannelRegisterMemberData;
use App\Domains\Member\DataObjects\UpdateMemberEcommerceData;
use App\Domains\Member\Enums\Genders;
use App\Domains\Member\Enums\MemberChannelEnum;
use App\Domains\Member\MemberQueries;
use App\Domains\Member\Resources\EcommerceCreateMemberResource;
use App\Domains\Member\Resources\EcommerceMemberResource;
use App\Domains\MemberChannelReference\MemberChannelReferenceQueries;
use App\Domains\SaleChannel\Enums\SaleChannelTypes;
use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\SaleChannel;
use Illuminate\Http\Request;

class MemberController extends Controller
{
    public function __construct(
        protected MemberQueries $memberQueries
    ) {
    }

    public function registerMember(SaleChannelRegisterMemberData $registerMemberData, Request $request): array
    {
        $saleChannel = $request->user();

        if (! $saleChannel instanceof SaleChannel) {
            abort(401, 'You are not authenticated.');
        }

        $validateData = $registerMemberData->all();
        $validateData['created_location_id'] = $saleChannel->getDefaultLocationId();
        $validateData['company_id'] = $saleChannel->getCompanyId();

        $validateData['gender_id'] = 'Male' === $registerMemberData->gender ? Genders::MALE->value : Genders::FEMALE->value;

        $member = $this->memberQueries->addNewMemberRegistrationForEcommerce(
            $validateData,
            MemberChannelEnum::E_COMMERCE->value,
            $saleChannel->id
        );

        return [
            'member' => new EcommerceCreateMemberResource($member),
        ];
    }

    public function firstOrCreateMember(FirstOrCreateMemberData $firstOrCreateMemberData, Request $request): array
    {
        $saleChannel = $request->user();

        if (! $saleChannel instanceof SaleChannel) {
            abort(401, 'You are not authenticated.');
        }

        $validateData = $firstOrCreateMemberData->all();

        $member = $this->memberQueries->findMemberByMobileNumber(
            $validateData['mobile_number'],
            $saleChannel->getCompanyId()
        );
        if ($member instanceof Member) {
            return [
                'member' => new EcommerceCreateMemberResource($member),
            ];
        }

        $memberByEmail = $this->memberQueries->findMemberByEmail($validateData['email'], $saleChannel->getCompanyId());
        if ($memberByEmail instanceof Member) {
            return [
                'member' => new EcommerceMemberResource($memberByEmail),
            ];
        }

        $validateData['created_location_id'] = $saleChannel->getDefaultLocationId();
        $validateData['company_id'] = $saleChannel->getCompanyId();

        $member = $this->memberQueries->addNewMemberRegistrationForEcommerce(
            $validateData,
            MemberChannelEnum::E_COMMERCE->value,
            $saleChannel->id
        );

        return [
            'member' => new EcommerceMemberResource($member),
        ];
    }

    public function update(
        int $externalMemberId,
        UpdateMemberEcommerceData $updateMemberEcommerceData,
        Request $request
    ): void {
        $saleChannel = $request->user();

        $validateData = $updateMemberEcommerceData->all();

        if (! $saleChannel instanceof SaleChannel) {
            abort(401, 'You are not authenticated.');
        }

        if (SaleChannelTypes::ECOMMERCE === $saleChannel->type_id) {
            $memberChannelReferenceQueries = resolve(MemberChannelReferenceQueries::class);

            /** @var int $memberId */
            $memberId = $memberChannelReferenceQueries->getByMemberId($externalMemberId, $saleChannel->id);

            /** @var string $mobileNumber */
            $mobileNumber = $updateMemberEcommerceData->mobile_number;

            $memberExists = $this->memberQueries->checkUniqueEmailAndMobileNumber(
                $memberId,
                $updateMemberEcommerceData->email,
                $mobileNumber,
            );

            if ($memberExists) {
                abort(401, 'Email or Mobile number already exists.');
            }

            $validateData['gender_id'] = 'Male' === $updateMemberEcommerceData->gender ? Genders::MALE->value : Genders::FEMALE->value;

            $this->memberQueries->updateMemberForEcommerce($memberId, $validateData);

            return;
        }

        $memberExists = $this->memberQueries->checkUniqueEmailAndMobileNumber(
            $externalMemberId,
            $updateMemberEcommerceData->email,
            $validateData['mobile_number'],
        );

        if ($memberExists) {
            abort(401, 'Email or Mobile number already exists.');
        }

        $this->memberQueries->updateMemberForEcommerce($externalMemberId, $validateData);
    }

    public function memberExists(Request $request, SaleChannelMemberData $saleChannelMemberData): array
    {
        $saleChannel = $request->user();

        if (! $saleChannel instanceof SaleChannel) {
            abort(401, 'You are not authenticated.');
        }

        $memberExists = $this->memberQueries->existsByMobileOrEmail(
            $saleChannelMemberData->mobile_number ?? null,
            $saleChannelMemberData->email ?? null,
            $saleChannel->getCompanyId()
        );

        return [
            'member_available' => $memberExists,
        ];
    }

    public function memberIsExists(Request $request, SaleChannelMemberData $saleChannelMemberData): array
    {
        $saleChannel = $request->user();

        if (! $saleChannel instanceof SaleChannel) {
            abort(401, 'You are not authenticated.');
        }

        $memberExists = $this->memberQueries->existsByMobileOrEmail(
            $saleChannelMemberData->mobile_number ?? null,
            $saleChannelMemberData->email ?? null,
            $saleChannel->getCompanyId()
        );

        return [
            'member_available' => $memberExists,
        ];
    }

    public function getPaginatedList(
        PaginatedMemberListDataForEcommerce $paginatedMemberListDataForEcommerce,
        Request $request
    ): array {
        $saleChannel = $request->user();

        if (! $saleChannel instanceof SaleChannel) {
            abort(401, 'You are not authenticated.');
        }

        $filteredData = [
            'per_page' => $paginatedMemberListDataForEcommerce->per_page,
            'sort_by' => $paginatedMemberListDataForEcommerce->sort_by,
            'search_text' => $paginatedMemberListDataForEcommerce->search_text,
            'mobile_number' => $paginatedMemberListDataForEcommerce->mobile_number,
            'email' => $paginatedMemberListDataForEcommerce->email,
            'sort_direction' => $paginatedMemberListDataForEcommerce->sort_direction,
            'after_updated_at' => $paginatedMemberListDataForEcommerce->after_updated_at,
        ];

        $members = $this->memberQueries->getPaginatedListForEcommerce($filteredData, $saleChannel->getCompanyId());

        return [
            'members' => EcommerceMemberResource::collection($members),
            'total_records' => $members->total(),
            'last_page' => $members->lastPage(),
            'current_page' => $members->currentPage(),
            'per_page' => $members->perPage(),
        ];
    }

    public function deleteMember(Request $request, int $memberId): void
    {
        $saleChannel = $request->user();

        if (! $saleChannel instanceof SaleChannel) {
            abort(401, 'You are not authenticated.');
        }

        $member = $this->memberQueries->getByIdForEcommerce($memberId, $saleChannel->getCompanyId());
        $this->memberQueries->deleteMember($member);
    }

    public function getMemberByMobileNumber(Request $request): array
    {
        $saleChannel = $request->user();

        if (! $saleChannel instanceof SaleChannel) {
            abort(401, 'You are not authenticated.');
        }

        $request->validate([
            'mobile_number' => ['required'],
        ]);

        $normalizedNumber = preg_replace('/[^0-9]/', '', $request->mobile_number);

        $members = $this->memberQueries->getMembersByMobileNumberForEcommerce(
            $normalizedNumber,
            $saleChannel->getCompanyId()
        );

        return [
            'members' => EcommerceMemberResource::collection($members),
        ];
    }

    public function fetchMemberByMobile(Request $request): array
    {
        $saleChannel = $request->user();

        if (! $saleChannel instanceof SaleChannel) {
            abort(401, 'You are not authenticated.');
        }

        $request->validate([
            'mobile_number' => ['required'],
        ]);

        $normalizedNumber = preg_replace('/[^0-9]/', '', $request->mobile_number);

        $members = $this->memberQueries->getMembersByMobileNumberForEcommerce(
            $normalizedNumber,
            $saleChannel->getCompanyId()
        );

        return [
            'members' => EcommerceMemberResource::collection($members),
        ];
    }
}

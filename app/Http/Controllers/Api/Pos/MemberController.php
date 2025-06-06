<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Pos;

use App\CommonFunctions;
use App\Domains\Location\LocationQueries;
use App\Domains\Member\DataObjects\PaginatedMemberListDataForPos;
use App\Domains\Member\DataObjects\PosMemberData;
use App\Domains\Member\Enums\Genders;
use App\Domains\Member\Enums\MemberChannelEnum;
use App\Domains\Member\Enums\Races;
use App\Domains\Member\Enums\Status;
use App\Domains\Member\Enums\Titles;
use App\Domains\Member\Enums\Types;
use App\Domains\Member\MemberQueries;
use App\Domains\Member\Resources\PosMemberEmployeeResource;
use App\Domains\Member\Resources\PosMemberResource;
use App\Http\Controllers\Controller;
use App\Models\Cashier;
use Illuminate\Http\Request;

class MemberController extends Controller
{
    /**
     * @return array<string, mixed>
     */
    public function getPaginatedList(
        Request $request,
        PaginatedMemberListDataForPos $paginatedMemberListDataForPos
    ): array {
        $filteredData = [
            'per_page' => $paginatedMemberListDataForPos->per_page,
            'sort_by' => $paginatedMemberListDataForPos->sort_by,
            'search_text' => $paginatedMemberListDataForPos->search_text,
            'sort_direction' => $paginatedMemberListDataForPos->sort_direction,
            'after_updated_at' => $paginatedMemberListDataForPos->after_updated_at,
        ];

        /** @var Cashier $cashier */
        $cashier = $request->user();

        $companyId = CommonFunctions::getCashierCompanyId($cashier);

        $memberQueries = resolve(MemberQueries::class);
        $members = $memberQueries->getPaginatedListForPos($filteredData, $companyId);

        return [
            'members' => PosMemberResource::collection($members),
            'total_records' => $members->total(),
            'last_page' => $members->lastPage(),
            'current_page' => $members->currentPage(),
            'per_page' => $members->perPage(),
        ];
    }

    public function getEmployeeMembers(
        Request $request,
        PaginatedMemberListDataForPos $paginatedMemberListDataForPos
    ): array {
        $filteredData = [
            'per_page' => $paginatedMemberListDataForPos->per_page,
            'sort_by' => $paginatedMemberListDataForPos->sort_by,
            'search_text' => $paginatedMemberListDataForPos->search_text,
            'sort_direction' => $paginatedMemberListDataForPos->sort_direction,
            'after_updated_at' => $paginatedMemberListDataForPos->after_updated_at,
        ];

        /** @var Cashier $cashier */
        $cashier = $request->user();

        $companyId = CommonFunctions::getCashierCompanyId($cashier);

        $memberQueries = resolve(MemberQueries::class);
        $members = $memberQueries->fetchMembersListForPos($filteredData, $companyId);

        return [
            'members' => PosMemberEmployeeResource::collection($members),
            'total_records' => $members->total(),
            'last_page' => $members->lastPage(),
            'current_page' => $members->currentPage(),
            'per_page' => $members->perPage(),
        ];
    }

    /**
     * @return array<string, mixed[]>
     */
    public function getGenders(): array
    {
        return [
            'genders' => Genders::getList(),
        ];
    }

    /**
     * @return array<string, mixed[]>
     */
    public function getStatuses(): array
    {
        return [
            'statuses' => Status::getList(),
        ];
    }

    /**
     * @return array<string, mixed[]>
     */
    public function getRaces(): array
    {
        return [
            'races' => Races::getList(),
        ];
    }

    /**
     * @return array<string, mixed[]>
     */
    public function getTitles(): array
    {
        return [
            'titles' => Titles::getList(),
        ];
    }

    /**
     * @return array<string, mixed[]>
     */
    public function getTypes(): array
    {
        return [
            'types' => Types::getList(),
        ];
    }

    /**
     * @return array<string, PosMemberResource>
     */
    public function store(Request $request): array
    {
        /** @var Cashier $cashier */
        $cashier = $request->user();

        $companyId = CommonFunctions::getCashierCompanyId($cashier);

        if (! $cashier->getCounterUpdateId()) {
            abort(412, 'The counter has not been opened yet.');
        }

        $locationQueries = resolve(LocationQueries::class);
        $locationId = $locationQueries->getLocationByCountersCounterUpdateId($cashier->getCounterUpdateId())->getKey();

        $posMemberData = new PosMemberData(
            type_id: (int) $request->type_id,
            title_id: (int) $request->title_id,
            race_id: (int) $request->race_id,
            first_name: $request->first_name,
            last_name: $request->last_name,
            gender_id: (int) $request->gender_id,
            date_of_birth: $request->date_of_birth,
            mobile_number: $request->mobile_number,
            email: $request->email,
            address_line_1: $request->address_line_1,
            address_line_2: $request->address_line_2,
            city_name: $request->city,
            area_code: $request->area_code,
            company_name: $request->company_name,
            company_registration_number: $request->company_registration_number,
            company_tax_number: $request->company_tax_number,
            company_phone: $request->company_phone,
            notes: $request->notes,
            created_location_id: $locationId,
            card_number: $request->card_number,
        );

        $request->validate($posMemberData->rules($request, $companyId));

        $memberQueries = resolve(MemberQueries::class);
        $member = $memberQueries->addNew($posMemberData, $companyId, $cashier, MemberChannelEnum::POS->value);
        $member = $memberQueries->loadRelationsForPos($member);

        return [
            'member' => new PosMemberResource($member),
        ];
    }

    /**
     * @return array<string, PosMemberResource>
     */
    public function getMember(Request $request, int $memberId): array
    {
        /** @var Cashier $cashier */
        $cashier = $request->user();

        $companyId = CommonFunctions::getCashierCompanyId($cashier);

        $memberQueries = resolve(MemberQueries::class);
        $memberDetails = $memberQueries->getMemberDetailsForPos($memberId, $companyId);

        return [
            'member' => new PosMemberResource($memberDetails),
        ];
    }

    public function update(Request $request, int $memberId): void
    {
        /** @var Cashier $cashier */
        $cashier = $request->user();

        $companyId = CommonFunctions::getCashierCompanyId($cashier);

        if (! $cashier->getCounterUpdateId()) {
            abort(412, 'The counter has not been opened yet.');
        }

        $createdLocationId = (int) $request->created_store_id > 0 ? $request->created_store_id : $request->created_location_id;

        $posMemberData = new PosMemberData(
            type_id: (int) $request->type_id,
            title_id: (int) $request->title_id,
            race_id: (int) $request->race_id,
            first_name: $request->first_name,
            last_name: $request->last_name,
            gender_id: (int) $request->gender_id,
            date_of_birth: $request->date_of_birth,
            mobile_number: $request->mobile_number,
            email: $request->email,
            address_line_1: $request->address_line_1,
            address_line_2: $request->address_line_2,
            city_name: $request->city,
            area_code: $request->area_code,
            company_name: $request->company_name,
            company_registration_number: $request->company_registration_number,
            company_tax_number: $request->company_tax_number,
            company_phone: $request->company_phone,
            notes: $request->notes,
            created_location_id: (int) $createdLocationId,
        );

        $request->validate($posMemberData->rules($request, $companyId));

        $memberQueries = resolve(MemberQueries::class);
        $memberQueries->updatePosMember($posMemberData, $memberId, $companyId);
    }

    public function getMobileNumberRegex(): array
    {
        return [
            'regex' => config('app.mobile_number_regex'),
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Promoter;

use App\Domains\Employee\EmployeeQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\Member\DataObjects\AddMemberDataForPromoterApi;
use App\Domains\Member\DataObjects\MemberListDataForPromoterApi;
use App\Domains\Member\DataObjects\PromoterMemberData;
use App\Domains\Member\Enums\MemberChannelEnum;
use App\Domains\Member\Enums\Types;
use App\Domains\Member\MemberQueries;
use App\Domains\Member\Resources\ApiMemberResource;
use App\Domains\Member\Resources\MemberListApiResource;
use App\Domains\Member\Services\MemberService;
use App\Http\Controllers\Controller;
use App\Models\Promoter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class MemberController extends Controller
{
    public function store(Request $request, PromoterMemberData $promoterMemberData, int $locationId): array
    {
        DB::beginTransaction();

        try {
            /** @var Promoter $promoter */
            $promoter = $request->user();

            $validateData = $promoterMemberData->all();

            $memberQueries = resolve(MemberQueries::class);
            $locationQueries = resolve(LocationQueries::class);
            $companyId = $locationQueries->getCompanyIdOfStore($locationId);
            $member = $memberQueries->addNewMemberByPromoter(
                $validateData,
                $locationId,
                $companyId,
                $promoter,
                MemberChannelEnum::PROMOTER->value
            );

            DB::commit();

            return [
                'member' => new ApiMemberResource($member),
            ];
        } catch (Throwable $throwable) {
            Log::error([
                'save member api' => $throwable->getMessage(),
                'Full error' => [$throwable],
            ]);
            DB::rollBack();

            abort(417, 'An error occurred. Please try again.');
        }
    }

    public function memberStaticDetails(): array
    {
        return [
            'types' => Types::getList(),
        ];
    }

    public function getPaginatedList(
        Request $request,
        MemberListDataForPromoterApi $memberListDataForPromoterApi,
    ): array {
        $filteredData = [
            'per_page' => $memberListDataForPromoterApi->per_page,
            'sort_by' => $memberListDataForPromoterApi->sort_by,
            'search_text' => $memberListDataForPromoterApi->search_text,
            'sort_direction' => $memberListDataForPromoterApi->sort_direction,
        ];

        /** @var Promoter $promoter */
        $promoter = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);

        /** @var int $companyId */
        $companyId = $employeeQueries->getEmployeeCompanyId($promoter->employee_id);

        $memberQueries = resolve(MemberQueries::class);
        $members = $memberQueries->getPaginatedListForStoreManagerAndPromoterApp($filteredData, $companyId);

        return [
            'members' => MemberListApiResource::collection($members),
            'total_records' => $members->total(),
            'last_page' => $members->lastPage(),
            'current_page' => $members->currentPage(),
            'per_page' => $members->perPage(),
        ];
    }

    public function addMember(AddMemberDataForPromoterApi $addMemberDataForPromoterApi, Request $request): array
    {
        DB::beginTransaction();

        try {
            /** @var Promoter $promoter */
            $promoter = $request->user();
            $employeeQueries = resolve(EmployeeQueries::class);

            /** @var int $companyId */
            $companyId = $employeeQueries->getEmployeeCompanyId($promoter->employee_id);
            $memberData = $addMemberDataForPromoterApi->all();
            $memberData['company_id'] = $companyId;

            $memberQueries = resolve(MemberQueries::class);
            if (null === $memberData['card_number']) {
                $memberData['card_number'] = $memberQueries->generateUniqueCardNumber();
            }

            $memberData['created_location_id'] = $addMemberDataForPromoterApi->created_store_id ?? $addMemberDataForPromoterApi->created_location_id;
            unset($memberData['created_store_id']);

            $member = $memberQueries->create($memberData);

            DB::commit();

            return [
                'member' => new ApiMemberResource($member),
            ];
        } catch (Throwable $throwable) {
            Log::error([
                'add member api' => $throwable->getMessage(),
                'Full error' => [$throwable],
            ]);
            DB::rollBack();

            abort(417, 'An error occurred. Please try again.');
        }
    }

    public function getMemberPreference(int $memberId, Request $request): array
    {
        /** @var Promoter $promoter */
        $promoter = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);

        /** @var int $companyId */
        $companyId = $employeeQueries->getEmployeeCompanyId($promoter->employee_id);

        $memberService = resolve(MemberService::class);

        [$preferencesColor, $preferencesSize, $preferencesCategory,, $preferencesMasterProduct] = $memberService->getMemberPreferencesRecordsForApp(
            $memberId,
            $companyId
        );

        return [
            'preference_color' => $preferencesColor,
            'preference_size' => $preferencesSize,
            'preference_category' => $preferencesCategory,
            'preference_master_product' => $preferencesMasterProduct,
        ];
    }
}

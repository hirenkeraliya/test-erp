<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\StoreManager;

use App\Domains\Employee\EmployeeQueries;
use App\Domains\Member\DataObjects\AddMemberDataForStoreManagerApi;
use App\Domains\Member\DataObjects\StoreManagerApiMemberData;
use App\Domains\Member\Enums\Types;
use App\Domains\Member\MemberQueries;
use App\Domains\Member\Resources\ApiMemberResource;
use App\Domains\Member\Resources\MemberListApiResource;
use App\Domains\Member\Services\MemberService;
use App\Http\Controllers\Controller;
use App\Models\StoreManager;
use Illuminate\Http\Request;

class MemberController extends Controller
{
    /**
     * @return array<string, mixed>
     */
    public function getPaginatedList(Request $request, StoreManagerApiMemberData $storeManagerApiMemberData): array
    {
        $filteredData = [
            'per_page' => $storeManagerApiMemberData->per_page,
            'sort_by' => $storeManagerApiMemberData->sort_by,
            'search_text' => $storeManagerApiMemberData->search_text,
            'sort_direction' => $storeManagerApiMemberData->sort_direction,
        ];

        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);

        /** @var int $companyId */
        $companyId = $employeeQueries->getEmployeeCompanyId($storeManager->employee_id);

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

    public function addMember(AddMemberDataForStoreManagerApi $addMemberDataForStoreManagerApi, Request $request): array
    {
        /** @var StoreManager $storeManager */
        $storeManager = $request->user();
        $employeeQueries = resolve(EmployeeQueries::class);

        /** @var int $companyId */
        $companyId = $employeeQueries->getEmployeeCompanyId($storeManager->employee_id);
        $memberData = $addMemberDataForStoreManagerApi->all();
        $memberData['company_id'] = $companyId;
        $memberData['created_location_id'] = $memberData['created_store_id'] ?? $memberData['created_location_id'];
        unset($memberData['created_store_id']);

        $memberQueries = resolve(MemberQueries::class);
        if (null === $memberData['card_number']) {
            $memberData['card_number'] = $memberQueries->generateUniqueCardNumber();
        }

        $member = $memberQueries->create($memberData);

        return [
            'member' => new ApiMemberResource($member),
        ];
    }

    public function memberStaticDetails(): array
    {
        return [
            'types' => Types::getList(),
        ];
    }

    public function getMemberPreference(int $memberId, Request $request): array
    {
        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);

        /** @var int $companyId */
        $companyId = $employeeQueries->getEmployeeCompanyId($storeManager->employee_id);

        $memberService = resolve(MemberService::class);

        [$preferencesColor, $preferencesSize, $preferencesCategory, $preferencesProduct] = $memberService->getMemberPreferencesRecordsForApp(
            $memberId,
            $companyId
        );

        $memberQueries = resolve(MemberQueries::class);

        $memberSpentTillNow = $memberQueries->getLatestSpentTillNow($companyId, $memberId);

        return [
            'preference_color' => $preferencesColor,
            'preference_size' => $preferencesSize,
            'preference_category' => $preferencesCategory,
            'preference_product' => $preferencesProduct,
            'life_time_value' => $memberSpentTillNow,
        ];
    }
}

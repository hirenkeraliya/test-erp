<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\Category\CategoryQueries;
use App\Domains\Color\ColorQueries;
use App\Domains\Common\Jobs\EmailVerificationJob;
use App\Domains\Company\CompanyQueries;
use App\Domains\Country\CountryQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\LoyaltyPoint\Services\LoyaltyPointService;
use App\Domains\LoyaltyPointUpdate\LoyaltyPointUpdateQueries;
use App\Domains\Member\DataObjects\MemberData;
use App\Domains\Member\DataObjects\UpdateLoyaltyPointData;
use App\Domains\Member\DataObjects\UpdateMemberAddressData;
use App\Domains\Member\Enums\ConditionOperatorTypes;
use App\Domains\Member\Enums\FilterStatus;
use App\Domains\Member\Enums\Genders;
use App\Domains\Member\Enums\MemberChannelEnum;
use App\Domains\Member\Enums\Preferences;
use App\Domains\Member\Enums\PurchaseFilterTypes;
use App\Domains\Member\Enums\Races;
use App\Domains\Member\Enums\StaticMembers;
use App\Domains\Member\Enums\Status;
use App\Domains\Member\Enums\Titles;
use App\Domains\Member\Enums\Types;
use App\Domains\Member\Exports\MemberExport;
use App\Domains\Member\Exports\MembersBulkUpdateExport;
use App\Domains\Member\Jobs\MemberMergeJob;
use App\Domains\Member\Jobs\MemberSyncMainJob;
use App\Domains\Member\Jobs\MemberUpdatePointsAndTotalSalesJob;
use App\Domains\Member\Jobs\SendEmailsJob;
use App\Domains\Member\MemberQueries;
use App\Domains\Member\Resources\AdminMemberFilterListResource;
use App\Domains\Member\Resources\AdminMemberListResource;
use App\Domains\Member\Resources\MemberAddressResource;
use App\Domains\Member\Resources\MemberDetailListResource;
use App\Domains\Member\Resources\MemberListForMergeResource;
use App\Domains\Member\Resources\MemberLoyaltyPointHistoryResource;
use App\Domains\Member\Resources\MemberSaleDetailListResource;
use App\Domains\Member\Resources\MemberSaleReturnDetailListResource;
use App\Domains\Member\Services\MemberService;
use App\Domains\MemberAddress\MemberAddressQueries;
use App\Domains\MemberGroup\Jobs\CreateUpdateMemberSyncWithMemberGroupJob;
use App\Domains\MemberGroup\MemberGroupQueries;
use App\Domains\MemberGroupMember\MemberGroupMemberQueries;
use App\Domains\Membership\MembershipQueries;
use App\Domains\Permission\Enums\PermissionList;
use App\Domains\Sale\SaleQueries;
use App\Domains\SaleChannel\Services\SaleChannelService;
use App\Domains\SaleReturn\SaleReturnQueries;
use App\Domains\Size\SizeQueries;
use App\Domains\SyncTransaction\Enums\SyncTypes;
use App\Domains\SyncTransaction\SyncTransactionQueries;
use App\Exceptions\RedirectBackWithErrorException;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Member;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class MemberController extends Controller
{
    public function __construct(
        protected MemberQueries $memberQueries
    ) {
    }

    public function index(): Response
    {
        $companyId = session('admin_company_id');

        $locationQueries = resolve(LocationQueries::class);
        $locations = $locationQueries->getStoreWithBasicColumns($companyId);

        $membershipQueries = resolve(MembershipQueries::class);
        $memberships = $membershipQueries->getWithBasicColumns($companyId);

        $memberGroupQueries = resolve(MemberGroupQueries::class);
        $memberGroups = $memberGroupQueries->getAllByCompanyId(session('admin_company_id'));

        $categoryQueries = resolve(CategoryQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $syncTransactionQueries = resolve(SyncTransactionQueries::class);

        $saleChannelService = resolve(SaleChannelService::class);
        $saleChannels = $saleChannelService->getModifySaleChannels(
            SyncTypes::MEMBER->value,
            session('admin_company_id')
        );

        $hasPendingSyncTransaction = $syncTransactionQueries->hasPendingSyncTransaction(
            SyncTypes::MEMBER->value,
            session('admin_company_id')
        );

        $countryQueries = resolve(CountryQueries::class);
        $countries = $countryQueries->getAllCountries();

        return Inertia::render('members/Index', [
            'locations' => $locations,
            'memberships' => $memberships,
            'memberGroups' => $memberGroups,
            'staticMembers' => StaticMembers::STATIC_MEMBER->value,
            'statusAll' => FilterStatus::ALL->value,
            'exportPermission' => PermissionList::getExportPermissionName('member'),
            'memberStatuses' => FilterStatus::getList(),
            'preferences' => Preferences::getList(),
            'preferencesStaticUse' => Preferences::getFormattedArrayForStaticUse(),
            'purchaseFilterTypes' => PurchaseFilterTypes::getList(),
            'conditionOperatorTypes' => ConditionOperatorTypes::getList(),
            'categories' => $categoryQueries->getParentByCompanyId($companyId),
            'colors' => $colorQueries->getWithBasicColumns($companyId),
            'sizes' => $sizeQueries->getWithBasicColumns($companyId),
            'saleChannels' => $saleChannels,
            'hasPendingSyncTransaction' => $hasPendingSyncTransaction,
            'countries' => $countries,
        ]);
    }

    /**
     * @return array<string, AnonymousResourceCollection>|array<string, int>
     */
    public function fetchMembers(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'location_ids' => $request->get('location_ids'),
            'membership_ids' => $request->get('membership_ids'),
            'member_group_ids' => $request->get('member_group_ids'),
            'date_range' => $request->get('date_range'),
            'status' => (int) $request->get('status'),
            'product_id' => (int) $request->get('product_id'),
            'preference_id' => (int) $request->get('preference_id'),
            'color_id' => (int) $request->get('color_id'),
            'size_id' => (int) $request->get('size_id'),
            'category_id' => (int) $request->get('category_id'),
            'preferred_date' => $request->get('preferred_date'),
            'preferred_day' => $request->get('preferred_day'),
            'purchase_filter_type_id' => (int) $request->get('purchase_filter_type_id'),
            'condition_operator_type_id' => (int) $request->get('condition_operator_type_id'),
            'purchase_value' => $request->get('purchase_value'),
        ];

        $lengthAwarePaginator = $this->memberQueries->listQueryForMembers($filterData, session('admin_company_id'));

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => AdminMemberListResource::collection($lengthAwarePaginator->getCollection()),
        ];
    }

    public function changeStatus(Request $request): void
    {
        $this->memberQueries->changeStatus($request->memberId);
    }

    public function create(): Response
    {
        return Inertia::render('members/Manage', $this->getCommonRecords());
    }

    public function store(MemberData $memberData, Request $request): RedirectResponse
    {
        DB::beginTransaction();

        try {
            /** @var Admin $admin */
            $admin = $request->user();

            $this->memberQueries->addNewForAdminAndStoreManager(
                $memberData,
                session('admin_company_id'),
                $admin,
                MemberChannelEnum::ADMIN->value
            );

            DB::commit();

            return to_route('admin.members.index')
                ->with('success', 'Member added successfully.');
        } catch (Throwable $throwable) {
            Log::error('Add Member', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);

            DB::rollBack();

            throw new RedirectBackWithErrorException('An error occurred. Please try again.');
        }
    }

    public function edit(int $memberId): Response
    {
        $member = $this->memberQueries->getByIdWithMedia($memberId, session('admin_company_id'));
        $member['photo_url'] = $member->getDiskBasedFirstMediaUrl('photo');
        $member['member_addresses'] = $member->memberAddresses;

        return Inertia::render('members/Manage', [
            'member' => $member,
            ...$this->getCommonRecords(),
        ]);
    }

    public function update(MemberData $memberData, int $memberId): RedirectResponse
    {
        DB::beginTransaction();

        try {
            $this->memberQueries->update($memberData, $memberId, session('admin_company_id'));

            DB::commit();

            return to_route('admin.members.index')
                ->with('success', 'Member updated successfully.');
        } catch (Throwable $throwable) {
            Log::error('Update Member', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);

            DB::rollBack();

            throw new RedirectBackWithErrorException('An error occurred. Please try again.');
        }
    }

    public function updateLoyaltyPoints(UpdateLoyaltyPointData $updateLoyaltyPointData, int $memberId): void
    {
        DB::beginTransaction();

        try {
            $member = $this->memberQueries->getById($memberId, session('admin_company_id'));

            /** @var Admin $admin */
            $admin = Auth::guard('admin')->user();

            $loyaltyPointService = resolve(LoyaltyPointService::class);
            $loyaltyPointService->updateLoyaltyPointsForAdmin($member, $admin, $updateLoyaltyPointData);

            DB::commit();

            MemberUpdatePointsAndTotalSalesJob::dispatch($member->id)->onQueue('medium');
        } catch (Throwable $throwable) {
            Log::error('Update Member', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);

            DB::rollBack();

            abort(417, 'An error occurred. Please try again.');
        }
    }

    public function exportExistingMembers(): BinaryFileResponse
    {
        $members = $this->memberQueries->getMemberWithStore(session('admin_company_id'));

        return Excel::download(new MembersBulkUpdateExport($members), 'members-bulk-update.xlsx');
    }

    /**
     * @return array<string, AnonymousResourceCollection>|array<string, int>
     */
    public function getFilteredMembers(Request $request): array
    {
        $filterData = [
            'search_text' => $request->input('search_text'),
            'number_of_records' => $request->input('number_of_records'),
        ];

        $memberSearch = $this->memberQueries->searchMembersForFilter($filterData, session('admin_company_id'));

        return [
            'members' => AdminMemberFilterListResource::collection($memberSearch),
        ];
    }

    public function exportMembers(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = $this->getExportMembersFilterData($request);

        $companyId = session('admin_company_id');

        $members = $this->memberQueries->getMembersForExport($filterData, $companyId);

        return Excel::download(new MemberExport($members), $filename);
    }

    public function checkMemberExportLimit(Request $request): array
    {
        $filterData = $this->getExportMembersFilterData($request);

        $companyId = session('admin_company_id');

        /** @var Admin $admin */
        $admin = $request->user();

        $memberService = resolve(MemberService::class);

        return $memberService->exportMemberWithJob($admin, $filterData, $companyId);
    }

    public function resendVerificationEmail(int $memberId): RedirectResponse
    {
        $member = $this->memberQueries->getByIdForEmailVerification($memberId, session('admin_company_id'));
        EmailVerificationJob::dispatch($member)->delay(now()->addSeconds(5))->onQueue('high');

        return to_route('admin.members.index')
            ->with('success', 'The verification mail sent successfully.');
    }

    private function getExportMembersFilterData(Request $request): array
    {
        return [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'location_ids' => $request->get('location_ids'),
            'membership_ids' => $request->get('membership_ids'),
            'member_group_ids' => $request->get('member_group_ids'),
            'date_range' => $request->get('date_range'),
            'status' => (int) $request->get('status'),
            'product_id' => (int) $request->get('product_id'),
            'preference_id' => (int) $request->get('preference_id'),
            'color_id' => (int) $request->get('color_id'),
            'size_id' => (int) $request->get('size_id'),
            'category_id' => (int) $request->get('category_id'),
            'preferred_date' => $request->get('preferred_date'),
            'preferred_day' => $request->get('preferred_day'),
            'purchase_filter_type_id' => (int) $request->get('purchase_filter_type_id'),
            'condition_operator_type_id' => (int) $request->get('condition_operator_type_id'),
            'purchase_value' => $request->get('purchase_value'),
        ];
    }

    public function printMembers(Request $request): string
    {
        $memberData = [];
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'location_ids' => $request->get('location_ids'),
            'membership_ids' => $request->get('membership_ids'),
            'member_group_ids' => $request->get('member_group_ids'),
            'date_range' => $request->get('date_range'),
            'status' => (int) $request->get('status'),
            'product_id' => (int) $request->get('product_id'),
            'preference_id' => (int) $request->get('preference_id'),
            'color_id' => (int) $request->get('color_id'),
            'size_id' => (int) $request->get('size_id'),
            'category_id' => (int) $request->get('category_id'),
            'preferred_date' => $request->get('preferred_date'),
            'preferred_day' => $request->get('preferred_day'),
            'purchase_filter_type_id' => (int) $request->get('purchase_filter_type_id'),
            'condition_operator_type_id' => (int) $request->get('condition_operator_type_id'),
            'purchase_value' => $request->get('purchase_value'),
        ];

        $companyId = session('admin_company_id');
        $members = $this->memberQueries->getMembersForExport($filterData, $companyId);

        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);

        $memberService = resolve(MemberService::class);
        $memberData['details'] = $memberService->preparedMemberRecords($members);

        return view('prints.member_details', [
            'memberDetails' => $memberData['details'],
            'company' => $company,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
        ])->render();
    }

    public function loyaltyPointsHistory(int $memberId): array
    {
        $loyaltyPointsUpdateQueries = resolve(LoyaltyPointUpdateQueries::class);
        $memberLoyaltyPointDetails = $loyaltyPointsUpdateQueries->getMemberLoyaltyPointDetails($memberId);

        return [
            'loyalty_points_history' => MemberLoyaltyPointHistoryResource::collection($memberLoyaltyPointDetails),
        ];
    }

    public function fetchMemberSaleDetails(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'member_id' => $request->get('member_id'),
            'location_id' => null,
        ];

        $saleQueries = resolve(SaleQueries::class);
        $lengthAwarePaginator = $saleQueries->getPaginatedMemberSaleDetails(
            $filterData,
            (int) $filterData['member_id']
        );

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => MemberSaleDetailListResource::collection($lengthAwarePaginator->getCollection()),
        ];
    }

    public function fetchMemberSaleReturnDetails(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'member_id' => $request->get('member_id'),
            'location_id' => null,
        ];

        $saleReturnQueries = resolve(SaleReturnQueries::class);
        $lengthAwarePaginator = $saleReturnQueries->getPaginatedMemberSaleReturnDetails(
            $filterData,
            session('admin_company_id')
        );

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => MemberSaleReturnDetailListResource::collection($lengthAwarePaginator->getCollection()),
        ];
    }

    public function updateMemberAddresses(UpdateMemberAddressData $updateMemberAddressData, int $memberId): void
    {
        $companyId = session('admin_company_id');

        $memberQueries = resolve(MemberQueries::class);
        $member = $memberQueries->getById($memberId, $companyId);
        $memberQueries->updateMemberAddresses($member, $updateMemberAddressData);
    }

    public function fetchMemberAddresses(int $memberId): array
    {
        $memberAddressQueries = resolve(MemberAddressQueries::class);
        $memberAddressDetails = $memberAddressQueries->getMemberAddressDetails($memberId);

        return [
            'member_addresses' => MemberAddressResource::collection($memberAddressDetails),
        ];
    }

    public function memberDetails(int $memberId): Response
    {
        return Inertia::render('members/MemberPurchaseDetails', [
            'memberId' => $memberId,
        ]);
    }

    public function fetchMemberDetails(int $memberId): array
    {
        $member = $this->memberQueries->getActiveMemberDetailsById($memberId, session('admin_company_id'));

        $memberService = resolve(MemberService::class);

        $preferences = $memberService->getMemberPreferencesRecords($memberId, session('admin_company_id'));

        return [
            'member' => new MemberDetailListResource($member),
            'preferencesColor' => $preferences['preferences_color'],
            'preferencesSize' => $preferences['preferences_size'],
            'preferencesCategory' => $preferences['preferences_category'],
            'preferredDate' => $preferences['preferred_date'],
            'preferredDay' => $preferences['preferred_day'],
            'preferencesProduct' => $preferences['preferences_products'],
        ];
    }

    public function delete(int $memberId, Request $request): RedirectResponse
    {
        $companyId = session('admin_company_id');

        /** @var Admin $user */
        $user = $request->user();

        $member = $this->memberQueries->getById($memberId, $companyId);
        $this->memberQueries->deleteMemberByAdmin($member, $user->id);

        return to_route('admin.members.index')->with('success', 'Member deleted successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function getCommonRecords(): array
    {
        $locationQueries = resolve(LocationQueries::class);

        $countryQueries = resolve(CountryQueries::class);
        $countries = $countryQueries->getAllCountries();

        return [
            'genders' => Genders::formattedForSelection(),
            'races' => Races::formattedForSelection(),
            'titles' => Titles::formattedForSelection(),
            'types' => Types::formattedForSelection(),
            'corporateType' => Types::CORPORATE->value,
            'locations' => $locationQueries->getStoreWithBasicColumns(session('admin_company_id')),
            'countries' => $countries,
        ];
    }

    public function fetchMemberDetailsForMerge(int $memberId): array
    {
        $member = $this->memberQueries->getByIdForMergeDetails($memberId, session('admin_company_id'));

        return [
            'member' => new MemberListForMergeResource($member),
        ];
    }

    public function mergeAndDeleteMember(Request $request, int $oldMemberId, int $newMemberId): array
    {
        $companyId = session('admin_company_id');

        /** @var User $user */
        $user = $request->user();

        if ($this->memberQueries->checkMemberIsActive($companyId, $newMemberId) !== Status::ACTIVE->value) {
            abort(412, 'The new selected member is not active.');
        }

        if ($oldMemberId === $newMemberId) {
            abort(412, 'Make sure the merged member is not the same as its opposite.');
        }

        /** @var Member $newMember */
        $newMember = $this->memberQueries->getMemberEmployee($newMemberId, $companyId);

        if (null !== $newMember->employee_id) {
            abort(412, 'New Selected Member With Employee Cannot Be Merged.');
        }

        $this->memberQueries->markAsInActive($oldMemberId, $companyId);
        $this->memberQueries->markAsInActive($newMemberId, $companyId);

        $memberGroupMemberQueries = resolve(MemberGroupMemberQueries::class);
        $memberGroupMemberQueries->removeByMemberId($oldMemberId, $companyId);

        MemberMergeJob::dispatch($user, $oldMemberId, $newMemberId, $companyId)->onQueue('high');
        CreateUpdateMemberSyncWithMemberGroupJob::dispatch($newMemberId, $companyId)->onQueue('medium');

        return [
            'message' => 'Merged Member Is In Progress, It Will Take Some Time.',
        ];
    }

    public function sendEmails(Request $request): void
    {
        $validateData = $request->validate([
            'member_group_id' => ['required', 'integer'],
            'email_template_id' => ['required', 'integer'],
        ]);

        $companyId = session('admin_company_id');

        SendEmailsJob::dispatch(
            $validateData['member_group_id'],
            $validateData['email_template_id'],
            $companyId,
        )->onQueue(config('horizon.default_queue_name'));
    }

    public function deleteMemberAddress(int $id): void
    {
        $memberAddressQueries = resolve(MemberAddressQueries::class);
        $memberAddressQueries->deleteAddressById($id);
    }

    public function syncData(int $saleChannelId, Request $request): void
    {
        MemberSyncMainJob::dispatch($saleChannelId, session('admin_company_id'))->onQueue('high');
        $saleChannelService = resolve(SaleChannelService::class);

        /** @var Admin $admin */
        $admin = $request->user();

        $saleChannelService->updateSyncData(
            $saleChannelId,
            SyncTypes::MEMBER->value,
            $admin,
            session('admin_company_id')
        );
    }
}

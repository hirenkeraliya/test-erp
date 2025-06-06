<?php

declare(strict_types=1);

namespace App\Http\Controllers\StoreManager;

use App\Domains\Category\CategoryQueries;
use App\Domains\Color\ColorQueries;
use App\Domains\Company\CompanyQueries;
use App\Domains\Country\CountryQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\Member\DataObjects\MemberData;
use App\Domains\Member\DataObjects\OrderMemberData;
use App\Domains\Member\DataObjects\UpdateMemberAddressData;
use App\Domains\Member\Enums\ConditionOperatorTypes;
use App\Domains\Member\Enums\Genders;
use App\Domains\Member\Enums\MemberChannelEnum;
use App\Domains\Member\Enums\Preferences;
use App\Domains\Member\Enums\PurchaseFilterTypes;
use App\Domains\Member\Enums\Races;
use App\Domains\Member\Enums\Titles;
use App\Domains\Member\Enums\Types;
use App\Domains\Member\Exports\MemberExport;
use App\Domains\Member\MemberQueries;
use App\Domains\Member\Resources\AdminMemberFilterListResource;
use App\Domains\Member\Resources\MemberAddressResource;
use App\Domains\Member\Resources\MemberDetailListResource;
use App\Domains\Member\Resources\MemberSaleDetailListResource;
use App\Domains\Member\Resources\MemberSaleReturnDetailListResource;
use App\Domains\Member\Resources\StoreManagerMemberListResource;
use App\Domains\Member\Services\MemberService;
use App\Domains\MemberAddress\MemberAddressQueries;
use App\Domains\MemberGroup\MemberGroupQueries;
use App\Domains\Membership\MembershipQueries;
use App\Domains\Permission\Enums\PermissionList;
use App\Domains\Sale\SaleQueries;
use App\Domains\SaleReturn\SaleReturnQueries;
use App\Domains\Size\SizeQueries;
use App\Exceptions\RedirectBackWithErrorException;
use App\Http\Controllers\Controller;
use App\Models\StoreManager;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Cookie;
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
        $companyId = session('store_manager_selected_location_company_id');

        $locationQueries = resolve(LocationQueries::class);
        $locations = $locationQueries->getStoreWithBasicColumns($companyId);

        $membershipQueries = resolve(MembershipQueries::class);
        $memberships = $membershipQueries->getWithBasicColumns($companyId);

        $memberGroupQueries = resolve(MemberGroupQueries::class);
        $memberGroups = $memberGroupQueries->getAllByCompanyId($companyId);

        $categoryQueries = resolve(CategoryQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);

        $countryQueries = resolve(CountryQueries::class);
        $countries = $countryQueries->getAllCountries();

        return Inertia::render('members/Index', [
            'locations' => $locations,
            'memberships' => $memberships,
            'memberGroups' => $memberGroups,
            'exportPermission' => PermissionList::getExportPermissionName('member'),
            'preferences' => Preferences::getList(),
            'preferencesStaticUse' => Preferences::getFormattedArrayForStaticUse(),
            'purchaseFilterTypes' => PurchaseFilterTypes::getList(),
            'conditionOperatorTypes' => ConditionOperatorTypes::getList(),
            'categories' => $categoryQueries->getParentByCompanyId($companyId),
            'colors' => $colorQueries->getWithBasicColumns($companyId),
            'sizes' => $sizeQueries->getWithBasicColumns($companyId),
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
        $companyId = session('store_manager_selected_location_company_id');

        $lengthAwarePaginator = $this->memberQueries->listQueryForMembers($filterData, $companyId);

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => StoreManagerMemberListResource::collection($lengthAwarePaginator->getCollection()),
        ];
    }

    public function create(): Response
    {
        return Inertia::render('members/Manage', $this->getCommonRecords());
    }

    public function store(MemberData $memberData, Request $request): RedirectResponse
    {
        DB::beginTransaction();

        try {
            /** @var StoreManager $storeManager */
            $storeManager = $request->user();

            $companyId = session('store_manager_selected_location_company_id');

            $this->memberQueries->addNewForAdminAndStoreManager(
                $memberData,
                $companyId,
                $storeManager,
                MemberChannelEnum::STORE_MANAGER->value
            );

            DB::commit();

            return to_route('store_manager.members.index')
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
        $companyId = session('store_manager_selected_location_company_id');

        $member = $this->memberQueries->getByIdWithMedia($memberId, $companyId);
        $member['image_url'] = $member->getDiskBasedFirstMediaUrl('photo');

        return Inertia::render('members/Manage', [
            'member' => $member,
            ...$this->getCommonRecords(),
        ]);
    }

    public function update(MemberData $memberData, int $memberId): RedirectResponse
    {
        DB::beginTransaction();

        $companyId = session('store_manager_selected_location_company_id');

        try {
            $this->memberQueries->update($memberData, $memberId, $companyId);

            DB::commit();

            return to_route('store_manager.members.index')
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

    /**
     * @return array<string, AnonymousResourceCollection>|array<string, int>
     */
    public function getFilteredMembers(Request $request): array
    {
        $filterData = [
            'search_text' => $request->input('search_text'),
            'number_of_records' => $request->input('number_of_records'),
        ];

        $memberSearch = $this->memberQueries->searchMembersForFilter(
            $filterData,
            session('store_manager_selected_location_company_id')
        );

        return [
            'members' => AdminMemberFilterListResource::collection($memberSearch),
        ];
    }

    public function exportMembers(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = $this->getExportMembersFilterData($request);

        $members = $this->memberQueries->getMembersForExport(
            $filterData,
            session('store_manager_selected_location_company_id')
        );

        return Excel::download(new MemberExport($members), $filename);
    }

    public function checkMemberExportLimit(Request $request): array
    {
        $filterData = $this->getExportMembersFilterData($request);
        $companyId = session('store_manager_selected_location_company_id');

        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        $memberService = resolve(MemberService::class);

        return $memberService->exportMemberWithJob($storeManager, $filterData, $companyId);
    }

    public function getExportMembersFilterData(Request $request): array
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

        $companyId = session('store_manager_selected_location_company_id');
        $members = $this->memberQueries->getMembersForExport($filterData, $companyId);

        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getByIdWithPromoterCommissionDetails($companyId);

        $memberService = resolve(MemberService::class);
        $memberData['details'] = $memberService->preparedMemberRecords($members);

        return view('prints.member_details', [
            'memberDetails' => $memberData['details'],
            'company' => $company,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
        ])->render();
    }

    public function memberRegistration(): RedirectResponse
    {
        $locationId = session('store_manager_selected_location_id');
        $locationQueries = resolve(LocationQueries::class);
        $location = $locationQueries->getLocationTypeStoreById($locationId);
        if (! $location) {
            abort(417, 'Something went wrong! Please try again later.');
        }

        Cookie::queue('member-registration', 'member-registration');

        return to_route('front.member.member_add_view', $location->uuid);
    }

    public function createMemberForNewOrder(Request $request): array
    {
        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        $companyId = session('store_manager_selected_location_company_id');

        $orderMemberData = new OrderMemberData(
            type_id: (int) $request->get('type_id'),
            first_name: (string) $request->get('first_name'),
            mobile_number: (string) $request->get('mobile_number'),
            email: null,
            created_location_id: (int) $request->get('created_location_id'),
            card_number: (string) $request->get('card_number'),
            company_name: (string) $request->get('company_name'),
            company_address: (string) $request->get('company_address'),
            pic_name: (string) $request->get('pic_name'),
            pic_contact: (string) $request->get('pic_contact'),
        );

        $request->validate($orderMemberData->rules($companyId));

        if ($this->memberQueries->existsByMobileNumber($orderMemberData->mobile_number, $companyId)) {
            abort(417, 'Mobile Number Already Been Taken.');
        }

        if ($orderMemberData->card_number && $this->memberQueries->existsByCardNumber(
            $orderMemberData->card_number,
            $companyId
        )) {
            abort(417, 'Card Number Already Been Taken.');
        }

        $orderMemberData->card_number ??= $this->memberQueries->generateUniqueCardNumber();

        $member = $this->memberQueries->addNewForAdminAndStoreManager(
            $orderMemberData,
            $companyId,
            $storeManager,
            MemberChannelEnum::STORE_MANAGER->value
        );

        return [
            'member' => new AdminMemberFilterListResource($member),
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
            'location_id' => session('store_manager_selected_location_id'),
        ];

        $saleReturnQueries = resolve(SaleReturnQueries::class);
        $lengthAwarePaginator = $saleReturnQueries->getPaginatedMemberSaleReturnDetails(
            $filterData,
            session('store_manager_selected_location_company_id')
        );

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => MemberSaleReturnDetailListResource::collection($lengthAwarePaginator->getCollection()),
        ];
    }

    public function fetchMemberAddresses(int $memberId): array
    {
        $memberAddressQueries = resolve(MemberAddressQueries::class);
        $memberAddressDetails = $memberAddressQueries->getMemberAddressDetails($memberId);

        return [
            'member_addresses' => MemberAddressResource::collection($memberAddressDetails),
        ];
    }

    public function updateMemberAddresses(UpdateMemberAddressData $updateMemberAddressData, int $memberId): void
    {
        $companyId = session('store_manager_selected_location_company_id');

        $memberQueries = resolve(MemberQueries::class);
        $member = $memberQueries->getById($memberId, $companyId);

        $memberQueries->updateMemberAddresses($member, $updateMemberAddressData);
    }

    public function memberDetails(int $memberId): Response
    {
        return Inertia::render('members/MemberPurchaseDetails', [
            'memberId' => $memberId,
        ]);
    }

    public function fetchMemberDetails(int $memberId): array
    {
        $companyId = session('store_manager_selected_location_company_id');
        $member = $this->memberQueries->getActiveMemberDetailsById($memberId, $companyId);

        $memberService = resolve(MemberService::class);

        $preferences = $memberService->getMemberPreferencesRecords(
            $memberId,
            $companyId,
            session('store_manager_selected_location_id')
        );

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

    public function deleteMemberAddress(int $id): void
    {
        $memberAddressQueries = resolve(MemberAddressQueries::class);
        $memberAddressQueries->deleteAddressById($id);
    }

    /**
     * @return array<string, mixed>
     */
    private function getCommonRecords(): array
    {
        $countryQueries = resolve(CountryQueries::class);
        $countries = $countryQueries->getAllCountries();

        return [
            'genders' => Genders::formattedForSelection(),
            'races' => Races::formattedForSelection(),
            'titles' => Titles::formattedForSelection(),
            'types' => Types::formattedForSelection(),
            'corporateType' => Types::CORPORATE->value,
            'defaultStoreId' => session('store_manager_selected_location_id'),
            'countries' => $countries,
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\Category\CategoryQueries;
use App\Domains\Company\CompanyQueries;
use App\Domains\ImportRecord\DataObjects\ImportRecordData;
use App\Domains\ImportRecord\Enums\ImportTypes;
use App\Domains\ImportRecord\Enums\Status;
use App\Domains\ImportRecord\ImportRecordQueries;
use App\Domains\ImportRecord\Jobs\ImportRecordsJob;
use App\Domains\Member\MemberQueries;
use App\Domains\MemberGroup\DataObjects\MemberGroupData;
use App\Domains\MemberGroup\Enums\DateConditionTypes;
use App\Domains\MemberGroup\Enums\ElementConditionTypes;
use App\Domains\MemberGroup\Enums\GroupTypes;
use App\Domains\MemberGroup\Enums\NumberConditionTypes;
use App\Domains\MemberGroup\Enums\SmartGroupTypes;
use App\Domains\MemberGroup\Exports\MemberGroupExport;
use App\Domains\MemberGroup\Jobs\MemberGroupSyncMainJob;
use App\Domains\MemberGroup\Jobs\MembersSyncWithMemberGroupJob;
use App\Domains\MemberGroup\MemberGroupQueries;
use App\Domains\MemberGroup\Resources\MemberGroupListResource;
use App\Domains\MemberGroup\Services\MemberGroupService;
use App\Domains\MemberGroupMember\Jobs\MemberGroupSyncJob;
use App\Domains\Permission\Enums\PermissionList;
use App\Domains\SaleChannel\Services\SaleChannelService;
use App\Domains\SyncTransaction\Enums\SyncTypes;
use App\Domains\SyncTransaction\SyncTransactionQueries;
use App\Exceptions\RedirectBackWithErrorException;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class MemberGroupController extends Controller
{
    public function __construct(
        protected MemberGroupQueries $memberGroupQueries
    ) {
    }

    public function index(): Response
    {
        $companyQueries = resolve(CompanyQueries::class);

        $saleChannelService = resolve(SaleChannelService::class);
        $syncTransactionQueries = resolve(SyncTransactionQueries::class);
        $saleChannels = $saleChannelService->getModifySaleChannels(
            SyncTypes::MEMBER_GROUP->value,
            session('admin_company_id')
        );

        $hasPendingSyncTransaction = $syncTransactionQueries->hasPendingSyncTransaction(
            SyncTypes::MEMBER_GROUP->value,
            session('admin_company_id')
        );

        $isSynced = $companyQueries->getByIdWithAutoIncludeMemberGroup(
            session('admin_company_id')
        )->auto_include_in_member_group;

        return Inertia::render('member_groups/Index', [
            'exportPermission' => PermissionList::getExportPermissionName('member_group'),
            'statuses' => Status::getStatuses(),
            'isSynced' => $isSynced,
            'groupTypes' => [
                'manualGroup' => GroupTypes::MANUAL_GROUP->value,
                'smartGroup' => GroupTypes::SMART_GROUP->value,
            ],
            'saleChannels' => $saleChannels,
            'hasPendingSyncTransaction' => $hasPendingSyncTransaction,
        ]);
    }

    public function fetchMemberGroups(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $lengthAwarePaginator = $this->memberGroupQueries->listQuery($filterData, session('admin_company_id'));

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => MemberGroupListResource::collection($lengthAwarePaginator->getCollection()),
        ];
    }

    public function create(): Response
    {
        return Inertia::render('member_groups/Manage', $this->commonResponse());
    }

    public function store(MemberGroupData $memberGroupData, Request $request): RedirectResponse
    {
        $importRecordQueries = resolve(ImportRecordQueries::class);
        $companyId = session('admin_company_id');

        /** @var Admin $admin */
        $admin = $request->user();

        DB::beginTransaction();

        try {
            $memberGroup = $this->memberGroupQueries->addNew($memberGroupData, session('admin_company_id'));
            if ($memberGroup->type_id === GroupTypes::MANUAL_GROUP->value && $memberGroupData->member_file instanceof UploadedFile) {
                $importRecordData = [
                    'type_id' => ImportTypes::MEMBER_GROUP_MEMBERS->value,
                    'upload_file' => $memberGroupData->member_file,
                ];
                $importRecord = $importRecordQueries->addNew(
                    new ImportRecordData(...$importRecordData),
                    $admin,
                    $companyId,
                    $memberGroup,
                );
                ImportRecordsJob::dispatch($importRecord)->onQueue('high');
            }

            if ($memberGroup->type_id === GroupTypes::SMART_GROUP->value && $memberGroupData->product_file instanceof UploadedFile) {
                $importRecordData = [
                    'type_id' => ImportTypes::MEMBER_GROUP_PRODUCTS->value,
                    'upload_file' => $memberGroupData->product_file,
                ];
                $importRecord = $importRecordQueries->addNew(
                    new ImportRecordData(...$importRecordData),
                    $admin,
                    $companyId,
                    $memberGroup,
                );
                ImportRecordsJob::dispatch($importRecord)->onQueue('high');
            }

            DB::commit();
            if ($memberGroup->type_id === GroupTypes::SMART_GROUP->value && $memberGroup->smart_group_type_id != SmartGroupTypes::ITEM->value) {
                $importRecord = $importRecordQueries->addNewForMemberGroup(
                    ImportTypes::MEMBER_GROUP->value,
                    $admin,
                    $companyId,
                    $memberGroup,
                );
                MembersSyncWithMemberGroupJob::dispatch($memberGroup->id, $companyId, $importRecord->id)->onQueue(
                    config('horizon.default_queue_name')
                );
            }

            return to_route('admin.member_groups.index')->with(
                'success',
                'The member group has been added successfully.'
            );
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

    public function edit(int $memberGroupId): Response
    {
        $memberGroup = $this->memberGroupQueries->getById($memberGroupId, session('admin_company_id'));

        $importRecord = $memberGroup->importRecord;
        if ($importRecord && $importRecord->status !== Status::COMPLETED->value) {
            throw new RedirectBackWithErrorException('You cannot update while the process is in progress.');
        }

        $memberGroup['members'] = $memberGroup->memberGroupMembers->map(
            fn ($memberGroupMember) => $memberGroupMember->member
        );

        return Inertia::render('member_groups/Manage', [
            'memberGroup' => $memberGroup,
            ...$this->commonResponse(),
        ]);
    }

    public function update(MemberGroupData $memberGroupData, Request $request, int $memberGroupId): RedirectResponse
    {
        $importRecordQueries = resolve(ImportRecordQueries::class);
        $companyId = session('admin_company_id');

        /** @var Admin $admin */
        $admin = $request->user();

        $memberGroup = $this->memberGroupQueries->update($memberGroupData, $memberGroupId, session('admin_company_id'));

        $importRecord = $memberGroup->importRecord;
        if ($importRecord && $importRecord->status !== Status::COMPLETED->value) {
            throw new RedirectBackWithErrorException('You cannot update while the process is in progress.');
        }

        if ($memberGroup->type_id === GroupTypes::MANUAL_GROUP->value && $memberGroupData->member_file instanceof UploadedFile) {
            $importRecordData = [
                'type_id' => ImportTypes::MEMBER_GROUP_MEMBERS->value,
                'upload_file' => $memberGroupData->member_file,
            ];
            $importRecord = $importRecordQueries->addNew(
                new ImportRecordData(...$importRecordData),
                $admin,
                $companyId,
                $memberGroup,
            );
            ImportRecordsJob::dispatch($importRecord)->onQueue('high');
        }

        if ($memberGroup->type_id === GroupTypes::SMART_GROUP->value && $memberGroupData->product_file instanceof UploadedFile) {
            $importRecordData = [
                'type_id' => ImportTypes::MEMBER_GROUP_PRODUCTS->value,
                'upload_file' => $memberGroupData->product_file,
            ];
            $importRecord = $importRecordQueries->addNew(
                new ImportRecordData(...$importRecordData),
                $admin,
                $companyId,
                $memberGroup,
            );
            ImportRecordsJob::dispatch($importRecord)->onQueue('high');
        }

        if ($memberGroup->type_id === GroupTypes::SMART_GROUP->value && $memberGroup->smart_group_type_id != SmartGroupTypes::ITEM->value) {
            $importRecord = $importRecordQueries->addNewForMemberGroup(
                ImportTypes::MEMBER_GROUP->value,
                $admin,
                $companyId,
                $memberGroup,
            );
            MembersSyncWithMemberGroupJob::dispatch($memberGroup->id, $companyId, $importRecord->id)->onQueue(
                config('horizon.default_queue_name')
            );
        }

        return to_route('admin.member_groups.index')->with(
            'success',
            'The member group has been successfully updated.'
        );
    }

    public function exportMemberGroups(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $memberGroups = $this->memberGroupQueries->getMemberGroupsExport($filterData, session('admin_company_id'));

        return Excel::download(new MemberGroupExport($memberGroups), $filename);
    }

    public function syncData(int $saleChannelId, Request $request): void
    {
        MemberGroupSyncMainJob::dispatch($saleChannelId, session('admin_company_id'))->onQueue('high');
        $saleChannelService = resolve(SaleChannelService::class);

        /** @var Admin $admin */
        $admin = $request->user();

        $saleChannelService->updateSyncData(
            $saleChannelId,
            SyncTypes::MEMBER_GROUP->value,
            $admin,
            session('admin_company_id')
        );
    }

    public function getGroupMemberCount(Request $request): int
    {
        $memberQueries = resolve(MemberQueries::class);

        $memberGroupService = resolve(MemberGroupService::class);

        if ($request->groupType != GroupTypes::SMART_GROUP->value) {
            return 0;
        }

        if ($memberGroupService->isPurchaseCountType($request)) {
            return $memberQueries->getPurchaseCountByMemberCount(
                (float) $request->value,
                (float) $request->max_value ?: 0,
                $request->numberConditionTypeId
            );
        }

        if ($memberGroupService->isTotalSpentType($request)) {
            return $memberQueries->getTotalSpentByMemberCount(
                (float) $request->value,
                (float) $request->max_value ?: 0,
                $request->numberConditionTypeId
            );
        }

        if ($memberGroupService->isDateType($request)) {
            return $memberGroupService->getDateByMemberCount(
                $request->date,
                $request->max_date ?: now()->format('Y-m-d'),
                $request->dateConditionTypeId,
                $request->smartGroupType
            );
        }

        if ($memberGroupService->isProductType($request)) {
            return $memberQueries->getProductsIdByMemberCount(
                $request->productIds,
                $request->elementConditionTypeId
            );
        }

        if ($memberGroupService->isCategoryType($request)) {
            return $memberQueries->getCategoriesIdByMemberCount(
                $request->categoryIds,
                $request->elementConditionTypeId
            );
        }

        return 0;
    }

    public function removeSelectedMembers(int $memberGroupId): void
    {
        $this->memberGroupQueries->removeSelectedMembers($memberGroupId, session('admin_company_id'));
    }

    public function removeSelectedProducts(int $memberGroupId): void
    {
        $this->memberGroupQueries->removeSelectedProducts($memberGroupId, session('admin_company_id'));
    }

    public function syncMembers(Request $request): void
    {
        $request->validate([
            'member_group_id' => ['required', 'integer'],
        ]);

        /** @var Admin $user */
        $user = auth()->user();
        $importRecordQueries = resolve(ImportRecordQueries::class);
        $companyId = session('admin_company_id');
        $memberGroup = $this->memberGroupQueries->getMemberGroup($request->member_group_id, $companyId);

        $importRecord = $importRecordQueries->addNewForMemberGroup(
            ImportTypes::MEMBER_GROUP->value,
            $user,
            $companyId,
            $memberGroup,
        );

        MemberGroupSyncJob::dispatch(
            $memberGroup->id,
            $importRecord->company_id,
            $importRecord->id
        )->onQueue('medium');
    }

    public function commonResponse(): array
    {
        $categoryQueries = resolve(CategoryQueries::class);

        return [
            'categories' => $categoryQueries->getAll(session('admin_company_id')),
            'groupTypes' => GroupTypes::formattedForSelection(),
            'smartGroupTypes' => SmartGroupTypes::formattedForSelection(),
            'dateConditionTypes' => DateConditionTypes::formattedForSelection(),
            'elementConditionTypes' => ElementConditionTypes::formattedForSelection(),
            'numberConditionTypes' => NumberConditionTypes::formattedForSelection(),
            'smartGroupDate' => [
                SmartGroupTypes::PURCHASE_DATE->value,
                SmartGroupTypes::FIRST_VISIT_DATE->value,
                SmartGroupTypes::LAST_VISIT_DATE->value,
            ],
            'smartGroupCategoryItem' => [SmartGroupTypes::CATEGORY->value, SmartGroupTypes::ITEM->value],
            'smartGroupCategory' => [SmartGroupTypes::CATEGORY->value],
            'smartGroupProduct' => [SmartGroupTypes::ITEM->value],
            'smartGroupNumber' => [SmartGroupTypes::PURCHASE_COUNT->value, SmartGroupTypes::LIFETIME_SPENT->value],
            'groupManualSmart' => [GroupTypes::MANUAL_GROUP->value, GroupTypes::SMART_GROUP->value],
            'dateCondition' => [
                DateConditionTypes::MORE_THAN->value,
                DateConditionTypes::LESS_THAN->value,
                DateConditionTypes::EXACTLY_ON->value,
                DateConditionTypes::BETWEEN->value,
            ],
            'dateConditionBetween' => [DateConditionTypes::BETWEEN->value],
            'numberCondition' => [
                NumberConditionTypes::GREATER_THAN->value,
                NumberConditionTypes::LESS_THAN->value,
                NumberConditionTypes::BETWEEN->value,
                NumberConditionTypes::EXACTLY_TO->value,
            ],
            'numberConditionBetween' => [NumberConditionTypes::BETWEEN->value],
            'manualType' => GroupTypes::MANUAL_GROUP->value,
            'smartType' => GroupTypes::SMART_GROUP->value,
        ];
    }
}

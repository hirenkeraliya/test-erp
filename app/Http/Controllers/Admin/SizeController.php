<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\Permission\Enums\PermissionList;
use App\Domains\SaleChannel\Services\SaleChannelService;
use App\Domains\Size\DataObjects\SizeData;
use App\Domains\Size\Exports\SizeExport;
use App\Domains\Size\Jobs\SizeSyncMainJob;
use App\Domains\Size\SizeQueries;
use App\Domains\SizeGroup\SizeGroupQueries;
use App\Domains\SyncTransaction\Enums\SyncTypes;
use App\Domains\SyncTransaction\SyncTransactionQueries;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SizeController extends Controller
{
    public function __construct(
        protected SizeQueries $sizeQueries
    ) {
    }

    public function index(): Response
    {
        $sizeGroupQueries = resolve(SizeGroupQueries::class);
        $syncTransactionQueries = resolve(SyncTransactionQueries::class);
        $saleChannelService = resolve(SaleChannelService::class);
        $saleChannels = $saleChannelService->getModifySaleChannels(SyncTypes::SIZE->value, session('admin_company_id'));

        $hasPendingSyncTransaction = $syncTransactionQueries->hasPendingSyncTransaction(
            SyncTypes::SIZE->value,
            session('admin_company_id')
        );

        return Inertia::render('sizes/Index', [
            'sizeGroups' => $sizeGroupQueries->getSizeGroupByCompanyId(session('admin_company_id')),
            'exportPermission' => PermissionList::getExportPermissionName('size'),
            'saleChannels' => $saleChannels,
            'hasPendingSyncTransaction' => $hasPendingSyncTransaction,
        ]);
    }

    /**
     * @return array<string, Collection>|array<string, int>
     */
    public function fetchSizes(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'group_ids' => $request->get('group_ids'),
        ];

        $lengthAwarePaginator = $this->sizeQueries->listQuery($filterData, session('admin_company_id'));

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => $lengthAwarePaginator->getCollection(),
        ];
    }

    public function create(): Response
    {
        $sizeGroupQueries = resolve(SizeGroupQueries::class);

        return Inertia::render('sizes/Manage', [
            'sizes' => $this->getAllSizes(),
            'sizeGroups' => $sizeGroupQueries->getSizeGroupByCompanyId(session('admin_company_id')),
        ]);
    }

    public function store(SizeData $sizeData): RedirectResponse
    {
        $this->processSizeData($sizeData);

        $this->sizeQueries->addNew($sizeData, session('admin_company_id'));

        return to_route('admin.sizes.index')->with('success', 'Size added successfully.');
    }

    public function storeAndReturn(SizeData $sizeData): array
    {
        $this->processSizeData($sizeData);

        $size = $this->sizeQueries->addNew($sizeData, session('admin_company_id'));

        return [
            'size' => $size,
        ];
    }

    public function edit(int $sizeId): Response
    {
        $sizeGroupQueries = resolve(SizeGroupQueries::class);

        return Inertia::render('sizes/Manage', [
            'size' => $this->sizeQueries->getById($sizeId, session('admin_company_id')),
            'sizes' => $this->getAllSizes(),
            'sizeGroups' => $sizeGroupQueries->getSizeGroupByCompanyId(session('admin_company_id')),
        ]);
    }

    public function update(SizeData $sizeData, int $sizeId): RedirectResponse
    {
        $sizes = $this->sizeQueries->getAllSizes(session('admin_company_id'));

        $createAfterSortOrder = $sizes->where('id', $sizeData->sort_order)->first()->sort_order;

        $currentEditDataSortOrder = $sizes->where('id', $sizeId)->first()->sort_order;

        if ($currentEditDataSortOrder < $createAfterSortOrder) {
            $filterSizes = $sizes->where('sort_order', '<=', $createAfterSortOrder)
                ->where('sort_order', '>', $currentEditDataSortOrder)
                ->whereNotNull('sort_order')
                ->toArray();
        } else {
            $filterSizes = $sizes->where('sort_order', '>', $createAfterSortOrder)
                ->where('sort_order', '<', $currentEditDataSortOrder)
                ->whereNotNull('sort_order')
                ->toArray();
        }

        foreach ($filterSizes as $filterSize) {
            $sortOrder = $currentEditDataSortOrder < $createAfterSortOrder ? $filterSize['sort_order'] - 1 : $filterSize['sort_order'] + 1;

            $this->sizeQueries->updateSortOrder($filterSize['id'], session('admin_company_id'), $sortOrder);
        }

        $sizeData->sort_order = $currentEditDataSortOrder < $createAfterSortOrder ? $createAfterSortOrder : $createAfterSortOrder + 1;

        $this->sizeQueries->update($sizeData, $sizeId, session('admin_company_id'));

        return to_route('admin.sizes.index')->with('success', 'Size updated successfully.');
    }

    /**
     * @return array<string, Collection>
     */
    public function getFilteredSizes(Request $request): array
    {
        return [
            'sizes' => $this->sizeQueries->getFilteredSizesByCompanyId(
                $request->input('search_text'),
                session('admin_company_id')
            ),
        ];
    }

    public function exportSizes(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'group_ids' => $request->get('group_ids'),
        ];

        $sizes = $this->sizeQueries->getSizesExport($filterData, session('admin_company_id'));

        return Excel::download(new SizeExport($sizes), $filename);
    }

    public function getSizeSalesSummary(Request $request): array
    {
        $filterData = $request->all();
        $filterData['type'] = (int) $filterData['type'];
        $sizes = $this->sizeQueries->getSizeSalesSummary($filterData, session('admin_company_id'));

        return [
            'sizes' => $sizes,
            'total_sales' => $sizes->sum('total_sales'),
            'total_units_sold' => $sizes->sum('total_units_sold'),
        ];
    }

    public function syncData(int $saleChannelId, Request $request): void
    {
        SizeSyncMainJob::dispatch($saleChannelId, session('admin_company_id'))->onQueue('high');
        $saleChannelService = resolve(SaleChannelService::class);

        /** @var Admin $admin */
        $admin = $request->user();

        $saleChannelService->updateSyncData(
            $saleChannelId,
            SyncTypes::SIZE->value,
            $admin,
            session('admin_company_id')
        );
    }

    private function processSizeData(SizeData $sizeData): void
    {
        if (0 !== $sizeData->sort_order) {
            $sizes = $this->sizeQueries->getAllSizes(session('admin_company_id'));

            $createAfterSortOrder = $sizes->where('id', $sizeData->sort_order)->first()->sort_order;

            $filterSizes = $sizes->where('sort_order', '>', $createAfterSortOrder)
                ->whereNotNull('sort_order')
                ->whereNotIn('name', [$sizeData->name])
                ->toArray();

            foreach ($filterSizes as $filterSize) {
                $this->sizeQueries->updateSortOrder(
                    $filterSize['id'],
                    session('admin_company_id'),
                    $filterSize['sort_order'] + 1
                );
            }

            $sizeData->sort_order = $createAfterSortOrder + 1;
        }
    }

    /**
     * @return mixed[]
     */
    private function getAllSizes(): array
    {
        return $this->sizeQueries->getAllSizes(session('admin_company_id'))->toArray();
    }
}

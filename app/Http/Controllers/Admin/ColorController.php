<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\Color\ColorQueries;
use App\Domains\Color\DataObjects\ColorData;
use App\Domains\Color\Exports\ColorExport;
use App\Domains\Color\Jobs\ColorSyncMainJob;
use App\Domains\ColorGroup\ColorGroupQueries;
use App\Domains\Permission\Enums\PermissionList;
use App\Domains\SaleChannel\Services\SaleChannelService;
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

class ColorController extends Controller
{
    public function __construct(
        protected ColorQueries $colorQueries
    ) {
    }

    public function index(): Response
    {
        $colorGroupQueries = resolve(ColorGroupQueries::class);
        $saleChannelService = resolve(SaleChannelService::class);
        $syncTransactionQueries = resolve(SyncTransactionQueries::class);
        $hasPendingSyncTransaction = $syncTransactionQueries->hasPendingSyncTransaction(
            SyncTypes::COLOR->value,
            session('admin_company_id')
        );

        $saleChannels = $saleChannelService->getModifySaleChannels(
            SyncTypes::COLOR->value,
            session('admin_company_id')
        );

        return Inertia::render('colors/Index', [
            'colorGroups' => $colorGroupQueries->getColorGroupByCompanyId(session('admin_company_id')),
            'exportPermission' => PermissionList::getExportPermissionName('color'),
            'saleChannels' => $saleChannels,
            'hasPendingSyncTransaction' => $hasPendingSyncTransaction,
        ]);
    }

    /**
     * @return array<string, Collection>|array<string, int>
     */
    public function fetchColors(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'group_ids' => $request->get('group_ids'),
        ];

        $lengthAwarePaginator = $this->colorQueries->listQuery($filterData, session('admin_company_id'));

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => $lengthAwarePaginator->getCollection(),
        ];
    }

    public function create(): Response
    {
        $colorGroupQueries = resolve(ColorGroupQueries::class);

        return Inertia::render('colors/Manage', [
            'colorGroups' => $colorGroupQueries->getColorGroupByCompanyId(session('admin_company_id')),
        ]);
    }

    public function store(ColorData $colorData): RedirectResponse
    {
        $this->colorQueries->addNew($colorData, session('admin_company_id'));

        return to_route('admin.colors.index')->with('success', 'The color has been added successfully.');
    }

    public function storeAndReturn(ColorData $colorData): array
    {
        $color = $this->colorQueries->addNew($colorData, session('admin_company_id'));

        return [
            'color' => $color,
        ];
    }

    public function edit(int $colorId): Response
    {
        $colorGroupQueries = resolve(ColorGroupQueries::class);

        return Inertia::render('colors/Manage', [
            'color' => $this->colorQueries->getById($colorId, session('admin_company_id')),
            'colorGroups' => $colorGroupQueries->getColorGroupByCompanyId(session('admin_company_id')),
        ]);
    }

    public function update(ColorData $colorData, int $colorId): RedirectResponse
    {
        $this->colorQueries->update($colorData, $colorId, session('admin_company_id'));

        return to_route('admin.colors.index')->with('success', 'The color has been updated successfully.');
    }

    /**
     * @return array<string, Collection>
     */
    public function getFilteredColors(Request $request): array
    {
        return [
            'colors' => $this->colorQueries->getFilteredColorsByCompanyId(
                $request->input('search_text'),
                session('admin_company_id')
            ),
        ];
    }

    public function exportColors(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'group_ids' => $request->get('group_ids'),
        ];

        $colors = $this->colorQueries->getColorsExport($filterData, session('admin_company_id'));

        return Excel::download(new ColorExport($colors), $filename);
    }

    public function getColorSalesSummary(Request $request): array
    {
        $filterData = $request->all();
        $filterData['type'] = (int) $filterData['type'];
        $colors = $this->colorQueries->getColorSalesSummary($filterData, session('admin_company_id'));

        return [
            'colors' => $colors,
            'total_sales' => $colors->sum('total_sales'),
            'total_units_sold' => $colors->sum('total_units_sold'),
        ];
    }

    public function syncData(int $saleChannelId, Request $request): void
    {
        ColorSyncMainJob::dispatch($saleChannelId, session('admin_company_id'))->onQueue('high');
        $saleChannelService = resolve(SaleChannelService::class);

        /** @var Admin $admin */
        $admin = $request->user();

        $saleChannelService->updateSyncData(
            $saleChannelId,
            SyncTypes::COLOR->value,
            $admin,
            session('admin_company_id')
        );
    }
}

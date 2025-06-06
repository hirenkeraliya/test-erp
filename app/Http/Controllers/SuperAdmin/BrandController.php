<?php

declare(strict_types=1);

namespace App\Http\Controllers\SuperAdmin;

use App\Domains\Brand\BrandQueries;
use App\Domains\Brand\DataObjects\BrandData;
use App\Domains\Brand\Exports\BrandExport;
use App\Domains\Brand\Jobs\BrandSyncMainJob;
use App\Domains\SaleChannel\Services\SaleChannelService;
use App\Domains\SyncTransaction\Enums\SyncTypes;
use App\Domains\SyncTransaction\SyncTransactionQueries;
use App\Http\Controllers\Controller;
use App\Models\SuperAdmin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BrandController extends Controller
{
    public function __construct(
        protected BrandQueries $brandQueries
    ) {
    }

    public function index(): Response
    {
        $saleChannelService = resolve(SaleChannelService::class);
        $syncTransactionQueries = resolve(SyncTransactionQueries::class);
        $saleChannels = $saleChannelService->getModifySaleChannels(SyncTypes::BRAND->value, null);

        $hasPendingSyncTransaction = $syncTransactionQueries->hasPendingSyncTransaction(
            SyncTypes::BRAND->value,
            null
        );

        return Inertia::render('brands/Index', [
            'saleChannels' => $saleChannels,
            'hasPendingSyncTransaction' => $hasPendingSyncTransaction,
        ]);
    }

    /**
     * @return array<string, Collection>|array<string, int>
     */
    public function fetchBrands(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $lengthAwarePaginator = $this->brandQueries->listQuery($filterData);

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => $lengthAwarePaginator->getCollection(),
        ];
    }

    public function store(BrandData $brandData): RedirectResponse
    {
        $this->brandQueries->addNew($brandData);

        return to_route('super_admin.brands.index')->with('success', 'Brand added successfully.');
    }

    public function edit(int $brandId): Response
    {
        $brand = $this->brandQueries->getById($brandId);

        return Inertia::render('brands/Manage', [
            'brand' => $brand,
        ]);
    }

    public function update(BrandData $brandData, int $brandId): RedirectResponse
    {
        $this->brandQueries->update($brandData, $brandId);

        return to_route('super_admin.brands.index')->with('success', 'Brand updated successfully.');
    }

    public function exportBrands(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
        ];

        $brands = $this->brandQueries->getBrandsExport($filterData);

        return Excel::download(new BrandExport($brands), $filename);
    }

    public function syncData(int $saleChannelId, Request $request): void
    {
        BrandSyncMainJob::dispatch($saleChannelId)->onQueue('high');
        $saleChannelService = resolve(SaleChannelService::class);

        /** @var SuperAdmin $superAdmin */
        $superAdmin = $request->user();

        $saleChannelService->updateSyncData($saleChannelId, SyncTypes::BRAND->value, $superAdmin, null);
    }
}

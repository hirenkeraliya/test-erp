<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\Banner\BannerQueries;
use App\Domains\Banner\DataObjects\BannerData;
use App\Domains\Banner\Enums\ActionTypes;
use App\Domains\Banner\Jobs\BannerSyncMainJob;
use App\Domains\Banner\Resources\BannerListResource;
use App\Domains\SaleChannel\Services\SaleChannelService;
use App\Domains\SyncTransaction\Enums\SyncTypes;
use App\Domains\SyncTransaction\SyncTransactionQueries;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Inertia\Inertia;
use Inertia\Response;

class BannerController extends Controller
{
    public function __construct(
        protected BannerQueries $bannerQueries
    ) {
    }

    public function index(): Response
    {
        $saleChannelService = resolve(SaleChannelService::class);
        $syncTransactionQueries = resolve(SyncTransactionQueries::class);
        $saleChannels = $saleChannelService->getModifySaleChannels(
            SyncTypes::BANNER->value,
            session('admin_company_id')
        );

        $hasPendingSyncTransaction = $syncTransactionQueries->hasPendingSyncTransaction(
            SyncTypes::BANNER->value,
            session('admin_company_id')
        );

        return Inertia::render('banners/Index', [
            'saleChannels' => $saleChannels,
            'hasPendingSyncTransaction' => $hasPendingSyncTransaction,
        ]);
    }

    /**
     * @return array<string, AnonymousResourceCollection>|array<string, int>
     */
    public function fetchBanners(Request $request): mixed
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $lengthAwarePaginator = $this->bannerQueries->listQuery($filterData, session('admin_company_id'));

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => BannerListResource::collection($lengthAwarePaginator->getCollection()),
        ];
    }

    public function create(): Response
    {
        return Inertia::render('banners/Manage', [
            'actionTypes' => ActionTypes::formattedForSelection(),
            'actionTypesDetails' => ActionTypes::getFormattedArrayForStaticUse(),
        ]);
    }

    public function store(BannerData $bannerData): RedirectResponse
    {
        $this->bannerQueries->addNew($bannerData, session('admin_company_id'));

        return to_route('admin.banners.index')->with('success', 'The banner has been added successfully.');
    }

    public function edit(int $bannerId): Response
    {
        $banner = $this->bannerQueries->getById($bannerId, session('admin_company_id'));

        $banner['image_url'] = $banner->getDiskBasedFirstMediaUrl('banner');

        return Inertia::render('banners/Manage', [
            'banner' => $banner,
            'actionTypes' => ActionTypes::formattedForSelection(),
            'actionTypesDetails' => ActionTypes::getFormattedArrayForStaticUse(),
        ]);
    }

    public function update(BannerData $bannerData, int $bannerId): RedirectResponse
    {
        $this->bannerQueries->update($bannerData, $bannerId, session('admin_company_id'));

        return to_route('admin.banners.index')->with('success', 'The banner has been updated successfully.');
    }

    public function setStatus(int $bannerId, bool $status): RedirectResponse
    {
        $this->bannerQueries->updateStatus($bannerId, session('admin_company_id'), $status);

        return to_route('admin.banners.index')->with('success', 'Status changed successfully.');
    }

    public function syncData(int $saleChannelId, Request $request): void
    {
        BannerSyncMainJob::dispatch($saleChannelId, session('admin_company_id'))->onQueue('high');
        $saleChannelService = resolve(SaleChannelService::class);

        /** @var Admin $admin */
        $admin = $request->user();

        $saleChannelService->updateSyncData(
            $saleChannelId,
            SyncTypes::BANNER->value,
            $admin,
            session('admin_company_id')
        );
    }
}

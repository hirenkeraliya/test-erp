<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Common\Enums\Statuses;
use App\Domains\DreamPrice\DataObjects\DreamPriceData;
use App\Domains\DreamPrice\DreamPriceQueries;
use App\Domains\DreamPrice\Exports\DreamPriceExport;
use App\Domains\DreamPrice\Jobs\DreamPriceSyncMainJob;
use App\Domains\DreamPrice\Resources\AdminDreamPriceListResource;
use App\Domains\DreamPriceProduct\DataObjects\DreamPriceProductsData;
use App\Domains\DreamPriceProduct\DreamPriceProductQueries;
use App\Domains\DreamPriceProduct\Exports\DreamPriceProductExport;
use App\Domains\DreamPriceProduct\Resources\DreamPriceProductDetailResource;
use App\Domains\EmployeeGroup\EmployeeGroupQueries;
use App\Domains\ImportRecord\DataObjects\ImportRecordData;
use App\Domains\ImportRecord\Enums\ImportTypes;
use App\Domains\ImportRecord\Enums\Status;
use App\Domains\ImportRecord\ImportRecordQueries;
use App\Domains\ImportRecord\Jobs\ImportRecordsJob;
use App\Domains\ImportRecord\Services\ImportRecordService;
use App\Domains\Location\LocationQueries;
use App\Domains\MemberGroup\MemberGroupQueries;
use App\Domains\Permission\Enums\PermissionList;
use App\Domains\SaleChannel\SaleChannelQueries;
use App\Domains\SaleChannel\Services\SaleChannelService;
use App\Domains\SyncTransaction\Enums\SyncTypes;
use App\Domains\SyncTransaction\SyncTransactionQueries;
use App\Exceptions\RedirectBackWithErrorException;
use App\Exceptions\RedirectWithErrorException;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class DreamPriceController extends Controller
{
    public function __construct(
        protected DreamPriceQueries $dreamPriceQueries
    ) {
    }

    public function index(): Response
    {
        $saleChannelService = resolve(SaleChannelService::class);
        $saleChannels = $saleChannelService->getModifySaleChannels(
            SyncTypes::DREAM_PRICE->value,
            session('admin_company_id')
        );

        $syncTransactionQueries = resolve(SyncTransactionQueries::class);
        $hasPendingSyncTransaction = $syncTransactionQueries->hasPendingSyncTransaction(
            SyncTypes::DREAM_PRICE->value,
            session('admin_company_id')
        );

        return Inertia::render('dream_prices/Index', [
            'exportPermission' => PermissionList::getExportPermissionName('dream_price'),
            'importRecordStatus' => Status::getStatuses(),
            'dreamPriceModelMappingType' => ModelMapping::DREAM_PRICE->name,
            'statuses' => Statuses::getList(),
            'allStatuses' => Statuses::getFormattedArrayForStaticUse(),
            'saleChannels' => $saleChannels,
            'hasPendingSyncTransaction' => $hasPendingSyncTransaction,
        ]);
    }

    /**
     * @return array<string, int>|array<string, AnonymousResourceCollection>
     */
    public function fetchDreamPrices(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'status' => $request->get('status'),
        ];

        $lengthAwarePaginator = $this->dreamPriceQueries->listQuery($filterData, session('admin_company_id'));

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => AdminDreamPriceListResource::collection($lengthAwarePaginator->getCollection()),
        ];
    }

    public function create(): Response
    {
        $locations = $this->fetchLocations(session('admin_company_id'));
        $memberGroupQueries = resolve(MemberGroupQueries::class);
        $employeeGroupQueries = resolve(EmployeeGroupQueries::class);

        $saleChannelQueries = resolve(SaleChannelQueries::class);
        $saleChannels = $saleChannelQueries->getAllByCompanyId((int) session('admin_company_id'));

        return Inertia::render('dream_prices/Manage', [
            'locations' => $locations,
            'memberGroups' => $memberGroupQueries->getByCompanyId(session('admin_company_id')),
            'employeeGroups' => $employeeGroupQueries->getByCompanyId(session('admin_company_id')),
            'saleChannels' => $saleChannels,
        ]);
    }

    public function store(DreamPriceData $dreamPriceData, Request $request): RedirectResponse
    {
        $this->validateSelectedRecordsWithCompany(session('admin_company_id'), $dreamPriceData);

        DB::beginTransaction();

        try {
            /** @var User $user */
            $user = $request->user();

            $this->dreamPriceQueries->addNew($dreamPriceData, session('admin_company_id'), $user);

            DB::commit();

            return to_route('admin.dream_prices.index')
                ->with('success', 'Dream price added successfully.');
        } catch (Throwable $throwable) {
            Log::error('Add Dream Price', [
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

    public function edit(int $dreamPriceId): Response
    {
        $dreamPrice = $this->dreamPriceQueries->getByIdWithLocations($dreamPriceId, session('admin_company_id'));

        $locations = $this->fetchLocations(session('admin_company_id'));
        $memberGroupQueries = resolve(MemberGroupQueries::class);
        $employeeGroupQueries = resolve(EmployeeGroupQueries::class);

        $saleChannelQueries = resolve(SaleChannelQueries::class);
        $saleChannels = $saleChannelQueries->getAllByCompanyId((int) session('admin_company_id'));

        return Inertia::render('dream_prices/Manage', [
            'memberGroups' => $memberGroupQueries->getByCompanyId(session('admin_company_id')),
            'employeeGroups' => $employeeGroupQueries->getByCompanyId(session('admin_company_id')),
            'dreamPrice' => $dreamPrice,
            'locations' => $locations,
            'saleChannels' => $saleChannels,
        ]);
    }

    public function update(DreamPriceData $dreamPriceData, int $dreamPriceId): RedirectResponse
    {
        $this->validateSelectedRecordsWithCompany(session('admin_company_id'), $dreamPriceData);

        $dreamPrice = $this->dreamPriceQueries->getById($dreamPriceId, session('admin_company_id'));

        if (false === $dreamPrice->status) {
            abort(417, 'This dream price is currently inactive.');
        }

        DB::beginTransaction();

        try {
            $this->dreamPriceQueries->update($dreamPriceData, $dreamPriceId, session('admin_company_id'));

            DB::commit();

            return to_route('admin.dream_prices.index')
                ->with('success', 'Dream price updated successfully.');
        } catch (Throwable $throwable) {
            Log::error('Update Dream Price', [
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

    public function uploadForm(int $dreamPriceId): Response
    {
        return Inertia::render('dream_prices/UploadForm', [
            'dreamPriceId' => $dreamPriceId,
        ]);
    }

    public function uploadProducts(
        Request $request,
        DreamPriceProductsData $dreamPriceProductsData,
        int $dreamPriceId
    ): RedirectResponse {
        /** @var Admin $admin */
        $admin = $request->user();

        $companyId = session('admin_company_id');
        $dreamPriceProductQueries = resolve(DreamPriceProductQueries::class);
        $importRecordQueries = resolve(ImportRecordQueries::class);
        $importRecordService = resolve(ImportRecordService::class);
        $dreamPrice = $this->dreamPriceQueries->getById($dreamPriceId, $companyId);

        if (false === $dreamPrice->status) {
            abort(417, 'This dream price is currently inactive.');
        }

        $importRecordService->validateColumns(
            $dreamPriceProductsData->dream_price_products,
            [],
            $companyId,
            ImportTypes::DREAM_PRICE->value
        );

        DB::beginTransaction();

        try {
            $dreamPriceProductQueries->delete($dreamPrice);

            $importRecordData = new ImportRecordData(
                ImportTypes::DREAM_PRICE->value,
                $dreamPriceProductsData->dream_price_products
            );

            $importRecord = $importRecordQueries->addNew($importRecordData, $admin, $companyId, $dreamPrice);
            DB::commit();

            ImportRecordsJob::dispatch($importRecord)->onQueue('high');

            return to_route('admin.dream_prices.index')
                ->with('success', 'Dream-priced products added successfully.');
        } catch (Throwable $throwable) {
            Log::error('Dream Price Product Upload', [
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

    public function getDreamPriceProduct(int $dreamPriceId): array
    {
        $dreamPriceProductQueries = resolve(DreamPriceProductQueries::class);
        $dreamPriceProducts = $dreamPriceProductQueries->getByIdWithProduct($dreamPriceId);

        return [
            'dream_price_products' => DreamPriceProductDetailResource::collection($dreamPriceProducts),
        ];
    }

    public function exportDreamPriceProducts(int $dreamPriceId, string $filename): BinaryFileResponse
    {
        $dreamPriceProductQueries = resolve(DreamPriceProductQueries::class);
        $dreamPriceProducts = $dreamPriceProductQueries->getByIdWithProduct($dreamPriceId);

        return Excel::download(new DreamPriceProductExport($dreamPriceProducts), $filename);
    }

    public function exportDreamPrices(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'status' => $request->get('status'),
        ];

        $dreamPrices = $this->dreamPriceQueries->getDreamPricesExport($filterData, session('admin_company_id'));

        return Excel::download(new DreamPriceExport($dreamPrices), $filename);
    }

    public function updateStatus(int $dreamPriceId, bool $status): RedirectResponse
    {
        $this->dreamPriceQueries->updateStatus($dreamPriceId, session('admin_company_id'), $status);

        return to_route('admin.dream_prices.index')->with('success', 'Status changed successfully.');
    }

    public function syncData(int $saleChannelId, Request $request): void
    {
        DreamPriceSyncMainJob::dispatch($saleChannelId, session('admin_company_id'))->onQueue('high');

        $saleChannelService = resolve(SaleChannelService::class);

        /** @var Admin $admin */
        $admin = $request->user();

        $saleChannelService->updateSyncData(
            $saleChannelId,
            SyncTypes::DREAM_PRICE->value,
            $admin,
            session('admin_company_id')
        );
    }

    private function validateSelectedRecordsWithCompany(int $companyId, DreamPriceData $dreamPriceData): void
    {
        $locationQueries = resolve(LocationQueries::class);

        $allStoresExist = $locationQueries->doAllStoresExist($companyId, $dreamPriceData->location_ids);

        if (! $allStoresExist) {
            throw new RedirectWithErrorException(
                'admin.dream_prices.index',
                'One of the selected stores does not match the current company.'
            );
        }

        if (null !== $dreamPriceData->sale_channel_ids) {
            $saleChannelQueries = resolve(SaleChannelQueries::class);

            $allSaleChannelExist = $saleChannelQueries->doAllSaleChannelExist(
                $companyId,
                $dreamPriceData->sale_channel_ids
            );

            if (! $allSaleChannelExist) {
                throw new RedirectWithErrorException(
                    'admin.dream_prices.index',
                    'One of the selected sale channel does not match the current company.'
                );
            }
        }
    }

    private function fetchLocations(int $companyId): Collection
    {
        $locationQueries = resolve(LocationQueries::class);

        return $locationQueries->getStoreWithBasicColumns($companyId);
    }
}

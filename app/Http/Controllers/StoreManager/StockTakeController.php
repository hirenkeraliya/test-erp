<?php

declare(strict_types=1);

namespace App\Http\Controllers\StoreManager;

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\ImportRecord\DataObjects\ImportRecordData;
use App\Domains\ImportRecord\Enums\ImportTypes;
use App\Domains\ImportRecord\Enums\Status;
use App\Domains\ImportRecord\ImportRecordQueries;
use App\Domains\ImportRecord\Jobs\ImportRecordsJob;
use App\Domains\ImportRecord\Services\ImportRecordService;
use App\Domains\Permission\Enums\PermissionList;
use App\Domains\StockTake\DataObjects\StockTakesBulkData;
use App\Domains\StockTake\Exports\StockTakeExportForStoreManager;
use App\Domains\StockTake\Jobs\StockTakeJob;
use App\Domains\StockTake\Resources\StoreStockTakeListResource;
use App\Domains\StockTake\StockTakeQueries;
use App\Domains\StockTakeProduct\Exports\StoreManagerStockTakeProductDownload;
use App\Domains\StockTakeProduct\Exports\StoreManagerStockTakeProductExport;
use App\Domains\StockTakeProduct\Jobs\StockTakeProductsUpdateActualStockJob;
use App\Domains\StockTakeProduct\StockTakeProductQueries;
use App\Exceptions\RedirectBackWithErrorException;
use App\Exceptions\RedirectWithErrorException;
use App\Http\Controllers\Controller;
use App\Models\Color;
use App\Models\Product;
use App\Models\Size;
use App\Models\StockTakeProduct;
use App\Models\StoreManager;
use App\Models\UnitOfMeasure;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class StockTakeController extends Controller
{
    public function __construct(
        protected StockTakeQueries $stockTakeQueries
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('stock_takes/Index', [
            'statuses' => Status::getStatuses(),
            'stockTakeModelMappingType' => ModelMapping::STOCK_TAKES->name,
            'exportPermission' => PermissionList::getExportPermissionName('stock_take'),
        ]);
    }

    /**
     * @return array<string, int>|array<string, AnonymousResourceCollection>
     */
    public function fetchStockTake(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $lengthAwarePaginator = $this->stockTakeQueries->listQuery(
            $filterData,
            session('store_manager_selected_location_id'),
            session('store_manager_selected_location_company_id')
        );

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => StoreStockTakeListResource::collection($lengthAwarePaginator->getCollection()),
        ];
    }

    public function addStockTake(Request $request): RedirectResponse
    {
        $validatedData = $request->validate([
            'stock_record_date' => ['required', 'date', 'date_format:Y-m-d'],
            'notes' => ['nullable', 'string'],
        ]);

        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        $locationId = session('store_manager_selected_location_id');
        $companyId = session('store_manager_selected_location_company_id');
        $anyPendingStockTakeByStoreManager = $this->stockTakeQueries->anyPendingStockTakeByManager(
            $locationId,
            $companyId
        );

        if ($anyPendingStockTakeByStoreManager) {
            abort(412, 'A stock take is already pending for the selected location. Please complete it first.');
        }

        $validatedData['company_id'] = $companyId;
        $validatedData['requested_by_id'] = $storeManager->id;
        $validatedData['requested_by_type'] = ModelMapping::STORE_MANAGER->name;
        $validatedData['location_id'] = $locationId;

        $stockTake = $this->stockTakeQueries->addNew($validatedData);

        $importRecordQueries = resolve(ImportRecordQueries::class);

        DB::beginTransaction();

        try {
            $importRecord = $importRecordQueries->addNewForStockTake(
                ImportTypes::STOCK_TAKES->value,
                $storeManager,
                $companyId,
                $stockTake
            );

            DB::commit();

            StockTakeJob::dispatch($stockTake->id, $importRecord->id, $importRecord->company_id)->onQueue('high');

            return to_route('store_manager.stock_takes.index')
                ->with(
                    'success',
                    'The process of adding stock products will be happening in the background. We will show it soon.'
                );
        } catch (Throwable $throwable) {
            Log::error('Stock Take-Store', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
            DB::rollBack();

            abort(412, 'An error occurred. Please try again.');
        }
    }

    public function stockTakeProducts(int $stockTakeId): Response|RedirectResponse
    {
        $isStockTakePending = $this->stockTakeQueries->isStockTakePending($stockTakeId);

        if ($isStockTakePending) {
            throw new RedirectWithErrorException(
                'store_manager.stock_takes.index',
                'The stock take has already been submitted.'
            );
        }

        return Inertia::render('stock_takes/Manage', [
            'stockTakeId' => $stockTakeId,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function fetchStockTakeProducts(Request $request, int $stockTakeId): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'page' => $request->get('page'),
        ];

        $stockTakeProductQueries = resolve(StockTakeProductQueries::class);

        $stockTakeProducts = $stockTakeProductQueries->getLists(
            $filterData,
            $stockTakeId,
            session('store_manager_selected_location_id'),
            session('store_manager_selected_location_company_id')
        );

        $records = collect(
            $this->prepareDataArticleNumberWise($stockTakeProducts->groupBy('product.article_number'))
        );

        $lengthAwarePaginator = new LengthAwarePaginator(
            $records->forPage($filterData['page'], $filterData['per_page']),
            $records->count(),
            $filterData['per_page'],
            $filterData['page']
        );

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => $lengthAwarePaginator->getCollection()->toArray(),
        ];
    }

    public function grandTotalSubmittedStock(int $stockTakeId): array
    {
        $filterData = [
            'search_text' => null,
        ];

        $stockTakeProductQueries = resolve(StockTakeProductQueries::class);
        $lengthAwarePaginator = $stockTakeProductQueries->getLists(
            $filterData,
            $stockTakeId,
            session('store_manager_selected_location_id'),
            session('store_manager_selected_location_company_id')
        );

        return [
            'grandTotal' => $lengthAwarePaginator->sum('submitted_stock'),
        ];
    }

    public function updateSubmittedStock(Request $request, int $stockTakeId): void
    {
        $validatedData = $request->validate([
            'stock_take_product_id' => ['required', 'integer'],
            'product_id' => ['required', 'integer'],
            'submitted_stock' => ['required', 'numeric', 'min:0'],
        ]);

        $stockTakeProductQueries = resolve(StockTakeProductQueries::class);
        $stockTakeProductQueries->updateSubmittedStock(
            $validatedData,
            $stockTakeId,
            session('store_manager_selected_location_id'),
            session('store_manager_selected_location_company_id')
        );
    }

    public function submitStockTake(Request $request, int $stockTakeId): RedirectResponse
    {
        $validatedData = $request->validate([
            'compare_stock_date' => ['required', 'date', 'date_format:Y-m-d'],
        ]);

        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        $locationId = session('store_manager_selected_location_id');

        $stockTakeProductQueries = resolve(StockTakeProductQueries::class);

        DB::beginTransaction();

        try {
            $this->stockTakeQueries->submit(
                $stockTakeId,
                $storeManager->id,
                $locationId,
                ModelMapping::STORE_MANAGER->name,
                $validatedData['compare_stock_date'],
                session('store_manager_selected_location_company_id')
            );

            DB::commit();

            $productIds = $stockTakeProductQueries->getProductIdsByStockTakeId($stockTakeId);

            foreach (array_chunk($productIds, 5000) as $chunkedProductIds) {
                StockTakeProductsUpdateActualStockJob::dispatch(
                    $validatedData['compare_stock_date'],
                    $stockTakeId,
                    $chunkedProductIds,
                    $locationId,
                    ModelMapping::STORE_MANAGER->name,
                    session('store_manager_selected_location_company_id')
                )->onQueue('high');
            }

            return redirect(route('store_manager.stock_takes.index'))
                ->with('success', 'The stock take has been submitted successfully.');
        } catch (Exception $exception) {
            DB::rollBack();

            Log::error('Stock Take Products actual stock update', [
                'error_message' => 'Error message: ' . $exception->getMessage(),
                'error_code' => 'Error code: ' . $exception->getCode(),
                'file' => 'File: ' . $exception->getFile(),
                'line' => 'Line: ' . $exception->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($exception->getTrace(), JSON_PRETTY_PRINT),
                'Stock Take Products actual stock update' => $exception->getMessage(),
                'Full error' => [$exception],
            ]);

            throw new RedirectBackWithErrorException('An error occurred. Please try again.');
        }
    }

    public function exportStockTakeProducts(int $stockTakeId, string $fileName): BinaryFileResponse
    {
        $stockTakeProductQueries = resolve(StockTakeProductQueries::class);
        $stockTakeProducts = $stockTakeProductQueries->getProductsOfSubmittedStockTake(
            $stockTakeId,
            session('store_manager_selected_location_id'),
            session('store_manager_selected_location_company_id')
        );

        return Excel::download(new StoreManagerStockTakeProductExport($stockTakeProducts), $fileName);
    }

    public function downloadStockTakeProducts(int $stockTakeId, string $fileName, Request $request): BinaryFileResponse
    {
        $filterData = [
            'brand_ids' => $request->brand_ids,
            'department_ids' => $request->department_ids,
            'color_ids' => $request->color_ids,
            'size_ids' => $request->size_ids,
        ];

        $stockTakeProductQueries = resolve(StockTakeProductQueries::class);
        $stockTakeProducts = $stockTakeProductQueries->downloadStockTakeProducts(
            $stockTakeId,
            session('store_manager_selected_location_id'),
            $filterData,
            session('store_manager_selected_location_company_id')
        );

        return Excel::download(new StoreManagerStockTakeProductDownload($stockTakeProducts), $fileName);
    }

    public function bulkUpdateStocks(
        Request $request,
        StockTakesBulkData $stockTakesBulkData,
        int $stockTakeId
    ): RedirectResponse {
        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        $companyId = session('store_manager_selected_location_company_id');

        $importRecordQueries = resolve(ImportRecordQueries::class);
        $importRecordService = resolve(ImportRecordService::class);

        $importRecordService->validateColumns(
            $stockTakesBulkData->stock_take_bulk_submitted_stocks,
            [],
            $companyId,
            ImportTypes::STOCK_TAKES->value
        );

        DB::beginTransaction();

        try {
            $stockTakes = $this->stockTakeQueries->getById($stockTakeId);
            $importRecordData = new ImportRecordData(
                ImportTypes::STOCK_TAKES->value,
                $stockTakesBulkData->stock_take_bulk_submitted_stocks
            );

            $importRecord = $importRecordQueries->addNew($importRecordData, $storeManager, $companyId, $stockTakes);

            DB::commit();

            ImportRecordsJob::dispatch($importRecord)->onQueue('high');

            return to_route('store_manager.stock_takes.index')->with(
                'success',
                'Bulk update for submitted stock performed successfully.'
            );
        } catch (Throwable $throwable) {
            Log::error('Stock takes data Upload', [
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
     * @return array<string, mixed>
     */
    public function getPendingStockProductsSubmissionCount(int $stockTakeId): array
    {
        $stockTakeProductQueries = resolve(StockTakeProductQueries::class);

        $pendingStockProductsSubmissionCount = $stockTakeProductQueries->getPendingStockProductsSubmissionCount(
            $stockTakeId,
            session('store_manager_selected_location_id'),
            session('store_manager_selected_location_company_id')
        );

        return [
            'pending_stock_products_submission_count' => $pendingStockProductsSubmissionCount,
        ];
    }

    public function exportStockTakes(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $stockTakes = $this->stockTakeQueries->getStoreAndWarehouseMangerStockTakesExport(
            $filterData,
            session('store_manager_selected_location_id'),
            session('store_manager_selected_location_company_id')
        );

        return Excel::download(new StockTakeExportForStoreManager($stockTakes), $filename);
    }

    public function updateSubmittedStockByStockId(Request $request, int $stockTakeId): void
    {
        $validatedRecords = $request->validate([
            'products' => ['required', 'array'],
            'products.*.product_id' => ['required', 'integer'],
            'products.*.submitted_stock' => ['required', 'numeric', 'min:0'],
        ]);

        $stockTakeProductQueries = resolve(StockTakeProductQueries::class);

        foreach ($validatedRecords['products'] as $validatedData) {
            $stockTakeProductQueries->updateSubmittedStockByStockId(
                $validatedData,
                $stockTakeId,
                session('store_manager_selected_location_id'),
                session('store_manager_selected_location_company_id')
            );
        }
    }

    private function prepareDataArticleNumberWise(Collection $stockTakeProductsData): array
    {
        $articleNumberWiseData = [];
        foreach ($stockTakeProductsData as $stockTakeProductData) {
            /** @var StockTakeProduct $stockTakeProduct */
            $stockTakeProduct = $stockTakeProductData->first();

            /** @var Product $product */
            $product = $stockTakeProduct->product;

            $articleNumberWiseData[$product->article_number] = [
                'product' => $product->name,
                'article_number' => $product->article_number,
                'items' => [],
            ];

            foreach ($stockTakeProductData as $stockTakeProduct) {
                /** @var Product $product */
                $product = $stockTakeProduct->product;

                /** @var ?Color $color */
                $color = $product->color;

                /** @var ?Size $size */
                $size = $product->size;

                /** @var ?UnitOfMeasure $unitOfMeasure */
                $unitOfMeasure = $product->unitOfMeasure;

                $articleNumberWiseData[$product->article_number]['items'][] = [
                    'id' => $product->id,
                    'stock_take_product_id' => $stockTakeProduct->id,
                    'UPC' => $product->upc,
                    'color' => $color instanceof Color ? $color->getName() : 'N/A',
                    'size' => $size instanceof Size ? $size->getName() : 'N/A',
                    'unit_of_measure' => $unitOfMeasure instanceof UnitOfMeasure ? $unitOfMeasure->name : 'N/A',
                    'submitted_stock' => $stockTakeProduct->submitted_stock,
                ];
            }
        }

        return $articleNumberWiseData;
    }
}

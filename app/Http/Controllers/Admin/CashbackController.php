<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\Cashback\CashbackQueries;
use App\Domains\Cashback\DataObjects\CashbackData;
use App\Domains\Cashback\Enums\ConditionTypes;
use App\Domains\Cashback\Enums\ExcludeByTypes;
use App\Domains\Cashback\Exports\CashbackExport;
use App\Domains\Cashback\Exports\CashbackProductsExport;
use App\Domains\Cashback\Resources\AdminCashbackListResource;
use App\Domains\Category\CategoryQueries;
use App\Domains\Common\Enums\DiscountTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\Permission\Enums\PermissionList;
use App\Domains\Product\ProductQueries;
use App\Exceptions\RedirectBackWithErrorException;
use App\Exceptions\RedirectWithErrorException;
use App\Http\Controllers\Controller;
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

class CashbackController extends Controller
{
    public function __construct(
        protected CashbackQueries $cashbackQueries
    ) {
    }

    public function index(): Response
    {
        $companyId = session('admin_company_id');

        $locationQueries = resolve(LocationQueries::class);

        $locations = $locationQueries->getStoreWithBasicColumns($companyId);

        return Inertia::render('cashbacks/Index', [
            'locations' => $locations,
            'exportPermission' => PermissionList::getExportPermissionName('cashback'),
        ]);
    }

    /**
     * @return array<string, int>|array<string, AnonymousResourceCollection>
     */
    public function fetchCashbacks(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'date_range' => $request->get('date_range'),
            'location_ids' => $request->get('location_ids'),
        ];

        $lengthAwarePaginator = $this->cashbackQueries->listQuery($filterData, session('admin_company_id'));

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => AdminCashbackListResource::collection($lengthAwarePaginator->getCollection()),
        ];
    }

    public function create(): Response
    {
        return Inertia::render('cashbacks/Manage', $this->getCommonRecords(session('admin_company_id')));
    }

    public function store(CashbackData $cashbackData): RedirectResponse
    {
        $this->checkRequestDetails(session('admin_company_id'), $cashbackData);

        DB::beginTransaction();

        try {
            $this->cashbackQueries->addNew($cashbackData, session('admin_company_id'));

            DB::commit();

            return to_route('admin.cashbacks.index')
                ->with('success', 'Cashback added successfully.');
        } catch (Throwable $throwable) {
            Log::error('Add Cashback', [
                'error_message' => $throwable->getMessage(),
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

    public function edit(int $cashbackId): Response
    {
        $cashback = $this->cashbackQueries->getByIdWithStoresProductsAndCategories(
            $cashbackId,
            session('admin_company_id')
        );
        $cashback['tiers'] = $cashback->cashbackPrices;

        return Inertia::render('cashbacks/Manage', [
            'cashback' => $cashback,
            ...$this->getCommonRecords(session('admin_company_id')),
        ]);
    }

    public function update(CashbackData $cashbackData, int $cashbackId): RedirectResponse
    {
        DB::beginTransaction();

        try {
            $this->cashbackQueries->update($cashbackData, $cashbackId, session('admin_company_id'));

            DB::commit();

            return to_route('admin.cashbacks.index')
                ->with('success', 'Cashback updated successfully.');
        } catch (Throwable $throwable) {
            Log::error('Update Cashback', [
                'error_message' => $throwable->getMessage(),
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

    public function removeSelectedProducts(Request $request): void
    {
        $validatedData = $request->validate([
            'id' => ['required', 'exists:cashbacks,id'],
        ]);

        $this->cashbackQueries->removeSelectedProducts($validatedData, session('admin_company_id'));
    }

    public function exportCashBackProducts(int $id, string $filename, Request $request): BinaryFileResponse
    {
        $request->validate([
            'id' => ['required', 'integer'],
        ]);

        $cashBackProducts = $this->cashbackQueries->getByIdWithCashbackProducts(
            $id,
            session('admin_company_id')
        )->products;

        return Excel::download(new CashbackProductsExport($cashBackProducts), $filename);
    }

    public function exportCashbacks(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'date_range' => $request->get('date_range'),
            'location_ids' => $request->get('location_ids'),
        ];

        $cashbacks = $this->cashbackQueries->getCashbacksExport($filterData, session('admin_company_id'));

        return Excel::download(new CashbackExport($cashbacks), $filename);
    }

    /**
     * @return array<int, mixed[]>|Collection[]
     */
    private function getCommonRecords(int $companyId): array
    {
        $locationQueries = resolve(LocationQueries::class);
        $categoryQueries = resolve(CategoryQueries::class);

        return [
            'locations' => $locationQueries->getStoreWithBasicColumns($companyId),
            'categories' => $categoryQueries->getMainCategoriesWithBasicColumns($companyId),
            'excludeByTypes' => ExcludeByTypes::formattedForSelection(),
            'conditionTypes' => ConditionTypes::formattedForSelection(),
            'excludeByTypeOptions' => [
                'none' => ExcludeByTypes::NONE->value,
                'products' => ExcludeByTypes::PRODUCTS->value,
                'categories' => ExcludeByTypes::CATEGORIES->value,
                'originalItemPrice' => ExcludeByTypes::ORIGINAL_ITEM_PRICE->value,
                'discountItemPrice' => ExcludeByTypes::DISCOUNT_ITEM_PRICE->value,
            ],
            'discountTypes' => DiscountTypes::formattedForSelection(),
            'discountStaticTypes' => DiscountTypes::getFormattedArrayForStaticUse(),
        ];
    }

    private function checkRequestDetails(int $companyId, CashbackData $cashbackData): void
    {
        $locationQueries = resolve(LocationQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $categoryQueries = resolve(CategoryQueries::class);

        $allStoresExist = $locationQueries->doAllStoresExist($companyId, $cashbackData->location_ids);

        if (! $allStoresExist) {
            throw new RedirectWithErrorException(
                'admin.cashbacks.index',
                'One of the selected stores does not match the current company.'
            );
        }

        if ($cashbackData->product_ids) {
            $allProductsExist = $productQueries->doAllActiveProductsExist($companyId, $cashbackData->product_ids);

            if (! $allProductsExist) {
                throw new RedirectWithErrorException(
                    'admin.cashbacks.index',
                    'One of the selected products does not match the current company.'
                );
            }
        }

        if ($cashbackData->category_ids) {
            $allCategoriesExist = $categoryQueries->doAllParentCategoriesExist($companyId, $cashbackData->category_ids);

            if (! $allCategoriesExist) {
                throw new RedirectWithErrorException(
                    'admin.cashbacks.index',
                    'One of the selected categories does not match the current company.'
                );
            }
        }
    }
}

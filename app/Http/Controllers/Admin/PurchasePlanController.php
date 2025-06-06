<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\Admin\AdminQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\Permission\Enums\PermissionList;
use App\Domains\PurchasePlan\DataObjects\PurchasePlanData;
use App\Domains\PurchasePlan\Enums\Statuses;
use App\Domains\PurchasePlan\PurchasePlanQueries;
use App\Domains\PurchasePlan\Resource\PurchasePlanEditResource;
use App\Domains\PurchasePlan\Services\PurchasePlanCheckRequestService;
use App\Domains\PurchasePlan\Services\PurchasePlanPrintService;
use App\Domains\PurchasePlan\Services\PurchasePlanService;
use App\Domains\Vendor\VendorQueries;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class PurchasePlanController extends Controller
{
    public function __construct(
        protected PurchasePlanQueries $purchasePlanQueries
    ) {
    }

    public function index(): Response
    {
        $companyId = session('admin_company_id');

        $locationQueries = resolve(LocationQueries::class);
        $vendorQueries = resolve(VendorQueries::class);
        $locations = $locationQueries->getWithBasicColumns($companyId);
        $stores = $locations->where('type_id', LocationTypes::STORE->value)->values()->all();
        $warehouses = $locations->where('type_id', LocationTypes::WAREHOUSE->value)->values()->all();

        return Inertia::render('purchase_plans/Index', [
            'statuses' => Statuses::getStatuses(),
            'planStatuses' => Statuses::getList(),
            'stores' => $stores,
            'warehouses' => $warehouses,
            'exportPermission' => PermissionList::getExportPermissionName('purchase_order'),
            'staticLocationTypes' => LocationTypes::getFormattedArrayForStaticUse(),
            'locationTypes' => LocationTypes::getList(),
            'vendors' => $vendorQueries->getWithBasicColumns($companyId),
        ]);
    }

    public function fetchPurchasePlans(Request $request): array
    {
        $companyId = session('admin_company_id');

        $filterData = $this->getPreparedFilters($request);

        $purchasePlanService = resolve(PurchasePlanService::class);

        return $purchasePlanService->fetchPurchasePlans($filterData, $companyId);
    }

    public function create(): Response
    {
        $companyId = session('admin_company_id');

        $locationQueries = resolve(LocationQueries::class);
        $locations = $locationQueries->getWithBasicColumns($companyId);
        $stores = $locations->where('type_id', LocationTypes::STORE->value)->values()->all();
        $warehouses = $locations->where('type_id', LocationTypes::WAREHOUSE->value)->values()->all();
        $vendorQueries = resolve(VendorQueries::class);

        return Inertia::render('purchase_plans/Manage', [
            'stores' => $stores,
            'warehouses' => $warehouses,
            'staticDetails' => [
                'staticLocationTypes' => LocationTypes::getFormattedArrayForStaticUse(),
            ],
            'locationTypes' => LocationTypes::getList(),
            'vendors' => $vendorQueries->getWithBasicColumns($companyId),
        ]);
    }

    public function store(PurchasePlanData $purchasePlanData): RedirectResponse
    {
        $companyId = session('admin_company_id');
        $purchasePlanCheckRequestService = resolve(PurchasePlanCheckRequestService::class);
        $products = $purchasePlanCheckRequestService->getProducts($companyId, $purchasePlanData);
        $purchasePlanCheckRequestService->checkRequestDetails($products, $purchasePlanData);

        DB::beginTransaction();

        try {
            $purchasePlanService = resolve(PurchasePlanService::class);
            $purchasePlanService->savePurchasePlan($purchasePlanData->all(), $products, $companyId);

            DB::commit();

            return to_route('admin.purchase_plans.index')
                ->with('success', 'Purchase Plan is created successfully.');
        } catch (Throwable $throwable) {
            Log::error([
                'purchase order' => $throwable->getMessage(),
                'Full error' => [$throwable],
            ]);
            DB::rollBack();

            abort(417, 'An error occurred. Please try again.');
        }
    }

    public function edit(int $purchasePlanId): Response
    {
        $companyId = session('admin_company_id');
        $vendorQueries = resolve(VendorQueries::class);
        $purchasePlan = $this->purchasePlanQueries->getByIdWithItems($purchasePlanId);

        $locationQueries = resolve(LocationQueries::class);
        $locations = $locationQueries->getWithBasicColumns($companyId);
        $stores = $locations->where('type_id', LocationTypes::STORE->value)->values()->all();
        $warehouses = $locations->where('type_id', LocationTypes::WAREHOUSE->value)->values()->all();

        $purchasePlanService = resolve(PurchasePlanService::class);

        $purchasePlan['source_inventories'] = $purchasePlanService->getStocks($purchasePlan);

        return Inertia::render('purchase_plans/Manage', [
            'stores' => $stores,
            'warehouses' => $warehouses,
            'staticDetails' => [
                'staticLocationTypes' => LocationTypes::getFormattedArrayForStaticUse(),
            ],
            'locationTypes' => LocationTypes::getList(),
            'vendors' => $vendorQueries->getWithBasicColumns($companyId),
            'purchasePlan' => new PurchasePlanEditResource($purchasePlan),
        ]);
    }

    public function update(PurchasePlanData $purchasePlanData, int $purchasePlanId): RedirectResponse
    {
        $companyId = session('admin_company_id');
        $purchasePlanCheckRequestService = resolve(PurchasePlanCheckRequestService::class);
        $products = $purchasePlanCheckRequestService->getProducts($companyId, $purchasePlanData);
        $purchasePlanCheckRequestService->checkRequestDetails($products, $purchasePlanData);

        DB::beginTransaction();

        try {
            $this->purchasePlanQueries->update($purchasePlanData->all(), $purchasePlanId, $products);

            DB::commit();

            return to_route('admin.purchase_plans.index')
                ->with('success', 'Purchase Plan Update successfully.');
        } catch (Throwable $throwable) {
            Log::error([
                'purchase plan' => $throwable->getMessage(),
                'Full error' => [$throwable],
            ]);
            DB::rollBack();

            abort(417, 'An error occurred. Please try again.');
        }
    }

    public function exportPurchasePlans(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = $this->getPreparedFilters($request);

        $companyId = session('admin_company_id');

        $purchasePlanService = resolve(PurchasePlanService::class);

        return $purchasePlanService->exportPurchasePlans($filterData, $filename, $companyId);
    }

    public function getPreparedFilters(Request $request): array
    {
        return [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'select_status' => $request->get('select_status'),
            'date_range' => $request->get('date_range'),
            'location_id' => $request->get('location_id'),
            'vendor_id' => $request->get('vendor_id'),
            'plan_number' => $request->get('plan_number'),
        ];
    }

    public function fetchPurchasePlanItemByPurchasePlanId(int $purchasePlanId): array
    {
        $purchasePlanService = resolve(PurchasePlanService::class);

        return $purchasePlanService->fetchPurchasePlanItemByPurchasePlanId($purchasePlanId);
    }

    public function exportPurchasePlanItems(int $purchasePlanId, string $fileName): BinaryFileResponse
    {
        $purchasePlanService = resolve(PurchasePlanService::class);

        return $purchasePlanService->exportPurchasePlanItems($purchasePlanId, $fileName);
    }

    public function cancel(Request $request, int $purchasePlanId): RedirectResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();

        $adminQueries = resolve(AdminQueries::class);
        $admin = $adminQueries->loadEmployee($admin);

        $companyId = session('admin_company_id');

        $purchasePlan = $this->purchasePlanQueries->getById($purchasePlanId, $companyId);

        $purchasePlanService = resolve(PurchasePlanService::class);

        DB::beginTransaction();

        try {
            $purchasePlanService->purchasePlanMarkAsCanceled($purchasePlan, $admin);

            DB::commit();

            return to_route('admin.purchase_plans.index')->with(
                'success',
                'The specified purchase plan has been marked as canceled successfully'
            );
        } catch (Throwable $throwable) {
            Log::error([
                'purchase order' => $throwable->getMessage(),
                'Full error' => [$throwable],
            ]);
            DB::rollBack();

            abort(417, 'An error occurred. Please try again.');
        }
    }

    public function approve(Request $request, int $purchasePlanId): RedirectResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();

        $adminQueries = resolve(AdminQueries::class);
        $admin = $adminQueries->loadEmployee($admin);

        $companyId = session('admin_company_id');

        $purchasePlan = $this->purchasePlanQueries->getById($purchasePlanId, $companyId);

        $purchasePlanService = resolve(PurchasePlanService::class);

        DB::beginTransaction();

        try {
            $purchasePlanService->purchasePlanMarkAsApprove($purchasePlan, $admin);

            DB::commit();

            return to_route('admin.purchase_plans.index')->with(
                'success',
                'The specified purchase plan has been marked as approved successfully'
            );
        } catch (Throwable $throwable) {
            Log::error([
                'purchase order' => $throwable->getMessage(),
                'Full error' => [$throwable],
            ]);
            DB::rollBack();

            abort(417, 'An error occurred. Please try again.');
        }
    }

    public function print(int $purchasePlanId): string
    {
        $purchasePlanPrintService = resolve(PurchasePlanPrintService::class);

        $companyId = session('admin_company_id');

        return $purchasePlanPrintService->print($purchasePlanId, $companyId);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Domains\GenuineProductVerification\Exports\ProductVerificationReportExport;
use App\Domains\GenuineProductVerification\GenuineProductVerificationQueries;
use App\Domains\GenuineProductVerification\Recourses\ProductsVerificationReportListResource;
use App\Domains\GenuineProductVerification\Services\ProductVerificationReportService;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\Permission\Enums\PermissionList;
use App\Domains\Product\ProductQueries;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class GenuineProductVerificationReportController extends Controller
{
    public function productVerificationReports(Request $request): Response
    {
        $productId = (int) $request->get('product_id');
        $companyId = session('admin_company_id');
        $locationId = (int) $request->get('location_id');

        $dateRange = [now()->format('Y-m-d 00:00:00'), now()->format('Y-m-d 23:59:59')];
        $locationQueries = resolve(LocationQueries::class);
        $selectedLocations = [];
        if ($locationId > 0) {
            $location = $locationQueries->getById($locationId, $companyId, LocationTypes::STORE->value);
            $selectedLocations = [
                'code' => $location->code,
                'id' => $location->id,
                'name' => $location->name,
            ];

            $dateRange = [];
        }

        $selectedProducts = [];
        $productQueries = resolve(ProductQueries::class);
        if ($productId > 0) {
            $product = $productQueries->getByIdOnlyName((int) $request->get('product_id'), $companyId);

            $selectedProducts[] = [
                'id' => $product->id,
                'name' => $product->name,
            ];
        }

        $filterData = [
            'locationIds' => $locationId > 0 ? [$locationId] : null,
            'productIds' => $productId > 0 ? [$productId] : null,
            'selectedLocations' => $selectedLocations,
            'selectedProducts' => $selectedProducts,
            'dateRange' => $dateRange,
        ];

        $locations = $locationQueries->getStoreWithBasicColumns($companyId);

        return Inertia::render('reports/genuine_product_report/Index', [
            'locations' => $locations,
            'filterData' => $filterData,
            'exportPermission' => PermissionList::getExportPermissionName('genuine_product_verification'),
        ]);
    }

    public function fetchProductVerificationReports(Request $request): array
    {
        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $genuineProductVerificationQueries = resolve(GenuineProductVerificationQueries::class);
        $lengthAwarePaginator = $genuineProductVerificationQueries->getPaginatedProductVerificationReport(
            $filterData,
            $companyId
        );

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => ProductsVerificationReportListResource::collection($lengthAwarePaginator),
        ];
    }

    public function printProductVerifications(Request $request): string
    {
        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        /** @var array $exportColumns */
        $exportColumns = $filterData['export_columns'];

        $filteredColumns = collect($exportColumns)->pluck('key');

        $productVerificationService = resolve(ProductVerificationReportService::class);

        return $productVerificationService->print($filterData, $filteredColumns, $companyId);
    }

    public function exportProductsVerificationReport(string $filename, Request $request): BinaryFileResponse
    {
        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        /** @var array $exportColumns */
        $exportColumns = $filterData['export_columns'];

        $filteredColumns = collect($exportColumns)->pluck('key');

        $genuineProductVerificationQueries = resolve(GenuineProductVerificationQueries::class);
        $genuineProductVerification = $genuineProductVerificationQueries->getProductVerificationReportDataForExport(
            $filterData,
            $companyId
        );

        return Excel::download(
            new ProductVerificationReportExport($genuineProductVerification, $filteredColumns),
            $filename
        );
    }

    private function getFilterData(Request $request): array
    {
        return [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'product_ids' => $request->get('product_ids'),
            'location_ids' => $request->get('location_ids'),
            'date_range' => $request->get('date_range'),
            'is_genuine' => $request->get('is_genuine'),
            'export_columns' => $request->get('export_columns'),
        ];
    }
}

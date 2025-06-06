<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\Consignment\Exports\ConsignmentReportExport;
use App\Domains\Consignment\Resources\AdminConsignmentReportListResource;
use App\Domains\Consignment\Services\ConsignmentReportService;
use App\Domains\Permission\Enums\PermissionList;
use App\Domains\Product\ProductQueries;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Collection as SupportCollection;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ConsignmentReportController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('reports/consignment_report/Index', [
            'helpCenterMessages' => "The consignment report displays each active product's mapped to vendors with consignment status true with commission percentage. <br/> Additionally, the report provides units sold, sales, and commission for each product, as well as overall totals for all products. It includes filter and export functionalities.",
            'exportPermission' => PermissionList::getExportPermissionName('consignment_report'),
        ]);
    }

    /**
     * @return array<string, int>|array<string, AnonymousResourceCollection>|array<string, float>
     */
    public function fetchConsignmentReport(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'per_page' => $request->get('per_page'),
            'date_range' => $request->get('date_range'),
        ];

        $productQueries = resolve(ProductQueries::class);
        $companyId = session('admin_company_id');

        $lengthAwarePaginator = $productQueries->getPaginatedConsignmentReport($filterData, $companyId);

        $products = $productQueries->getConsignmentReportForExport($filterData, $companyId);

        [$totalUnitsSold, $totalSales, $totalCommission] = $this->badgesCount($products);

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => AdminConsignmentReportListResource::collection($lengthAwarePaginator),
            'total_units_sold' => $totalUnitsSold,
            'total_sales' => $totalSales,
            'total_commission' => $totalCommission,
        ];
    }

    public function exportConsignmentReport(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'per_page' => $request->get('per_page'),
            'date_range' => $request->get('date_range'),
            'export_columns' => $request->get('export_columns'),
        ];

        /** @var array $exportColumns */
        $exportColumns = $filterData['export_columns'];

        $filteredColumns = collect($exportColumns)->pluck('key');

        $productQueries = resolve(ProductQueries::class);
        $products = $productQueries->getConsignmentReportForExport($filterData, session('admin_company_id'));

        return Excel::download(new ConsignmentReportExport($products, $filteredColumns), $filename);
    }

    public function printConsignment(Request $request): string
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'per_page' => $request->get('per_page'),
            'date_range' => $request->get('date_range'),
        ];

        $filterData['export_columns'] = $request->get('export_columns');

        /** @var array $exportColumns */
        $exportColumns = $filterData['export_columns'];

        $filteredColumns = collect($exportColumns)->pluck('key');

        $companyId = session('admin_company_id');

        $consignmentService = resolve(ConsignmentReportService::class);

        return $consignmentService->print($filterData, $companyId, $filteredColumns);
    }

    private function badgesCount(SupportCollection $products): array
    {
        $totalUnitsSold = 0;
        $totalSales = 0;
        $totalCommission = 0;
        foreach ($products as $product) {
            $totalUnitsSold += $product->saleItems->sum('quantity');
            $totalSales += $totalUnitsSold * $product->retail_price;
            $totalCommission += ($totalSales * $product->vendor->commission_percentage) / 100;
        }

        return [$totalUnitsSold, $totalSales, $totalCommission];
    }
}

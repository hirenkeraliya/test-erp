<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\Currency\CurrencyQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\Permission\Enums\PermissionList;
use App\Domains\StoreDayClose\Exports\StoreDayCloseExport;
use App\Domains\StoreDayClose\Resources\DayCloseReportListResource;
use App\Domains\StoreDayClose\StoreDayCloseQueries;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Location;
use App\Models\PaymentType;
use App\Models\StoreManager;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DayCloseReportController extends Controller
{
    public function index(): Response
    {
        $companyId = session('admin_company_id');
        $locationQueries = resolve(LocationQueries::class);
        $locations = $locationQueries->getStoreWithBasicColumns($companyId);

        return Inertia::render('reports/day_close/Index', [
            'locations' => $locations,
            'exportPermission' => PermissionList::getExportPermissionName('day_close'),
            'helpCenterMessages' => 'Show all the day close report with sale details, payment details, cash transaction details, order details and order payments and offering advanced filters, search options, and seamless export capabilities for detailed analysis and insights.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function fetchDayCloseReport(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'location_ids' => $request->get('location_ids'),
            'employee_id' => $request->get('employee_id'),
            'date_range' => $request->get('date_range'),
            'closed_at' => $request->get('closed_at'),
        ];

        $storeDayCloseQueries = resolve(StoreDayCloseQueries::class);

        $lengthAwarePaginator = $storeDayCloseQueries->getPaginatedDayCloseReportList(
            $filterData,
            session('admin_company_id')
        );
        [$totalSaleCollectionAmount, $totalOrderCollectionAmount] = $storeDayCloseQueries->totalSaleCollectionAmount(
            $filterData,
            session('admin_company_id')
        );

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => DayCloseReportListResource::collection($lengthAwarePaginator->getCollection()),
            'total_sale_collection' => $totalSaleCollectionAmount,
            'total_order_collection' => $totalOrderCollectionAmount,
        ];
    }

    public function exportStoreDayClose(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'location_ids' => $request->get('location_ids'),
            'employee_id' => $request->get('employee_id'),
            'date_range' => $request->get('date_range'),
            'closed_at' => $request->get('closed_at'),
            'export_columns' => $request->get('export_columns'),
        ];

        /** @var array $exportColumns */
        $exportColumns = $filterData['export_columns'];

        $filteredColumns = collect($exportColumns)->pluck('key');

        $storeDayCloseQueries = resolve(StoreDayCloseQueries::class);

        $storeDayCloseLists = $storeDayCloseQueries->getPaginatedDayCloseListForExport(
            $filterData,
            session('admin_company_id')
        );

        return Excel::download(new StoreDayCloseExport($storeDayCloseLists, $filteredColumns), $filename);
    }

    public function fetchDayClosedReportById(int $dayCloseId): array
    {
        $storeDayCloseQueries = resolve(StoreDayCloseQueries::class);
        $dayCloseDetails = $storeDayCloseQueries->getDayCloseReportById(session('admin_company_id'), $dayCloseId);

        return [
            'day_close_details' => $dayCloseDetails,
        ];
    }

    public function printDayCloseReport(int $id): string
    {
        $storeDayCloseQueries = resolve(StoreDayCloseQueries::class);

        $storeDayCloseDetails = $storeDayCloseQueries->getDayCloseReportById(session('admin_company_id'), $id);

        /** @var Location $location */
        $location = $storeDayCloseDetails->location;

        /** @var ?StoreManager $storeManager */
        $storeManager = $storeDayCloseDetails->storeManager;

        $employee = null;
        if ($storeManager instanceof StoreManager) {
            /** @var Employee $employee */
            $employee = $storeManager->employee;
        }

        $currencyQueries = resolve(CurrencyQueries::class);
        $currency = $currencyQueries->getByCompanyId(session('admin_company_id'));

        $storeDayCloseDetails['location'] = $location->name;
        $storeDayCloseDetails['store_manager'] = $employee instanceof Employee ? $employee->getFullName() : 'Automatic';
        $storeDayCloseDetails['payments'] = $this->getPreparedStoreDayClosePayments($storeDayCloseDetails->payments);

        return view('prints.day_close_report', [
            'dayClose' => $storeDayCloseDetails,
            'currencySymbol' => $currency->getSymbol(),
        ])->render();
    }

    /**
     * @return mixed[]
     */
    private function getPreparedStoreDayClosePayments(?Collection $payments): array
    {
        if (! $payments instanceof Collection) {
            return [];
        }

        return $payments->map(function ($storeDayClosePayment): array {
            /** @var PaymentType $paymentType */
            $paymentType = $storeDayClosePayment->paymentType;

            return [
                'id' => $storeDayClosePayment->id,
                'payment_type' => $paymentType->name,
                'total_transactions' => $storeDayClosePayment->total_transactions,
                'total' => $storeDayClosePayment->total_amount,
            ];
        })->toArray();
    }
}

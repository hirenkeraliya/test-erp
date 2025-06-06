<?php

declare(strict_types=1);

namespace App\Http\Controllers\StoreManager;

use App\Domains\Currency\CurrencyQueries;
use App\Domains\Permission\Enums\PermissionList;
use App\Domains\StoreDayClose\Exports\StoreDayCloseExport;
use App\Domains\StoreDayClose\Resources\DayCloseReportListResource;
use App\Domains\StoreDayClose\StoreDayCloseQueries;
use App\Domains\StoreManager\StoreManagerQueries;
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
        $storeManagerQueries = resolve(StoreManagerQueries::class);

        $storeManagers = $storeManagerQueries->getByStoreIdWithEmployee(session('store_manager_selected_location_id'));

        $storeManagers->transform(function ($storeManager): array {
            /** @var Employee $employee */
            $employee = $storeManager->employee;

            return [
                'id' => $storeManager->id,
                'name' => $employee->getFullName(),
            ];
        });

        return Inertia::render('reports/day_close/Index', [
            'storeManagers' => $storeManagers,
            'exportPermission' => PermissionList::getExportPermissionName('day_close_report'),
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
            'store_manager_id' => $request->get('store_manager_id'),
            'date_range' => $request->get('date_range'),
            'closed_at' => $request->get('closed_at'),
        ];

        $storeDayCloseQueries = resolve(StoreDayCloseQueries::class);

        $lengthAwarePaginator = $storeDayCloseQueries->getPaginatedDayCloseReportListForStoreManager(
            $filterData,
            session('store_manager_selected_location_id'),
        );

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => DayCloseReportListResource::collection($lengthAwarePaginator->getCollection()),
        ];
    }

    public function exportStoreDayClose(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'store_manager_id' => $request->get('store_manager_id'),
            'date_range' => $request->get('date_range'),
            'closed_at' => $request->get('closed_at'),
            'export_columns' => $request->get('export_columns'),
        ];

        /** @var array $exportColumns */
        $exportColumns = $filterData['export_columns'];

        $filteredColumns = collect($exportColumns)->pluck('key');

        $storeDayCloseQueries = resolve(StoreDayCloseQueries::class);

        $storeDayCloseQueries = resolve(StoreDayCloseQueries::class);

        $storeDayCloseLists = $storeDayCloseQueries->getDayCloseListForExportInStoreManagerPanel(
            $filterData,
            session('store_manager_selected_location_id'),
        );

        return Excel::download(new StoreDayCloseExport($storeDayCloseLists, $filteredColumns), $filename);
    }

    public function printDayCloseReport(int $id): string
    {
        $storeDayCloseQueries = resolve(StoreDayCloseQueries::class);

        $storeDayCloseDetails = $storeDayCloseQueries->getDayCloseReportById(
            session('store_manager_selected_location_company_id'),
            $id
        );

        $currencyQueries = resolve(CurrencyQueries::class);
        $currency = $currencyQueries->getByCompanyId(session('store_manager_selected_location_company_id'));

        /** @var Location $location */
        $location = $storeDayCloseDetails->location;

        /** @var ?StoreManager $storeManager */
        $storeManager = $storeDayCloseDetails->storeManager;

        $employee = null;
        if ($storeManager instanceof StoreManager) {
            /** @var Employee $employee */
            $employee = $storeManager->employee;
        }

        $storeDayCloseDetails['location'] = $location->name;
        $storeDayCloseDetails['payments'] = $this->getPreparedStoreDayClosePayments($storeDayCloseDetails->payments);
        $storeDayCloseDetails['store_manager'] = $employee instanceof Employee ? $employee->getFullName() : 'Automatic';

        return view('prints.day_close_report', [
            'dayClose' => $storeDayCloseDetails,
            'currencySymbol' => $currency->getSymbol(),
        ])->render();
    }

    public function fetchDayClosedReportById(int $dayCloseId): array
    {
        $storeDayCloseQueries = resolve(StoreDayCloseQueries::class);
        $dayCloseDetails = $storeDayCloseQueries->getDayCloseReportById(
            session('store_manager_selected_location_company_id'),
            $dayCloseId
        );

        return [
            'day_close_details' => $dayCloseDetails,
        ];
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

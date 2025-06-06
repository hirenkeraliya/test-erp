<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\StoreManager;

use App\CommonFunctions;
use App\Domains\Company\RoundOffConfiguration;
use App\Domains\Counter\CounterQueries;
use App\Domains\Employee\EmployeeQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\Member\MemberQueries;
use App\Domains\Promoter\PromoterQueries;
use App\Domains\Sale\SaleQueries;
use App\Domains\SaleItem\SaleItemQueries;
use App\Domains\SaleReturn\SaleReturnQueries;
use App\Domains\SaleReturnItem\SaleReturnItemQueries;
use App\Domains\StockTransfer\Enums\StatusTypes;
use App\Domains\StockTransfer\Enums\StockTransferTypes;
use App\Domains\StockTransfer\StockTransferQueries;
use App\Domains\StoreManager\StoreManagerQueries;
use App\Domains\StoreWiseDailyTotal\StoreWiseDailyTotalQueries;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Promoter;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\StoreManager;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function getDashboardData(Request $request): array
    {
        $validatedData = $request->validate([
            'store_id' => ['required_without_all:location_id', 'integer', 'exists:locations,id'],
            'location_id' => ['required_without_all:store_id', 'integer', 'exists:locations,id'],
        ]);

        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);
        /** @var int $companyId */
        $companyId = $employeeQueries->getEmployeeCompanyId($storeManager->employee_id);

        $locationId = $validatedData['store_id'] ?? $validatedData['location_id'];

        [$currentNetSales, $currentTotalReceiptCounts, $currentNetSaleReturns,$currentTotalSaleReturnReceiptCounts] = $this->getCurrentDaysData(
            (int) $locationId,
            $companyId
        );
        [$thisMonthNetSales, $thisMonthTotalReceiptCounts, $thisMonthNetSaleReturns, $thisMonthTotalSaleReturnReceiptCounts] = $this->getThisMonthDaysData(
            (int) $locationId,
            $companyId
        );

        return [
            'today' => [
                'net_sales' => (float) $currentNetSales,
                'total_receipts' => (int) $currentTotalReceiptCounts,
                'net_sale_returns' => (float) $currentNetSaleReturns,
                'total_sale_return_receipts' => (int) $currentTotalSaleReturnReceiptCounts,
            ],
            'this_month' => [
                'net_sales' => (float) $thisMonthNetSales,
                'total_receipts' => (int) $thisMonthTotalReceiptCounts,
                'net_sale_returns' => (float) $thisMonthNetSaleReturns,
                'total_sale_return_receipts' => (int) $thisMonthTotalSaleReturnReceiptCounts,
            ],
        ];
    }

    public function getTransferStatusesData(Request $request): array
    {
        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        $validatedData = $request->validate([
            'store_id' => ['required_without_all:location_id', 'integer', 'exists:locations,id'],
            'location_id' => ['required_without_all:store_id', 'integer', 'exists:locations,id'],
        ]);

        $locationId = $validatedData['store_id'] ?? $validatedData['location_id'];

        $this->checkStoreAuthority($storeManager->id, (int) $locationId);

        $locationQueries = resolve(LocationQueries::class);
        $companyId = $locationQueries->getCompanyIdOfStore((int) $locationId);

        $filterData = [
            'location_id' => (int) $locationId,
            'transfer_type' => null,
        ];

        return [
            'transfer_orders' => $this->getTransferOrders($filterData, $companyId, (int) $locationId),
            'request_orders' => $this->getRequestOrders($filterData, $companyId, (int) $locationId),
        ];
    }

    public function getDashboardAllDetails(Request $request): array
    {
        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);
        /** @var int $companyId */
        $companyId = $employeeQueries->getEmployeeCompanyId($storeManager->employee_id);

        $validatedData = $request->validate([
            'store_id' => ['required_without_all:location_id', 'integer', 'exists:locations,id'],
            'location_id' => ['required_without_all:store_id', 'integer', 'exists:locations,id'],
        ]);

        $locationId = $validatedData['store_id'] ?? $validatedData['location_id'];

        $this->checkStoreAuthority($storeManager->id, (int) $locationId);

        [$date, $monthDate, $yearlyDate] = $this->formatDateRanges();

        return [
            'total_counters' => $this->getTotalCounterCounts((int) $locationId),
            'open_counters' => $this->getTotalOpenCounterCounts((int) $locationId),
            'total_promoters' => $this->getPromoterCounts((int) $locationId),
            'total_registered_members' => $this->getTotalMemberCounts((int) $locationId),
            'total_members_registered_this_month' => $this->getTotalCurrentMonthMemberCounts(
                (int) $locationId,
                $monthDate
            ),
            'today' => $this->getTodaySalesAndSaleReturns($date, (int) $locationId, $companyId),
            'this_month' => $this->getCurrentMonthSalesAndSaleReturns($monthDate, (int) $locationId, $companyId),
            'yearly' => $this->getCurrentYearSalesAndSaleReturns($yearlyDate, (int) $locationId, $companyId),
        ];
    }

    public function getTopTenPromoter(Request $request): array
    {
        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        $validatedData = $request->validate([
            'store_id' => ['required_without_all:location_id', 'integer', 'exists:locations,id'],
            'location_id' => ['required_without_all:store_id', 'integer', 'exists:locations,id'],
        ]);

        $locationId = $validatedData['store_id'] ?? $validatedData['location_id'];

        $this->checkStoreAuthority($storeManager->id, (int) $locationId);

        $startOfMonth = Carbon::now()->startOfMonth()->format('Y-m-d');
        $currentDate = Carbon::now()->format('Y-m-d');

        $locationId = (int) $locationId;
        $locationQueries = resolve(LocationQueries::class);
        $companyId = $locationQueries->getCompanyIdOfStore($locationId);

        $promoterQueries = resolve(PromoterQueries::class);
        $promoters = $promoterQueries->getSalesByPromotersForDashboard(
            $companyId,
            $locationId,
            null,
            $startOfMonth,
            $currentDate,
            false,
        );

        $topTenPromoter = $promoters->map(function (Promoter $promoter): array {
            /** @var Employee $employee */
            $employee = $promoter->employee;

            return [
                'id' => $promoter['id'],
                'name' => $employee->getFullName() . '(' . $employee->staff_id . ')',
                'units_sold' => $promoter['units_sold'] ?? 0,
                'amount_sold' => $promoter['amount_sold'] ?? 0,
            ];
        });

        return [
            'top_ten_promoter' => $topTenPromoter,
        ];
    }

    private function getTodaySalesAndSaleReturns(array $date, int $locationId, int $companyId): array
    {
        [$totalSales, $totalReturnSales, $totalSaleAmount, $totalSaleReturnAmount, $netUnit] = $this->getDashboardDetails(
            $locationId,
            $date,
            $companyId
        );
        $totalSaleReturnAmount = CommonFunctions::numberFormat((float) $totalSaleReturnAmount);
        $totalSaleReturnAmount = CommonFunctions::numberFormat(
            $totalSaleReturnAmount + RoundOffConfiguration::roundOffCalculationFor((string) $totalSaleReturnAmount)
        );

        $totalSaleAmount = CommonFunctions::numberFormat((float) $totalSaleAmount);
        $totalSaleAmount = CommonFunctions::numberFormat(
            $totalSaleAmount + RoundOffConfiguration::roundOffCalculationFor((string) $totalSaleAmount)
        );

        return [
            'total_sales' => $totalSales,
            'total_return_sales' => $totalReturnSales,
            'total_sale_amount' => $totalSaleAmount,
            'total_sale_return_amount' => $totalSaleReturnAmount,
            'net_sale_amount' => CommonFunctions::numberFormat($totalSaleAmount - $totalSaleReturnAmount),
            'unit_sold' => CommonFunctions::numberFormat($netUnit),
        ];
    }

    private function getCurrentMonthSalesAndSaleReturns(array $monthDate, int $locationId, int $companyId): array
    {
        [$totalMonthSales, $totalMonthReturnSales, $totalMonthSaleAmount, $totalMonthSaleReturnAmount] = $this->getDashboardDetailsForMonth(
            $locationId,
            $monthDate,
            $companyId
        );

        $totalMonthSaleAmount = CommonFunctions::numberFormat((float) $totalMonthSaleAmount);
        $totalMonthSaleAmount = CommonFunctions::numberFormat(
            $totalMonthSaleAmount + RoundOffConfiguration::roundOffCalculationFor((string) $totalMonthSaleAmount)
        );

        $totalMonthSaleReturnAmount = CommonFunctions::numberFormat((float) $totalMonthSaleReturnAmount);
        $totalMonthSaleReturnAmount = CommonFunctions::numberFormat(
            $totalMonthSaleReturnAmount + RoundOffConfiguration::roundOffCalculationFor(
                (string) $totalMonthSaleReturnAmount
            )
        );

        return [
            'total_month_sales' => $totalMonthSales,
            'total_month_return_sales' => $totalMonthReturnSales,
            'total_month_sale_amount' => $totalMonthSaleAmount,
            'total_month_sale_return_amount' => $totalMonthSaleReturnAmount,
            'net_month_sale_amount' => CommonFunctions::numberFormat(
                $totalMonthSaleAmount - $totalMonthSaleReturnAmount
            ),
            'unit_sold' => CommonFunctions::numberFormat($totalMonthSales - $totalMonthReturnSales),
        ];
    }

    private function getCurrentYearSalesAndSaleReturns(array $yearlyDate, int $locationId, int $companyId): array
    {
        [$totalYearlySales, $totalYearlyReturnSales, $totalYearlySaleAmount, $totalYearlySaleReturnAmount] = $this->getDashboardDetailsForYear(
            $locationId,
            $yearlyDate,
            $companyId
        );

        $totalYearlySaleAmount = CommonFunctions::numberFormat((float) $totalYearlySaleAmount);
        $totalYearlySaleAmount = CommonFunctions::numberFormat(
            $totalYearlySaleAmount + RoundOffConfiguration::roundOffCalculationFor((string) $totalYearlySaleAmount)
        );

        $totalYearlySaleReturnAmount = CommonFunctions::numberFormat((float) $totalYearlySaleReturnAmount);

        $totalYearlySaleReturnAmount = CommonFunctions::numberFormat(
            $totalYearlySaleReturnAmount + RoundOffConfiguration::roundOffCalculationFor(
                (string) $totalYearlySaleReturnAmount
            )
        );

        return [
            'total_yearly_sales' => $totalYearlySales,
            'total_yearly_return_sales' => $totalYearlyReturnSales,
            'total_yearly_sale_amount' => $totalYearlySaleAmount,
            'total_yearly_sale_return_amount' => $totalYearlySaleReturnAmount,
            'net_yearly_sale_amount' => CommonFunctions::numberFormat(
                $totalYearlySaleAmount - $totalYearlySaleReturnAmount
            ),
            'unit_sold' => CommonFunctions::numberFormat($totalYearlySales - $totalYearlyReturnSales),
        ];
    }

    private function getPromoterCounts(int $locationId): int
    {
        $promoterQueries = resolve(PromoterQueries::class);

        return $promoterQueries->getPromoterCount($locationId);
    }

    private function getDashboardDetails(int $locationId, array $date, int $companyId): array
    {
        $saleItemQueries = resolve(SaleItemQueries::class);
        $saleReturnItemQueries = resolve(SaleReturnItemQueries::class);

        /** @var SaleItem $saleItem */
        $saleItem = $saleItemQueries->getSaleItemsForTheStoreManagerApplicationDashboard(
            $locationId,
            $date,
            $companyId
        );

        /** @var SaleReturnItem $saleReturnItem */
        $saleReturnItem = $saleReturnItemQueries->getSaleReturnItemForTheStoreManagerApplicationDashboard(
            $locationId,
            $date
        );

        return [
            $saleItem['total_sales'],
            $saleReturnItem['total_sales'],
            (float) $saleItem['total_sales_amount'],
            (float) $saleReturnItem['total_sales_amount'],
            (int) $saleItem['unit_sold'] - $saleReturnItem['return_units'],
        ];
    }

    private function getDashboardDetailsForMonth(int $locationId, array $date, int $companyId): array
    {
        $storeWiseDailyTotalQueries = resolve(StoreWiseDailyTotalQueries::class);
        $monthSalesDetails = $storeWiseDailyTotalQueries->getTotalSalesDetailsByDateForStoreManagerApplication(
            $locationId,
            $date,
            $companyId
        );

        return [
            $monthSalesDetails['total_sales'],
            $monthSalesDetails['total_sales_return'],
            (float) $monthSalesDetails['total_sales_amount'],
            (float) $monthSalesDetails['total_sales_return_amount'],
        ];
    }

    private function getDashboardDetailsForYear(int $locationId, array $date, int $companyId): array
    {
        $storeWiseDailyTotalQueries = resolve(StoreWiseDailyTotalQueries::class);
        $monthSalesDetails = $storeWiseDailyTotalQueries->getTotalSalesDetailsByDateForStoreManagerApplication(
            $locationId,
            $date,
            $companyId
        );

        return [
            $monthSalesDetails['total_sales'],
            $monthSalesDetails['total_sales_return'],
            (float) $monthSalesDetails['total_sales_amount'],
            (float) $monthSalesDetails['total_sales_return_amount'],
        ];
    }

    private function getTotalCounterCounts(int $locationId): int
    {
        $counterQueries = resolve(CounterQueries::class);

        return $counterQueries->getCountByLocation($locationId);
    }

    private function getTotalOpenCounterCounts(int $locationId): int
    {
        $counterQueries = resolve(CounterQueries::class);

        return $counterQueries->getCountByOpenCounterForLocation($locationId);
    }

    private function getTotalMemberCounts(int $locationId): int
    {
        $locationQueries = resolve(LocationQueries::class);
        $companyId = $locationQueries->getCompanyIdOfStore($locationId);

        $memberQueries = resolve(MemberQueries::class);

        return $memberQueries->getTotalRegisteredMembersForStoreManagerDashboard($locationId, $companyId);
    }

    private function getTotalCurrentMonthMemberCounts(int $locationId, array $monthDate): int
    {
        $locationQueries = resolve(LocationQueries::class);
        $companyId = $locationQueries->getCompanyIdOfStore($locationId);

        $memberQueries = resolve(MemberQueries::class);

        return $memberQueries->getTotalMembersRegisteredThisMonthForStoreManagerDashboard(
            $locationId,
            $companyId,
            $monthDate
        );
    }

    private function formatDateRanges(): array
    {
        $currentDate = Carbon::now()->format('Y-m-d');
        $startOfMonth = Carbon::now()->startOfMonth()->format('Y-m-d');
        $endOfMonth = Carbon::now()->endOfMonth()->format('Y-m-d');
        $startOfYear = Carbon::now()->firstOfYear()->format('Y-m-d');
        $endOfYear = Carbon::now()->lastOfYear()->format('Y-m-d');

        $dateRange = [$currentDate, $currentDate];
        $monthDateRange = [$startOfMonth, $endOfMonth];
        $yearlyDateRange = [$startOfYear, $endOfYear];

        return [$dateRange, $monthDateRange, $yearlyDateRange];
    }

    private function getCurrentDaysData(int $locationId, int $companyId): array
    {
        $currentDate = Carbon::now()->format('Y-m-d');

        $date = [$currentDate, $currentDate];

        $saleQueries = resolve(SaleQueries::class);
        $saleReturnQueries = resolve(SaleReturnQueries::class);

        /** @var Sale $sale */
        $sale = $saleQueries->getSalesReceiptCount($locationId, $date);

        /** @var SaleReturn $saleReturn */
        $saleReturn = $saleReturnQueries->getSaleReturnsReceiptCount($locationId, $companyId, $date);

        return [
            (float) $sale['total_sales_amount'],
            $sale['total_sales'],
            (float) $saleReturn['total_sales_amount'],
            $saleReturn['total_sales'],
        ];
    }

    private function getThisMonthDaysData(int $locationId, int $companyId): array
    {
        $startOfMonth = Carbon::now()->startOfMonth()->format('Y-m-d');
        $currentDate = Carbon::now()->format('Y-m-d');

        $saleQueries = resolve(SaleQueries::class);
        $saleReturnQueries = resolve(SaleReturnQueries::class);

        /** @var Sale $sale */
        $sale = $saleQueries->getSalesReceiptCount($locationId, [$startOfMonth, $currentDate]);

        /** @var SaleReturn $saleReturn */
        $saleReturn = $saleReturnQueries->getSaleReturnsReceiptCount(
            $locationId,
            $companyId,
            [$startOfMonth, $currentDate]
        );

        return [
            (float) $sale['total_sales_amount'],
            $sale['total_sales'],
            (float) $saleReturn['total_sales_amount'],
            $saleReturn['total_sales'],
        ];
    }

    private function checkStoreAuthority(int $storeManagerId, int $locationId): void
    {
        $storeManagerQueries = resolve(StoreManagerQueries::class);
        $storeManagerWithStoreExists = $storeManagerQueries->existsByIdAndStoreId($storeManagerId, $locationId);

        if (! $storeManagerWithStoreExists) {
            abort(412, 'You do not have authorization for the selected location.');
        }
    }

    private function getTransferOrders(array $filterData, int $companyId, int $locationId): array
    {
        $transferOrders = [];

        $stockTransferQueries = resolve(StockTransferQueries::class);
        $transferOrderStatusCounts = $stockTransferQueries->storeManagerTransferOrderStatusCount(
            [StockTransferTypes::TRANSFER_ORDER->value],
            $filterData,
            $companyId,
            $locationId
        );

        foreach ($transferOrderStatusCounts as $transferOrderStatusCount) {
            $statusName = StatusTypes::getFormattedCaseName($transferOrderStatusCount->status);
            $transferOrders[] = [
                'id' => $transferOrderStatusCount->status,
                'name' => $statusName,
                'count' => $transferOrderStatusCount->count,
            ];
        }

        return $transferOrders;
    }

    private function getRequestOrders(array $filterData, int $companyId, int $locationId): array
    {
        $requestOrders = [];

        $stockTransferQueries = resolve(StockTransferQueries::class);
        $requestOrderStatusCounts = $stockTransferQueries->storeManagerRequestOrderStatusCount(
            [StockTransferTypes::REQUEST_ORDER->value],
            $filterData,
            $companyId,
            $locationId
        );

        foreach ($requestOrderStatusCounts as $requestOrderStatusCount) {
            $statusName = StatusTypes::getFormattedCaseName($requestOrderStatusCount->status);
            $requestOrders[] = [
                'id' => $requestOrderStatusCount->status,
                'name' => $statusName,
                'count' => $requestOrderStatusCount->count,
            ];
        }

        return $requestOrders;
    }
}

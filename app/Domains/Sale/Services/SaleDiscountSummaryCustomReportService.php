<?php

declare(strict_types=1);

namespace App\Domains\Sale\Services;

use App\CommonFunctions;
use App\Domains\Company\CompanyQueries;
use App\Domains\CustomReport\Services\CustomReportService;
use App\Domains\Location\LocationQueries;
use App\Domains\Sale\Enums\SaleDiscountTypeReports;
use App\Domains\Sale\Exports\SaleDiscountSummaryReportExport;
use App\Domains\Sale\SaleQueries;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\Location;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SaleDiscountSummaryCustomReportService
{
    public function print(array $filterData, int $companyId): string
    {
        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);

        $saleQueries = resolve(SaleQueries::class);
        $sales = $saleQueries->getSaleDiscounts($filterData);

        $locationQueries = resolve(LocationQueries::class);
        $locations = $locationQueries->getByIdsWithNameAndCode($company->id, $filterData['location_ids']);

        [$saleDiscounts, $columns, $dateRange, $grandTotal] = $this->preparedByDocument(
            $sales,
            $filterData,
            $locations
        );

        return view('prints.sale_discount_summary_report', [
            'saleDiscounts' => $saleDiscounts,
            'dateRange' => $dateRange,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'company' => $company,
            'columns' => $columns,
            'grandTotal' => $grandTotal,
            'reportType' => $filterData['report_type'] && 0 !== $filterData['report_type'] ? SaleDiscountTypeReports::getFormattedCaseName(
                (int) $filterData['report_type']
            ) : 'All Discount',
        ])->render();
    }

    public function export(array $filterData, int $companyId, string $filename): BinaryFileResponse
    {
        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);

        $locationQueries = resolve(LocationQueries::class);
        $locations = $locationQueries->getByIdsWithNameAndCode($company->id, $filterData['location_ids']);

        $saleQueries = resolve(SaleQueries::class);
        $sales = $saleQueries->getSaleDiscounts($filterData);

        [$saleDiscounts, $columns, $dateRange, $grandTotal] = $this->preparedByDocument(
            $sales,
            $filterData,
            $locations
        );

        $reportType = $filterData['report_type'] ? SaleDiscountTypeReports::getFormattedCaseName(
            (int) $filterData['report_type']
        ) : 'All Discount';

        return Excel::download(
            new SaleDiscountSummaryReportExport(
                $saleDiscounts,
                $dateRange,
                $company,
                $columns,
                $reportType,
                $grandTotal
            ),
            $filename
        );
    }

    public function preparedByDocument(Collection $sales, array $filterData, Collection $locations): array
    {
        $customReportService = resolve(CustomReportService::class);
        $dateRange = $customReportService->prepareDateRange($filterData);

        $locationsSales = [];
        $grandTotal = [];

        foreach ($locations as $location) {
            $locationSales = [
                'location_name' => $location->name . ' [' . $location->code . ']',
                'sales_data' => [],
            ];

            $saleWiseDiscounts = $this->getSaleWiseDiscounts(
                $sales->where('counterUpdate.counter.location_id', $location->id)
            );

            $locationSales['sales_data'] = $saleWiseDiscounts;

            $grandTotal['grand_cart_total'] = $saleWiseDiscounts->sum('cart_total');
            $grandTotal['grand_cart_discount'] = $saleWiseDiscounts->sum('cart_discount');
            $grandTotal['grand_total_five_per_off'] = $saleWiseDiscounts->sum('five_per_off');
            $grandTotal['grand_total_ten_per_off'] = $saleWiseDiscounts->sum('ten_per_off');
            $grandTotal['grand_total_twenty_per_off'] = $saleWiseDiscounts->sum('twenty_per_off');
            $grandTotal['grand_total_thirty_per_off'] = $saleWiseDiscounts->sum('thirty_per_off');
            $grandTotal['grand_total_forty_per_off'] = $saleWiseDiscounts->sum('forty_per_off');
            $grandTotal['grand_total_other_discount'] = $saleWiseDiscounts->sum('other_discount');

            $locationsSales[] = $locationSales;
        }

        $columns = [
            'Offline Sale ID',
            'Location Code',
            'Counter Code',
            'Date',
            'Cart Total',
            'Cart Discount',
            '5% OFF',
            '10% OFF',
            '20% OFF',
            '30% OFF',
            '40% OFF',
            'Other discounts',
        ];

        return [$locationsSales, $columns, $dateRange, $grandTotal];
    }

    public function getSaleWiseDiscounts(Collection $sales): Collection
    {
        $saleWiseDiscounts = collect([]);

        foreach ($sales as $sale) {
            /** @var Collection $saleDiscounts */
            $saleDiscounts = $sale->saleDiscounts;

            /** @var CounterUpdate $counterUpdate */
            $counterUpdate = $sale->counterUpdate;

            /** @var Counter $counter */
            $counter = $counterUpdate->counter;

            /** @var Location $location */
            $location = $counter->location;
            $discountsAmount = $saleDiscounts->sum('amount');
            $amount = $sale->total_amount_paid + $sale->cart_discount_amount;

            /** @var Carbon $happenedAtFormat */
            $happenedAtFormat = Carbon::createFromFormat('Y-m-d H:i:s', $sale->happened_at);
            $happenedAt = $happenedAtFormat->format('d-m-Y');

            $discountPercentage = CommonFunctions::numberFormat($discountsAmount * 100 / $amount);

            $saleWiseDiscounts->push([
                'offline_sale_id' => $sale->offline_sale_id,
                'location_code' => $location->code,
                'counter_code' => $counter->name,
                'date' => $happenedAt,
                'cart_total' => (float) CommonFunctions::currencyFormat((float) $amount),
                'cart_discount' => (float) CommonFunctions::currencyFormat((float) $discountsAmount),
                'five_per_off' => (float) ($discountPercentage <= 5 ? $discountsAmount : 0),
                'ten_per_off' => (float) ($discountPercentage > 5 && $discountPercentage <= 10 ? $discountsAmount : 0),
                'twenty_per_off' => (float) ($discountPercentage > 10 && $discountPercentage <= 20 ? $discountsAmount : 0),
                'thirty_per_off' => (float) ($discountPercentage > 20 && $discountPercentage <= 30 ? $discountsAmount : 0),
                'forty_per_off' => (float) ($discountPercentage > 30 && $discountPercentage <= 40 ? $discountsAmount : 0),
                'other_discount' => (float) ($discountPercentage > 40 ? $discountPercentage . ' ' . $discountsAmount : 0),
            ]);
        }

        return $saleWiseDiscounts;
    }
}

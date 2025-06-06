<?php

declare(strict_types=1);

namespace App\Domains\Sale\Services;

use App\Domains\Cashier\CashierQueries;
use App\Domains\Company\CompanyQueries;
use App\Domains\Counter\CounterQueries;
use App\Domains\Currency\CurrencyQueries;
use App\Domains\CustomReport\Services\CustomReportService;
use App\Domains\Location\LocationQueries;
use App\Domains\Sale\Enums\SaleReturnAndSaleExchangeFilterTypes;
use App\Domains\Sale\Exports\SaleReturnAndExchangeReportExport;
use App\Domains\SaleReturn\SaleReturnQueries;
use App\Models\Employee;
use App\Models\SaleItem;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SaleReturnAndSaleExchangeReportService
{
    public function print(int $companyId, array $filterData): string
    {
        [$locationsSales, $company] = $this->prepareData($filterData, $companyId);

        $customReportService = resolve(CustomReportService::class);

        $currencyQueries = resolve(CurrencyQueries::class);
        $currency = $currencyQueries->getByCompanyId($companyId);

        return view('prints.sales_return_and_exchange', [
            'locationSales' => $locationsSales,
            'dateRange' => $customReportService->prepareDateRange($filterData),
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'company' => $company,
            'filterBy' => $this->filterBy($filterData),
            'currencySymbol' => $currency->getSymbol(),
        ])->render();
    }

    public function export(array $filterData, int $companyId, string $fileName): BinaryFileResponse
    {
        [$locationsSales, $company] = $this->prepareData($filterData, $companyId);

        return Excel::download(new SaleReturnAndExchangeReportExport($locationsSales), $fileName);
    }

    private function prepareData(array $filterData, int $companyId): array
    {
        $currencyQueries = resolve(CurrencyQueries::class);
        $currency = $currencyQueries->getByCompanyId($companyId);

        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);
        $locationQueries = resolve(LocationQueries::class);
        $locations = $locationQueries->getByIdsWithNameAndCode($company->id, $filterData['location_ids']);

        $saleReturnQueries = resolve(SaleReturnQueries::class);
        $saleReturns = $saleReturnQueries->getForSaleReturnAndSaleExchangeReport($filterData);

        $customReportService = resolve(CustomReportService::class);

        $locationsSales = [];
        foreach ($locations as $location) {
            $locationSales = [
                'location_name' => $location->name . ' [' . $location->code . ']',
                'sale' => [],
            ];

            foreach ($saleReturns->where('counterUpdate.counter.location_id', $location->id)->sortBy(
                'originalSale.happened_at'
            ) as $saleReturn) {
                $exchangeSale = $saleReturn->exchangeSale;

                $isExchange = false;

                if ($exchangeSale) {
                    $saleItems = $exchangeSale->saleItems;
                    $isExchange = $saleItems->where('is_exchange', true)->isNotEmpty();
                }

                /** @var Carbon $happenedAtFormat */
                $happenedAtFormat = Carbon::createFromFormat('Y-m-d H:i:s', $saleReturn->originalSale->happened_at);
                $happenedAt = $happenedAtFormat->format('d-m-Y');

                /** @var Carbon $saleReturnHappenedAtFormat */
                $saleReturnHappenedAtFormat = Carbon::createFromFormat('Y-m-d H:i:s', $saleReturn->happened_at);
                $saleReturnHappenedAt = $saleReturnHappenedAtFormat->format('d-m-Y');

                $exchangeSaleHappenedAt = null;

                if (null !== $exchangeSale) {
                    /** @var Carbon $exchangeSaleHappenedAtFormat */
                    $exchangeSaleHappenedAtFormat = Carbon::createFromFormat(
                        'Y-m-d H:i:s',
                        $saleReturn->exchangeSale->happened_at
                    );
                    $exchangeSaleHappenedAt = $exchangeSaleHappenedAtFormat->format('d-m-Y');
                }

                $locationSales['sale'][] = [
                    'sale_offline_id' => $saleReturn->originalSale->offline_sale_id,
                    'sale_happened_at' => $happenedAt,
                    'sale_products' => $customReportService->getProductDetails($saleReturn->originalSale->saleItems),
                    'return_sale_offline_id' => $saleReturn->offline_sale_return_id,
                    'return_sale_happened_at' => $saleReturnHappenedAt,
                    'return_sale_products' => $customReportService->getProductDetailsWithReturnAndExchange(
                        $saleReturn->saleReturnItems,
                        $isExchange
                    ),
                    'new_sale_offline_id' => $saleReturn->exchangeSale?->offline_sale_id,
                    'new_sale_happened_at' => $exchangeSaleHappenedAt,
                    'new_sale_products' => $saleReturn->exchangeSale ? $customReportService->getProductDetails(
                        $saleReturn->exchangeSale->saleItems
                    ) : [],
                    'promoters' => implode(',', $this->getPromoters($saleReturn->saleReturnItems)),
                    'currency_symbol' => $currency->getSymbol(),
                ];
            }

            $locationsSales[] = $locationSales;
        }

        return [$locationsSales, $company];
    }

    private function getPromoters(Collection $saleReturnItems): array
    {
        $promotersData = [];
        foreach ($saleReturnItems as $saleReturnItem) {
            $promotersData = [];

            /** @var SaleItem $saleItem */
            $saleItem = $saleReturnItem->saleItem;

            /** @var Collection $promoters */
            $promoters = $saleItem->promoters;

            foreach ($promoters as $promoter) {
                if ($promoters->isEmpty()) {
                    return $promotersData;
                }

                /** @var Employee $employee */
                $employee = $promoter->employee;

                $promotersData[] = $employee->first_name;
            }
        }

        return $promotersData;
    }

    private function filterBy(array $filterData): string
    {
        if (! isset($filterData['filter_by'])) {
            return '';
        }

        $counterQueries = resolve(CounterQueries::class);
        $cashierQueries = resolve(CashierQueries::class);

        $filterBy = (int) $filterData['filter_by'];

        if ($filterBy === SaleReturnAndSaleExchangeFilterTypes::BY_COUNTER->value && isset($filterData['counter_ids']) && '' !== $filterData['counter_ids']) {
            $counters = $counterQueries->getByIds($filterData['counter_ids']);

            return $this->formatFilterResult(
                SaleReturnAndSaleExchangeFilterTypes::BY_COUNTER->value,
                $counters->pluck('name')->implode(', ')
            );
        }

        if ($filterBy === SaleReturnAndSaleExchangeFilterTypes::BY_CASHIER->value && isset($filterData['cashier_ids']) && '' !== $filterData['cashier_ids']) {
            $cashiers = $cashierQueries->getByIds($filterData['cashier_ids']);

            return $this->formatFilterResult(
                SaleReturnAndSaleExchangeFilterTypes::BY_CASHIER->value,
                $cashiers->pluck('employee.first_name')->implode(', ')
            );
        }

        return '';
    }

    private function formatFilterResult(int $filterType, ?string $value): string
    {
        return SaleReturnAndSaleExchangeFilterTypes::getFormattedCaseName($filterType) . ' (' . $value . ')';
    }
}

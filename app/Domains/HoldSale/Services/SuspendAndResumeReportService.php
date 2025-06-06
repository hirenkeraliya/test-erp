<?php

declare(strict_types=1);

namespace App\Domains\HoldSale\Services;

use App\Domains\Cashier\CashierQueries;
use App\Domains\Company\CompanyQueries;
use App\Domains\Counter\CounterQueries;
use App\Domains\Currency\CurrencyQueries;
use App\Domains\CustomReport\Services\CustomReportService;
use App\Domains\HoldSale\Exports\SuspendAndResumeExport;
use App\Domains\HoldSale\HoldSaleQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\SuspendAndResume\Enums\SuspendAndResumeFilterTypes;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SuspendAndResumeReportService
{
    public function print(int $companyId, array $filterData): string
    {
        [$locationsHoldSales, $company] = $this->fetchHoldSaleRecords($companyId, $filterData);

        $customReportService = resolve(CustomReportService::class);

        $currencyQueries = resolve(CurrencyQueries::class);
        $currency = $currencyQueries->getByCompanyId($companyId);

        return view('prints.suspend_and_resume', [
            'locationHoldSales' => $locationsHoldSales,
            'dateRange' => $customReportService->prepareDateRange($filterData),
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'company' => $company,
            'filterBy' => $this->filterBy($filterData),
            'currencySymbol' => $currency->getSymbol(),
        ])->render();
    }

    public function fetchHoldSaleRecords(int $companyId, array $filterData): array
    {
        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);
        $locationQueries = resolve(LocationQueries::class);
        $locations = $locationQueries->getByIdsWithNameAndCode($company->id, $filterData['location_ids']);

        $holdSaleQueries = resolve(HoldSaleQueries::class);
        $holdSale = $holdSaleQueries->getSuspendAndResumeReport($filterData);

        $locationsHoldSales = [];
        foreach ($locations as $location) {
            $locationsHoldSale = [
                'location_name' => $location->name . ' [' . $location->code . ']',
                'hold_sale' => [],
            ];

            foreach ($holdSale->where('counterUpdate.counter.location_id', $location->id) as $holdSale) {
                foreach ($holdSale->holdSaleDetails as $holdSaleDetail) {
                    /** @var Carbon $happenedAtFormat */
                    $happenedAtFormat = Carbon::createFromFormat('Y-m-d H:i:s', $holdSaleDetail->happened_at);
                    $happenedAt = $happenedAtFormat->format('d-m-Y h:i:s A');

                    /** @var Carbon|string $cancelledAt */
                    $cancelledAt = 'N/A';

                    if ($holdSale->cancelled_at) {
                        /** @var Carbon $cancelledAtFormat */
                        $cancelledAtFormat = Carbon::createFromFormat('Y-m-d H:i:s', $holdSale->cancelled_at);
                        $cancelledAt = $cancelledAtFormat->format('d-m-Y h:i:s A');
                    }

                    /** @var Carbon|string $releasedAt */
                    $releasedAt = 'N/A';

                    if ($holdSaleDetail->released_at) {
                        /** @var Carbon $releasedAtFormat */
                        $releasedAtFormat = Carbon::createFromFormat('Y-m-d H:i:s', $holdSaleDetail->released_at);
                        $releasedAt = $releasedAtFormat->format('d-m-Y h:i:s A');
                    }

                    /** @var Carbon|string $completeAt */
                    $completeAt = 'N/A';

                    if ($holdSale->complete_at) {
                        /** @var Carbon $completeAtFormat */
                        $completeAtFormat = Carbon::createFromFormat('Y-m-d H:i:s', $holdSale->complete_at);
                        $completeAt = $completeAtFormat->format('d-m-Y h:i:s A');
                    }

                    $counterUpdate = $holdSale->counterUpdate;
                    $totalNetSale = $holdSaleDetail->total_amount_paid - $holdSaleDetail->total_discount_amount;
                    $locationsHoldSale['hold_sale'][] = [
                        'suspend_counter' => $counterUpdate->counter->name,
                        'suspend_date' => $happenedAt,
                        'suspend_receipt_no' => $holdSale->offline_id,
                        'total_sales' => $holdSaleDetail->total_amount_paid,
                        'discount' => $holdSaleDetail->total_discount_amount,
                        'total_net_sales' => $totalNetSale,
                        'cashier' => $counterUpdate->cashier->employee->getFullName(),
                        'cancelled_date' => $cancelledAt,
                        'reason' => $holdSaleDetail->reason ?? 'N/A',
                        'resume_date' => $releasedAt,
                        'resume_receipt_no' => $holdSale->complete_offline_id ?? 'N/A',
                        'completed_date' => $completeAt,
                    ];
                }
            }

            $locationsHoldSales[] = $locationsHoldSale;
        }

        return [$locationsHoldSales, $company];
    }

    public function exportSuspendAndResume(int $companyId, array $filterData, string $filename): BinaryFileResponse
    {
        [$locationsHoldSales, $company] = $this->fetchHoldSaleRecords($companyId, $filterData);

        $customReportService = resolve(CustomReportService::class);
        $dateRange = $customReportService->prepareDateRange($filterData);

        $filterBy = $this->filterBy($filterData);

        $currencyQueries = resolve(CurrencyQueries::class);
        $currency = $currencyQueries->getByCompanyId($companyId);

        return Excel::download(
            new SuspendAndResumeExport($locationsHoldSales, $dateRange, $company, $filterBy, $currency->getSymbol()),
            $filename
        );
    }

    private function filterBy(array $filterData): string
    {
        if (! isset($filterData['filter_by'])) {
            return '';
        }

        $counterQueries = resolve(CounterQueries::class);
        $cashierQueries = resolve(CashierQueries::class);

        $filterBy = (int) $filterData['filter_by'];

        if ($filterBy === SuspendAndResumeFilterTypes::BY_COUNTER->value && isset($filterData['counter_ids']) && '' !== $filterData['counter_ids']) {
            $counters = $counterQueries->getByIds($filterData['counter_ids']);

            return $this->formatFilterResult(
                SuspendAndResumeFilterTypes::BY_COUNTER->value,
                $counters->pluck('name')->implode(', ')
            );
        }

        if ($filterBy === SuspendAndResumeFilterTypes::BY_CASHIER->value && isset($filterData['cashier_ids']) && '' !== $filterData['cashier_ids']) {
            $cashiers = $cashierQueries->getByIds($filterData['cashier_ids']);

            return $this->formatFilterResult(
                SuspendAndResumeFilterTypes::BY_CASHIER->value,
                $cashiers->pluck('employee.first_name')->implode(', ')
            );
        }

        return '';
    }

    private function formatFilterResult(int $filterType, ?string $value): string
    {
        return SuspendAndResumeFilterTypes::getFormattedCaseName($filterType) . ' (' . $value . ')';
    }
}

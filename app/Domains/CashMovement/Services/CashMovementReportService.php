<?php

declare(strict_types=1);

namespace App\Domains\CashMovement\Services;

use App\CommonFunctions;
use App\Domains\Cashier\CashierQueries;
use App\Domains\CashMovement\CashMovementQueries;
use App\Domains\CashMovement\Enums\CashMovementFilterTypes;
use App\Domains\CashMovement\Enums\CashMovementTypes;
use App\Domains\CashMovement\Exports\CashMovementForCustomExport;
use App\Domains\Company\CompanyQueries;
use App\Domains\Counter\CounterQueries;
use App\Domains\CustomReport\Services\CustomReportService;
use App\Domains\Location\LocationQueries;
use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CashMovementReportService
{
    public function printCashMovement(int $companyId, array $filterData): string
    {
        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);
        $locationQueries = resolve(LocationQueries::class);
        $locations = $locationQueries->getByIdsWithNameAndCode($company->id, $filterData['location_ids']);

        return $this->renderPreparedCashMovement($filterData, $company, $locations);
    }

    public function exportCashMovement(int $companyId, array $filterData, string $filename): BinaryFileResponse
    {
        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);
        $locationQueries = resolve(LocationQueries::class);
        $location = $locationQueries->getByIdsWithNameAndCode($company->id, $filterData['location_ids']);

        $customReportService = resolve(CustomReportService::class);
        $dateRange = $customReportService->prepareDateRange($filterData);

        [$cashMovement, $columns] = $this->preparedCashMovement($filterData, $location, $companyId);

        $filterBy = $this->filterBy($filterData);

        return Excel::download(
            new CashMovementForCustomExport($cashMovement, $dateRange, $company, $columns, $filterBy),
            $filename
        );
    }

    private function renderPreparedCashMovement(array $filterData, Company $company, Collection $locations): string
    {
        $customReportService = resolve(CustomReportService::class);

        [$cashMovement, $columns] = $this->preparedCashMovement($filterData, $locations, $company->id);

        return view('prints.cash_movement', [
            'cashMovements' => $cashMovement,
            'dateRange' => $customReportService->prepareDateRange($filterData),
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'company' => $company,
            'columns' => $columns,
            'filterBy' => $this->filterBy($filterData),
        ])->render();
    }

    /**
     * @return array<int, mixed[]>
     */
    private function preparedCashMovement(array $filterData, Collection $locations, int $companyId): array
    {
        $cashMovementQueries = resolve(CashMovementQueries::class);
        $cashMovements = $cashMovementQueries->getCashMovementForReport($filterData, $companyId);

        $locationsCashMovements = [];
        foreach ($locations as $location) {
            $locationCashMovements = [
                'location_name' => $location->name . ' [' . $location->code . ']',
                'cash_movements' => [],
            ];

            $selectedLocationCashMovements = $cashMovements->where('counterUpdate.counter.location_id', $location->id);

            foreach ($selectedLocationCashMovements as $selectedLocationCashMovement) {
                $locationCashMovements['cash_movements'][] = [
                    'sales_date' => $selectedLocationCashMovement->happened_at,
                    'counter' => $selectedLocationCashMovement->counterUpdate->counter->name,
                    'cashier' => $selectedLocationCashMovement->counterUpdate->cashier->employee->getFullName(),
                    'cash_in' => $selectedLocationCashMovement->cash_movement_type_id === CashMovementTypes::CASH_IN->value ? CommonFunctions::currencyFormat(
                        (float) $selectedLocationCashMovement->amount
                    ) : 0.00,
                    'cash_out' => $selectedLocationCashMovement->cash_movement_type_id === CashMovementTypes::CASH_OUT->value ? CommonFunctions::currencyFormat(
                        (float) $selectedLocationCashMovement->amount
                    ) : 0.00,
                ];
            }

            $cashInTotal = $selectedLocationCashMovements->where(
                'cash_movement_type_id',
                CashMovementTypes::CASH_IN->value
            )->sum('amount');

            $cashOutTotal = $selectedLocationCashMovements->where(
                'cash_movement_type_id',
                CashMovementTypes::CASH_OUT->value
            )->sum('amount');

            $locationCashMovements['cash_in_total'] = CommonFunctions::currencyFormat((float) $cashInTotal);
            $locationCashMovements['cash_out_total'] = CommonFunctions::currencyFormat((float) $cashOutTotal);

            $locationsCashMovements[] = $locationCashMovements;
        }

        $columns = ['Sales Date', 'Counter', 'Cashier', 'Cash In', 'Cash Out'];

        return [$locationsCashMovements, $columns];
    }

    private function filterBy(array $filterData): string
    {
        if (! isset($filterData['filter_by'])) {
            return '';
        }

        $counterQueries = resolve(CounterQueries::class);
        $cashierQueries = resolve(CashierQueries::class);

        $filterBy = (int) $filterData['filter_by'];

        if ($filterBy === CashMovementFilterTypes::BY_COUNTER->value && isset($filterData['counter_ids']) && '' !== $filterData['counter_ids']) {
            $counters = $counterQueries->getByIds($filterData['counter_ids']);

            return $this->formatFilterResult(
                CashMovementFilterTypes::BY_COUNTER->value,
                $counters->pluck('name')->implode(', ')
            );
        }

        if ($filterBy === CashMovementFilterTypes::BY_CASHIER->value && isset($filterData['cashier_ids']) && '' !== $filterData['cashier_ids']) {
            $cashiers = $cashierQueries->getByIds($filterData['cashier_ids']);

            return $this->formatFilterResult(
                CashMovementFilterTypes::BY_CASHIER->value,
                $cashiers->pluck('employee.first_name')->implode(', ')
            );
        }

        return '';
    }

    private function formatFilterResult(int $filterType, ?string $value): string
    {
        return CashMovementFilterTypes::getFormattedCaseName($filterType) . ' (' . $value . ')';
    }
}

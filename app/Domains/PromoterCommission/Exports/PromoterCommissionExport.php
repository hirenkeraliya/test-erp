<?php

declare(strict_types=1);

namespace App\Domains\PromoterCommission\Exports;

use App\CommonFunctions;
use App\Domains\Common\Services\ExportService;
use App\Domains\Company\Enums\CommissionTypes;
use App\Domains\PromoterCommission\Services\PromoterCommisionPrintService;
use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PromoterCommissionExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function __construct(
        protected Collection $promoterCommissions,
        protected Company $company,
        protected Collection $filteredColumns
    ) {
    }

    public function collection(): Collection
    {
        $exportService = resolve(ExportService::class);
        $promoterCommisionPrintService = resolve(PromoterCommisionPrintService::class);

        $preparedData = [];
        $promoterCommissionsData = [];
        $promoterCommissionsTotalSalesAmount = 0;
        $promoterCommissionsTotalCommissionAmount = 0;

        foreach ($this->promoterCommissions as $promoterCommission) {
            $promoter = $promoterCommission->promoter;
            $employee = $promoter->employee;

            $date = Carbon::createFromFormat('Y-m-d', $promoterCommission['commission_date']);
            $commissionDate = $date ? $date->format('F Y') : '';

            $locations = $promoter->locations;

            $locationNames = $locations->map(fn ($location) => $location->name)->implode(', ');
            $promoterCommissionUpdates = $promoterCommission->promoterCommissionUpdates;
            $totalSalesAmount = (float) $promoterCommissionUpdates->sum('amount');
            $commissionAmount = (float) $promoterCommissionUpdates->sum('commission_amount');
            $monthlySalesTarget = $promoterCommission['monthly_sales_target'] ?? null;

            $promoterCommissionsData[] = [
                'id' => $promoterCommission->id,
                'promoter' => $employee->getFullName(),
                'locations' => $locationNames,
                'staff_id' => $employee->staff_id,
                'designation' => $employee->designation?->name,
                'commission_date' => $commissionDate,
                'monthly_sales_target' => (CommissionTypes::BY_PROMOTER === $this->company->commission_type_id) ? $monthlySalesTarget : '0',
                'total_sales_amount' => CommonFunctions::currencyFormat($totalSalesAmount),
                'commission_amount' => CommonFunctions::currencyFormat($commissionAmount),
            ];

            $promoterCommissionsTotalSalesAmount += $totalSalesAmount;
            $promoterCommissionsTotalCommissionAmount += $commissionAmount;
        }

        /** @var Collection $promoterCommissions */
        $promoterCommissions = [
            'data' => collect($promoterCommissionsData),
        ];

        foreach ($promoterCommissions['data'] as $value) {
            $preparedData[] = $exportService->exportData($value, $this->filteredColumns);
        }

        $columns = [
            'total_sales_amount' => $promoterCommissionsTotalSalesAmount,
            'commission_amount' => $promoterCommissionsTotalCommissionAmount,
        ];

        $filteredColumns = $this->filteredColumns->filter(fn ($column): bool => isset($columns[$column]));

        if ($filteredColumns->isNotEmpty() && [] !== $preparedData) {
            $summaryData = $promoterCommisionPrintService->createDynamicSummaryArrayForExportAndPdf(
                $preparedData[0],
                $filteredColumns->contains('total_sales_amount') ? $columns['total_sales_amount'] : null,
                $filteredColumns->contains('commission_amount') ? $columns['commission_amount'] : null
            );

            $preparedData[] = count($preparedData[0]) === $filteredColumns->count()
                ? $summaryData
                : array_merge(['Total'], $summaryData);
        }

        return collect($preparedData);
    }

    public function headings(): array
    {
        $exportService = resolve(ExportService::class);

        return $exportService->getHeadings($this->filteredColumns);
    }
}

<?php

declare(strict_types=1);

namespace App\Domains\Sale\Exports;

use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class GeneralSalesReportByCurrentVsPreviousExport implements FromView, ShouldAutoSize
{
    public function __construct(
        protected Company $company,
        protected array $locationSales,
        protected array $grandTotals,
        protected array $columns,
        protected array $mainColumns,
        protected array $yearComparisons,
        protected array $previousDates,
        protected string $dateRange,
        protected string $reportType,
        protected ?string $filterBy,
        protected ?int $eInvoiceFilter,
    ) {
    }

    public function view(): View
    {
        return view('prints.general_sales_by_current_day_vs_previous_day', [
            'company' => $this->company,
            'locationSales' => $this->locationSales,
            'columns' => $this->columns,
            'mainColumns' => $this->mainColumns,
            'yearComparisons' => $this->yearComparisons,
            'previousDates' => $this->previousDates,
            'selectedDate' => $this->dateRange,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'filterBy' => $this->filterBy,
            'grandTotals' => $this->grandTotals,
            'reportType' => $this->reportType,
            'excludeByEInvoiceFilter' => $this->eInvoiceFilter,
        ]);
    }
}

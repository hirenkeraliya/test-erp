<?php

declare(strict_types=1);

namespace App\Domains\StockSummary\Exports;

use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class StockSummaryByModuleReportExport implements FromView, ShouldAutoSize
{
    public function __construct(
        protected array $stockSummaryData,
        protected array $dateRange,
        protected Company $company,
        protected string $filteredLocation,
        protected ?int $reportBy,
        protected ?int $reportType,
        protected array $grandTotals,
    ) {
    }

    public function view(): View
    {
        return view('prints.stock_summary_by_module', [
            'sellThroughAggregate' => $this->stockSummaryData,
            'dateRange' => $this->dateRange,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'company' => $this->company,
            'filteredLocation' => $this->filteredLocation,
            'report_by' => $this->reportBy,
            'report_type' => $this->reportType,
            'grandTotals' => $this->grandTotals,
        ]);
    }
}

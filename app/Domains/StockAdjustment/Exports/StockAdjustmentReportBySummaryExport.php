<?php

declare(strict_types=1);

namespace App\Domains\StockAdjustment\Exports;

use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class StockAdjustmentReportBySummaryExport implements FromView, ShouldAutoSize
{
    public function __construct(
        protected Collection $stockAdjustmentRecords,
        protected array $dateRange,
        protected Company $company,
        protected array $columns,
        protected string $filterBy,
        protected string $stockAdjustmentType,
    ) {
    }

    public function view(): View
    {
        return view('prints.stock_adjustment_by_summary', [
            'stockAdjustmentRecords' => $this->stockAdjustmentRecords,
            'dateRange' => $this->dateRange,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'company' => $this->company,
            'columns' => $this->columns,
            'filterBy' => $this->filterBy,
            'stockAdjustmentType' => $this->stockAdjustmentType,
        ]);
    }
}

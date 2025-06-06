<?php

declare(strict_types=1);

namespace App\Domains\StockTransfer\Exports;

use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class StockTransferDiscrepancyReportBySummaryExport implements FromView, ShouldAutoSize
{
    public function __construct(
        protected Collection $stockTransferItems,
        protected array $dateRange,
        protected Company $company,
        protected array $columns,
        protected string $filterBy,
        protected string $transferType,
        protected string $dateSelectionType,
    ) {
    }

    public function view(): View
    {
        return view('prints.stock_transfer_discrepancy_by_summary', [
            'stockTransfersData' => $this->stockTransferItems,
            'dateRange' => $this->dateRange,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'company' => $this->company,
            'columns' => $this->columns,
            'filterBy' => $this->filterBy,
            'transferType' => $this->transferType,
            'dateSelectionType' => $this->dateSelectionType,
        ]);
    }
}

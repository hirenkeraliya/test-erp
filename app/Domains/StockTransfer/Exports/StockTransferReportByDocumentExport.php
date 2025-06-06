<?php

declare(strict_types=1);

namespace App\Domains\StockTransfer\Exports;

use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class StockTransferReportByDocumentExport implements FromView, ShouldAutoSize
{
    public function __construct(
        protected array $stockTransferData,
        protected array $dateRange,
        protected Company $company,
        protected array $columns,
        protected bool $displayTotal,
        protected bool $isStatusAllowed,
        protected ?string $status,
        protected string $filterBy,
        protected string $transferType,
        protected string $dateSelectionType,
    ) {
    }

    public function view(): View
    {
        return view('prints.stock_transfer_by_document', [
            'stockTransfersData' => $this->stockTransferData,
            'dateRange' => $this->dateRange,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'company' => $this->company,
            'columns' => $this->columns,
            'displayTotal' => $this->displayTotal,
            'isStatusAllowed' => $this->isStatusAllowed,
            'status' => $this->status,
            'filterBy' => $this->filterBy,
            'transferType' => $this->transferType,
            'dateSelectionType' => $this->dateSelectionType,
        ]);
    }
}

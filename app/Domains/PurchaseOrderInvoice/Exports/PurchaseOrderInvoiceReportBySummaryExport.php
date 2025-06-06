<?php

declare(strict_types=1);

namespace App\Domains\PurchaseOrderInvoice\Exports;

use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class PurchaseOrderInvoiceReportBySummaryExport implements FromView, ShouldAutoSize
{
    public function __construct(
        protected Collection $purchaseOrderInvoicesData,
        protected array $dateRange,
        protected Company $company,
        protected array $columns,
        protected string $filterBy,
    ) {
    }

    public function view(): View
    {
        return view('prints.purchase_order_invoice_by_summary', [
            'purchaseOrderInvoicesData' => $this->purchaseOrderInvoicesData,
            'dateRange' => $this->dateRange,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'company' => $this->company,
            'columns' => $this->columns,
            'filterBy' => $this->filterBy,
        ]);
    }
}

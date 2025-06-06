<?php

declare(strict_types=1);

namespace App\Domains\Sale\Exports;

use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class GeneralSalesReportByDateAndBrandExport implements FromView, ShouldAutoSize
{
    public function __construct(
        protected array $brandLocationsSales,
        protected array $grandTotal,
        protected array $columns,
        protected array $dateRange,
        protected Company $company,
        protected ?string $filterBy,
        protected string $reportType,
        protected string $currencySymbol,
        protected ?int $eInvoiceFilter,
    ) {
    }

    public function view(): View
    {
        return view('prints.general_sales_by_date_and_brand', [
            'brandLocationsSales' => $this->brandLocationsSales,
            'dateRange' => $this->dateRange,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'company' => $this->company,
            'columns' => $this->columns,
            'filterBy' => $this->filterBy,
            'reportType' => $this->reportType,
            'grandTotal' => $this->grandTotal,
            'currencySymbol' => $this->currencySymbol,
            'excludeByEInvoiceFilter' => $this->eInvoiceFilter,
        ]);
    }
}

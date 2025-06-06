<?php

declare(strict_types=1);

namespace App\Domains\Sale\Exports;

use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class SalesCollectionByMonthAndBrandExport implements FromView, ShouldAutoSize
{
    public function __construct(
        protected array $brandLocationsSalesCollection,
        protected array $grandTotal,
        protected array $columns,
        protected array $dateRange,
        protected Company $company,
        protected string $filterBy,
        protected string $currencySymbol,
        protected ?int $eInvoiceSubmitted,
    ) {
    }

    public function view(): View
    {
        return view('prints.sales_collection_by_summary_month_and_brand', [
            'brandLocationsSalesCollection' => $this->brandLocationsSalesCollection,
            'grandTotal' => $this->grandTotal,
            'dateRange' => $this->dateRange,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'company' => $this->company,
            'columns' => $this->columns,
            'filterBy' => $this->filterBy,
            'currencySymbol' => $this->currencySymbol,
            'excludeByEInvoiceFilter' => $this->eInvoiceSubmitted,
        ]);
    }
}

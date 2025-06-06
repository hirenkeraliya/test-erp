<?php

declare(strict_types=1);

namespace App\Domains\Sale\Exports;

use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class SalesCollectionByCounterAndByCashierExport implements FromView, ShouldAutoSize
{
    public function __construct(
        protected array $locationPayments,
        protected array $columns,
        protected Company $company,
        protected array $dateRange,
        protected string $filterBy,
        protected ?int $eInvoiceSubmitted,
    ) {
    }

    public function view(): View
    {
        return view('prints.sales_collection_by_counter_and_cashier', [
            'locationPayments' => $this->locationPayments,
            'dateRange' => $this->dateRange,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'company' => $this->company,
            'columns' => $this->columns,
            'filterBy' => $this->filterBy,
            'excludeByEInvoiceFilter' => $this->eInvoiceSubmitted,
        ]);
    }
}

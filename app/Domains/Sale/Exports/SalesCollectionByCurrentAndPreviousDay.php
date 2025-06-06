<?php

declare(strict_types=1);

namespace App\Domains\Sale\Exports;

use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class SalesCollectionByCurrentAndPreviousDay implements FromView, ShouldAutoSize
{
    public function __construct(
        protected Company $company,
        protected array $preparedSales,
        protected array $grandTotal,
        protected array $mainColumns,
        protected array $columns,
        protected string $filterBy,
        protected string $selectedDate,
        protected array $yearComparisons,
        protected array $previousDates,
        protected ?int $eInvoiceSubmitted,
    ) {
    }

    public function view(): View
    {
        return view('prints.sales_collection_by_current_and_previous_day', [
            'locationSales' => $this->preparedSales,
            'selectedDate' => $this->selectedDate,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'company' => $this->company,
            'columns' => $this->columns,
            'mainColumns' => $this->mainColumns,
            'yearComparisons' => $this->yearComparisons,
            'previousDates' => $this->previousDates,
            'grandTotals' => $this->grandTotal,
            'filterBy' => $this->filterBy,
            'excludeByEInvoiceFilter' => $this->eInvoiceSubmitted,
        ]);
    }
}

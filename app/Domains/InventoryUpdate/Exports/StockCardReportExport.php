<?php

declare(strict_types=1);

namespace App\Domains\InventoryUpdate\Exports;

use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class StockCardReportExport implements FromView, ShouldAutoSize
{
    public function __construct(
        protected array $stockCards,
        protected string $location,
        protected Company $company,
        protected array $dateRange,
        protected ?string $articleNumber,
        protected array $storeInventoriesTotals,
        protected string $filterBy,
    ) {
    }

    public function view(): View
    {
        return view('prints.stock_card', [
            'locationName' => $this->location,
            'articleNumber' => $this->articleNumber,
            'company' => $this->company,
            'storeInventories' => $this->stockCards,
            'dateRange' => $this->dateRange,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'grandTotals' => $this->storeInventoriesTotals,
            'filterBy' => $this->filterBy,
        ]);
    }
}

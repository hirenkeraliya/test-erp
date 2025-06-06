<?php

declare(strict_types=1);

namespace App\Domains\InventoryUpdate\Exports;

use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class CustomStockMovementExport implements FromView, ShouldAutoSize
{
    public function __construct(
        protected array $stockMovements,
        protected Company $company,
        protected array $dateRange,
        protected ?string $filterBy,
        protected string $reportType
    ) {
    }

    public function view(): View
    {
        return view('prints.stock_movement', [
            'company' => $this->company,
            'stockMovements' => $this->stockMovements,
            'dateRange' => $this->dateRange,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'filterBy' => $this->filterBy,
            'reportType' => $this->reportType,
        ]);
    }
}

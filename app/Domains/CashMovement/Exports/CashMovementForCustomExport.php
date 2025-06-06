<?php

declare(strict_types=1);

namespace App\Domains\CashMovement\Exports;

use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class CashMovementForCustomExport implements FromView, ShouldAutoSize
{
    public function __construct(
        protected array $cashMovements,
        protected array $dateRange,
        protected Company $company,
        protected array $columns,
        protected string $filterBy,
    ) {
    }

    public function view(): View
    {
        return view('prints.cash_movement', [
            'cashMovements' => $this->cashMovements,
            'dateRange' => $this->dateRange,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'company' => $this->company,
            'columns' => $this->columns,
            'filterBy' => $this->filterBy,
        ]);
    }
}

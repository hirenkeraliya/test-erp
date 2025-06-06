<?php

declare(strict_types=1);

namespace App\Domains\Sale\Exports;

use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class WorstTwentyByBrandExport implements FromView, ShouldAutoSize
{
    public function __construct(
        protected array $locationsSales,
        protected array $dateRange,
        protected Company $company,
        protected string $filterBy,
        protected bool $displayAmount,
    ) {
    }

    public function view(): View
    {
        return view('prints.worst_twenty_by_brands', [
            'locationsSales' => $this->locationsSales,
            'dateRange' => $this->dateRange,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'company' => $this->company,
            'filterBy' => $this->filterBy,
            'displayAmount' => $this->displayAmount,
        ]);
    }
}

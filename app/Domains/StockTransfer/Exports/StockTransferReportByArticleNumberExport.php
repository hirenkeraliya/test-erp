<?php

declare(strict_types=1);

namespace App\Domains\StockTransfer\Exports;

use App\Models\Company;
use App\Models\Location;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromView;

class StockTransferReportByArticleNumberExport implements FromView
{
    public function __construct(
        protected Collection $stockTransferItems,
        protected Location $location,
        protected array $dateRange,
        protected Company $company,
        protected array $columns,
        protected bool $displayTotal,
    ) {
    }

    public function view(): View
    {
        return view('prints.stock_transfer_by_article_number', [
            'stockTransfersData' => $this->stockTransferItems,
            'location' => $this->location,
            'dateRange' => $this->dateRange,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'company' => $this->company,
            'columns' => $this->columns,
            'displayTotal' => $this->displayTotal,
        ]);
    }
}

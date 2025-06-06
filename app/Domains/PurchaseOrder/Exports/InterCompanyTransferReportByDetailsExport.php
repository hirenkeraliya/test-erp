<?php

declare(strict_types=1);

namespace App\Domains\PurchaseOrder\Exports;

use App\Models\Company;
use App\Models\Location;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class InterCompanyTransferReportByDetailsExport implements FromView, ShouldAutoSize
{
    public function __construct(
        protected Collection $interCompanyStockTransferData,
        protected Location $location,
        protected array $dateRange,
        protected Company $company,
        protected bool $displayPurchaseCost,
        protected string $filterBy,
        protected string $transferType,
        protected string $currencySymbol,
    ) {
    }

    public function view(): View
    {
        return view('prints.inter_company_stock_transfer_by_details_for_export', [
            'interCompanyStockTransfersData' => $this->interCompanyStockTransferData,
            'location' => $this->location,
            'dateRange' => $this->dateRange,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'company' => $this->company,
            'displayPurchaseCost' => $this->displayPurchaseCost,
            'filterBy' => $this->filterBy,
            'transferType' => $this->transferType,
            'currencySymbol' => $this->currencySymbol,
        ]);
    }
}

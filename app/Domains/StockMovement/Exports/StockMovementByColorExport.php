<?php

declare(strict_types=1);

namespace App\Domains\StockMovement\Exports;

use App\Domains\SellThroughAggregate\Enums\SellThroughTypes;
use App\Models\Company;
use App\Models\Location;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class StockMovementByColorExport implements FromView, ShouldAutoSize
{
    public function __construct(
        protected Collection $stockMovementDataByColor,
        protected array $stockMovementTotalDataByColor,
        protected Company $company,
        protected ?Location $locations,
        protected string|array $date,
        protected array $columns,
        protected array $getFilterLabels,
    ) {
    }

    public function view(): View
    {
        return view('prints.stock_movement_by_color', [
            'stockMovementDataByColors' => $this->stockMovementDataByColor,
            'stockMovementTotalDataByColors' => $this->stockMovementTotalDataByColor,
            'reportType' => Str::of(SellThroughTypes::COLORS->name)->title()->value(),
            'filterDate' => $this->date,
            'date' => Carbon::now()->format('Y-m-d H:i:s'),
            'locations' => $this->locations,
            'company' => $this->company,
            'columns' => $this->columns,
            'getFilterLabels' => $this->getFilterLabels,
        ]);
    }
}

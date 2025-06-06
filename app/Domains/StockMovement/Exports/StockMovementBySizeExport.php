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

class StockMovementBySizeExport implements FromView, ShouldAutoSize
{
    public function __construct(
        protected Collection $stockMovementDataBySize,
        protected array $stockMovementTotalDataBySize,
        protected Company $company,
        protected ?Location $locations,
        protected array|string $dateRange,
        protected array $columns,
        protected array $getFilterLabels,
    ) {
    }

    public function view(): View
    {
        return view('prints.stock_movement_by_size', [
            'stockMovementDataBySizes' => $this->stockMovementDataBySize,
            'stockMovementTotalDataBySizes' => $this->stockMovementTotalDataBySize,
            'reportType' => Str::of(SellThroughTypes::SIZES->name)->title()->value(),
            'filterDate' => $this->dateRange,
            'date' => Carbon::now()->format('Y-m-d H:i:s'),
            'locations' => $this->locations,
            'company' => $this->company,
            'columns' => $this->columns,
            'getFilterLabels' => $this->getFilterLabels,
        ]);
    }
}

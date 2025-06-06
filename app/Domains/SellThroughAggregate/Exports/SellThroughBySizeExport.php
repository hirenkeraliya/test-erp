<?php

declare(strict_types=1);

namespace App\Domains\SellThroughAggregate\Exports;

use App\Domains\SellThroughAggregate\Enums\SellThroughTypes;
use App\Domains\SellThroughAggregate\Resources\SellThroughBySizeListResource;
use App\Models\Company;
use App\Models\Location;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class SellThroughBySizeExport implements FromView, ShouldAutoSize
{
    public function __construct(
        protected Collection $saleThroughDataBySize,
        protected array $saleThroughTotalDataBySize,
        protected Company $company,
        protected ?Location $locations,
        protected array|string $dateRange,
        protected array $columns,
        protected array $getFilterLabels,
        protected int $colspan,
    ) {
    }

    public function view(): View
    {
        return view('prints.sell_through_by_size', [
            'sellThroughDataBySizes' => SellThroughBySizeListResource::collection(
                $this->saleThroughDataBySize
            )->jsonSerialize(),
            'sellThroughTotalDataBySizes' => $this->saleThroughTotalDataBySize,
            'reportType' => Str::of(SellThroughTypes::SIZES->name)->title()->value(),
            'chartRecords' => [],
            'filterDate' => $this->dateRange,
            'date' => Carbon::now()->format('Y-m-d H:i:s'),
            'locations' => $this->locations,
            'company' => $this->company,
            'columns' => $this->columns,
            'getFilterLabels' => $this->getFilterLabels,
            'colspan' => $this->colspan,
        ]);
    }
}

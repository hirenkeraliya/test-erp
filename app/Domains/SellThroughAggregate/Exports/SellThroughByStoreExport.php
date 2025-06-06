<?php

declare(strict_types=1);

namespace App\Domains\SellThroughAggregate\Exports;

use App\Domains\SellThroughAggregate\Enums\SellThroughTypes;
use App\Domains\SellThroughAggregate\Resources\SellThroughByStoreListResource;
use App\Models\Company;
use App\Models\Location;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class SellThroughByStoreExport implements FromView, ShouldAutoSize
{
    public function __construct(
        protected Collection $sellThroughDataByLocation,
        protected array $sellThroughTotalDataByLocation,
        protected Company $company,
        protected ?Location $locations,
        protected string|array $date,
        protected array $columns,
        protected array $getFilterLabels,
        protected int $colspan,
    ) {
    }

    public function view(): View
    {
        return view('prints.sell_through_by_location', [
            'sellThroughDataByLocations' => SellThroughByStoreListResource::collection(
                $this->sellThroughDataByLocation
            )->jsonSerialize(),
            'sellThroughTotalDataByLocations' => $this->sellThroughTotalDataByLocation,
            'reportType' => Str::of(SellThroughTypes::LOCATIONS->name)->title()->value(),
            'filterDate' => $this->date,
            'date' => Carbon::now()->format('Y-m-d H:i:s'),
            'locations' => $this->locations,
            'company' => $this->company,
            'chartRecords' => [],
            'columns' => $this->columns,
            'getFilterLabels' => $this->getFilterLabels,
            'colspan' => $this->colspan,
        ]);
    }
}

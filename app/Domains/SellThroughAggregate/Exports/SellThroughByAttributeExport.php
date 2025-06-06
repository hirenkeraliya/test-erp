<?php

declare(strict_types=1);

namespace App\Domains\SellThroughAggregate\Exports;

use App\Domains\SellThroughAggregate\Enums\SellThroughTypes;
use App\Domains\SellThroughAggregate\Resources\SellThroughByAttributeListResource;
use App\Models\Company;
use App\Models\Location;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class SellThroughByAttributeExport implements FromView, ShouldAutoSize
{
    public function __construct(
        protected Collection $saleThroughDataByAttribute,
        protected array $saleThroughTotalDataByAttribute,
        protected Company $company,
        protected ?Location $locations,
        protected array|string $dateRange,
        protected array $columns,
        protected array $getFilterLabels,
        protected int $colSpan,
    ) {
    }

    public function view(): View
    {
        return view('prints.sell_through_by_attribute', [
            'sellThroughDataByAttributes' => SellThroughByAttributeListResource::collection(
                $this->saleThroughDataByAttribute
            )->jsonSerialize(),
            'sellThroughTotalDataByAttributes' => $this->saleThroughTotalDataByAttribute,
            'reportType' => Str::of(SellThroughTypes::BY_ATTRIBUTES->name)->replace('_', ' ')->title()->value(),
            'chartRecords' => [],
            'filterDate' => $this->dateRange,
            'date' => Carbon::now()->format('Y-m-d H:i:s'),
            'locations' => $this->locations,
            'company' => $this->company,
            'columns' => $this->columns,
            'getFilterLabels' => $this->getFilterLabels,
            'colspan' => $this->colSpan,
        ]);
    }
}

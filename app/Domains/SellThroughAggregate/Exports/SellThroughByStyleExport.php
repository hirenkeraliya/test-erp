<?php

declare(strict_types=1);

namespace App\Domains\SellThroughAggregate\Exports;

use App\Domains\SellThroughAggregate\Enums\SellThroughTypes;
use App\Domains\SellThroughAggregate\Resources\SellThroughByStyleListResource;
use App\Models\Company;
use App\Models\Location;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class SellThroughByStyleExport implements FromView, ShouldAutoSize
{
    public function __construct(
        protected Collection $sellThroughDataByStyle,
        protected array $sellThroughTotalDataByStyle,
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
        return view('prints.sell_through_by_style', [
            'sellThroughDataByStyles' => SellThroughByStyleListResource::collection(
                $this->sellThroughDataByStyle
            )->jsonSerialize(),
            'sellThroughTotalDataByStyles' => $this->sellThroughTotalDataByStyle,
            'reportType' => Str::of(SellThroughTypes::STYLES->name)->title()->value(),
            'filterDate' => $this->date,
            'date' => Carbon::now()->format('Y-m-d H:i:s'),
            'locations' => $this->locations,
            'company' => $this->company,
            'columns' => $this->columns,
            'chartRecords' => [],
            'getFilterLabels' => $this->getFilterLabels,
            'colspan' => $this->colspan,
        ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Domains\SellThroughAggregate\Exports;

use App\Domains\SellThroughAggregate\Enums\SellThroughTypes;
use App\Domains\SellThroughAggregate\Resources\SellThroughByCategoryListResource;
use App\Models\Company;
use App\Models\Location;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class SellThroughByCategoryExport implements FromView, ShouldAutoSize
{
    public function __construct(
        protected Collection $sellThroughDataByCategories,
        protected array $sellThroughTotalDataByCategories,
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
        return view('prints.sell_through_by_category', [
            'sellThroughDataByCategories' => SellThroughByCategoryListResource::collection(
                $this->sellThroughDataByCategories
            )->jsonSerialize(),
            'sellThroughTotalDataByCategories' => $this->sellThroughTotalDataByCategories,
            'reportType' => Str::of(SellThroughTypes::CATEGORIES->name)->title()->value(),
            'filterDate' => $this->date,
            'chartRecords' => [],
            'date' => Carbon::now()->format('Y-m-d H:i:s'),
            'locations' => $this->locations,
            'company' => $this->company,
            'columns' => $this->columns,
            'getFilterLabels' => $this->getFilterLabels,
            'colspan' => $this->colspan,
        ]);
    }
}

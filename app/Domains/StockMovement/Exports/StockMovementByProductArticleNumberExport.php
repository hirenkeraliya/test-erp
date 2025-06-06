<?php

declare(strict_types=1);

namespace App\Domains\StockMovement\Exports;

use App\Domains\SellThroughAggregate\Enums\SellThroughTypes;
use App\Domains\StockMovement\Resources\StockMovementByProductArticleNumberListResource;
use App\Models\Company;
use App\Models\Location;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class StockMovementByProductArticleNumberExport implements FromView, ShouldAutoSize
{
    public function __construct(
        protected Collection $stockMovementDataByProductArticleNumber,
        protected array $stockMovementTotalDataByProductArticleNumber,
        protected Company $company,
        protected ?Location $locations,
        protected string|array $date,
        protected array $columns,
        protected array $getFilterLabels,
        protected int $colSpan,
    ) {
    }

    public function view(): View
    {
        return view('prints.stock_movement_by_product_article_number_or_upc', [
            'stockMovementDataByProducts' => StockMovementByProductArticleNumberListResource::collection(
                $this->stockMovementDataByProductArticleNumber
            ),
            'stockMovementTotalDataByProducts' => $this->stockMovementTotalDataByProductArticleNumber,
            'reportType' => Str::of(SellThroughTypes::BY_MASTER_PRODUCT->name)->replace('_', ' ')->title()->value(),
            'reportTypeByArticleNumber' => SellThroughTypes::BY_MASTER_PRODUCT->value,
            'filterDate' => $this->date,
            'date' => Carbon::now()->format('Y-m-d H:i:s'),
            'locations' => $this->locations,
            'company' => $this->company,
            'columns' => $this->columns,
            'getFilterLabels' => $this->getFilterLabels,
            'colSpan' => $this->colSpan,
        ]);
    }
}

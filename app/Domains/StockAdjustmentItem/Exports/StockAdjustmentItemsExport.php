<?php

declare(strict_types=1);

namespace App\Domains\StockAdjustmentItem\Exports;

use App\Domains\Location\Enums\LocationTypes;
use App\Models\Location;
use App\Models\Product;
use App\Models\StockAdjustment;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StockAdjustmentItemsExport implements FromCollection, WithHeadings
{
    public function __construct(
        protected StockAdjustment $stockAdjustment
    ) {
    }

    public function collection(): Collection
    {
        return $this->stockAdjustment->items->map(function ($stockAdjustmentItem): array {
            /** @var Product $product */
            $product = $stockAdjustmentItem->product;

            /** @var Location $location */
            $location = $stockAdjustmentItem->location;

            /** @var Carbon $createdAt */
            $createdAt = $this->stockAdjustment->created_at;

            $locationType = LocationTypes::getFormattedCaseName($location->type_id);

            return [
                'stock_adjustment_no' => $this->stockAdjustment->id,
                'date' => $createdAt->format('d-m-Y h:i:s A'),
                'location_type' => $locationType,
                'location_name' => $location->name,
                'upc' => $product->upc,
                'product_name' => $product->name,
                'quantity' => $stockAdjustmentItem->quantity,
                'reason' => $this->stockAdjustment->reason,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Stock Adjustment No',
            'Date',
            'location_type',
            'location_name',
            'UPC',
            'Product Name',
            'Quantity',
            'Reason',
        ];
    }
}

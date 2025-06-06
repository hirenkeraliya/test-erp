<?php

declare(strict_types=1);

namespace App\Domains\AutomatedNotificationProduct\Exports;

use App\Models\AutomatedNotificationProduct;
use App\Models\Location;
use App\Models\Product;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AutomatedNotificationProductExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function __construct(
        protected Collection $automatedNotificationProducts
    ) {
    }

    public function collection(): Collection
    {
        return $this->automatedNotificationProducts->map(
            function (AutomatedNotificationProduct $automatedNotificationProduct): array {
                /** @var Product $product */
                $product = $automatedNotificationProduct->product;
                /** @var Location $location */
                $location = $automatedNotificationProduct->location;

                return [
                    'id' => $product->id,
                    'upc' => $product->upc,
                    'name' => $product->name,
                    'location_name' => $location->name,
                    'location_code' => $location->code,
                    'low_stock_alert_threshold' => $automatedNotificationProduct->low_stock_alert_threshold,
                ];
            }
        );
    }

    public function headings(): array
    {
        return ['Id', 'UPC', 'Name', 'Location Name', 'Location Code', 'Low Stock Alert Threshold'];
    }
}

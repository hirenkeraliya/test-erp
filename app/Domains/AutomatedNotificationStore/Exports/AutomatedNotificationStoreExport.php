<?php

declare(strict_types=1);

namespace App\Domains\AutomatedNotificationStore\Exports;

use App\Models\AutomatedNotificationStore;
use App\Models\Location;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AutomatedNotificationStoreExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function __construct(
        protected Collection $automatedNotificationStores
    ) {
    }

    public function collection(): Collection
    {
        return $this->automatedNotificationStores->map(
            function (AutomatedNotificationStore $automatedNotificationStore): array {
                /** @var Location $location */
                $location = $automatedNotificationStore->location;

                return [
                    'id' => $location->id,
                    'name' => $location->name,
                    'code' => $location->code,
                    'low_stock_alert_threshold' => $automatedNotificationStore->low_stock_alert_threshold,
                ];
            }
        );
    }

    public function headings(): array
    {
        return ['Id', 'Name', 'Code', 'Low Stock Alert Threshold'];
    }
}

<?php

declare(strict_types=1);

namespace App\Domains\PurchaseOrderFulfillment\DataPreparer;

use App\Domains\PurchaseOrderFulfillment\Services\PurchaseOrderFulfillmentPrintService;
use App\Models\City;
use App\Models\Location;
use App\Models\PurchaseOrder;

class PurchaseOrderLocationDataPreparer
{
    public function getToLocation(PurchaseOrder $purchaseOrder): array
    {
        $purchaseOrderFulfillmentPrintService = resolve(PurchaseOrderFulfillmentPrintService::class);
        $toLocation = $purchaseOrderFulfillmentPrintService->getToLocation($purchaseOrder);

        $toCity = null;
        if ($toLocation instanceof Location) {
            /** @var ?City $city */
            $city = $toLocation->city;
            $toCity = $city?->name ?? 'N/A';
        }

        return [
            'name' => $toLocation?->name,
            'address_line_1' => $toLocation?->address_line_1,
            'address_line_2' => $toLocation?->address_line_2,
            'city' => $toCity,
            'phone' => $toLocation?->phone,
            'fax' => $toLocation?->fax,
        ];
    }

    public function getFromLocation(PurchaseOrder $purchaseOrder): array
    {
        $purchaseOrderFulfillmentPrintService = resolve(PurchaseOrderFulfillmentPrintService::class);
        $fromLocation = $purchaseOrderFulfillmentPrintService->getFromLocation($purchaseOrder);

        $fromCity = $fromLocation?->city;
        if ($fromLocation instanceof Location) {
            /** @var ?City $city */
            $city = $fromLocation->city;
            $fromCity = $city?->name ?? 'N/A';
        }

        return [
            'name' => $fromLocation?->name,
            'address_line_1' => $fromLocation?->address_line_1,
            'address_line_2' => $fromLocation?->address_line_2,
            'city' => $fromCity,
            'phone' => $fromLocation?->phone,
            'fax' => $fromLocation?->fax,
        ];
    }
}

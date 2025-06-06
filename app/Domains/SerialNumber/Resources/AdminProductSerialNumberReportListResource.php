<?php

declare(strict_types=1);

namespace App\Domains\SerialNumber\Resources;

use App\Domains\Location\Enums\LocationTypes;
use App\Domains\SerialNumber\Enums\SerialNumberStatus;
use App\Models\InventoryUnit;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminProductSerialNumberReportListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $serialNumber = $this->resource;

        /** @var Product $product */
        $product = $serialNumber->product;

        $inventoryUnit = $serialNumber->inventoryUnit;

        return [
            'id' => $serialNumber->getKey(),
            'product' => $product->name,
            'serial_number' => $serialNumber->serial_number,
            'location_details' => $this->getLocationDetails($inventoryUnit),
            'stock' => $inventoryUnit?->quantity > 0 ? 'In Stock' : 'Out of Stock',
            'status' => SerialNumberStatus::getFormattedCaseName($serialNumber->status),
        ];
    }

    private function getLocationDetails(?InventoryUnit $inventoryUnit): string
    {
        if (! $inventoryUnit instanceof InventoryUnit) {
            return 'N/A';
        }

        $inventory = $inventoryUnit->inventory;
        if (! $inventory) {
            return 'N/A';
        }

        $location = $inventory->location;
        if (! $location) {
            return 'N/A';
        }

        $locationName = $location->getCode() ?? $location->getName();

        return LocationTypes::getFormattedCaseName($location->type_id) . ': ' . $locationName;
    }
}

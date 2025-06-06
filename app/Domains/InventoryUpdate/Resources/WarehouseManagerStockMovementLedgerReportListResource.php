<?php

declare(strict_types=1);

namespace App\Domains\InventoryUpdate\Resources;

use App\Domains\InventoryUpdate\DataPreparer\StockMovementDataPreparer;
use App\Domains\InventoryUpdate\Services\StockMovementLedgerReportService;
use App\Models\Location;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WarehouseManagerStockMovementLedgerReportListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $inventoryUpdate = $this->resource;

        /** @var Location $location */
        $location = $inventoryUpdate->location;

        $stockMovementLedgerReportService = resolve(StockMovementLedgerReportService::class);
        $referenceNumber = $stockMovementLedgerReportService->getStockMovementLedgerReportReferenceNumber(
            $inventoryUpdate,
            'warehouse_manager'
        );

        /** @var Carbon $happenedAtFormat */
        $happenedAtFormat = Carbon::createFromFormat('Y-m-d H:i:s', $inventoryUpdate->getHappenedAt());
        $happenedAt = $happenedAtFormat->format('d-m-Y h:i:s A');
        $stockMovementDataPreparer = resolve(StockMovementDataPreparer::class);

        return [
            'id' => $inventoryUpdate->getKey(),
            'date' => $happenedAt,
            'opening_stock' => $inventoryUpdate->getOpeningStock(),
            'closing_stock' => $inventoryUpdate->getClosingStock(),
            'updates' => $inventoryUpdate->getQuantity(),
            'from_location' => $stockMovementDataPreparer->getFromLocation($inventoryUpdate),
            'to_location' => $stockMovementDataPreparer->getToLocation($inventoryUpdate),
            'location_details' => $this->getNameOrCode($location->getCode(), $location->getName()),
            'reference_number' => $referenceNumber,
        ];
    }

    private function getNameOrCode(?string $code, ?string $name): string
    {
        $locationName = $code ?? $name;

        return 'Location : ' . $locationName;
    }
}

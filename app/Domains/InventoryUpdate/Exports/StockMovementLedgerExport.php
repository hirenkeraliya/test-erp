<?php

declare(strict_types=1);

namespace App\Domains\InventoryUpdate\Exports;

use App\Domains\Common\Services\ExportService;
use App\Domains\InventoryUpdate\Services\StockMovementLedgerReportService;
use App\Domains\Location\Enums\LocationTypes;
use App\Models\InventoryUpdate;
use App\Models\Location;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StockMovementLedgerExport implements FromCollection, WithHeadings
{
    public function __construct(
        protected Collection $stockMovementLedger,
        protected Collection $filteredColumns
    ) {
    }

    public function collection(): Collection
    {
        return $this->stockMovementLedger->map(function (InventoryUpdate $inventoryUpdate): array {
            /** @var Location $location */
            $location = $inventoryUpdate->location;

            $affectedBy = $inventoryUpdate->affectedBy;

            $stockMovementLedgerReportService = resolve(StockMovementLedgerReportService::class);
            $referenceNumber = $stockMovementLedgerReportService->getStockMovementLedgerReportReferenceNumber(
                $inventoryUpdate,
                null
            );

            /** @var Carbon $happenedAtFormat */
            $happenedAtFormat = Carbon::createFromFormat('Y-m-d H:i:s', $inventoryUpdate->getHappenedAt());
            $happenedAt = $happenedAtFormat->format('d-m-Y h:i:s A');

            $stockMovementLedgerReportData = [
                'date' => $happenedAt,
                'opening_stock' => $inventoryUpdate->getOpeningStock(),
                'from_location' => $affectedBy instanceof StockTransferItem ? $this->getSourceLocation(
                    $inventoryUpdate->affectedBy
                ) : 'N/A',
                'to_location' => $affectedBy instanceof StockTransferItem ? $this->getDestinationLocation(
                    $inventoryUpdate->affectedBy
                ) : 'N/A',
                'location_details' => $this->getLocationDetails(
                    LocationTypes::getFormattedCaseName($location->type_id),
                    $location->getCode(),
                    $location->getName()
                ),
                'updates' => $inventoryUpdate->getQuantity(),
                'reference_number' => $referenceNumber['message'],
                'closing_stock' => $inventoryUpdate->getClosingStock(),
            ];

            $exportService = resolve(ExportService::class);

            return $exportService->exportData($stockMovementLedgerReportData, $this->filteredColumns);
        });
    }

    public function headings(): array
    {
        $exportService = resolve(ExportService::class);

        return $exportService->getHeadings($this->filteredColumns);
    }

    private function getLocationDetails(string $locationType, ?string $code, ?string $name): string
    {
        $locationName = $code ?? $name;

        return $locationType . ': ' . $locationName;
    }

    private function getSourceLocation(StockTransferItem $stockTransferItem): string
    {
        /** @var StockTransfer $stockTransfer */
        $stockTransfer = $stockTransferItem->stockTransfer;

        return $stockTransfer->getSourceLocation();
    }

    private function getDestinationLocation(StockTransferItem $stockTransferItem): string
    {
        /** @var StockTransfer $stockTransfer */
        $stockTransfer = $stockTransferItem->stockTransfer;

        return $stockTransfer->getDestinationLocation();
    }
}

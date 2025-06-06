<?php

declare(strict_types=1);

namespace App\Domains\PurchaseOrder\Exports;

use App\Domains\Location\Enums\LocationTypes;
use App\Domains\PurchaseOrder\Enums\OrderTypes;
use App\Domains\PurchaseOrder\Enums\Statuses;
use App\Models\ExternalLocation;
use App\Models\Location;
use App\Models\PurchaseOrder;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PurchaseOrderExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function __construct(
        protected Collection $purchaseOrders,
    ) {
    }

    public function collection(): Collection
    {
        return $this->purchaseOrders->map(function (PurchaseOrder $purchaseOrder): array {
            /** @var string $createdAt */
            $createdAt = $purchaseOrder->created_at;

            /** @var Carbon $createdAtFormat */
            $createdAtFormat = Carbon::createFromFormat('Y-m-d H:i:s', $createdAt);

            return [
                'created_at' => $createdAtFormat->format('d-m-y H:i:s A'),
                'order_type' => OrderTypes::getFormattedCaseName($purchaseOrder->order_type),
                'order_number' => $purchaseOrder->order_number,
                'from' => $this->getFromLocation($purchaseOrder),
                'to' => $this->getToLocation($purchaseOrder),
                'status' => Statuses::getFormattedCaseName($purchaseOrder->getStatus()),
                'reference_number' => $purchaseOrder->reference_number,
            ];
        });
    }

    public function headings(): array
    {
        return ['Date', 'Transfer Type', 'Order Number', 'From', 'To', 'Status', 'Reference Number'];
    }

    public function getToLocation(PurchaseOrder $purchaseOrder): string
    {
        /** @var Location $location */
        $location = $purchaseOrder->location;

        /** @var ExternalLocation $externalLocation */
        $externalLocation = $purchaseOrder->externalLocation;

        $locationType = LocationTypes::getFormattedCaseName($location->type_id);
        $externalLocationType = $externalLocation->type_id ? LocationTypes::getFormattedCaseName(
            $externalLocation->type_id
        ) : null;

        if (
            $purchaseOrder->order_type === OrderTypes::PURCHASE_REQUEST->value
            && $purchaseOrder->created_by_company_id
        ) {
            return $location->name . ' (' . $locationType . ')';
        }

        if (
            $purchaseOrder->order_type === OrderTypes::TRANSFER_REQUEST->value
            && null === $purchaseOrder->created_by_company_id
        ) {
            return $location->name . ' (' . $locationType . ')';
        }

        if ($purchaseOrder->order_type === OrderTypes::PURCHASE_ORDER->value) {
            return $location->name . ' (' . $locationType . ')';
        }

        return $externalLocation->name . ' (' . $externalLocationType . ')';
    }

    public function getFromLocation(PurchaseOrder $purchaseOrder): string
    {
        /** @var Location $location */
        $location = $purchaseOrder->location;

        /** @var ExternalLocation $externalLocation */
        $externalLocation = $purchaseOrder->externalLocation;

        $locationType = LocationTypes::getFormattedCaseName($location->type_id);
        $externalLocationType = $externalLocation->type_id ? LocationTypes::getFormattedCaseName(
            $externalLocation->type_id
        ) : null;

        if (
            $purchaseOrder->order_type === OrderTypes::PURCHASE_REQUEST->value
            && $purchaseOrder->created_by_company_id
        ) {
            return $externalLocation->name . ' (' . $externalLocationType . ')';
        }

        if (
            $purchaseOrder->order_type === OrderTypes::TRANSFER_REQUEST->value
            && null === $purchaseOrder->created_by_company_id
        ) {
            return $externalLocation->name . ' (' . $externalLocationType . ')';
        }

        if ($purchaseOrder->order_type === OrderTypes::PURCHASE_ORDER->value) {
            return $externalLocation->name . ' (' . $externalLocationType . ')';
        }

        return $location->name . ' (' . $locationType . ')';
    }
}

<?php

declare(strict_types=1);

namespace App\Domains\PurchaseOrderFulfillment\Resource;

use App\Domains\Location\Enums\LocationTypes;
use App\Domains\PurchaseOrder\Enums\OrderTypes;
use App\Domains\PurchaseOrderFulfillment\Enums\FulfillmentStatuses;
use App\Domains\PurchaseOrderFulfillment\Services\PurchaseOrderFulfillmentService;
use App\Models\ExternalLocation;
use App\Models\Location;
use App\Models\PurchaseOrder;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class PurchaseDeliveryOrdersListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $purchaseOrderFulfillment = $this->resource;

        /** @var PurchaseOrder $purchaseOrder */
        $purchaseOrder = $purchaseOrderFulfillment->purchaseOrder;

        /** @var Carbon $happenedAtFormat */
        $happenedAtFormat = Carbon::createFromFormat('Y-m-d H:i:s', $purchaseOrderFulfillment->happened_at);
        $happenedAt = $happenedAtFormat->format('d-m-Y h:i:s A');

        /** @var Collection $purchaseOrderFulfillmentTransactions */
        $purchaseOrderFulfillmentTransactions = $purchaseOrderFulfillment->getTransactions();

        return [
            'id' => $purchaseOrderFulfillment->id,
            'happened_at' => $happenedAt,
            'status' => FulfillmentStatuses::getFormattedCaseName($purchaseOrderFulfillment->getStatus()),
            'status_id' => $purchaseOrderFulfillment->getStatus(),
            'created_by_company_id' => $purchaseOrderFulfillment->created_by_company_id,
            'status_times' => $this->getTransactions($purchaseOrderFulfillmentTransactions),
            'order_numbers' => PurchaseOrderFulfillmentService::getOrderNumbers(
                $purchaseOrder,
                $purchaseOrderFulfillment->delivery_order_number
            ),
            'to' => $this->getToLocation($purchaseOrder),
            'from' => $this->getFromLocation($purchaseOrder),
        ];
    }

    public function getTransactions(Collection $purchaseOrderFulfillmentTransactions): string
    {
        $transactions = $purchaseOrderFulfillmentTransactions->map(
            function ($purchaseOrderFulfillmentTransaction): array {
                $createdAt = $purchaseOrderFulfillmentTransaction->created_at ? $purchaseOrderFulfillmentTransaction->created_at->format(
                    'd-m-y h:i:s A'
                ) : null;

                return [
                    'status' => FulfillmentStatuses::getFormattedCaseName(
                        $purchaseOrderFulfillmentTransaction->new_status
                    ) . ' : ' . $createdAt,
                ];
            }
        );

        return $transactions->pluck('status')->implode("\n");
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

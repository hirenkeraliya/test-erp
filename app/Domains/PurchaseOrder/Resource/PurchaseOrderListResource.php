<?php

declare(strict_types=1);

namespace App\Domains\PurchaseOrder\Resource;

use App\Domains\Location\Enums\LocationTypes;
use App\Domains\PurchaseOrder\Enums\OrderTypes;
use App\Domains\PurchaseOrder\Enums\Statuses;
use App\Domains\PurchaseOrderFulfillment\Enums\FulfillmentStatuses;
use App\Models\Company;
use App\Models\ExternalLocation;
use App\Models\Location;
use App\Models\PurchaseOrder;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class PurchaseOrderListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $purchaseOrder = $this->resource;

        /** @var Collection $purchaseOrderTransactions */
        $purchaseOrderTransactions = $purchaseOrder->getTransactions();

        $locationType = LocationTypes::getFormattedCaseName($purchaseOrder->location->type_id);
        $externalLocationType = LocationTypes::getFormattedCaseName($purchaseOrder->externalLocation->type_id);

        $requireDate = null;

        $dateParseFormat = 'Y-m-d';
        $dateStringFormat = 'd-m-Y';

        if ($purchaseOrder->require_date) {
            /** @var Carbon $requireDateFormat */
            $requireDateFormat = Carbon::createFromFormat($dateParseFormat, $purchaseOrder->require_date);
            $requireDate = $requireDateFormat->format($dateStringFormat);
        }

        /** @var Carbon $createdAtFormat */
        $createdAtFormat = Carbon::createFromFormat('Y-m-d H:i:s', $purchaseOrder->created_at);
        $createdAt = $createdAtFormat->format('d-m-y h:i:s A');

        return [
            'require_date' => $requireDate,
            'created_at' => $createdAt,
            'order_type' => OrderTypes::getFormattedCaseName($purchaseOrder->order_type),
            'order_type_id' => $purchaseOrder->order_type,
            'id' => $purchaseOrder->id,
            'to' => $this->getToLocation($purchaseOrder),
            'to_company' => $this->getToCompany($purchaseOrder),
            'from' => $this->getFromLocation($purchaseOrder),
            'from_company' => $this->getFromCompany($purchaseOrder),
            'status' => Statuses::getFormattedCaseName($purchaseOrder->getStatus()),
            'status_id' => $purchaseOrder->getStatus(),
            'reference_number' => $purchaseOrder->reference_number,
            'location_id' => $purchaseOrder->location_id,
            'location_type' => $locationType,
            'external_location_id' => $purchaseOrder->external_location_id,
            'external_location_type' => $externalLocationType,
            'created_by_company_id' => $purchaseOrder->created_by_company_id,
            'order_numbers' => $this->getOrderNumbers($purchaseOrder),
            'status_times' => $this->getTransactions($purchaseOrderTransactions),
            'fulfillmentStatusesSummary' => $this->getFulfillments($purchaseOrder),
            'DOStatus' => $this->getDOStatus($purchaseOrder),
        ];
    }

    public function getTransactions(Collection $purchaseOrderTransactions): string
    {
        $transactions = $purchaseOrderTransactions->map(function ($purchaseOrderTransaction): array {
            $createdAt = $purchaseOrderTransaction->created_at ? $purchaseOrderTransaction->created_at->format(
                'd-m-y h:i:s A'
            ) : null;

            return [
                'status' => Statuses::getFormattedCaseName($purchaseOrderTransaction->new_status) . ' : ' . $createdAt,
            ];
        });

        return $transactions->pluck('status')->implode('<br>');
    }

    public function getFulfillments(PurchaseOrder $purchaseOrder): string
    {
        /** @var Collection $purchaseOrderFulfillments */
        $purchaseOrderFulfillments = $purchaseOrder->fulfillments;
        $purchaseOrderFulfillmentStatusesWithCount = collect([]);

        if ($purchaseOrderFulfillments->isNotEmpty()) {
            $statuses = FulfillmentStatuses::getList();

            $purchaseOrderFulfillmentStatusesWithCount[] = [
                'status' => 'DO Statuses With Count :',
            ];

            foreach ($statuses as $status) {
                $filteredFulfillments = $purchaseOrderFulfillments->where('status', $status['id']);

                if ($filteredFulfillments->isNotEmpty()) {
                    $purchaseOrderFulfillmentStatusesWithCount[] = [
                        'status' => FulfillmentStatuses::getFormattedCaseName(
                            $status['id']
                        ) . ': ' . $filteredFulfillments->first()->status_count,
                    ];
                }
            }

            return $purchaseOrderFulfillmentStatusesWithCount->pluck('status')->implode('<br>');
        }

        return '';
    }

    public function getDOStatus(PurchaseOrder $purchaseOrder): string
    {
        /** @var Collection $purchaseOrderFulfillments */
        $purchaseOrderFulfillments = $purchaseOrder->fulfillments;
        if ($purchaseOrderFulfillments->where('status', FulfillmentStatuses::SHIPPED->value)->isNotEmpty()) {
            return 'Pending Receiving';
        }

        if ($purchaseOrderFulfillments->where('status', FulfillmentStatuses::DISCREPANCY->value)->isNotEmpty()) {
            return 'Discrepancy';
        }

        return '';
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

    public function getOrderNumbers(PurchaseOrder $purchaseOrder): array
    {
        $orderNumber = [];

        $orderNumber[] = OrderTypes::getFormattedCaseName(
            $purchaseOrder->order_type
        ) . ' : ' . $purchaseOrder->order_number;

        $orderNumber[] = $this->getExternalOrderNumber($purchaseOrder);

        if ($purchaseOrder->parentPurchaseOrder) {
            $orderNumber[] = OrderTypes::getFormattedCaseName(
                $purchaseOrder->parentPurchaseOrder->order_type
            ) . ' : ' . $purchaseOrder->parentPurchaseOrder->order_number;

            $orderNumber[] = $this->getExternalOrderNumber($purchaseOrder->parentPurchaseOrder);
        }

        return $orderNumber;
    }

    public function getExternalOrderNumber(PurchaseOrder $purchaseOrder): string
    {
        if (! $purchaseOrder->external_order_number) {
            return '';
        }

        if ($purchaseOrder->order_type === OrderTypes::SALES_ORDER->value) {
            return 'External ' . OrderTypes::getFormattedCaseName(
                OrderTypes::PURCHASE_ORDER->value
            ) . ' : ' . $purchaseOrder->external_order_number;
        }

        if ($purchaseOrder->order_type === OrderTypes::PURCHASE_ORDER->value) {
            return 'External ' . OrderTypes::getFormattedCaseName(
                OrderTypes::SALES_ORDER->value
            ) . ' : ' . $purchaseOrder->external_order_number;
        }

        return 'External ' . OrderTypes::getFormattedCaseName(
            $purchaseOrder->order_type
        ) . ' : ' . $purchaseOrder->external_order_number;
    }

    public function getToCompany(PurchaseOrder $purchaseOrder): ?string
    {
        if ($purchaseOrder->order_type === OrderTypes::PURCHASE_REQUEST->value) {
            if ($purchaseOrder->created_by_company_id) {
                /** @var Company $company */
                $company = $purchaseOrder->company;

                return $company->name;
            }

            /** @var Company $externalCompany */
            $externalCompany = $purchaseOrder->externalCompany;

            return $externalCompany->name;
        }

        if (
            $purchaseOrder->order_type === OrderTypes::TRANSFER_REQUEST->value
            && $purchaseOrder->created_by_company_id
        ) {
            return $purchaseOrder->externalCompany?->name;
        }

        if ($purchaseOrder->order_type === OrderTypes::SALES_ORDER->value) {
            return $purchaseOrder->externalCompany?->name;
        }

        return $purchaseOrder->company?->name;
    }

    public function getFromCompany(PurchaseOrder $purchaseOrder): ?string
    {
        if ($purchaseOrder->order_type === OrderTypes::PURCHASE_REQUEST->value) {
            if ($purchaseOrder->created_by_company_id) {
                /** @var Company $externalCompany */
                $externalCompany = $purchaseOrder->externalCompany;

                return $externalCompany->name;
            }

            /** @var Company $company */
            $company = $purchaseOrder->company;

            return $company->name;
        }

        if (
            $purchaseOrder->order_type === OrderTypes::TRANSFER_REQUEST->value
            && $purchaseOrder->created_by_company_id
        ) {
            return $purchaseOrder->company?->name;
        }

        if ($purchaseOrder->order_type === OrderTypes::SALES_ORDER->value) {
            return $purchaseOrder->company?->name;
        }

        return $purchaseOrder->externalCompany?->name;
    }
}

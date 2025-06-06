<?php

declare(strict_types=1);

namespace App\Domains\PurchaseOrderFulfillment\Resource;

use App\Domains\PurchaseOrderFulfillment\Enums\FulfillmentStatuses;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class PurchaseOrderFulfillmentListInternalApplicationResource extends JsonResource
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

        /** @var Carbon $happenedAtFormat */
        $happenedAtFormat = Carbon::createFromFormat('Y-m-d H:i:s', $purchaseOrderFulfillment->happened_at);
        $happenedAt = $happenedAtFormat->format('d-m-Y h:i:s A');

        /** @var Collection $purchaseOrderFulfillmentTransactions */
        $purchaseOrderFulfillmentTransactions = $purchaseOrderFulfillment->getTransactions();

        return [
            'id' => $purchaseOrderFulfillment->id,
            'purchase_order_id' => $purchaseOrderFulfillment->purchase_order_id,
            'happened_at' => $happenedAt,
            'status' => FulfillmentStatuses::getFormattedCaseName($purchaseOrderFulfillment->getStatus()),
            'status_id' => $purchaseOrderFulfillment->getStatus(),
            'delivery_order_number' => $purchaseOrderFulfillment->delivery_order_number,
            'created_by_company_id' => $purchaseOrderFulfillment->created_by_company_id,
            'status_times' => $this->getTransactions($purchaseOrderFulfillmentTransactions),
        ];
    }

    public function getTransactions(Collection $purchaseOrderFulfillmentTransactions): string
    {
        $transactions = $purchaseOrderFulfillmentTransactions->map(
            function ($purchaseOrderFulfillmentTransaction): array {
                $createdAt = $purchaseOrderFulfillmentTransaction->created_at ? $purchaseOrderFulfillmentTransaction->created_at->format(
                    'd-m-y H:i:s'
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
}

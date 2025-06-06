<?php

declare(strict_types=1);

namespace App\Domains\PurchaseOrderInvoice\Resource;

use App\Domains\PurchaseOrderInvoice\Enums\InvoiceStatuses;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class PurchaseOrderInvoiceListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $purchaseOrderInvoice = $this->resource;

        /** @var Collection $purchaseOrderInvoiceTransactions */
        $purchaseOrderInvoiceTransactions = $purchaseOrderInvoice->getTransactions();

        return [
            'id' => $purchaseOrderInvoice->id,
            'purchase_order_id' => $purchaseOrderInvoice->purchase_order_id,
            'created_by_company_id' => $purchaseOrderInvoice->created_by_company_id,
            'status' => InvoiceStatuses::getFormattedCaseName($purchaseOrderInvoice->status),
            'status_id' => $purchaseOrderInvoice->status,
            'invoice_number' => $purchaseOrderInvoice->invoice_number,
            'status_times' => $this->getTransactions($purchaseOrderInvoiceTransactions),
            'created_at' => $purchaseOrderInvoice->created_at ? $purchaseOrderInvoice->created_at->format(
                'd-m-y H:i:s A'
            ) : 'N/A',
        ];
    }

    public function getTransactions(Collection $purchaseOrderInvoiceTransactions): string
    {
        $transactions = $purchaseOrderInvoiceTransactions->map(
            function ($purchaseOrderInvoiceTransaction): array {
                $createdAt = $purchaseOrderInvoiceTransaction->created_at ? $purchaseOrderInvoiceTransaction->created_at->format(
                    'd-m-y H:i:s'
                ) : null;

                return [
                    'status' => InvoiceStatuses::getFormattedCaseName(
                        $purchaseOrderInvoiceTransaction->new_status
                    ) . ' : ' . $createdAt,
                ];
            }
        );

        return $transactions->pluck('status')->implode("\n");
    }
}

<?php

declare(strict_types=1);

namespace App\Domains\PurchaseOrderInvoice\Services;

use App\Domains\PurchaseOrderFulfillment\PurchaseOrderFulfillmentQueries;
use App\Domains\PurchaseOrderInvoice\Enums\InvoiceStatuses;
use App\Domains\PurchaseOrderInvoice\PurchaseOrderInvoiceQueries;
use App\Domains\PurchaseOrderInvoice\Resource\PurchaseOrderInvoiceListResource;
use App\Domains\PurchaseOrderInvoiceTransaction\PurchaseOrderInvoiceTransactionQueries;
use App\Domains\Sequence\Enums\SequenceTypes;
use App\Domains\Sequence\SequenceQueries;
use App\Models\ExternalCompany;
use App\Models\ExternalConnection;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderFulfillment;
use App\Models\PurchaseOrderInvoice;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class PurchaseOrderInvoiceService
{
    public function paidExternalPurchaseOrderInvoice(PurchaseOrderInvoice $purchaseOrderInvoice): void
    {
        /** @var PurchaseOrder $purchaseOrder */
        $purchaseOrder = $purchaseOrderInvoice->purchaseOrder;

        /** @var ExternalCompany $externalCompany */
        $externalCompany = $purchaseOrder->externalCompany;

        /** @var ExternalConnection $externalConnection */
        $externalConnection = $externalCompany->externalConnection;

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->timeout(config('services.http_time_out'))->post(
            $externalConnection->url . '/api/external-connection/purchase-order-invoices/paid',
            [
                'token' => $externalConnection->token,
                'purchase_order_invoice_id' => $purchaseOrderInvoice->external_purchase_order_invoice_id,
                'company_id' => $externalCompany->external_company_id,
            ]
        );

        if ($response->successful()) {
            return;
        }

        abort(417, 'An error occurred. Please try again.');
    }

    public function markExternalPurchaseOrderInvoiceAsReceived(PurchaseOrderInvoice $purchaseOrderInvoice): void
    {
        /** @var PurchaseOrder $purchaseOrder */
        $purchaseOrder = $purchaseOrderInvoice->purchaseOrder;

        /** @var ExternalCompany $externalCompany */
        $externalCompany = $purchaseOrder->externalCompany;

        /** @var ExternalConnection $externalConnection */
        $externalConnection = $externalCompany->externalConnection;

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->timeout(config('services.http_time_out'))->post(
            $externalConnection->url . '/api/external-connection/purchase-order-invoices/mark-as-received',
            [
                'token' => $externalConnection->token,
                'purchase_order_invoice_id' => $purchaseOrderInvoice->external_purchase_order_invoice_id,
                'company_id' => $externalCompany->external_company_id,
            ]
        );

        if ($response->successful()) {
            return;
        }

        abort(417, 'An error occurred. Please try again.');
    }

    public function postExternalInvoice(PurchaseOrderInvoice $purchaseOrderInvoice): array
    {
        /** @var PurchaseOrder $purchaseOrder */
        $purchaseOrder = $purchaseOrderInvoice->purchaseOrder;

        /** @var ExternalCompany $externalCompany */
        $externalCompany = $purchaseOrder->externalCompany;

        /** @var ExternalConnection $externalConnection */
        $externalConnection = $externalCompany->externalConnection;

        $purchaseOrderFulfillments = $purchaseOrderInvoice->fulfillments;

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->timeout(config('services.http_time_out'))->post(
            $externalConnection->url . '/api/external-connection/purchase-order-invoices/sent',
            [
                'token' => $externalConnection->token,
                'external_purchase_order_invoice_id' => $purchaseOrderInvoice->id,
                'purchase_order_id' => $purchaseOrder->external_purchase_order_id,
                'company_id' => $externalCompany->external_company_id,
                'status' => InvoiceStatuses::SENT->value,
                'invoice_number' => $purchaseOrderInvoice->invoice_number,
                'purchase_order_fulfillments' => $purchaseOrderFulfillments->map(
                    fn (PurchaseOrderFulfillment $purchaseOrderFulfillment): array => [
                        'purchase_order_fulfillment_id' => $purchaseOrderFulfillment->external_purchase_order_fulfillment_id,
                    ]
                )->toArray(),
            ]
        );

        if (! $response->successful()) {
            abort(417, 'An error occurred. Please try again.');
        }

        return json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);
    }

    public function saveExternalInvoice(
        array $purchaseOrderInvoiceData,
        Collection $purchaseOrderFulfillments,
    ): array {
        $purchaseOrderFulfillmentQueries = resolve(PurchaseOrderFulfillmentQueries::class);
        $purchaseOrderInvoiceQueries = resolve(PurchaseOrderInvoiceQueries::class);

        $purchaseOrderInvoice = $purchaseOrderInvoiceQueries->addNew([
            'purchase_order_id' => $purchaseOrderInvoiceData['purchase_order_id'],
            'company_id' => $purchaseOrderInvoiceData['company_id'],
            'status' => $purchaseOrderInvoiceData['status'],
            'invoice_number' => $purchaseOrderInvoiceData['invoice_number'],
            'external_purchase_order_invoice_id' => $purchaseOrderInvoiceData['external_purchase_order_invoice_id'],
        ]);

        foreach ($purchaseOrderInvoiceData['purchase_order_fulfillments'] as $purchaseOrderFulfillmentData) {
            $purchaseOrderFulfillment = $purchaseOrderFulfillments->firstWhere(
                'id',
                $purchaseOrderFulfillmentData['purchase_order_fulfillment_id']
            );
            $purchaseOrderFulfillmentQueries->updateInvoiceId($purchaseOrderFulfillment, $purchaseOrderInvoice->id);
        }

        return [
            'purchase_order_invoice_id' => $purchaseOrderInvoiceData['external_purchase_order_invoice_id'],
            'external_purchase_order_invoice_id' => $purchaseOrderInvoice->id,
        ];
    }

    public function fetchPurchaseOrderInvoices(array $filterData, int $companyId): array
    {
        $purchaseOrderInvoiceQueries = resolve(PurchaseOrderInvoiceQueries::class);

        $lengthAwarePaginator = $purchaseOrderInvoiceQueries->listQuery($filterData, $companyId);

        $invoiceCounts = $purchaseOrderInvoiceQueries->allInvoiceStatusCount($filterData, $companyId);

        $statusCounts = [];
        foreach ($invoiceCounts as $invoiceCount) {
            $statusName = InvoiceStatuses::getFormattedCaseName($invoiceCount->status);
            $statusCounts[$statusName] = [
                'count' => $invoiceCount->count,
                'id' => $invoiceCount->status,
            ];
        }

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => PurchaseOrderInvoiceListResource::collection($lengthAwarePaginator->getCollection()),
            'statusCounts' => $statusCounts,
        ];
    }

    public function storePurchaseOrderInvoice(
        Collection $purchaseOrderFulfillments,
        int $companyId,
        int $purchaseOrderId,
        int $purchaseOrderLocationId,
    ): void {
        $purchaseOrderFulfillmentQueries = resolve(PurchaseOrderFulfillmentQueries::class);
        $purchaseOrderInvoiceQueries = resolve(PurchaseOrderInvoiceQueries::class);

        $sequenceQueries = resolve(SequenceQueries::class);
        $sequence = $sequenceQueries->addNew($purchaseOrderLocationId, SequenceTypes::IN->value);

        $purchaseOrderInvoice = $purchaseOrderInvoiceQueries->addNew([
            'purchase_order_id' => $purchaseOrderId,
            'company_id' => $companyId,
            'created_by_company_id' => $companyId,
            'invoice_number' => $sequence->getCompleteNumber(),
            'status' => InvoiceStatuses::DRAFT->value,
        ]);

        foreach ($purchaseOrderFulfillments as $purchaseOrderFulfillment) {
            $purchaseOrderFulfillmentQueries->updateInvoiceId($purchaseOrderFulfillment, $purchaseOrderInvoice->id);
        }
    }

    public function purchaseOrderInvoiceCancel(
        PurchaseOrderInvoice $purchaseOrderInvoice,
        Collection $purchaseOrderFulfillments,
        User $user
    ): void {
        $purchaseOrderInvoiceTransactionQueries = resolve(PurchaseOrderInvoiceTransactionQueries::class);
        $purchaseOrderFulfillmentQueries = resolve(PurchaseOrderFulfillmentQueries::class);
        $purchaseOrderInvoiceQueries = resolve(PurchaseOrderInvoiceQueries::class);

        $purchaseOrderInvoiceTransactionQueries->addNew(
            $purchaseOrderInvoice->getKey(),
            $purchaseOrderInvoice->status,
            InvoiceStatuses::CANCELLED->value,
            $user
        );

        $purchaseOrderInvoiceQueries->cancelInvoice($purchaseOrderInvoice, InvoiceStatuses::CANCELLED->value);

        foreach ($purchaseOrderFulfillments as $purchaseOrderFulfillment) {
            $purchaseOrderFulfillmentQueries->updateRemoveInvoiceId($purchaseOrderFulfillment);
        }
    }

    public function purchaseOrderInvoiceSent(
        PurchaseOrderInvoice $purchaseOrderInvoice,
        User $user,
        int $companyId
    ): void {
        $purchaseOrderInvoiceTransactionQueries = resolve(PurchaseOrderInvoiceTransactionQueries::class);
        $purchaseOrderInvoiceQueries = resolve(PurchaseOrderInvoiceQueries::class);

        $purchaseOrderInvoiceTransactionQueries->addNew(
            $purchaseOrderInvoice->getKey(),
            $purchaseOrderInvoice->status,
            InvoiceStatuses::SENT->value,
            $user
        );

        $purchaseOrderInvoiceQueries->updateInvoiceStatus($purchaseOrderInvoice, InvoiceStatuses::SENT->value);

        $data = $this->postExternalInvoice($purchaseOrderInvoice);

        $purchaseOrderInvoiceQueries->updateExternalInvoiceId(
            $data['purchase_order_invoice_id'],
            $data['external_purchase_order_invoice_id'],
            $companyId
        );
    }

    public function purchaseOrderInvoicePaid(PurchaseOrderInvoice $purchaseOrderInvoice, User $user): void
    {
        $purchaseOrderInvoiceTransactionQueries = resolve(PurchaseOrderInvoiceTransactionQueries::class);
        $purchaseOrderInvoiceQueries = resolve(PurchaseOrderInvoiceQueries::class);

        $purchaseOrderInvoiceTransactionQueries->addNew(
            $purchaseOrderInvoice->getKey(),
            $purchaseOrderInvoice->status,
            InvoiceStatuses::PAID->value,
            $user
        );

        $purchaseOrderInvoiceQueries->updateInvoiceStatus($purchaseOrderInvoice, InvoiceStatuses::PAID->value);

        $this->paidExternalPurchaseOrderInvoice($purchaseOrderInvoice);
    }

    public function purchaseOrderInvoiceReceived(PurchaseOrderInvoice $purchaseOrderInvoice, User $user): void
    {
        $purchaseOrderInvoiceTransactionQueries = resolve(PurchaseOrderInvoiceTransactionQueries::class);
        $purchaseOrderInvoiceQueries = resolve(PurchaseOrderInvoiceQueries::class);

        $purchaseOrderInvoiceTransactionQueries->addNew(
            $purchaseOrderInvoice->getKey(),
            $purchaseOrderInvoice->status,
            InvoiceStatuses::RECEIVED->value,
            $user
        );

        $purchaseOrderInvoiceQueries->updateInvoiceStatus($purchaseOrderInvoice, InvoiceStatuses::RECEIVED->value);

        $this->markExternalPurchaseOrderInvoiceAsReceived($purchaseOrderInvoice);
    }
}

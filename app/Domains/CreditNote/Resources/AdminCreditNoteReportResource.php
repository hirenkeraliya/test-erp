<?php

declare(strict_types=1);

namespace App\Domains\CreditNote\Resources;

use App\Domains\CreditNote\Enums\CreditNoteStatuses;
use App\Models\BookingPaymentPayment;
use App\Models\CancelLayawaySale;
use App\Models\Cashier;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\CreditNote;
use App\Models\CreditNoteExpiration;
use App\Models\CreditNoteRefund;
use App\Models\Employee;
use App\Models\Location;
use App\Models\PaymentType;
use App\Models\PosMismatch;
use App\Models\SalePayment;
use App\Models\SaleReturn;
use App\Models\StoreManager;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class AdminCreditNoteReportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var CreditNote $creditNote */
        $creditNote = $this;

        /** @var CounterUpdate $counterUpdate */
        $counterUpdate = $creditNote->counterUpdate;

        /** @var ?SaleReturn $saleReturn */
        $saleReturn = $creditNote->saleReturn;

        /** @var Cashier $cashier */
        $cashier = $counterUpdate->cashier;

        /** @var Employee $employee */
        $employee = $cashier->getEmployee();

        /** @var Counter $counter */
        $counter = $counterUpdate->getCounter();

        /** @var Location $location */
        $location = $counter->location;

        /** @var ?CreditNoteRefund $creditNoteRefund */
        $creditNoteRefund = $creditNote->getCreditNoteRefund();

        /** @var ?CreditNoteExpiration $creditNoteExpiration */
        $creditNoteExpiration = $creditNote->getCreditNoteExpiration();

        /** @var ?CancelLayawaySale $cancelLayawaySale */
        $cancelLayawaySale = $creditNote->cancelLayawaySale;

        $creditNoteExpiryDate = 'N/A';

        if ($creditNote->expiry_date) {
            /** @var Carbon $creditNoteExpiryDate */
            $creditNoteExpiryDate = Carbon::createFromFormat('Y-m-d', $creditNote->expiry_date);
            $creditNoteExpiryDate = $creditNoteExpiryDate->format('d-m-Y');
        }

        /** @var Carbon $createdAt */
        $createdAt = $creditNote->created_at;

        /** @var Collection $mismatches */
        $mismatches = $creditNote->mismatches;

        return [
            'id' => $creditNote->id,
            'receipt_id' => $saleReturn->offline_sale_return_id ?? $cancelLayawaySale->sale->offline_sale_id ?? 'N/A',
            'member' => $creditNote->member ? $creditNote->member->getFullName() : 'Walk in Member',
            'expiry_date' => $creditNoteExpiryDate,
            'total_amount' => $creditNote->total_amount,
            'available_amount' => $creditNote->available_amount,
            'created_at' => $createdAt->format('d-m-Y h:i:s A'),
            'status' => CreditNoteStatuses::getCaseNameByValue($creditNote->status),
            'status_id' => $creditNote->status,
            'location' => $location->name,
            'cashier' => $employee->getFullName(),
            'counter' => $counter->getName(),
            'uses' => $creditNote->uses->map(function ($use): array {
                /** @var ?SalePayment $salePayment */
                $salePayment = $use->salePayment;
                /** @var ?BookingPaymentPayment $bookingPaymentPayment */
                $bookingPaymentPayment = $use->BookingPaymentPayment;
                /** @var Carbon $createdAt */
                $createdAt = $use->created_at;

                return [
                    'sale_id' => $salePayment ? $salePayment->sale_id : 'N/A',
                    'booking_payment_id' => $bookingPaymentPayment ? $bookingPaymentPayment->booking_payment_id : 'N/A',
                    'amount' => $use->amount,
                    'date' => $createdAt->format('d-m-Y h:i:s A'),
                ];
            }),
            'refund_details' => $this->getRefundDetails($creditNoteRefund),
            'expiry_details' => $this->getExpiryDetails($creditNoteExpiryDate, $creditNoteExpiration),
            'credit_note_refund_mismatches' => PosMismatch::getPreparedMismatches($mismatches),
            'digital_invoice_submitted' => $creditNote->digital_invoice_submitted,
            'digital_invoice_number' => $creditNote->digital_invoice_number ?: 'N/A',
        ];
    }

    private function getRefundDetails(?CreditNoteRefund $creditNoteRefund): ?array
    {
        if (! $creditNoteRefund instanceof CreditNoteRefund) {
            return null;
        }

        /** @var PaymentType $paymentType */
        $paymentType = $creditNoteRefund->getPaymentType();

        /** @var StoreManager $storeManager */
        $storeManager = $creditNoteRefund->getStoreManager();

        /** @var Employee $employee */
        $employee = $storeManager->getEmployee();

        /** @var Carbon $createdAt */
        $createdAt = $creditNoteRefund->created_at;

        return [
            [
                'payment_name' => $paymentType->name,
                'amount' => $creditNoteRefund->amount,
                'approved_by' => $employee->getFullName(),
                'refunded_date' => $createdAt->format('d-m-Y h:i:s A'),
            ],
        ];
    }

    private function getExpiryDetails(
        string $creditNoteExpiryDate,
        ?CreditNoteExpiration $creditNoteExpiration
    ): ?array {
        if (! $creditNoteExpiration instanceof CreditNoteExpiration) {
            return null;
        }

        return [
            [
                'expiry_date' => $creditNoteExpiryDate,
                'expired_amount' => $creditNoteExpiration->amount,
            ],
        ];
    }
}

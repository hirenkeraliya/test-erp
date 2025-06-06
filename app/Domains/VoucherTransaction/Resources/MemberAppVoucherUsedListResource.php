<?php

declare(strict_types=1);

namespace App\Domains\VoucherTransaction\Resources;

use App\Domains\Common\Enums\DiscountTypes;
use App\Domains\Sale\Enums\SaleStatus;
use App\Domains\Voucher\Enums\VoucherStatusTypes;
use App\Models\Voucher;
use App\Models\VoucherTransaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberAppVoucherUsedListResource extends JsonResource
{
    public function __construct(
        protected Voucher $voucher,
        protected ?VoucherTransaction $voucherCreateTransaction,
        protected ?VoucherTransaction $voucherUsedTransaction
    ) {
        parent::__construct($voucherCreateTransaction);
        $this->voucherCreateTransaction = $voucherCreateTransaction;
        $this->voucherUsedTransaction = $voucherUsedTransaction;
    }

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $voucher = $this->voucher;

        $createSale = $this->voucherCreateTransaction?->sale;
        $usedSale = $this->voucherUsedTransaction?->sale;

        $expiryDate = '';

        if ($voucher->expiry_date) {
            /** @var Carbon $expiryDateFormat */
            $expiryDateFormat = Carbon::createFromFormat('Y-m-d', $voucher->expiry_date);
            $expiryDate = $expiryDateFormat->format('d-m-Y');
        }

        return [
            'voucher_id' => $voucher->id,
            'voucher' => [
                'id' => $voucher->id,
                'number' => $voucher->number,
                'discount_type' => DiscountTypes::getCaseNameByValue($voucher->discount_type),
                'minimum_spend_amount' => $voucher->minimum_spend_amount,
                'percentage' => $voucher->percentage,
                'flat_amount' => $voucher->flat_amount,
                'used_at' => $voucher->used_at,
                'expiry_date' => $expiryDate,
                'status' => VoucherStatusTypes::getFormattedArrayForPos($voucher->status),
            ],
            'create_sale' => $createSale ? [
                'id' => $createSale->id,
                'offline_sale_id' => $createSale->offline_sale_id,
                'member_id' => $createSale->member_id,
                'total_tax_amount' => (float) $createSale->total_tax_amount,
                'cart_discount_amount' => (float) $createSale->cart_discount_amount,
                'items_discount_amount' => (float) $createSale->items_discount_amount,
                'total_discount_amount' => (float) $createSale->total_discount_amount,
                'total_amount_before_round_off' => (float) $createSale->total_amount_before_round_off,
                'round_off_amount' => (float) $createSale->round_off,
                'change_due' => (float) $createSale->change_due,
                'total_amount_paid' => (float) $createSale->total_amount_paid,
                'layaway_pending_amount' => (float) $createSale->layaway_pending_amount,
                'layaway_completed_at' => (float) $createSale->layaway_completed_at,
                'credit_pending_amount' => (float) $createSale->credit_pending_amount,
                'credit_completed_at' => (float) $createSale->credit_completed_at,
                'sale_notes' => $createSale->notes,
                'bill_reference_number' => $createSale->bill_reference_number,
                'extra_details' => $createSale->extra_details ?? null,
                'has_mismatch' => $createSale->has_mismatch,
                'status' => SaleStatus::getCaseNameByValue($createSale->getStatus()),
                'happened_at' => $createSale->happened_at,
            ] : null,
            'sale' => $usedSale ? [
                'id' => $usedSale->id,
                'offline_sale_id' => $usedSale->offline_sale_id,
                'member_id' => $usedSale->member_id,
                'total_tax_amount' => (float) $usedSale->total_tax_amount,
                'cart_discount_amount' => (float) $usedSale->cart_discount_amount,
                'items_discount_amount' => (float) $usedSale->items_discount_amount,
                'total_discount_amount' => (float) $usedSale->total_discount_amount,
                'total_amount_before_round_off' => (float) $usedSale->total_amount_before_round_off,
                'round_off_amount' => (float) $usedSale->round_off,
                'change_due' => (float) $usedSale->change_due,
                'total_amount_paid' => (float) $usedSale->total_amount_paid,
                'layaway_pending_amount' => (float) $usedSale->layaway_pending_amount,
                'layaway_completed_at' => (float) $usedSale->layaway_completed_at,
                'credit_pending_amount' => (float) $usedSale->credit_pending_amount,
                'credit_completed_at' => (float) $usedSale->credit_completed_at,
                'sale_notes' => $usedSale->notes,
                'bill_reference_number' => $usedSale->bill_reference_number,
                'extra_details' => $usedSale->extra_details ?? null,
                'has_mismatch' => $usedSale->has_mismatch,
                'status' => SaleStatus::getCaseNameByValue($usedSale->getStatus()),
                'happened_at' => $usedSale->happened_at,
            ] : null,
        ];
    }
}

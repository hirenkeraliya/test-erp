<?php

declare(strict_types=1);

namespace App\Domains\VoidSale\Services;

use App\Domains\BookingPayment\BookingPaymentQueries;
use App\Domains\BookingPaymentVoidUse\BookingPaymentVoidUseQueries;
use App\Domains\CashMovement\CashMovementQueries;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\CreditNote\CreditNoteQueries;
use App\Domains\CreditNoteVoidUse\CreditNoteVoidUseQueries;
use App\Domains\GiftCard\GiftCardQueries;
use App\Domains\GiftCardTransaction\Enums\GiftCardTransactionTypes;
use App\Domains\GiftCardTransaction\GiftCardTransactionQueries;
use App\Domains\Inventory\InventoryQueries;
use App\Domains\Inventory\Services\VoidSaleInventoryService;
use App\Domains\LoyaltyPoint\LoyaltyPointQueries;
use App\Domains\LoyaltyPoint\Services\LoyaltyPointService;
use App\Domains\LoyaltyPoint\Services\RevertLoyaltyPointService;
use App\Domains\LoyaltyPointUpdate\Enums\LoyaltyPointUpdateTypes;
use App\Domains\LoyaltyPointUpdate\LoyaltyPointUpdateQueries;
use App\Domains\Product\Enums\ProductTypes;
use App\Domains\ReservedStock\Services\SaleReservedStockService;
use App\Domains\Sale\Enums\SaleStatus;
use App\Domains\Sale\SaleQueries;
use App\Domains\SaleCashback\SaleCashbackQueries;
use App\Domains\SaleDiscount\SaleDiscountQueries;
use App\Domains\SalePayment\SalePaymentQueries;
use App\Domains\SaleVoidCashback\SaleVoidCashbackQueries;
use App\Domains\VoidSale\DataObjects\PosVoidSaleData;
use App\Domains\VoidSale\VoidSaleQueries;
use App\Domains\Voucher\VoucherQueries;
use App\Domains\VoucherTransaction\Enums\VoucherTransactionActionTypes;
use App\Domains\VoucherTransaction\VoucherTransactionQueries;
use App\Models\BookingPaymentUse;
use App\Models\CreditNoteUse;
use App\Models\Member;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Models\VoidSale;
use Illuminate\Foundation\Auth\User;

class VoidSaleService
{
    public function saveVoidDetails(PosVoidSaleData $posVoidSaleData, int $saleId, int $companyId): VoidSale
    {
        $voidSaleQueries = resolve(VoidSaleQueries::class);

        return $voidSaleQueries->addNew($posVoidSaleData, $saleId, $companyId);
    }

    public function updateInventory(Sale $sale, VoidSale $voidSale, User $user, int $locationId): void
    {
        if ($sale->status === SaleStatus::PENDING_LAYAWAY_SALE->value) {
            $this->revertReservedStock($sale);

            return;
        }

        $voidSaleInventoryService = resolve(VoidSaleInventoryService::class);
        $inventoryQueries = resolve(InventoryQueries::class);
        foreach ($sale->getSaleItems() as $saleItem) {
            foreach ($saleItem->saleItemUnits as $saleItemUnit) {
                $productId = $saleItem->product_id;

                if ($saleItem->product->type_id === ProductTypes::ASSEMBLY_PRODUCT->value) {
                    $inventory = $inventoryQueries->getInventoryById($saleItemUnit->inventory_id);

                    $productId = $inventory->product_id;
                }

                $voidSaleInventoryService->addInventory(
                    $voidSale,
                    $user,
                    (float) $saleItemUnit->quantity,
                    $locationId,
                    $productId,
                    $saleItemUnit->purchase_amount_id,
                    $saleItemUnit->batch_id,
                );
            }
        }
    }

    public function checkAndRevertLoyaltyPoints(Sale $sale, VoidSale $voidSale): void
    {
        $loyaltyPointQueries = resolve(LoyaltyPointQueries::class);
        $loyaltyPoints = $loyaltyPointQueries->getLoyaltyPointForGivenSale($sale->id);
        if ($loyaltyPoints->isEmpty()) {
            return;
        }

        $saleQueries = resolve(SaleQueries::class);
        $sale = $saleQueries->loadVoidSaleRelations($sale);

        if (! $sale->member) {
            return;
        }

        $loyaltyPointService = resolve(LoyaltyPointService::class);
        $loyaltyPointService->decreaseLoyaltyPoints(
            $sale->member,
            $loyaltyPoints->sum('points'),
            LoyaltyPointUpdateTypes::VOID_SALE->value,
            $voidSale->id,
            ModelMapping::VOID_SALE->name,
            now()->format('Y-m-d H:i:s')
        );
    }

    public function revertUsedItemLoyaltyPoints(Sale $sale, VoidSale $voidSale): void
    {
        $saleQueries = resolve(SaleQueries::class);
        $sale = $saleQueries->loadVoidSaleRelations($sale);

        foreach ($sale->getSaleItems() as $saleItem) {
            $this->revertUsedLoyaltyPoints($saleItem->id, ModelMapping::SALE_ITEM->name, $sale->member, $voidSale);
        }
    }

    public function revertUsedLoyaltyPoints(
        int $affectedById,
        string $affectedByType,
        ?Member $member,
        VoidSale $voidSale,
    ): void {
        if (! $member instanceof Member) {
            return;
        }

        $loyaltyPointUpdateQueries = resolve(LoyaltyPointUpdateQueries::class);
        $loyaltyPointUpdates = $loyaltyPointUpdateQueries->getUsedLoyaltyPoint(
            $affectedById,
            $affectedByType,
            LoyaltyPointUpdateTypes::USED->value
        );

        if ($loyaltyPointUpdates->isEmpty()) {
            return;
        }

        foreach ($loyaltyPointUpdates as $loyaltyPointUpdate) {
            $expiryDate = null;
            if ($loyaltyPointUpdate->loyaltyPoint) {
                $expiryDate = $loyaltyPointUpdate->loyaltyPoint->expiry_date;
            }

            $revertLoyaltyPointService = resolve(RevertLoyaltyPointService::class);
            $revertLoyaltyPointService->increaseLoyaltyPoints(
                $member,
                $voidSale,
                (int) abs($loyaltyPointUpdate->points),
                now()->format('Y-m-d H:i:s'),
                $expiryDate
            );
        }
    }

    public function checkAndRevertCreditNote(int $saleId, int $voidSaleId): void
    {
        $salePaymentQueries = resolve(SalePaymentQueries::class);
        $creditNoteQueries = resolve(CreditNoteQueries::class);
        $creditNoteVoidUseQueries = resolve(CreditNoteVoidUseQueries::class);

        $salePayments = $salePaymentQueries->getSalePaymentIdAndAmountOfCreditNote($saleId);
        if ($salePayments->isEmpty()) {
            return;
        }

        foreach ($salePayments as $salePayment) {
            /** @var CreditNoteUse $creditNoteUses */
            $creditNoteUses = $salePayment->creditNoteUse;

            $creditNoteVoidUseQueries->addNew(
                $creditNoteUses->credit_note_id,
                $creditNoteUses->id,
                $voidSaleId,
                (float) $salePayment->amount
            );

            $creditNoteQueries->incrementAvailableAmountAndActivate(
                $creditNoteUses->credit_note_id,
                (float) $salePayment->amount
            );
        }
    }

    public function checkAndRevertVouchersGenerated(int $saleId, int $locationId): void
    {
        $voucherQueries = resolve(VoucherQueries::class);
        $voucherTransactionQueries = resolve(VoucherTransactionQueries::class);

        $vouchers = $voucherQueries->getVouchersBySaleId($saleId);

        if ($vouchers->isEmpty()) {
            return;
        }

        foreach ($vouchers as $voucher) {
            $voucherTransactionQueries->addNew(
                $voucher->id,
                VoucherTransactionActionTypes::CANCELLED->value,
                now()->format('Y-m-d H:i:s'),
                $saleId,
                $locationId
            );

            $voucherQueries->updateCancelledAt($voucher);
        }
    }

    public function checkAndRevertBookingPayment(int $saleId, int $voidSaleId): void
    {
        $salePaymentQueries = resolve(SalePaymentQueries::class);
        $bookingPaymentQueries = resolve(BookingPaymentQueries::class);
        $bookingPaymentVoidUseQueries = resolve(BookingPaymentVoidUseQueries::class);

        $salePayments = $salePaymentQueries->getSalePaymentIdAndAmountOfBookingPayment($saleId);

        if ($salePayments->isEmpty()) {
            return;
        }

        foreach ($salePayments as $salePayment) {
            /** @var BookingPaymentUse $bookingPaymentUse */
            $bookingPaymentUse = $salePayment->bookingPaymentUse;

            $bookingPaymentVoidUseQueries->addNew(
                $bookingPaymentUse->booking_payment_id,
                $bookingPaymentUse->id,
                $voidSaleId,
                (float) $salePayment->amount
            );

            $bookingPaymentQueries->incrementAvailableAmountAndActivate(
                $bookingPaymentUse->booking_payment_id,
                (float) $salePayment->amount
            );
        }
    }

    public function checkAndRevertCashback(int $saleId, int $voidSaleId): void
    {
        $saleCashbackQueries = resolve(SaleCashbackQueries::class);
        $cashMovementQueries = resolve(CashMovementQueries::class);
        $saleVoidCashbackQueries = resolve(SaleVoidCashbackQueries::class);

        $saleCashback = $saleCashbackQueries->getBySaleId($saleId);

        if (null === $saleCashback) {
            return;
        }

        /** @var Sale $sale */
        $sale = $saleCashback->sale;

        $cashMovementId = $cashMovementQueries->addNewForCashbackReversal(
            $sale->offline_sale_id,
            $sale->counter_update_id,
            (float) $saleCashback->amount,
            $sale->happened_at
        );

        $saleVoidCashbackQueries->addNew($saleCashback->id, $voidSaleId, $cashMovementId);
    }

    public function checkAndRevertGiftCard(int $saleId, int $voidSaleId): void
    {
        $giftCardTransactionQueries = resolve(GiftCardTransactionQueries::class);
        $giftCardQueries = resolve(GiftCardQueries::class);

        $giftCardTransactions = $giftCardTransactionQueries->getBySaleId($saleId);

        if ($giftCardTransactions->isEmpty()) {
            return;
        }

        foreach ($giftCardTransactions as $giftCardTransaction) {
            /** @var SalePayment $salePayment */
            $salePayment = $giftCardTransaction->affectedBy;

            $giftCardTransactionQueries->addNewForVoidSale(
                $giftCardTransaction->gift_card_id,
                $voidSaleId,
                GiftCardTransactionTypes::VOID_SALE->value,
                (float) $salePayment->amount
            );

            $giftCardQueries->incrementAvailableAmountAndActivate(
                $giftCardTransaction->gift_card_id,
                (float) $salePayment->amount
            );
        }
    }

    public function checkAndRevertUsedVoucher(int $saleId, int $locationId): void
    {
        $saleDiscountQueries = resolve(SaleDiscountQueries::class);
        $voucherQueries = resolve(VoucherQueries::class);
        $voucherTransactionQueries = resolve(VoucherTransactionQueries::class);

        $voucherId = $saleDiscountQueries->getVoucherIdBySale($saleId);

        if (null === $voucherId) {
            return;
        }

        $voucher = $voucherQueries->getById($voucherId);

        $voucherTransactionQueries->addNew(
            $voucher->id,
            VoucherTransactionActionTypes::RESET->value,
            now()->format('Y-m-d H:i:s'),
            $saleId,
            $locationId
        );

        $voucherQueries->resetUsedAt($voucher);
    }

    public function revertReservedStock(Sale $sale): void
    {
        $saleReservedStockService = resolve(SaleReservedStockService::class);
        foreach ($sale->getSaleItems() as $saleItem) {
            $saleReservedStockService->revertReservedStock($saleItem);
        }
    }
}

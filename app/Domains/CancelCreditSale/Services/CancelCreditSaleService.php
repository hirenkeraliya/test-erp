<?php

declare(strict_types=1);

namespace App\Domains\CancelCreditSale\Services;

use App\Domains\CancelCreditSale\CancelCreditSaleQueries;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\CreditNote\CreditNoteQueries;
use App\Domains\Inventory\Services\SaleInventoryService;
use App\Domains\LoyaltyPoint\LoyaltyPointQueries;
use App\Domains\LoyaltyPoint\Services\LoyaltyPointService;
use App\Domains\LoyaltyPoint\Services\RevertLoyaltyPointService;
use App\Domains\LoyaltyPointUpdate\Enums\LoyaltyPointUpdateTypes;
use App\Domains\LoyaltyPointUpdate\LoyaltyPointUpdateQueries;
use App\Domains\PaymentType\Enums\StaticPaymentTypes;
use App\Domains\Sale\DataObjects\CancelCreditSaleData;
use App\Domains\Sequence\Enums\SequenceTypes;
use App\Domains\Sequence\SequenceQueries;
use App\Domains\StoreManager\StoreManagerQueries;
use App\Domains\StoreManagerAuthorizationCodeUsage\Services\StoreManagerAuthorizationCodeUsageService;
use App\Domains\Voucher\VoucherQueries;
use App\Domains\VoucherTransaction\Enums\VoucherTransactionActionTypes;
use App\Domains\VoucherTransaction\VoucherTransactionQueries;
use App\Models\CancelCreditSale;
use App\Models\Cashier;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\Location;
use App\Models\Member;
use App\Models\Sale;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Collection;

class CancelCreditSaleService
{
    public function checkRequestDetails(
        CancelCreditSaleData $cancelCreditSaleData,
        Sale $sale,
        Location $location,
        int $companyId,
        Collection $saleMismatches,
    ): void {
        $this->checkStore($location, $sale);

        $voucherQueries = resolve(VoucherQueries::class);

        if ($voucherQueries->checkGeneratedVoucherIsUsed($sale->id)) {
            abort(
                412,
                'I apologize, but it seems that this voucher has already been used for another transaction and is no longer eligible for refunding.'
            );
        }

        $storeManagerQueries = resolve(StoreManagerQueries::class);

        $storeManager = $storeManagerQueries->getById($cancelCreditSaleData->store_manager_id, $companyId);

        if ($cancelCreditSaleData->passcode !== $storeManager->passcode) {
            abort(412, 'Wrong passcode.');
        }

        $storeManagerAuthorizationCodeUsageService = resolve(StoreManagerAuthorizationCodeUsageService::class);
        $storeManagerAuthorizationCodeUsageService->checkStoreManagerAuthorizationCode(
            $saleMismatches,
            $cancelCreditSaleData->store_manager_id,
            $cancelCreditSaleData->store_manager_authorization_code,
            $cancelCreditSaleData->happened_at
        );
    }

    public function checkStore(Location $location, Sale $sale): void
    {
        /** @var CounterUpdate $counterUpdate */
        $counterUpdate = $sale->counterUpdate;

        /** @var Counter $counter */
        $counter = $counterUpdate->counter;

        if ((int) $location->id === (int) $counter->location_id) {
            return;
        }

        abort(412, 'Credit sale cannot be canceled at a different location.');
    }

    public function saveDetails(
        CancelCreditSaleData $cancelCreditSaleData,
        Sale $sale,
        int $counterUpdateId,
        Location $location,
        Cashier $cashier,
    ): void {
        $cancelCreditSaleQueries = resolve(CancelCreditSaleQueries::class);
        $cancelCreditSale = $cancelCreditSaleQueries->addNew($cancelCreditSaleData, $sale->id);
        $this->loyaltyPointsRevert($sale, $cancelCreditSale, $cancelCreditSaleData);
        $this->vouchersRevert($sale->id, $location->id);

        $this->creditNoteCreateAndRefund(
            $sale,
            $location,
            $cancelCreditSaleData,
            $counterUpdateId,
            $cancelCreditSale->id,
            $location->credit_note_expiration_days
        );

        $this->revertInventory($sale, $cashier, $location->id);

        $this->revertUsedItemLoyaltyPoints($sale, $cancelCreditSale, $cancelCreditSaleData->happened_at);

        $this->revertUsedLoyaltyPoints(
            $sale->id,
            ModelMapping::SALE->name,
            $sale->member,
            $cancelCreditSale,
            $cancelCreditSaleData->happened_at,
        );
    }

    public function loyaltyPointsRevert(
        Sale $sale,
        CancelCreditSale $cancelCreditSale,
        CancelCreditSaleData $cancelCreditSaleData
    ): void {
        /** @var Member $member */
        $member = $sale->member;
        $loyaltyPointQueries = resolve(LoyaltyPointQueries::class);
        $loyaltyPoints = $loyaltyPointQueries->getLoyaltyPointForGivenSale($sale->id);

        if ($loyaltyPoints->isEmpty()) {
            return;
        }

        $loyaltyPointService = resolve(LoyaltyPointService::class);
        $loyaltyPointService->decreaseLoyaltyPoints(
            $member,
            $loyaltyPoints->sum('points'),
            LoyaltyPointUpdateTypes::CANCEL_CREDIT_SALE->value,
            $cancelCreditSale->id,
            ModelMapping::CANCEL_CREDIT_SALE->name,
            $cancelCreditSaleData->happened_at
        );
    }

    public function vouchersRevert(int $saleId, int $locationId): void
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

    public function creditNoteCreateAndRefund(
        Sale $sale,
        Location $location,
        CancelCreditSaleData $cancelCreditSaleData,
        int $counterUpdateId,
        int $cancelCreditSaleId,
        ?int $creditNoteExpirationDays,
    ): void {
        $totalAmountPaid = $sale->payments
            ->where('payment_type_id', '!=', StaticPaymentTypes::LOYALTY_POINT->value)
            ->sum('amount');

        $digitalInvoiceNumber = $this->getSequenceNumber($location, SequenceTypes::CN);
        $creditNoteQueries = resolve(CreditNoteQueries::class);
        $creditNoteQueries->addNewForCancelCreditSale(
            $counterUpdateId,
            $cancelCreditSaleId,
            $digitalInvoiceNumber,
            (float) $totalAmountPaid,
            $cancelCreditSaleData->happened_at,
            $creditNoteExpirationDays,
            $sale->member_id,
        );
    }

    public function revertUsedItemLoyaltyPoints(
        Sale $sale,
        CancelCreditSale $cancelCreditSale,
        string $happenedAt,
    ): void {
        foreach ($sale->getSaleItems() as $saleItem) {
            $this->revertUsedLoyaltyPoints(
                $saleItem->id,
                ModelMapping::SALE_ITEM->name,
                $sale->member,
                $cancelCreditSale,
                $happenedAt,
            );
        }
    }

    public function revertUsedLoyaltyPoints(
        int $affectedById,
        string $affectedByType,
        ?Member $member,
        CancelCreditSale $cancelCreditSale,
        string $happenedAt,
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
                $cancelCreditSale,
                (int) abs($loyaltyPointUpdate->points),
                $happenedAt,
                $expiryDate
            );
        }
    }

    public function revertInventory(Sale $sale, User $cashier, int $locationId): void
    {
        $saleInventoryService = resolve(SaleInventoryService::class);
        foreach ($sale->getSaleItems() as $saleItem) {
            foreach ($saleItem->saleItemUnits as $saleItemUnit) {
                $saleInventoryService->addInventory(
                    $saleItem,
                    $cashier,
                    (float) $saleItemUnit->quantity,
                    $locationId,
                    $saleItemUnit->purchase_amount_id,
                    $saleItemUnit->batch_id,
                    $sale->happened_at
                );
            }
        }
    }

    public function getSequenceNumber(Location $location, SequenceTypes $sequenceType): string
    {
        $sequenceQueries = resolve(SequenceQueries::class);
        $number = $sequenceQueries->addNew($location->id, $sequenceType->value)->number;

        return $location->code.'-'.$sequenceType->name.'-'.$number;
    }
}

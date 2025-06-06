<?php

declare(strict_types=1);

namespace App\Domains\CancelLayawaySale\Services;

use App\Domains\CancelLayawaySale\CancelLayawaySaleQueries;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\CreditNote\CreditNoteQueries;
use App\Domains\LoyaltyPoint\LoyaltyPointQueries;
use App\Domains\LoyaltyPoint\Services\LoyaltyPointService;
use App\Domains\LoyaltyPoint\Services\RevertLoyaltyPointService;
use App\Domains\LoyaltyPointUpdate\Enums\LoyaltyPointUpdateTypes;
use App\Domains\LoyaltyPointUpdate\LoyaltyPointUpdateQueries;
use App\Domains\PaymentType\Enums\StaticPaymentTypes;
use App\Domains\ReservedStock\Services\SaleReservedStockService;
use App\Domains\Sale\DataObjects\CancelLayawaySaleData;
use App\Domains\Sequence\Enums\SequenceTypes;
use App\Domains\Sequence\SequenceQueries;
use App\Domains\StoreManager\StoreManagerQueries;
use App\Domains\StoreManagerAuthorizationCodeUsage\Services\StoreManagerAuthorizationCodeUsageService;
use App\Domains\Voucher\VoucherQueries;
use App\Domains\VoucherTransaction\Enums\VoucherTransactionActionTypes;
use App\Domains\VoucherTransaction\VoucherTransactionQueries;
use App\Models\CancelLayawaySale;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\Location;
use App\Models\Member;
use App\Models\Sale;
use Illuminate\Support\Collection;

class CancelLayawaySaleService
{
    public function checkRequestDetails(
        CancelLayawaySaleData $cancelLayawaySaleData,
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

        $storeManager = $storeManagerQueries->getById($cancelLayawaySaleData->store_manager_id, $companyId);

        if ($cancelLayawaySaleData->passcode !== $storeManager->passcode) {
            abort(412, 'Wrong passcode.');
        }

        $storeManagerAuthorizationCodeUsageService = resolve(StoreManagerAuthorizationCodeUsageService::class);
        $storeManagerAuthorizationCodeUsageService->checkStoreManagerAuthorizationCode(
            $saleMismatches,
            $cancelLayawaySaleData->store_manager_id,
            $cancelLayawaySaleData->store_manager_authorization_code,
            $cancelLayawaySaleData->happened_at
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

        abort(412, 'Layaway sale cannot be canceled at a different location.');
    }

    public function saveDetails(
        CancelLayawaySaleData $cancelLayawaySaleData,
        Sale $sale,
        int $counterUpdateId,
        Location $location,
    ): void {
        $cancelLayawaySaleQueries = resolve(CancelLayawaySaleQueries::class);
        $cancelLayawaySale = $cancelLayawaySaleQueries->addNew($cancelLayawaySaleData, $sale->id);

        $this->loyaltyPointsRevert($sale, $cancelLayawaySale, $cancelLayawaySaleData);
        $this->vouchersRevert($sale->id, $location->id);

        $this->creditNoteCreateAndRefund(
            $sale,
            $location,
            $cancelLayawaySaleData,
            $counterUpdateId,
            $cancelLayawaySale->id,
            $location->credit_note_expiration_days
        );

        $this->revertReservedStock($sale);
        $this->revertUsedItemLoyaltyPoints($sale, $cancelLayawaySale, $cancelLayawaySaleData->happened_at);

        $this->revertUsedLoyaltyPoints(
            $sale->id,
            ModelMapping::SALE->name,
            $sale->member,
            $cancelLayawaySale,
            $cancelLayawaySaleData->happened_at,
        );
    }

    public function loyaltyPointsRevert(
        Sale $sale,
        CancelLayawaySale $cancelLayawaySale,
        CancelLayawaySaleData $cancelLayawaySaleData
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
            LoyaltyPointUpdateTypes::CANCEL_LAYAWAY_SALE->value,
            $cancelLayawaySale->id,
            ModelMapping::CANCEL_LAYAWAY_SALE->name,
            $cancelLayawaySaleData->happened_at
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

    public function revertReservedStock(Sale $sale): void
    {
        $saleReservedStockService = resolve(SaleReservedStockService::class);
        foreach ($sale->getSaleItems() as $saleItem) {
            $saleReservedStockService->revertReservedStock($saleItem);
        }
    }

    public function creditNoteCreateAndRefund(
        Sale $sale,
        Location $location,
        CancelLayawaySaleData $cancelLayawaySaleData,
        int $counterUpdateId,
        int $cancelLayawaySaleId,
        ?int $creditNoteExpirationDays,
    ): void {
        $totalAmountPaid = $sale->payments
            ->where('payment_type_id', '!=', StaticPaymentTypes::LOYALTY_POINT->value)
            ->sum('amount');

        $creditNoteQueries = resolve(CreditNoteQueries::class);

        $digitalInvoiceNumber = $this->getSequenceNumber($location, SequenceTypes::CN);

        $creditNoteQueries->addNewForCancelLayawaySale(
            $counterUpdateId,
            $cancelLayawaySaleId,
            $digitalInvoiceNumber,
            (float) $totalAmountPaid,
            $cancelLayawaySaleData->happened_at,
            $creditNoteExpirationDays,
            $sale->member_id,
        );
    }

    public function revertUsedItemLoyaltyPoints(
        Sale $sale,
        CancelLayawaySale $cancelLayawaySale,
        string $happenedAt,
    ): void {
        foreach ($sale->getSaleItems() as $saleItem) {
            $this->revertUsedLoyaltyPoints(
                $saleItem->id,
                ModelMapping::SALE_ITEM->name,
                $sale->member,
                $cancelLayawaySale,
                $happenedAt,
            );
        }
    }

    public function revertUsedLoyaltyPoints(
        int $affectedById,
        string $affectedByType,
        ?Member $member,
        CancelLayawaySale $cancelLayawaySale,
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
                $cancelLayawaySale,
                (int) abs($loyaltyPointUpdate->points),
                $happenedAt,
                $expiryDate
            );
        }
    }

    public function getSequenceNumber(Location $location, SequenceTypes $sequenceType): string
    {
        $sequenceQueries = resolve(SequenceQueries::class);
        $number = $sequenceQueries->addNew($location->id, $sequenceType->value)->number;

        return $location->code.'-'.$sequenceType->name.'-'.$number;
    }
}

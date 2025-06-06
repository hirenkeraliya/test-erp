<?php

declare(strict_types=1);

namespace App\Domains\HoldSale\Services;

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\HoldBookingPaymentItem\HoldBookingPaymentItemQueries;
use App\Domains\HoldSale\Enums\HoldSaleTypes;
use App\Domains\HoldSale\HoldSaleQueries;
use App\Domains\HoldSaleDetail\HoldSaleDetailQueries;
use App\Domains\HoldSaleItem\HoldSaleItemQueries;
use App\Domains\PosMismatch\PosMismatchQueries;
use App\Domains\StoreManagerAuthorizationCodeUsage\Enum\StoreManagerAuthorizationCodeUsageTypes;
use App\Domains\StoreManagerAuthorizationCodeUsage\Services\StoreManagerAuthorizationCodeUsageService;
use App\Models\Cashier;
use App\Models\HoldSale;

class SaveHoldSaleDetailsService
{
    public function saveDetails(
        Cashier $cashier,
        CheckHoldSaleDetailsService $checkHoldSaleDetailsService,
        array $extraDetails
    ): ?HoldSale {
        /** @var int $counterUpdateId */
        $counterUpdateId = $cashier->getCounterUpdateId();

        $holdSale = $this->getHoldSale($checkHoldSaleDetailsService, $counterUpdateId);

        $holdSaleDetailQueries = resolve(HoldSaleDetailQueries::class);
        $holdSaleDetail = $holdSaleDetailQueries->addNew(
            $holdSale->getKey(),
            $checkHoldSaleDetailsService->holdSaleData,
            $extraDetails,
            $checkHoldSaleDetailsService->member?->id,
        );

        if ($checkHoldSaleDetailsService->hasCartItems() && $checkHoldSaleDetailsService->holdSaleData->type_id === HoldSaleTypes::BOOKING_PAYMENT->value) {
            $holdBookingPaymentItemQueries = resolve(HoldBookingPaymentItemQueries::class);
            foreach ($checkHoldSaleDetailsService->cartItems as $cartItem) {
                $holdBookingPaymentItemQueries->addNew($holdSaleDetail->getKey(), $cartItem);
            }
        }

        if ($checkHoldSaleDetailsService->hasCartItems() && $checkHoldSaleDetailsService->holdSaleData->type_id !== HoldSaleTypes::BOOKING_PAYMENT->value) {
            $holdSaleItemQueries = resolve(HoldSaleItemQueries::class);
            foreach ($checkHoldSaleDetailsService->cartItems as $cartItem) {
                $holdSaleItemQueries->addNew($holdSaleDetail->getKey(), $cartItem);
            }
        }

        $saveHoldSaleReturnDetailsService = resolve(SaveHoldSaleReturnDetailsService::class);
        $saveHoldSaleReturnDetailsService->saveSaleReturnDetails(
            $holdSaleDetail->getKey(),
            $checkHoldSaleDetailsService
        );

        $this->saveSaleMismatches($holdSale, $checkHoldSaleDetailsService);

        $storeManagerAuthorizationCodeUsageService = resolve(StoreManagerAuthorizationCodeUsageService::class);
        $storeManagerAuthorizationCodeUsageService->addStoreManagerAuthorizationCodeUsage(
            StoreManagerAuthorizationCodeUsageTypes::HOLD_SALE_CANCEL->value,
            $holdSale->id,
            ModelMapping::HOLD_SALE->name,
            $checkHoldSaleDetailsService->holdSaleData->store_manager_authorization_code
        );

        $holdSaleQueries = resolve(HoldSaleQueries::class);

        return $holdSaleQueries->loadRelations($holdSale);
    }

    public function saveSaleMismatches(
        HoldSale $holdSale,
        CheckHoldSaleDetailsService $checkHoldSaleDetailsService
    ): void {
        $posMismatchQueries = resolve(PosMismatchQueries::class);

        foreach ($checkHoldSaleDetailsService->saleMismatches as $saleMismatch) {
            $posMismatchQueries->addNew($holdSale, $saleMismatch);
        }
    }

    public function getHoldSale(
        CheckHoldSaleDetailsService $checkHoldSaleDetailsService,
        int $counterUpdateId
    ): HoldSale {
        $holdSaleQueries = resolve(HoldSaleQueries::class);
        if ($checkHoldSaleDetailsService->holdSaleData->cancelled_at && $checkHoldSaleDetailsService->holdSale instanceof HoldSale) {
            $holdSaleQueries->markAsCancel(
                $checkHoldSaleDetailsService->holdSale,
                $checkHoldSaleDetailsService->holdSaleData->cancelled_at
            );

            return $checkHoldSaleDetailsService->holdSale;
        }

        if ($checkHoldSaleDetailsService->holdSaleData->complete_at && $checkHoldSaleDetailsService->holdSaleData->complete_offline_id && $checkHoldSaleDetailsService->holdSale instanceof HoldSale) {
            $holdSaleQueries->markAsComplete(
                $checkHoldSaleDetailsService->holdSale,
                $checkHoldSaleDetailsService->holdSaleData->complete_at,
                $checkHoldSaleDetailsService->holdSaleData->complete_offline_id,
                $checkHoldSaleDetailsService->sale?->id,
                $checkHoldSaleDetailsService->saleReturn?->id,
            );

            return $checkHoldSaleDetailsService->holdSale;
        }

        if ($checkHoldSaleDetailsService->holdSaleData->released_at && $checkHoldSaleDetailsService->holdSale instanceof HoldSale) {
            return $checkHoldSaleDetailsService->holdSale;
        }

        return $holdSaleQueries->addNew(
            $checkHoldSaleDetailsService->holdSaleData->offline_id,
            $counterUpdateId,
            $checkHoldSaleDetailsService->holdSaleData->type_id
        );
    }
}

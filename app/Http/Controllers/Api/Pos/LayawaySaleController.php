<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Pos;

use App\CommonFunctions;
use App\Domains\BookingPayment\BookingPaymentQueries;
use App\Domains\BookingPayment\Enums\BookingPaymentStatuses;
use App\Domains\BookingPaymentUse\BookingPaymentUseQueries;
use App\Domains\CancelLayawaySale\Resources\PosCancelLayawaySalesResource;
use App\Domains\CancelLayawaySale\Services\CancelLayawaySaleService;
use App\Domains\Cashier\CashierQueries;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Company\CompanyQueries;
use App\Domains\Company\Services\CheckCompanySettingService;
use App\Domains\CounterUpdate\CounterUpdateQueries;
use App\Domains\CreditNote\CreditNoteQueries;
use App\Domains\CreditNote\Enums\CreditNoteStatuses;
use App\Domains\CreditNoteUse\CreditNoteUseQueries;
use App\Domains\Currency\CurrencyQueries;
use App\Domains\GiftCard\Enums\GiftCardStatuses;
use App\Domains\GiftCard\Enums\GiftCardTypes;
use App\Domains\GiftCard\GiftCardQueries;
use App\Domains\GiftCardTransaction\Enums\GiftCardTransactionTypes;
use App\Domains\GiftCardTransaction\GiftCardTransactionQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\LoyaltyPoint\Services\LoyaltyPointService;
use App\Domains\LoyaltyPointUpdate\Enums\LoyaltyPointUpdateTypes;
use App\Domains\Member\Jobs\MemberUpdatePointsAndTotalSalesJob;
use App\Domains\PaymentType\Enums\StaticPaymentTypes;
use App\Domains\PosMismatch\PosMismatchQueries;
use App\Domains\ReservedStock\Services\SaleReservedStockService;
use App\Domains\Sale\DataObjects\CancelLayawaySaleData;
use App\Domains\Sale\DataObjects\CompleteLayawaySaleData;
use App\Domains\Sale\DataObjects\PendingLayawaySalesDataForPos;
use App\Domains\Sale\Enums\SaleStatus;
use App\Domains\Sale\Resources\PosLayawaySaleListResource;
use App\Domains\Sale\Resources\PosPendingLayawaySaleListResource;
use App\Domains\Sale\SaleQueries;
use App\Domains\Sale\Services\GenerateLoyaltyPointsService;
use App\Domains\Sale\Services\LayawayAndCreditSaleCashbackService;
use App\Domains\SaleItem\SaleItemQueries;
use App\Domains\SalePayment\SalePaymentQueries;
use App\Domains\StoreManagerAuthorizationCodeUsage\Enum\StoreManagerAuthorizationCodeUsageTypes;
use App\Domains\StoreManagerAuthorizationCodeUsage\Services\StoreManagerAuthorizationCodeUsageService;
use App\Domains\Voucher\Services\LayawayAndCreditSaleGenerateVoucherService;
use App\Http\Controllers\Controller;
use App\Models\Cashier;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\Location;
use App\Models\Member;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\LaravelData\DataCollection;
use Throwable;

class LayawaySaleController extends Controller
{
    /**
     * @return array<string, AnonymousResourceCollection>
     */
    public function getPendingLayawaySales(
        Request $request,
        PendingLayawaySalesDataForPos $pendingLayawaySalesDataForPos
    ): array {
        /** @var Cashier $cashier */
        $cashier = $request->user();

        if (! $cashier->getCounterUpdateId()) {
            abort(412, 'The counter has not been opened yet.');
        }

        $filterData = [
            'member_id' => $pendingLayawaySalesDataForPos->member_id,
            'employee_id' => $pendingLayawaySalesDataForPos->employee_id,
            'from_date' => $pendingLayawaySalesDataForPos->from_date,
            'to_date' => $pendingLayawaySalesDataForPos->to_date,
            'search_text' => $pendingLayawaySalesDataForPos->search_text,
            'after_updated_at' => $pendingLayawaySalesDataForPos->after_updated_at,
        ];

        $locationQueries = resolve(LocationQueries::class);
        $location = $locationQueries->getLocationByCountersCounterUpdateId($cashier->getCounterUpdateId());

        $saleQueries = resolve(SaleQueries::class);
        $pendingLayawaySales = $saleQueries->getPendingLayawaySalesWithItemsPaymentsAndMismatches(
            $filterData,
            $location->id
        );

        return [
            'pending_layaway_sales' => PosPendingLayawaySaleListResource::collection($pendingLayawaySales),
        ];
    }

    /**
     * @return array<string, PosLayawaySaleListResource>
     */
    public function getPendingLayawaySale(Request $request, int $saleId): array
    {
        /** @var Cashier $cashier */
        $cashier = $request->user();

        if (! $cashier->getCounterUpdateId()) {
            abort(412, 'The counter has not been opened yet.');
        }

        $locationQueries = resolve(LocationQueries::class);
        $location = $locationQueries->getLocationByCountersCounterUpdateId($cashier->getCounterUpdateId());

        $saleQueries = resolve(SaleQueries::class);
        $sale = $saleQueries->getPendingLayawaySaleByIdWithItemsPaymentsAndMismatches($saleId, $location->id);

        $this->checkSaleStatus($sale);

        return [
            'sale' => new PosLayawaySaleListResource($sale),
        ];
    }

    public function completeLayawaySale(
        CompleteLayawaySaleData $completeLayawaySaleDataRequest,
        Request $request,
        int $saleId
    ): array {
        $saleMismatches = collect([]);

        /** @var Cashier $cashier */
        $cashier = $request->user();

        if (! $cashier->getCounterUpdateId()) {
            abort(412, 'The counter has not been opened yet.');
        }

        $cashierQueries = resolve(CashierQueries::class);
        $cashier = $cashierQueries->loadDetailsForCounterCloseApi($cashier);

        $saleQueries = resolve(SaleQueries::class);
        $sale = $saleQueries->getSaleByIdWithSaleItems($saleId);

        $payments = collect($completeLayawaySaleDataRequest->payments);
        $loyaltyPointPayments = $payments->where('type_id', StaticPaymentTypes::LOYALTY_POINT->value);

        if ($loyaltyPointPayments->count() > 0) {
            $this->checkLoyaltyPoint($sale, $loyaltyPointPayments, $saleMismatches);
        }

        /** @var CounterUpdate $counterUpdate */
        $counterUpdate = $sale->counterUpdate;

        /** @var Counter $counter */
        $counter = $counterUpdate->counter;

        /** @var Location $location */
        $location = $counter->location;

        /** @var Company $company */
        $company = $location->company;

        /** @var CompanySetting $companySetting */
        $companySetting = $company->companySetting;

        $companyId = $location->company_id;

        $this->checkRequestDetails($sale, $payments, $location, $saleMismatches, $companyId);

        $checkCompanySettingService = resolve(CheckCompanySettingService::class);
        $checkCompanySettingService->setDetails($companySetting);
        $checkCompanySettingService->checkCompleteLayawaySaleSettings($completeLayawaySaleDataRequest, $saleMismatches);

        $saleFinalAmount = $payments->sum('amount');

        $currencyQueries = resolve(CurrencyQueries::class);
        $currency = $currencyQueries->getByCompanyId($companyId);

        if ($payments->where('type_id', StaticPaymentTypes::CREDIT_NOTE->value)->count() > 0) {
            $this->checkCreditNoteDetails($sale, $payments, $saleMismatches, $companyId, $currency->getSymbol());
        }

        if ($payments->where('type_id', StaticPaymentTypes::BOOKING_PAYMENT->value)->count() > 0) {
            $this->checkBookingPayment($sale, $payments, $saleMismatches, $companyId, $location->id);
        }

        if ($payments->where('type_id', StaticPaymentTypes::GIFT_CARD->value)->count() > 0) {
            $this->checkGiftCard($payments, $saleMismatches, $companyId);
        }

        $happenedAt = $completeLayawaySaleDataRequest->happened_at ?? now()->format('Y-m-d H:i:s');
        $generateLoyaltyPointService = resolve(GenerateLoyaltyPointsService::class);
        if ($generateLoyaltyPointService->hasGenerateLoyaltyPointsForLayawaySale($completeLayawaySaleDataRequest)) {
            $generateLoyaltyPointService->setDetails($completeLayawaySaleDataRequest->loyalty_points, $companyId);

            $loyaltyPointsMismatches = $generateLoyaltyPointService->checkLayawaySaleLoyaltyPoints(
                $saleFinalAmount,
                $sale->member_id,
                $sale,
                $happenedAt
            );

            $saleMismatches = $saleMismatches->merge($loyaltyPointsMismatches);
        }

        $layawayAndCreditSaleGenerateVoucherService = resolve(LayawayAndCreditSaleGenerateVoucherService::class);
        if ($completeLayawaySaleDataRequest->vouchers instanceof DataCollection) {
            $layawayAndCreditSaleGenerateVoucherService->setDetails($completeLayawaySaleDataRequest, $sale, $companyId);

            $subtotal = $saleFinalAmount + $sale->total_amount_paid;
            $layawayAndCreditSaleGenerateVoucherService->checkVouchers($subtotal, $saleMismatches);
        }

        $layawayAndCreditSaleCashbackService = resolve(LayawayAndCreditSaleCashbackService::class);
        if ($layawayAndCreditSaleCashbackService->hasCashback($completeLayawaySaleDataRequest)) {
            /** @var int $cashbackId */
            $cashbackId = $completeLayawaySaleDataRequest->cashback_id;
            $layawayAndCreditSaleCashbackService->setDetails($cashbackId, $companyId);

            $subtotal = $saleFinalAmount + $sale->total_amount_paid;
            $layawayAndCreditSaleCashbackService->checkForApplicability(
                $subtotal,
                $completeLayawaySaleDataRequest,
                $saleMismatches,
                $location,
                $sale
            );
        }

        DB::beginTransaction();

        try {
            $salePaymentQueries = resolve(SalePaymentQueries::class);
            $saleItemQueries = resolve(SaleItemQueries::class);
            $creditNoteQueries = resolve(CreditNoteQueries::class);
            $creditNoteUseQueries = resolve(CreditNoteUseQueries::class);
            $loyaltyPointService = resolve(LoyaltyPointService::class);

            /** @var Member $member */
            $member = $sale->member;

            $generateLoyaltyPointService->generateLoyaltyPointsForLayawaySale(
                $completeLayawaySaleDataRequest,
                $sale,
                $companySetting,
                $companyId,
                $saleFinalAmount,
                $member->id,
            );

            if ($completeLayawaySaleDataRequest->vouchers instanceof DataCollection) {
                $layawayAndCreditSaleGenerateVoucherService->saveVouchers($sale, $cashier);
            }

            /** @var int $counterUpdateId */
            $counterUpdateId = $cashier->getCounterUpdateId();

            if ($layawayAndCreditSaleCashbackService->hasCashback($completeLayawaySaleDataRequest)) {
                $layawayAndCreditSaleCashbackService->saveCashback(
                    $sale,
                    $completeLayawaySaleDataRequest,
                    $counterUpdateId
                );
            }

            $bookingPaymentQueries = resolve(BookingPaymentQueries::class);
            $bookingPaymentUseQueries = resolve(BookingPaymentUseQueries::class);
            $giftCardQueries = resolve(GiftCardQueries::class);
            $giftCardTransactionQueries = resolve(GiftCardTransactionQueries::class);

            foreach ($payments as $payment) {
                $salePaymentId = $salePaymentQueries->addNew($sale, $happenedAt, $payment, $counterUpdateId);

                if ((int) $payment['type_id'] === StaticPaymentTypes::CREDIT_NOTE->value) {
                    $creditNote = $creditNoteQueries->getById((int) $payment['credit_note_id']);
                    $paymentAmount = (float) $payment['amount'];

                    $creditNoteQueries->decreaseAvailableAmountAndMarkAsUsed($creditNote, $paymentAmount);

                    $creditNoteUseQueries->addNew($creditNote, $salePaymentId, $counterUpdateId, $paymentAmount);
                }

                if ((int) $payment['type_id'] === StaticPaymentTypes::BOOKING_PAYMENT->value) {
                    $paymentAmount = (float) $payment['amount'];
                    $bookingPayment = $bookingPaymentQueries->getById(
                        (int) $payment['booking_payment_id'],
                        $companyId,
                        $location->id
                    );

                    $bookingPaymentQueries->markAsUsed($bookingPayment, $paymentAmount);
                    $bookingPaymentUseQueries->addNew(
                        $bookingPayment,
                        $salePaymentId,
                        $counterUpdateId,
                        $paymentAmount
                    );
                }

                if ((int) $payment['type_id'] === StaticPaymentTypes::GIFT_CARD->value) {
                    $paymentAmount = (float) $payment['amount'];
                    $giftCard = $giftCardQueries->getById((int) $payment['gift_card_id'], $companyId);

                    if (! $giftCard) {
                        continue;
                    }

                    $giftCardQueries->decreaseAvailableAmountAndMarkAsUsed($giftCard, $paymentAmount);

                    $giftCardTransactionQueries->addNew(
                        $giftCard,
                        $salePaymentId,
                        ModelMapping::SALE_PAYMENT->name,
                        $paymentAmount,
                        GiftCardTransactionTypes::USED->value
                    );
                }

                if ((int) $payment['type_id'] === StaticPaymentTypes::LOYALTY_POINT->value) {
                    $loyaltyPointService->decreaseLoyaltyPoints(
                        $member,
                        (int) $payment['loyalty_points'],
                        LoyaltyPointUpdateTypes::USED->value,
                        $sale->getKey(),
                        ModelMapping::SALE->name,
                        $happenedAt
                    );
                }
            }

            $isCompletedLayawaySale = false;
            if (($sale->layaway_pending_amount - $payments->sum('amount')) <= 0) {
                $isCompletedLayawaySale = true;
            }

            $saleItemQueries->updateLayawayAmountOf($sale, $payments->sum('amount'), $isCompletedLayawaySale);
            $sale = $saleQueries->updateLayawayAmountOf($sale, $payments, $happenedAt);

            if ($sale->status === SaleStatus::COMPLETE_LAYAWAY_SALE->value) {
                $saleReservedStockService = resolve(SaleReservedStockService::class);
                foreach ($sale->saleItems as $saleItem) {
                    $saleReservedStockService->removeReservationStock($saleItem, $cashier, $happenedAt);
                }
            }

            $this->saveSaleMismatches($sale, $saleMismatches);

            DB::commit();

            $sale = $saleQueries->loadRelations($sale);

            if ($sale->member_id) {
                MemberUpdatePointsAndTotalSalesJob::dispatch($sale->member_id)->onQueue('medium');
            }

            return [
                'sale' => new PosLayawaySaleListResource($sale),
            ];
        } catch (Throwable $throwable) {
            Log::error('Complete Layaway Sale', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);

            DB::rollBack();

            abort(412, 'An error occurred. Please try again.');
        }
    }

    public function saveSaleMismatches(Sale $sale, Collection $saleMismatches): void
    {
        $posMismatchQueries = resolve(PosMismatchQueries::class);

        foreach ($saleMismatches as $saleMismatch) {
            $posMismatchQueries->addNew($sale, $saleMismatch);
        }
    }

    public function cancelLayawaySale(
        CancelLayawaySaleData $cancelLayawaySaleData,
        Request $request,
        int $saleId
    ): array {
        /** @var Cashier $cashier */
        $cashier = $request->user();

        if (! $cashier->getCounterUpdateId()) {
            abort(412, 'The counter has not been opened yet.');
        }

        /** @var int $counterUpdateId */
        $counterUpdateId = $cashier->getCounterUpdateId();

        $locationQueries = resolve(LocationQueries::class);
        $location = $locationQueries->getLocationByCountersCounterUpdateId($counterUpdateId);

        $companyId = CommonFunctions::getCashierCompanyId($cashier);

        $saleQueries = resolve(SaleQueries::class);
        $sale = $saleQueries->getPendingLayawaySaleByIdWithRelations($saleId);

        $saleMismatches = collect([]);

        $cancelLayawaySaleService = resolve(CancelLayawaySaleService::class);
        $cancelLayawaySaleService->checkRequestDetails(
            $cancelLayawaySaleData,
            $sale,
            $location,
            $companyId,
            $saleMismatches
        );

        DB::beginTransaction();

        try {
            $cancelLayawaySaleService->saveDetails($cancelLayawaySaleData, $sale, $counterUpdateId, $location);

            $saleQueries->markAsCancelLayaway($sale);

            $storeManagerAuthorizationCodeUsageService = resolve(StoreManagerAuthorizationCodeUsageService::class);
            $storeManagerAuthorizationCodeUsageService->addStoreManagerAuthorizationCodeUsage(
                StoreManagerAuthorizationCodeUsageTypes::CANCEL_LAYAWAY_SALE->value,
                $sale->id,
                ModelMapping::SALE->name,
                $cancelLayawaySaleData->store_manager_authorization_code
            );

            $this->saveSaleMismatches($sale, $saleMismatches);

            DB::commit();

            $sale = $saleQueries->loadCancelLayawaySaleRelations($sale);

            if ($sale->member_id) {
                MemberUpdatePointsAndTotalSalesJob::dispatch($sale->member_id)->onQueue('medium');
            }

            return [
                'sale' => new PosCancelLayawaySalesResource($sale),
            ];
        } catch (Throwable $throwable) {
            Log::error('Cancel Layaway Sale', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
            DB::rollBack();

            abort(412, 'An error occurred. Please try again.');
        }
    }

    private function checkRequestDetails(
        Sale $sale,
        Collection $payments,
        Location $location,
        Collection $saleMismatches,
        int $companyId
    ): void {
        $this->checkSaleStatus($sale);
        $this->checkDeferentStore($location, $sale, $saleMismatches);
        $this->checkPaymentCurrency($payments, $saleMismatches, $companyId);

        if ((float) $payments->sum('amount') > $sale->getLayWayPendingAmount()) {
            abort(412, 'Payments exceeding the pending layaway amount are not permitted.');
        }
    }

    public function checkPaymentCurrency(Collection $payments, Collection $saleMismatches, int $companyId): void
    {
        $currencyIds = [];
        $currencyRates = [];

        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getConfigurationColumnsById($companyId);

        foreach ($company->countries as $country) {
            $currencyIds[] = $country->currency?->id;
            $currencyRates[] = CommonFunctions::numberFormat((float) $country->currency?->currencyRate?->rate);
        }

        foreach ($payments as $payment) {
            if (! array_key_exists('currency_id', $payment)) {
                continue;
            }

            if (! in_array($payment['currency_id'], $currencyIds)) {
                $saleMismatchMessage = 'Payment currency id ' . $payment['currency_id'] . ' is not available in this company.';
                CommonFunctions::addMismatchOrAbort($saleMismatches, $saleMismatchMessage);
            }

            if (! array_key_exists('current_currency_rate', $payment)) {
                continue;
            }

            if (! in_array($payment['current_currency_rate'], $currencyRates)) {
                $saleMismatchMessage = 'Payment currency rate ' . $payment['current_currency_rate'] . ' does not match with the actual currency rate of ' . implode(
                    ', ',
                    $currencyRates
                ) . ' for the currency id ' . $payment['currency_id'];
                CommonFunctions::addMismatchOrAbort($saleMismatches, $saleMismatchMessage);
            }

            if (! array_key_exists('currency_amount', $payment)) {
                continue;
            }

            $currencyAmount = CommonFunctions::numberFormat(
                CommonFunctions::numberFormat((float) $payment['currency_amount']) / CommonFunctions::numberFormat(
                    (float) $payment['current_currency_rate']
                )
            );

            if (! CommonFunctions::compareFloatNumbers($currencyAmount, (float) $payment['amount'])) {
                $saleMismatchMessage = 'Payment amount ' . $payment['amount'] . ' does not match with the actual currency amount of ' . $currencyAmount . '.';
                CommonFunctions::addMismatchOrAbort($saleMismatches, $saleMismatchMessage);
            }
        }
    }

    private function checkDeferentStore(Location $location, Sale $sale, Collection $saleMismatches): void
    {
        /** @var CounterUpdate $counterUpdate */
        $counterUpdate = $sale->counterUpdate;

        /** @var Counter $counter */
        $counter = $counterUpdate->counter;

        if ((int) $location->id === (int) $counter->location_id) {
            return;
        }

        $saleMismatchMessage = 'Layaway sale cannot be completed at a different location.';
        CommonFunctions::addMismatchOrAbort($saleMismatches, $saleMismatchMessage);
    }

    private function checkSaleStatus(Sale $sale): void
    {
        if ($sale->getStatus() !== SaleStatus::PENDING_LAYAWAY_SALE->value) {
            abort(412, 'The specified sale is not a layaway sale.');
        }
    }

    private function checkCreditNoteDetails(
        Sale $sale,
        Collection $payments,
        Collection $saleMismatches,
        int $companyId,
        string $currencySymbol
    ): void {
        /** @var Member $member */
        $member = $sale->member;

        $creditNoteQueries = resolve(CreditNoteQueries::class);
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);

        foreach ($payments as $payment) {
            if (
                (int) $payment['type_id'] === StaticPaymentTypes::CREDIT_NOTE->value
                && ! array_key_exists('credit_note_id', $payment)
            ) {
                abort(
                    412,
                    'When using credit notes as a payment method, providing a valid credit note ID is mandatory. Without this information, the process cannot be completed as it serves as a crucial element in processing a credit note-based payment.'
                );
            }

            if (! array_key_exists('credit_note_id', $payment)) {
                continue;
            }

            if (! $payment['credit_note_id']) {
                continue;
            }

            $creditNote = $creditNoteQueries->getById((int) $payment['credit_note_id']);

            if ($creditNote->expiry_date && $creditNote->expiry_date < now()->format('Y-m-d')) {
                $saleMismatchMessage = 'We apologize, but the credit note you are attempting to use has expired and is no longer valid. Please contact customer support for further assistance.';
                CommonFunctions::addMismatchOrAbort($saleMismatches, $saleMismatchMessage);
            }

            if ($creditNote->status !== CreditNoteStatuses::ACTIVE->value) {
                $saleMismatchMessage = 'This credit note is currently inactive and cannot be used for transactions.';
                CommonFunctions::addMismatchOrAbort($saleMismatches, $saleMismatchMessage);
            }

            if ($creditNote->member_id !== $member->id) {
                abort(412, 'The designated user is currently unable to utilize the provided credit note.');
            }

            if ($creditNote->available_amount < $payment['amount']) {
                $saleMismatchMessage = 'The requested payment amount of ' . $currencySymbol . '.' . $payment['amount'] . ' exceeds the available credit note amount of ' . $currencySymbol . '' . $creditNote->available_amount . '. Please adjust your payment amount accordingly.';
                CommonFunctions::addMismatchOrAbort($saleMismatches, $saleMismatchMessage);
            }

            $creditNoteCompanyId = $counterUpdateQueries->getCompanyIdByCounterUpdateId(
                $creditNote->counter_update_id
            );

            if ($companyId !== $creditNoteCompanyId) {
                abort(412, 'It is not permitted to use credit notes from multiple companies.');
            }
        }
    }

    private function checkGiftCard(Collection $payments, Collection $saleMismatches, int $companyId): void
    {
        $giftCardQueries = resolve(GiftCardQueries::class);

        foreach ($payments as $payment) {
            $payment = (array) $payment;

            if (
                (int) $payment['type_id'] === StaticPaymentTypes::GIFT_CARD->value &&
                ! array_key_exists('gift_card_id', $payment)
            ) {
                abort(
                    412,
                    'Please ensure you enter a valid Gift Card ID when choosing Gift Card as the payment method.'
                );
            }

            if (! array_key_exists('gift_card_id', $payment)) {
                continue;
            }

            if (! $payment['gift_card_id']) {
                continue;
            }

            $giftCard = $giftCardQueries->getById((int) $payment['gift_card_id'], $companyId);

            if (! $giftCard) {
                abort(412, 'Unfortunately, we couldn`t find records of some of the gift cards you requested.');
            }

            if ($giftCard->expiry_date && $giftCard->expiry_date < now()->format('Y-m-d')) {
                $saleMismatchMessage = 'The payment was made using an expired gift card (Number: [' . $giftCard->number . ']). Please use a valid gift card to complete your transaction.';
                CommonFunctions::addMismatchOrAbort($saleMismatches, $saleMismatchMessage);
            }

            if ($giftCard->type_id === GiftCardTypes::SINGLE_USE_ONLY->value && $giftCard->status === GiftCardStatuses::USED->value) {
                $saleMismatchMessage = 'The gift card with number ' . $giftCard->number . ' can only be used once.';
                CommonFunctions::addMismatchOrAbort($saleMismatches, $saleMismatchMessage);
            }

            if ($giftCard->status !== GiftCardStatuses::ACTIVE->value) {
                $saleMismatchMessage = 'The gift card with (number - [' . $giftCard->number . ']) is not active.';
                CommonFunctions::addMismatchOrAbort($saleMismatches, $saleMismatchMessage);
            }

            if ($giftCard->available_amount < $payment['amount']) {
                $saleMismatchMessage = 'The requested payment amount of ' . $payment['amount'] . 'exceeds the available amount of the gift card (number - [' . $giftCard->number . ']) , which is ' . $giftCard->available_amount . '.';
                CommonFunctions::addMismatchOrAbort($saleMismatches, $saleMismatchMessage);
            }

            if ($companyId !== $giftCard->company_id) {
                abort(412, 'You cannot use a gift card from a different company.');
            }
        }
    }

    private function checkBookingPayment(
        Sale $sale,
        Collection $payments,
        Collection $saleMismatches,
        int $companyId,
        int $locationId
    ): void {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $bookingPaymentQueries = resolve(BookingPaymentQueries::class);

        $member = $sale->member;

        foreach ($payments as $payment) {
            $payment = (array) $payment;

            if (
                (int) $payment['type_id'] === StaticPaymentTypes::BOOKING_PAYMENT->value &&
                ! array_key_exists('booking_payment_id', $payment)
            ) {
                abort(412, 'Please provide the Booking Payment ID when selecting the Booking Payment option.');
            }

            if (array_key_exists('booking_payment_id', $payment) && $payment['booking_payment_id']) {
                $bookingPayment = $bookingPaymentQueries->getById(
                    (int) $payment['booking_payment_id'],
                    $companyId,
                    $locationId
                );

                if ($bookingPayment->status !== BookingPaymentStatuses::ACTIVE->value) {
                    $saleMismatchMessage = 'Sorry, booking payment is currently inactive.';
                    CommonFunctions::addMismatchOrAbort($saleMismatches, $saleMismatchMessage);
                }

                if ($member instanceof Member && ($bookingPayment->member_id && (int) $member->id !== $bookingPayment->member_id)) {
                    abort(
                        412,
                        'The selected member does not match the member associated with the payment for the booking.'
                    );
                }

                if ((int) $payment['type_id'] !== StaticPaymentTypes::BOOKING_PAYMENT->value) {
                    abort(412, 'Please provide the Booking Payment type along with the Booking Payment ID.');
                }

                $bookingPaymentCompanyId = $counterUpdateQueries->getCompanyIdByCounterUpdateId(
                    $bookingPayment->counter_update_id
                );

                if ($companyId !== $bookingPaymentCompanyId) {
                    abort(412, 'Sorry, you can`t mix bookings from different companies.');
                }

                if ($bookingPayment->available_amount < $payment['amount']) {
                    $saleMismatchMessage = 'The requested payment amount of ' . $payment['amount'] . ' exceeds the available booking payment balance of' . $bookingPayment->available_amount;
                    CommonFunctions::addMismatchOrAbort($saleMismatches, $saleMismatchMessage);
                }
            }
        }
    }

    private function checkLoyaltyPoint(Sale $sale, Collection $payments, Collection $saleMismatches): void
    {
        /** @var Member|null $member */
        $member = $sale->member;

        if (null === $member) {
            abort(412, 'To pay with loyalty points, a user account is required.');
        }

        if (! $member->membership_id) {
            abort(412, 'To redeem loyalty points, a membership must be associated with your user account.');
        }

        foreach ($payments as $payment) {
            if (! array_key_exists('loyalty_points', $payment) || ! $payment['loyalty_points']) {
                abort(
                    412,
                    'To ensure successful processing of the payment, it is necessary to provide a valid loyalty point value since loyalty points are the selected payment method.'
                );
            }

            $amountFromLoyaltyPoints = 0;
            if ($member->membership) {
                $amountFromLoyaltyPoints = CommonFunctions::numberFormat(
                    $payment['loyalty_points'] / $member->membership->loyalty_points_per_currency_unit
                );

                $minPoints = $member->membership->min_loyalty_points_for_redemption;
                $maxPoints = $member->membership->max_loyalty_points_for_redemption;

                if (! ($payment['loyalty_points'] >= $minPoints && $payment['loyalty_points'] <= $maxPoints)) {
                    $saleMismatchMessage = 'The specified loyalty points (' . $payment['loyalty_points'] . ') are not valid. Loyalty points must be between ' . $minPoints . ' and ' . $maxPoints . '.';
                    CommonFunctions::addMismatchOrAbort($saleMismatches, $saleMismatchMessage);
                }
            }

            if ($member->loyalty_points < $payment['loyalty_points']) {
                $saleMismatchMessage = 'The loyalty points you are trying to use exceed the balance available in your account.';
                CommonFunctions::addMismatchOrAbort($saleMismatches, $saleMismatchMessage);
            }

            if (! CommonFunctions::compareFloatNumbers($amountFromLoyaltyPoints, (float) $payment['amount'])) {
                $saleMismatchMessage = 'The amount you are trying to use, ' . $payment['amount'] . ', exceeds the maximum amount that can be redeemed from your loyalty points, ' . $amountFromLoyaltyPoints . ' according to your membership.';
                CommonFunctions::addMismatchOrAbort($saleMismatches, $saleMismatchMessage);
            }
        }
    }
}

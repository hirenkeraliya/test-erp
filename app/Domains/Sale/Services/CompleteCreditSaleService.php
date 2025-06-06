<?php

declare(strict_types=1);

namespace App\Domains\Sale\Services;

use App\CommonFunctions;
use App\Domains\BookingPayment\BookingPaymentQueries;
use App\Domains\BookingPayment\Enums\BookingPaymentStatuses;
use App\Domains\BookingPaymentUse\BookingPaymentUseQueries;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Company\CompanyQueries;
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
use App\Domains\LoyaltyPoint\Services\LoyaltyPointService;
use App\Domains\LoyaltyPointUpdate\Enums\LoyaltyPointUpdateTypes;
use App\Domains\PaymentType\Enums\StaticPaymentTypes;
use App\Domains\PosMismatch\PosMismatchQueries;
use App\Domains\Sale\DataObjects\CompleteCreditSaleData;
use App\Domains\Sale\Enums\SaleStatus;
use App\Domains\SalePayment\SalePaymentQueries;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\Member;
use App\Models\Sale;
use Illuminate\Support\Collection;

class CompleteCreditSaleService
{
    public function checkRequestDetails(
        CompleteCreditSaleData $completeCreditSaleData,
        Sale $sale,
        Collection $saleMismatches,
        int $companyId,
        int $locationId,
    ): void {
        $this->checkDeferentStore($locationId, $sale, $saleMismatches);

        $payments = collect($completeCreditSaleData->payments);

        $this->checkPaymentCurrency($payments, $saleMismatches, $companyId);

        if (! $sale->getCreditPendingAmount() || $sale->getStatus() !== SaleStatus::PENDING_CREDIT_SALE->value) {
            abort(412, 'The specified sale is not a credit sale.');
        }

        if ((float) $payments->sum('amount') > $sale->getCreditPendingAmount()) {
            abort(412, 'Payments exceeding the pending credit amount are not permitted.');
        }

        $loyaltyPointPayments = $payments->where('type_id', StaticPaymentTypes::LOYALTY_POINT->value);

        if ($loyaltyPointPayments->count() > 0) {
            $this->checkLoyaltyPoint($sale, $loyaltyPointPayments, $saleMismatches);
        }

        if ($payments->where('type_id', StaticPaymentTypes::CREDIT_NOTE->value)->count() > 0) {
            $this->checkCreditNoteDetails($sale, $payments, $saleMismatches, $companyId);
        }

        if ($payments->where('type_id', StaticPaymentTypes::BOOKING_PAYMENT->value)->count() > 0) {
            $this->checkBookingPayment($sale, $payments, $saleMismatches, $companyId, $locationId);
        }

        if ($payments->where('type_id', StaticPaymentTypes::GIFT_CARD->value)->count() > 0) {
            $this->checkGiftCard($payments, $saleMismatches, $companyId);
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

    public function checkDeferentStore(int $locationId, Sale $sale, Collection $saleMismatches): void
    {
        /** @var CounterUpdate $counterUpdate */
        $counterUpdate = $sale->counterUpdate;

        /** @var Counter $counter */
        $counter = $counterUpdate->counter;

        if ((int) $counter->location_id === $locationId) {
            return;
        }

        $saleMismatchMessage = 'Credit sale cannot be completed at a different location.';
        CommonFunctions::addMismatchOrAbort($saleMismatches, $saleMismatchMessage);
    }

    public function checkLoyaltyPoint(Sale $sale, Collection $payments, Collection $saleMismatches): void
    {
        if (! $sale->member) {
            abort(412, 'To pay with loyalty points, a user account is required.');
        }

        /** @var Member $member */
        $member = $sale->member;

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

            if ($member->loyalty_points < $payment['loyalty_points']) {
                $saleMismatchMessage = 'The loyalty points you are trying to use exceed the balance available in your account.';
                CommonFunctions::addMismatchOrAbort($saleMismatches, $saleMismatchMessage);
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

            if (! CommonFunctions::compareFloatNumbers($amountFromLoyaltyPoints, (float) $payment['amount'])) {
                $saleMismatchMessage = 'The amount you are trying to use, ' . $payment['amount'] . ', exceeds the maximum amount that can be redeemed from your loyalty points, ' . $amountFromLoyaltyPoints . ' according to your membership.';
                CommonFunctions::addMismatchOrAbort($saleMismatches, $saleMismatchMessage);
            }
        }
    }

    public function checkCreditNoteDetails(
        Sale $sale,
        Collection $payments,
        Collection $saleMismatches,
        int $companyId
    ): void {
        $currencyQueries = resolve(CurrencyQueries::class);
        $currency = $currencyQueries->getByCompanyId($companyId);

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
                $saleMismatchMessage = 'The requested payment amount of ' . $currency->getSymbol() . $payment['amount'] . ' exceeds the available credit note amount of ' . $currency->getSymbol() . $creditNote->available_amount . '. Please adjust your payment amount accordingly.';
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

    public function checkGiftCard(Collection $payments, Collection $saleMismatches, int $companyId): void
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
                $saleMismatchMessage = 'The requested payment amount of ' . $payment['amount'] . ' exceeds the available amount of the gift card (number - [' . $giftCard->number . ']) , which is ' . $giftCard->available_amount . '.';
                CommonFunctions::addMismatchOrAbort($saleMismatches, $saleMismatchMessage);
            }

            if ($companyId !== $giftCard->company_id) {
                abort(412, 'You cannot use a gift card from a different company.');
            }
        }
    }

    public function checkBookingPayment(
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

            if (! array_key_exists('booking_payment_id', $payment)) {
                continue;
            }

            if (! $payment['booking_payment_id']) {
                continue;
            }

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
                $saleMismatchMessage = 'The requested payment amount of ' . $payment['amount'] . ' exceeds the available booking payment balance of ' . $bookingPayment->available_amount;
                CommonFunctions::addMismatchOrAbort($saleMismatches, $saleMismatchMessage);
            }
        }
    }

    public function saveDetails(
        CompleteCreditSaleData $completeCreditSaleData,
        Sale $sale,
        Collection $payments,
        Collection $saleMismatches,
        int $counterUpdateId,
        int $companyId,
        int $locationId,
    ): void {
        /** @var Member $member */
        $member = $sale->member;

        $happenedAt = $completeCreditSaleData->happened_at ?? now()->format('Y-m-d H:i:s');

        $bookingPaymentQueries = resolve(BookingPaymentQueries::class);
        $bookingPaymentUseQueries = resolve(BookingPaymentUseQueries::class);
        $giftCardQueries = resolve(GiftCardQueries::class);
        $giftCardTransactionQueries = resolve(GiftCardTransactionQueries::class);
        $salePaymentQueries = resolve(SalePaymentQueries::class);
        $creditNoteQueries = resolve(CreditNoteQueries::class);
        $creditNoteUseQueries = resolve(CreditNoteUseQueries::class);
        $loyaltyPointService = resolve(LoyaltyPointService::class);

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
                    $locationId
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

        $this->saveSaleMismatches($sale, $saleMismatches);
    }

    public function saveSaleMismatches(Sale $sale, Collection $saleMismatches): void
    {
        $posMismatchQueries = resolve(PosMismatchQueries::class);

        foreach ($saleMismatches as $saleMismatch) {
            $posMismatchQueries->addNew($sale, $saleMismatch);
        }
    }
}

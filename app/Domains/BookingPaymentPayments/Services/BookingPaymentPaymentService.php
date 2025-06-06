<?php

declare(strict_types=1);

namespace App\Domains\BookingPaymentPayments\Services;

use App\CommonFunctions;
use App\Domains\BookingPayment\DataObjects\BookingPaymentData;
use App\Domains\BookingPaymentPayments\BookingPaymentPaymentQueries;
use App\Domains\BookingPaymentPayments\DataObjects\BookingPaymentTopUpData;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Company\CompanyQueries;
use App\Domains\CounterUpdate\CounterUpdateQueries;
use App\Domains\CreditNote\CreditNoteQueries;
use App\Domains\CreditNote\Enums\CreditNoteStatuses;
use App\Domains\CreditNoteUse\CreditNoteUseQueries;
use App\Domains\GiftCard\Enums\GiftCardStatuses;
use App\Domains\GiftCard\Enums\GiftCardTypes;
use App\Domains\GiftCard\GiftCardQueries;
use App\Domains\GiftCardTransaction\Enums\GiftCardTransactionTypes;
use App\Domains\GiftCardTransaction\GiftCardTransactionQueries;
use App\Domains\PaymentType\Enums\StaticPaymentTypes;
use App\Domains\PaymentType\PaymentTypeQueries;
use App\Models\BookingPayment;
use App\Models\Location;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class BookingPaymentPaymentService
{
    public BookingPaymentTopUpData|BookingPaymentData $paymentData;

    public Location $location;

    public int $companyId;

    public int $memberId;

    public Collection $paymentMismatches;

    public Collection $giftCards;

    public Collection $creditNotes;

    public function setDetails(
        BookingPaymentTopUpData|BookingPaymentData $paymentData,
        Location $location,
        int $companyId,
        int $memberId
    ): void {
        $this->paymentData = $paymentData;

        $this->location = $location;

        $this->companyId = $companyId;

        $this->memberId = $memberId;

        $this->paymentMismatches = collect([]);

        $this->giftCards = collect([]);
        $giftCardIds = collect($this->paymentData->payments)->pluck('gift_card_id')->unique()->filter();
        if (0 !== $giftCardIds->count()) {
            $giftCardQueries = resolve(GiftCardQueries::class);
            $this->giftCards = $giftCardQueries->getByIds($giftCardIds->toArray(), $this->companyId);
        }

        $this->creditNotes = collect([]);
        $creditNoteQueries = resolve(CreditNoteQueries::class);
        if ($paymentData->payments) {
            $creditNoteIds = collect($paymentData->payments)->pluck('credit_note_id')->unique()->filter();

            if (0 !== $creditNoteIds->count()) {
                $this->creditNotes = $creditNoteQueries->getByIds($creditNoteIds->toArray(), $location->id);
            }
        }
    }

    public function validateCreditNotes(
        BookingPaymentData|BookingPaymentTopUpData $bookingPaymentData,
        int $companyId,
        int $memberId
    ): void {
        $payments = [];
        if ($bookingPaymentData->payments) {
            $payments = $bookingPaymentData->payments;
        }

        if ([] === $payments) {
            return;
        }

        $counterUpdateQueries = resolve(CounterUpdateQueries::class);

        foreach ($payments as $payment) {
            $payment = (array) $payment;

            if ((int) $payment['payment_type_id'] === StaticPaymentTypes::CREDIT_NOTE->value &&
                ! array_key_exists('credit_note_id', $payment)
            ) {
                abort(412, 'Credit note id must be provided when payment type is credit note.');
            }

            if (! array_key_exists('credit_note_id', $payment)) {
                continue;
            }

            if (! $payment['credit_note_id']) {
                continue;
            }

            $creditNote = $this->creditNotes->firstWhere('id', $payment['credit_note_id']);

            if (! $creditNote) {
                abort(412, 'Some of the credit notes are not available in our records.');
            }

            if ($creditNote->expiry_date && $creditNote->expiry_date < now()->format('Y-m-d')) {
                $paymentMismatchMessage = 'Credit note is expired. You are not able to use expired credit notes.';
                CommonFunctions::addMismatchOrAbort($this->paymentMismatches, $paymentMismatchMessage);
            }

            if ($creditNote->status !== CreditNoteStatuses::ACTIVE->value) {
                $paymentMismatchMessage = 'Credit note is not active.';
                CommonFunctions::addMismatchOrAbort($this->paymentMismatches, $paymentMismatchMessage);
            }

            if ($memberId !== $creditNote->member_id) {
                abort(412, 'Selected member is not same as the credit note member');
            }

            if ((int) $payment['payment_type_id'] !== StaticPaymentTypes::CREDIT_NOTE->value) {
                abort(412, 'The Payment Type must be a credit note when you provide the credit note id.');
            }

            if ($creditNote->available_amount < $payment['amount']) {
                $paymentMismatchMessage = 'Specified payment amount exceeds the credit note available amount ' . $creditNote->available_amount . ' Requested Payment Amount is ' . $payment['amount'];
                CommonFunctions::addMismatchOrAbort($this->paymentMismatches, $paymentMismatchMessage);
            }

            $creditNoteCompanyId = $counterUpdateQueries->getCompanyIdByCounterUpdateId(
                $creditNote->counter_update_id
            );

            if ($companyId !== $creditNoteCompanyId) {
                $paymentMismatchMessage = 'You cannot use different companies credit notes.';
                CommonFunctions::addMismatchOrAbort($this->paymentMismatches, $paymentMismatchMessage);
            }
        }
    }

    public function getPaymentAmount(): float
    {
        $payments = collect($this->paymentData->payments);

        return $payments->sum('amount');
    }

    public function checkPaymentTypes(): void
    {
        if (
            $this->paymentData->payment_type_id
            && null !== $this->paymentData->payments
            && [] !== $this->paymentData->payments
        ) {
            abort(412, 'Simultaneous single and multiple payments are not allowed.');
        }

        if ($this->paymentData->payment_type_id) {
            $this->checkPaymentType();
            $this->checkPaymentCurrency();

            return;
        }

        if (
            null === $this->paymentData->payments
            || [] === $this->paymentData->payments
        ) {
            abort(412, 'payment is required for booking payment.');
        }

        if ($this->getPaymentAmount() !== $this->paymentData->amount) {
            $saleMismatchMessage = 'The payment amount and booking payment amount do not match.';
            CommonFunctions::addMismatchOrAbort($this->paymentMismatches, $saleMismatchMessage);
        }

        $paymentIds = collect($this->paymentData->payments)
            ->pluck('payment_type_id')
            ->unique()
            ->filter()
            ->toArray();

        $paymentTypeQueries = resolve(PaymentTypeQueries::class);
        $paymentTypes = $paymentTypeQueries->getByIds($paymentIds, $this->companyId);

        if ($paymentTypes->where('status', false)->isNotEmpty()) {
            $saleMismatchMessage = 'Some of the payment types are inactive.';
            CommonFunctions::addMismatchOrAbort($this->paymentMismatches, $saleMismatchMessage);
        }

        if ($paymentTypes->count() !== count($paymentIds)) {
            abort(412, 'Some of the payment types are not available in our records.');
        }

        $bookingPaymentPaymentCount = collect($this->paymentData->payments)
            ->where('payment_type_id', StaticPaymentTypes::BOOKING_PAYMENT->value)
            ->count();

        if ($bookingPaymentPaymentCount > 0) {
            abort(412, 'Payment type cannot be booking payment.');
        }

        $loyaltyPointPaymentCount = collect($this->paymentData->payments)
            ->where('payment_type_id', StaticPaymentTypes::LOYALTY_POINT->value)
            ->count();

        if ($loyaltyPointPaymentCount > 0) {
            abort(412, 'Payment type cannot be loyalty point.');
        }

        $this->validateGiftCards($this->paymentData->payments);

        $this->validateCreditNotes($this->paymentData, $this->companyId, $this->memberId);

        $this->checkPaymentCurrency();
    }

    public function validateGiftCards(array $payments): void
    {
        foreach ($payments as $payment) {
            $payment = (array) $payment;
            if ((int) $payment['payment_type_id'] !== StaticPaymentTypes::GIFT_CARD->value) {
                continue;
            }

            if (! array_key_exists('gift_card_id', $payment)) {
                abort(412, 'Gift Card id must be provided when payment type is gift card.');
            }

            if (! $payment['gift_card_id']) {
                abort(412, 'Gift Card id must be provided when payment type is gift card.');
            }

            $giftCard = $this->giftCards->firstWhere('id', $payment['gift_card_id']);

            if (! $giftCard) {
                abort(412, 'Some of the gift cards are not available in our records.');
            }

            $happenedAt = now();
            if (
                $this->paymentData instanceof BookingPaymentData
                && $this->paymentData->happened_at
            ) {
                /** @var Carbon $happenedAt */
                $happenedAt = Carbon::createFromFormat('Y-m-d H:i:s', $this->paymentData->happened_at);
            }

            if ($giftCard->expiry_date && $giftCard->expiry_date < $happenedAt->format('Y-m-d')) {
                $saleMismatchMessage = 'Expired gift card (number - [' . $giftCard->number . ']) was used for making a payment.';
                CommonFunctions::addMismatchOrAbort($this->paymentMismatches, $saleMismatchMessage);
            }

            if (
                $giftCard->type_id === GiftCardTypes::SINGLE_USE_ONLY->value
                && $giftCard->status === GiftCardStatuses::USED->value
            ) {
                $saleMismatchMessage = 'Specified Gift card (number - [' . $giftCard->number . ']) is single use only.';
                CommonFunctions::addMismatchOrAbort($this->paymentMismatches, $saleMismatchMessage);
            }

            if ($giftCard->status !== GiftCardStatuses::ACTIVE->value) {
                $saleMismatchMessage = 'Gift card (number - [' . $giftCard->number . ']) is not active.';
                CommonFunctions::addMismatchOrAbort($this->paymentMismatches, $saleMismatchMessage);
            }

            if ($giftCard->available_amount < $payment['amount']) {
                $saleMismatchMessage = 'The requested payment amount of ' . $payment['amount'] . ' exceeds the available amount of the gift card (number - [' . $giftCard->number . ']) , which is ' . $giftCard->available_amount . '.';
                CommonFunctions::addMismatchOrAbort($this->paymentMismatches, $saleMismatchMessage);
            }

            if ($this->companyId !== $giftCard->company_id) {
                abort(412, 'You cannot use different companies gift card.');
            }
        }
    }

    public function checkPaymentType(): void
    {
        if (StaticPaymentTypes::CREDIT_NOTE->value === $this->paymentData->payment_type_id) {
            abort(412, 'Payment type cannot be credit note payment.');
        }

        if (StaticPaymentTypes::BOOKING_PAYMENT->value === $this->paymentData->payment_type_id) {
            abort(412, 'Payment type cannot be booking payment.');
        }

        if (StaticPaymentTypes::LOYALTY_POINT->value === $this->paymentData->payment_type_id) {
            abort(412, 'Payment type cannot be loyalty point.');
        }

        if (StaticPaymentTypes::GIFT_CARD->value === $this->paymentData->payment_type_id) {
            abort(412, 'Payment type cannot be gift card.');
        }
    }

    public function checkPaymentCurrency(): void
    {
        $currencyIds = [];
        $currencyRates = [];

        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getConfigurationColumnsById($this->companyId);

        foreach ($company->countries as $country) {
            $currencyIds[] = $country->currency?->id;
            $currencyRates[] = CommonFunctions::numberFormat((float) $country->currency?->currencyRate?->rate);
        }

        if ($this->paymentData->payment_type_id) {
            return;
        }

        $this->checkMultiplePaymentCurrency($currencyIds, $currencyRates);
    }

    private function checkMultiplePaymentCurrency(array $currencyIds, array $currencyRates): void
    {
        if (! $this->paymentData->payments) {
            return;
        }

        foreach ($this->paymentData->payments as $payment) {
            if (
                isset($payment['currency_id'], $payment['current_currency_rate'], $payment['currency_amount'])
            ) {
                $this->validateCurrency(
                    (int) $payment['currency_id'],
                    $currencyIds,
                    (float) $payment['current_currency_rate'],
                    $currencyRates,
                    (float) $payment['amount'],
                    (float) $payment['currency_amount']
                );
            }
        }
    }

    private function validateCurrency(
        int $currencyId,
        array $currencyIds,
        float $currentCurrencyRate,
        array $currencyRates,
        float $amount,
        float $currencyAmount
    ): void {
        if (! in_array($currencyId, $currencyIds)) {
            $saleMismatchMessage = 'Payment currency id ' . $currencyId . ' is not available in this company.';
            CommonFunctions::addMismatchOrAbort($this->paymentMismatches, $saleMismatchMessage);
        }

        if (! in_array($currentCurrencyRate, $currencyRates)) {
            $saleMismatchMessage = 'Payment currency rate ' . $currentCurrencyRate . ' does not match with the actual currency rate of ' . implode(
                ', ',
                $currencyRates
            ) . ' for the currency id ' . $currencyId;
            CommonFunctions::addMismatchOrAbort($this->paymentMismatches, $saleMismatchMessage);
        }

        $currencyAmount = CommonFunctions::numberFormat(
            CommonFunctions::numberFormat($currencyAmount) / CommonFunctions::numberFormat($currentCurrencyRate)
        );

        if (! CommonFunctions::compareFloatNumbers($amount, $currencyAmount)) {
            $saleMismatchMessage = 'Payment amount ' . $amount . ' does not match with the actual currency amount of ' . $currencyAmount . '.';
            CommonFunctions::addMismatchOrAbort($this->paymentMismatches, $saleMismatchMessage);
        }
    }

    public function savePayments(BookingPayment $bookingPayment, int $counterUpdateId): void
    {
        $bookingPaymentPaymentQueries = resolve(BookingPaymentPaymentQueries::class);
        if ($this->paymentData->payment_type_id) {
            $bookingPaymentPayment = $bookingPaymentPaymentQueries->addNew(
                $this->paymentData,
                $bookingPayment->id,
                $counterUpdateId
            );

            $this->saveCreditNotes($bookingPaymentPayment->id, $counterUpdateId);

            return;
        }

        if (! $this->paymentData->payments) {
            return;
        }

        foreach ($this->paymentData->payments as $payment) {
            $bookingPaymentPaymentId = $bookingPaymentPaymentQueries->addNewForMultiple(
                $bookingPayment->id,
                $counterUpdateId,
                $payment
            );

            $this->useGiftCardIfApplicable($payment, $bookingPaymentPaymentId);

            $this->saveCreditNotes($bookingPaymentPaymentId, $counterUpdateId);
        }
    }

    public function saveCreditNotes(int $bookingPaymentPaymentId, int $counterUpdateId): void
    {
        if (! $this->paymentData->payments) {
            return;
        }

        foreach ($this->paymentData->payments as $payment) {
            if (! array_key_exists('credit_note_id', $payment)) {
                continue;
            }

            $creditNote = $this->creditNotes->firstWhere('id', $payment['credit_note_id']);
            if ($creditNote) {
                $creditNoteQueries = resolve(CreditNoteQueries::class);
                $creditNoteUseQueries = resolve(CreditNoteUseQueries::class);
                $paymentAmount = (float) $payment['amount'];

                $creditNoteQueries->decreaseAvailableAmountAndMarkAsUsed($creditNote, $paymentAmount);
                $creditNoteUseQueries->recordBookingPaymentUse(
                    $creditNote,
                    $bookingPaymentPaymentId,
                    $counterUpdateId,
                    $paymentAmount
                );
            }
        }
    }

    public function useGiftCardIfApplicable(array $payment, int $bookingPaymentPaymentId): void
    {
        if (! array_key_exists('gift_card_id', $payment)) {
            return;
        }

        if (! $payment['gift_card_id']) {
            return;
        }

        $giftCard = $this->giftCards->firstWhere('id', $payment['gift_card_id']);
        $paymentAmount = (float) $payment['amount'];

        $giftCardQueries = resolve(GiftCardQueries::class);
        $giftCardTransactionQueries = resolve(GiftCardTransactionQueries::class);

        $giftCardQueries->decreaseAvailableAmountAndMarkAsUsed($giftCard, $paymentAmount);

        $giftCardTransactionQueries->addNew(
            $giftCard,
            $bookingPaymentPaymentId,
            ModelMapping::BOOKING_PAYMENT_PAYMENT->name,
            $paymentAmount,
            GiftCardTransactionTypes::USED->value
        );
    }

    public function prepareAndCheckPayment(
        BookingPaymentTopUpData|BookingPaymentData $bookingPaymentTopUpData,
        Location $location,
        int $companyId,
        int $memberId
    ): void {
        $this->setDetails($bookingPaymentTopUpData, $location, $companyId, $memberId);

        $this->checkPaymentTypes();
    }
}

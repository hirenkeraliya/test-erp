<?php

declare(strict_types=1);

namespace App\Domains\CreditNote\Services;

use App\CommonFunctions;
use App\Domains\Company\CompanyQueries;
use App\Domains\CreditNote\Enums\CreditNoteStatuses;
use App\Domains\CreditNoteRefund\DataObjects\CreditNoteRefundData;
use App\Domains\Location\LocationQueries;
use App\Domains\PaymentType\Enums\StaticPaymentTypes;
use App\Domains\PaymentType\PaymentTypeQueries;
use App\Domains\StoreManager\StoreManagerQueries;
use App\Domains\StoreManagerAuthorizationCodeUsage\Services\StoreManagerAuthorizationCodeUsageService;
use App\Models\CreditNote;
use Illuminate\Support\Collection;

class CheckCreditNoteRefundRequestService
{
    public Collection $creditNoteMismatches;

    public function setDetails(): void
    {
        $this->creditNoteMismatches = collect([]);
    }

    public function checkRequestDetails(
        CreditNoteRefundData $creditNoteRefundData,
        CreditNote $creditNote,
        int $counterUpdateId,
    ): void {
        $locationQueries = resolve(LocationQueries::class);
        $location = $locationQueries->getLocationByCountersCounterUpdateId($counterUpdateId);

        /** @var int $counterId */
        $counterId = $creditNote->counterUpdate?->counter_id;
        $creditNoteLocation = $locationQueries->getStoreByCounters($counterId);

        $this->checkPaymentCurrency($creditNoteRefundData, $location->company_id);

        if ($location->company_id !== $creditNoteLocation->company_id) {
            CommonFunctions::addMismatchOrAbort(
                $this->creditNoteMismatches,
                'You cannot refund different company credit note.'
            );
        }

        if ($creditNote->status === CreditNoteStatuses::USED->value) {
            CommonFunctions::addMismatchOrAbort($this->creditNoteMismatches, 'Used Credit note cannot be refunded.');
        }

        if ($creditNote->status === CreditNoteStatuses::EXPIRED->value) {
            CommonFunctions::addMismatchOrAbort($this->creditNoteMismatches, 'Expired Credit note cannot be refunded.');
        }

        if (! CommonFunctions::compareFloatNumbers(
            $creditNoteRefundData->amount,
            (float) $creditNote->available_amount
        )) {
            CommonFunctions::addMismatchOrAbort(
                $this->creditNoteMismatches,
                'Only the full amount can be refunded. Requested amount is: ' . $creditNoteRefundData->amount . '. But expected amount is: ' . $creditNote->available_amount
            );
        }

        if ($creditNoteRefundData->payment_type_id === StaticPaymentTypes::CREDIT_NOTE->value) {
            CommonFunctions::addMismatchOrAbort(
                $this->creditNoteMismatches,
                'Credit Note refund payment type cannot be credit note.'
            );
        }

        if ($creditNoteRefundData->payment_type_id === StaticPaymentTypes::BOOKING_PAYMENT->value) {
            CommonFunctions::addMismatchOrAbort(
                $this->creditNoteMismatches,
                'Credit Note refund payment type cannot be booking payment.'
            );
        }

        $paymentTypeQueries = resolve(PaymentTypeQueries::class);
        $paymentType = $paymentTypeQueries->getById($creditNoteRefundData->payment_type_id, $location->company_id);

        if (! $paymentType->is_available_for_refund) {
            CommonFunctions::addMismatchOrAbort(
                $this->creditNoteMismatches,
                'Only refund payment types are allowed for refund.'
            );
        }

        $storeManagerQueries = resolve(StoreManagerQueries::class);
        $storeManagerExist = $storeManagerQueries->existsByIdStoreIdAndPasscode($location->id, $creditNoteRefundData);

        if (! $storeManagerExist) {
            CommonFunctions::addMismatchOrAbort(
                $this->creditNoteMismatches,
                "Only currently opened counter's store manager is allowed for credit note refund."
            );
        }

        $this->checkStoreManagerAuthorizationCode($creditNoteRefundData);
    }

    public function checkPaymentCurrency(CreditNoteRefundData $creditNoteRefundData, int $companyId): void
    {
        $currencyIds = [];
        $currencyRates = [];

        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getConfigurationColumnsById($companyId);

        foreach ($company->countries as $country) {
            $currencyIds[] = $country->currency?->id;
            $currencyRates[] = CommonFunctions::numberFormat((float) $country->currency?->currencyRate?->rate);
        }

        if (isset($creditNoteRefundData->currency_id, $creditNoteRefundData->current_currency_rate, $creditNoteRefundData->currency_amount)) {
            if (! in_array($creditNoteRefundData->currency_id, $currencyIds)) {
                $saleMismatchMessage = 'Payment currency id ' . $creditNoteRefundData->currency_id . ' is not available in this company.';
                CommonFunctions::addMismatchOrAbort($this->creditNoteMismatches, $saleMismatchMessage);
            }

            if (! in_array($creditNoteRefundData->current_currency_rate, $currencyRates)) {
                $saleMismatchMessage = 'Payment currency rate ' . $creditNoteRefundData->current_currency_rate . ' does not match with the actual currency rate of ' . implode(
                    ', ',
                    $currencyRates
                ) . ' for the currency id ' . $creditNoteRefundData->currency_id;
                CommonFunctions::addMismatchOrAbort($this->creditNoteMismatches, $saleMismatchMessage);
            }

            $currencyAmount = CommonFunctions::numberFormat(
                CommonFunctions::numberFormat($creditNoteRefundData->currency_amount) / CommonFunctions::numberFormat(
                    $creditNoteRefundData->current_currency_rate
                )
            );

            if (! CommonFunctions::compareFloatNumbers($currencyAmount, $creditNoteRefundData->amount)) {
                $saleMismatchMessage = 'Payment amount ' . $creditNoteRefundData->amount . ' does not match with the actual currency amount of ' . $currencyAmount . '.';
                CommonFunctions::addMismatchOrAbort($this->creditNoteMismatches, $saleMismatchMessage);
            }
        }
    }

    public function checkStoreManagerAuthorizationCode(CreditNoteRefundData $creditNoteRefundData): void
    {
        $storeManagerAuthorizationCodeUsageService = resolve(StoreManagerAuthorizationCodeUsageService::class);
        $storeManagerAuthorizationCodeUsageService->checkStoreManagerAuthorizationCode(
            $this->creditNoteMismatches,
            $creditNoteRefundData->store_manager_id,
            $creditNoteRefundData->store_manager_authorization_code,
            now()->format('Y-m-d H:i:s')
        );
    }
}

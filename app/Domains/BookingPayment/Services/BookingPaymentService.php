<?php

declare(strict_types=1);

namespace App\Domains\BookingPayment\Services;

use App\CommonFunctions;
use App\Domains\BookingPayment\BookingPaymentQueries;
use App\Domains\BookingPayment\DataObjects\BookingPaymentData;
use App\Domains\BookingPaymentPayments\DataObjects\BookingPaymentTopUpData;
use App\Domains\BookingPaymentPayments\Services\BookingPaymentPaymentService;
use App\Domains\BookingPaymentProduct\BookingPaymentProductQueries;
use App\Domains\BookingPaymentProduct\DataObjects\BookingPaymentProductData;
use App\Domains\BookingPaymentRefund\BookingPaymentRefundQueries;
use App\Domains\BookingPaymentRefund\DataObjects\BookingPaymentRefundData;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Location\LocationQueries;
use App\Domains\PosMismatch\PosMismatchQueries;
use App\Domains\PosMismatch\Services\PosMismatchService;
use App\Domains\StoreManagerAuthorizationCodeUsage\Enum\StoreManagerAuthorizationCodeUsageTypes;
use App\Domains\StoreManagerAuthorizationCodeUsage\Services\StoreManagerAuthorizationCodeUsageService;
use App\Models\BookingPayment;
use App\Models\Cashier;
use Illuminate\Support\Collection;

class BookingPaymentService
{
    public function getCompanyAndStore(Cashier $cashier): array
    {
        $locationQueries = resolve(LocationQueries::class);

        /** @var int $counterUpdateId */
        $counterUpdateId = $cashier->getCounterUpdateId();

        $location = $locationQueries->getStoreWithCompanyByCountersCounterUpdateId($counterUpdateId);
        $companyId = CommonFunctions::getCashierCompanyId($cashier);

        return [$location, $companyId];
    }

    public function resetBookingPaymentProducts(
        BookingPayment $bookingPayment,
        BookingPaymentProductData $bookingPaymentProductData,
        Collection $bookingPaymentMismatches
    ): void {
        $bookingPaymentQueries = resolve(BookingPaymentQueries::class);
        $bookingPaymentProductQueries = resolve(BookingPaymentProductQueries::class);

        $bookingPaymentProductQueries->deleteBookingPaymentProducts($bookingPayment->getKey());

        $bookingPaymentQueries->updatePromoters($bookingPayment, $bookingPaymentProductData->promoter_ids);

        $bookingPaymentProductQueries->createMany($bookingPaymentProductData->products, $bookingPayment->getKey());

        $this->addPosMismatchRecords($bookingPayment, $bookingPaymentMismatches);
    }

    public function addLogMismatchEntries(BookingPayment $bookingPayment, string $logMismatchTitle): BookingPayment
    {
        $bookingPaymentQueries = resolve(BookingPaymentQueries::class);
        $bookingPayment = $bookingPaymentQueries->loadProductsMemberAndMismatchesRelations($bookingPayment);

        $this->logMismatchEntries(
            $logMismatchTitle,
            $bookingPayment->id,
            $bookingPayment->mismatches,
            $bookingPayment->offline_id
        );

        return $bookingPayment;
    }

    public function bookingPaymentTopUp(
        BookingPayment $bookingPayment,
        BookingPaymentTopUpData $bookingPaymentTopUpData,
        BookingPaymentPaymentService $bookingPaymentPaymentService,
        Collection $mismatches,
        int $counterUpdateId
    ): void {
        $bookingPaymentQueries = resolve(BookingPaymentQueries::class);

        $bookingPaymentPaymentService->savePayments($bookingPayment, $counterUpdateId);

        /** @var float $amount */
        $amount = $bookingPaymentTopUpData->amount;
        $bookingPaymentQueries->updateAmountColumnsForTopUp($bookingPayment, $amount);

        $this->addPosMismatchRecords($bookingPayment, $mismatches);
    }

    public function bookingPaymentRefund(
        BookingPayment $bookingPayment,
        BookingPaymentRefundData $bookingPaymentRefundData,
        Collection $mismatches,
        int $counterUpdateId
    ): void {
        $bookingPaymentRefundQueries = resolve(BookingPaymentRefundQueries::class);
        $bookingPaymentQueries = resolve(BookingPaymentQueries::class);

        $bookingPaymentDetails = [
            'booking_payment_id' => $bookingPayment->id,
            'counter_update_id' => $counterUpdateId,
            'payment_type_id' => $bookingPaymentRefundData->payment_type_id,
            'amount' => $bookingPaymentRefundData->amount,
            'currency_id' => $bookingPaymentRefundData->currency_id,
            'currency_rate' => $bookingPaymentRefundData->current_currency_rate,
            'currency_amount' => $bookingPaymentRefundData->currency_amount,
        ];

        $bookingPaymentRefundQueries->addNew($bookingPaymentDetails);

        $bookingPaymentQueries->markAsRefunded($bookingPayment, $bookingPaymentRefundData->amount);

        $this->addPosMismatchRecords($bookingPayment, $mismatches);
    }

    private function logMismatchEntries(
        string $infoMessage,
        int $bookingPaymentId,
        Collection $mismatches,
        ?string $offlineId
    ): void {
        if ($mismatches->isNotEmpty()) {
            $messages = $mismatches->pluck('message')->toArray();

            $posMismatchService = resolve(PosMismatchService::class);
            $posMismatchService->logMismatchEntries($infoMessage, $bookingPaymentId, $messages, $offlineId);
        }
    }

    private function addPosMismatchRecords(BookingPayment $bookingPayment, Collection $mismatches): void
    {
        $posMismatchQueries = resolve(PosMismatchQueries::class);

        foreach ($mismatches as $mismatch) {
            $posMismatchQueries->addNew($bookingPayment, $mismatch);
        }
    }

    public function storeBookingPayment(
        BookingPaymentData $bookingPaymentData,
        BookingPaymentPaymentService $bookingPaymentPaymentService,
        Collection $mismatches,
        string $digitalInvoiceNumber,
        int $counterUpdateId,
    ): BookingPayment {
        $bookingPaymentQueries = resolve(BookingPaymentQueries::class);

        $bookingPayment = $bookingPaymentQueries->addNew(
            $bookingPaymentData,
            $counterUpdateId,
            $digitalInvoiceNumber
        );
        $storeManagerAuthorizationCodeUsageService = resolve(StoreManagerAuthorizationCodeUsageService::class);
        $storeManagerAuthorizationCodeUsageService->addStoreManagerAuthorizationCodeUsage(
            StoreManagerAuthorizationCodeUsageTypes::BOOKING_PAYMENT->value,
            $bookingPayment->id,
            ModelMapping::BOOKING_PAYMENT->name,
            $bookingPaymentData->store_manager_authorization_code
        );

        $bookingPaymentPaymentService->savePayments($bookingPayment, $counterUpdateId);

        $bookingPaymentProductQueries = resolve(BookingPaymentProductQueries::class);
        /** @var ?array $products */
        $products = $bookingPaymentData->products;

        if ($products) {
            $bookingPaymentProductQueries->createMany($products, $bookingPayment->id);
        }

        $this->addPosMismatchRecords($bookingPayment, $mismatches);

        return $bookingPayment;
    }
}

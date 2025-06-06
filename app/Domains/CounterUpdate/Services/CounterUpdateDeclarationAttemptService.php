<?php

declare(strict_types=1);

namespace App\Domains\CounterUpdate\Services;

use App\CommonFunctions;
use App\Domains\BookingPaymentPayments\BookingPaymentPaymentQueries;
use App\Domains\BookingPaymentRefund\BookingPaymentRefundQueries;
use App\Domains\Counter\DataObjects\CloseCounterDataForStoreManager;
use App\Domains\CounterUpdateDeclarationAttempt\CounterUpdateDeclarationAttemptQueries;
use App\Domains\CounterUpdateDeclarationAttemptPayment\CounterUpdateDeclarationAttemptPaymentQueries;
use App\Domains\CreditNoteRefund\CreditNoteRefundQueries;
use App\Domains\PaymentType\Enums\StaticPaymentTypes;
use App\Domains\SalePayment\SalePaymentQueries;
use App\Models\CounterUpdate;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\LaravelData\DataCollection;
use Throwable;

class CounterUpdateDeclarationAttemptService
{
    public function getDeclarationAttemptPayments(
        CounterUpdate $counterUpdate,
        CloseCounterDataForStoreManager $closeCounterData
    ): Collection {
        $salePaymentQueries = resolve(SalePaymentQueries::class);
        $creditNoteRefundQueries = resolve(CreditNoteRefundQueries::class);
        $bookingPaymentRefundQueries = resolve(BookingPaymentRefundQueries::class);
        $bookingPaymentPaymentQueries = resolve(BookingPaymentPaymentQueries::class);

        $salePayments = $salePaymentQueries->getByCounterUpdateIdWithRelations($counterUpdate->id);

        $creditNotesRefunds = $creditNoteRefundQueries->getByCounterUpdateIdWithPaymentType($counterUpdate->id);

        $bookingPaymentRefunds = $bookingPaymentRefundQueries->getByCounterUpdateIdWithPaymentType($counterUpdate->id);

        $bookingPaymentPayments = $bookingPaymentPaymentQueries->getByCounterUpdateIdWithPaymentType(
            $counterUpdate->id
        );

        return $this->preparePayments(
            $salePayments,
            $bookingPaymentPayments,
            $bookingPaymentRefunds,
            $creditNotesRefunds,
            $closeCounterData
        );
    }

    public function preparePayments(
        Collection $salePayments,
        Collection $bookingPaymentPayments,
        Collection $bookingPaymentRefunds,
        Collection $creditNotesRefunds,
        CloseCounterDataForStoreManager $closeCounterData
    ): Collection {
        $paymentsRecords = $this->prepareMatchedPaymentsFor(
            $salePayments,
            $bookingPaymentPayments,
            $bookingPaymentRefunds,
            $creditNotesRefunds
        );

        $this->addUnmatchedBookingPayments($bookingPaymentPayments, $paymentsRecords);
        $this->addUnmatchedBookingPaymentRefunds($bookingPaymentRefunds, $paymentsRecords);
        $this->addUnmatchedCreditNoteRefundPayments($creditNotesRefunds, $paymentsRecords);

        return $this->addDenominations($closeCounterData, $paymentsRecords);
    }

    public function saveDeclarationAttemptDetails(
        CounterUpdate $counterUpdate,
        Collection $payments,
        int $casherId,
        int $locationId
    ): void {
        $counterUpdateDeclarationAttemptQueries = resolve(CounterUpdateDeclarationAttemptQueries::class);
        $counterUpdateDeclarationAttemptPaymentQueries = resolve(CounterUpdateDeclarationAttemptPaymentQueries::class);

        DB::beginTransaction();

        try {
            $offlineId = $this->getOfflineId($casherId, $counterUpdate->counter_id, $locationId);
            $counterUpdateDeclarationAttemptId = $counterUpdateDeclarationAttemptQueries->addNew(
                $offlineId,
                now()->format('Y-m-d H:i:s'),
                $counterUpdate->id
            );

            $counterUpdateDeclarationAttemptPaymentsData = $payments->map(
                function (array $payment) use ($counterUpdateDeclarationAttemptId): array {
                    $payment['counter_update_declaration_attempt_id'] = $counterUpdateDeclarationAttemptId;
                    $payment['created_at'] = now()->format('Y-m-d H:i:s');
                    $payment['updated_at'] = now()->format('Y-m-d H:i:s');
                    $payment['denominations'] = array_key_exists('denominations', $payment) ? json_encode(
                        $payment['denominations'],
                        JSON_THROW_ON_ERROR
                    ) : null;

                    return $payment;
                }
            )->toArray();

            $counterUpdateDeclarationAttemptPaymentQueries->createMany($counterUpdateDeclarationAttemptPaymentsData);

            DB::commit();
        } catch (Throwable $throwable) {
            Log::error('Attempt to update counter declaration', [
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

    public function getOfflineId(int $casherId, int $counterId, int $locationId): string
    {
        return $casherId . $counterId . $locationId . random_int(0, 8) . (int) round(microtime(true) * 1000);
    }

    private function addDenominations(
        CloseCounterDataForStoreManager $closeCounterData,
        Collection $paymentsRecords
    ): Collection {
        if ($paymentsRecords->isEmpty()) {
            $paymentsRecords->push([
                'payment_type_id' => StaticPaymentTypes::CASH->value,
                'declared_amount' => 0,
                'calculated_amount' => 0,
                'denominations' => [],
            ]);
        }

        return $paymentsRecords->groupBy('payment_type_id')
            ->map(function ($paymentsRecords, $paymentTypeId) use ($closeCounterData): array {
                $matchedPayments = $paymentsRecords->where('payment_type_id', $paymentTypeId);
                $denominations = [];
                if ($paymentTypeId === StaticPaymentTypes::CASH->value && $closeCounterData->denominations instanceof DataCollection) {
                    $denominations = $closeCounterData->denominations->toArray();
                }

                return [
                    'payment_type_id' => $paymentTypeId,
                    'declared_amount' => 0,
                    'calculated_amount' => CommonFunctions::numberFormat($matchedPayments->sum('amount')),
                    'denominations' => $denominations,
                ];
            })->values();
    }

    private function prepareMatchedPaymentsFor(
        Collection $salePayments,
        Collection $bookingPaymentPayments,
        Collection $bookingPaymentRefunds,
        Collection $creditNotesRefunds,
    ): Collection {
        return $salePayments->groupBy('payment_type_id')
            ->map(function ($salePayments, $paymentTypeId) use (
                $bookingPaymentPayments,
                $bookingPaymentRefunds,
                $creditNotesRefunds,
            ): array {
                $matchedBookingPayments = $bookingPaymentPayments->where('payment_type_id', $paymentTypeId);
                $refundedBookingPayments = $bookingPaymentRefunds->where('payment_type_id', $paymentTypeId);
                $refundedCreditNotePayments = $creditNotesRefunds->where('payment_type_id', $paymentTypeId);

                return [
                    'payment_type_id' => $paymentTypeId,
                    'declared_amount' => 0,
                    'calculated_amount' => CommonFunctions::numberFormat(
                        $salePayments->sum('amount') + $matchedBookingPayments->sum('amount')
                    ) - $refundedBookingPayments->sum('amount') - $refundedCreditNotePayments->sum('amount'),
                    'denominations' => [],
                ];
            })->values();
    }

    private function addUnmatchedBookingPayments(Collection $bookingPaymentPayments, Collection $paymentsRecords): void
    {
        $unmatchedBookingPayments = $bookingPaymentPayments->whereNotIn(
            'payment_type_id',
            $paymentsRecords->pluck('payment_type_id')->toArray()
        )->groupBy('payment_type_id');

        foreach ($unmatchedBookingPayments as $paymentTypeId => $payments) {
            $paymentsRecords->push([
                'payment_type_id' => $paymentTypeId,
                'declared_amount' => 0,
                'calculated_amount' => (float) $payments->sum('amount'),
                'denominations' => [],
            ]);
        }
    }

    private function addUnmatchedBookingPaymentRefunds(
        Collection $bookingPaymentRefunds,
        Collection $paymentsRecords
    ): void {
        $unmatchedRefundPayments = $bookingPaymentRefunds->whereNotIn(
            'payment_type_id',
            $paymentsRecords->pluck('payment_type_id')->toArray()
        )->groupBy('payment_type_id');

        foreach ($unmatchedRefundPayments as $paymentTypeId => $payments) {
            $paymentsRecords->push([
                'payment_type_id' => $paymentTypeId,
                'declared_amount' => 0,
                'calculated_amount' => '-' . (float) $payments->sum('amount'),
                'denominations' => [],
            ]);
        }
    }

    private function addUnmatchedCreditNoteRefundPayments(
        Collection $creditNotesRefunds,
        Collection $paymentsRecords
    ): void {
        $unmatchedRefundPayments = $creditNotesRefunds->whereNotIn(
            'payment_type_id',
            $paymentsRecords->pluck('payment_type_id')->toArray()
        )->groupBy('payment_type_id');

        foreach ($unmatchedRefundPayments as $paymentTypeId => $payments) {
            $paymentsRecords->push([
                'payment_type_id' => $paymentTypeId,
                'declared_amount' => 0,
                'calculated_amount' => '-' . (float) $payments->sum('amount'),
                'denominations' => [],
            ]);
        }
    }
}

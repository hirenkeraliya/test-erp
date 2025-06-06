<?php

declare(strict_types=1);

namespace App\Domains\CounterUpdate\Resources;

use App\Domains\CounterUpdateEvent\Enums\CounterUpdateEventTypes;
use App\Domains\PaymentType\Enums\StaticPaymentTypes;
use App\Models\CloseCounterDenomination;
use App\Models\CloseCounterPayment;
use App\Models\CounterUpdate;
use App\Models\CounterUpdateDeclarationAttempt;
use App\Models\CounterUpdateDeclarationAttemptPayment;
use App\Models\CounterUpdateEvent;
use App\Models\PaymentType;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class ClosedCounterDetailsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var CounterUpdate $counterUpdate */
        $counterUpdate = $this;

        /** @var Collection $payments */
        $payments = $counterUpdate->getPayments();

        /** @var Collection $denominations */
        $denominations = $counterUpdate->denominations;

        /** @var Collection $counterUpdateEvents */
        $counterUpdateEvents = $counterUpdate->counterUpdateEvents;

        /** @var Collection $counterUpdateDeclarationAttempts */
        $counterUpdateDeclarationAttempts = $counterUpdate->counterUpdateDeclarationAttempts;

        $payments = $this->getPreparedPayments($payments);

        return [
            'mismatch_amount' => $counterUpdate->getMismatchAmount(),
            'amount_mismatch_reason' => $counterUpdate->getAmountMismatchReason(),
            'sales_collection_amount' => $counterUpdate->sales_collection_amount,
            'opening_balance' => $counterUpdate->opening_balance,
            'closing_balance' => $counterUpdate->closing_balance,
            'total_sales' => $counterUpdate->total_sales,
            'total_sales_amount' => $counterUpdate->total_sales_amount,
            'total_layaway_sales' => $counterUpdate->total_layaway_sales,
            'total_layaway_sales_amount' => $counterUpdate->total_layaway_sales_amount,
            'total_credit_sales' => $counterUpdate->total_credit_sales,
            'total_credit_sales_amount' => (float) $counterUpdate->total_credit_sales_amount,
            'total_voided_sales' => $counterUpdate->total_voided_sales,
            'total_voided_sales_amount' => $counterUpdate->total_voided_sales_amount,
            'total_item_wise_discount_amount' => $counterUpdate->total_item_wise_discount_amount,
            'total_cart_wide_discount_amount' => $counterUpdate->total_cart_wide_discount_amount,
            'total_discount_amount' => $counterUpdate->total_item_wise_discount_amount + $counterUpdate->total_cart_wide_discount_amount,
            'total_tax_amount' => $counterUpdate->total_tax_amount,
            'total_sales_round_off' => $counterUpdate->total_sales_round_off,
            'total_sale_returns' => $counterUpdate->total_sale_returns,
            'total_sale_returns_amount' => $counterUpdate->total_sale_returns_amount,
            'total_credit_notes_used_amount' => $counterUpdate->total_credit_notes_used_amount,
            'total_credit_notes_used' => $counterUpdate->total_credit_notes_used,
            'total_credit_notes_refunded_amount' => $counterUpdate->total_credit_notes_refunded_amount,
            'total_credit_notes_refunded' => $counterUpdate->total_credit_notes_refunded,
            'total_sale_returns_round_off' => $counterUpdate->total_sale_returns_round_off,
            'total_cashback' => $counterUpdate->total_cashback,
            'total_cashback_amount' => $counterUpdate->total_cashback_amount,
            'total_vouchers_used' => $counterUpdate->total_vouchers_used,
            'total_vouchers_generated' => $counterUpdate->total_vouchers_generated,
            'total_booking_payment_amount' => $counterUpdate->total_booking_payment_amount,
            'total_booking_payment_refunded_amount' => $counterUpdate->total_booking_payment_refunded_amount,
            'total_booking_payment_used_amount' => $counterUpdate->total_booking_payment_used_amount,
            'total_cash_ins_amount' => $counterUpdate->total_cash_ins_amount,
            'total_cash_outs_amount' => $counterUpdate->total_cash_outs_amount,
            'total_cash_amount_in_sales' => $counterUpdate->total_cash_amount_in_sales,
            'total_cash_amount_in_booking_payment' => $counterUpdate->total_cash_amount_in_booking_payment,
            'total_cash_amount_in_booking_payment_refunded' => $counterUpdate->total_cash_amount_in_booking_payment_refunded,
            'total_cash_amount_in_credit_note_refunded' => $counterUpdate->total_cash_amount_in_credit_note_refunded,
            'total_new_booking_payments' => $counterUpdate->total_new_booking_payments,
            'total_used_booking_payments' => $counterUpdate->total_used_booking_payments,
            'total_cancel_layaway_sales' => $counterUpdate->total_cancel_layaway_sales,
            'total_cancel_layaway_sales_amount' => $counterUpdate->total_cancel_layaway_sales_amount,
            'denominations' => $this->getDenominationDetails($denominations),
            'payments' => $payments,
            'counter_till_details' => $this->getCounterUpdateEvents($counterUpdateEvents),
            'take_break_details' => $this->getTakeBreakDetails($counterUpdateEvents),
            'track_offline_mode' => $this->getTrackOfflineModeDetails($counterUpdateEvents),
            'drawer_details' => $this->drawerDetails($counterUpdateEvents),
            'counter_attempt_details' => $counterUpdateDeclarationAttempts->isNotEmpty() ? $this->getCounterUpdateDeclarativeAttempt(
                $counterUpdateDeclarationAttempts
            ) : null,
            'total_payments' => $payments->sum('total'),
            'total_cash_transaction' => $counterUpdate->opening_balance + $counterUpdate->total_cash_amount_in_sales + $counterUpdate->total_cash_amount_in_booking_payment + $counterUpdate->total_cash_amount_in_booking_payment_refunded + $counterUpdate->total_cash_ins_amount - $counterUpdate->total_cash_outs_amount + $counterUpdate->total_cash_amount_in_credit_note_refunded,
        ];
    }

    private function getDenominationDetails(Collection $denominations): Collection
    {
        return $denominations->map(function ($details): array {
            /** @var CloseCounterDenomination $closeCounterDenomination */
            $closeCounterDenomination = $details;

            return [
                'denomination' => $closeCounterDenomination->denomination,
                'denomination_quantity' => $closeCounterDenomination->quantity,
            ];
        });
    }

    private function getPreparedPayments(Collection $payments): Collection
    {
        $payments = $payments->where('paymentType.id', '!=', StaticPaymentTypes::CREDIT_NOTE->value);

        return $payments->map(function ($item): array {
            /** @var CloseCounterPayment $closeCounterPayment */
            $closeCounterPayment = $item;

            /** @var PaymentType $paymentType */
            $paymentType = $closeCounterPayment->paymentType;

            return [
                'payment_type_id' => $closeCounterPayment->payment_type_id,
                'payment_type' => $paymentType->name,
                'total_transactions' => $closeCounterPayment->total_transactions,
                'total' => (float) $closeCounterPayment->total_amount,
            ];
        });
    }

    private function getCounterUpdateEvents(Collection $counterUpdateEvents): Collection
    {
        return $counterUpdateEvents->map(function (CounterUpdateEvent $counterUpdateEvent): array {
            $happenedAt = Carbon::createFromFormat('Y-m-d H:i:s', $counterUpdateEvent->happened_at);

            return [
                'type_id' => $counterUpdateEvent->type_id,
                'type' => CounterUpdateEventTypes::getFormattedCaseName($counterUpdateEvent->type_id),
                'happened_at' => $happenedAt ? $happenedAt->format('d-m-Y h:i:s A') : '',
            ];
        });
    }

    private function getTakeBreakDetails(Collection $counterUpdateEvents): Collection
    {
        $breakDetails = [];
        $previousEvent = null;
        $totalDuration = 0;
        $totalTakeBreak = $counterUpdateEvents->where('type_id', CounterUpdateEventTypes::TAKE_A_BREAK->value)->count();
        foreach ($counterUpdateEvents as $counterUpdateEvent) {
            if (null !== $previousEvent && ($previousEvent->type_id === CounterUpdateEventTypes::TAKE_A_BREAK->value && $counterUpdateEvent->type_id === CounterUpdateEventTypes::BACK_FROM_BREAK->value)) {
                $previousEventDate = Carbon::createFromFormat('Y-m-d H:i:s', $previousEvent->happened_at);
                $counterUpdateEventDate = Carbon::createFromFormat('Y-m-d H:i:s', $counterUpdateEvent->happened_at);
                $durationInSeconds = 0;
                if (false !== $previousEventDate && false !== $counterUpdateEventDate) {
                    $durationInSeconds = $previousEventDate->diffInSeconds($counterUpdateEventDate);
                }

                $totalDuration += $durationInSeconds;

                $duration = CarbonInterval::seconds($durationInSeconds)->cascade()->format('%H:%I:%S');
                $previousEventHappenedAt = Carbon::createFromFormat('Y-m-d H:i:s', $previousEvent->happened_at);
                $counterUpdateEventHappenedAt = Carbon::createFromFormat(
                    'Y-m-d H:i:s',
                    $counterUpdateEvent->happened_at
                );

                $breakDetails['data'][] = [
                    'take_a_break' => $previousEventHappenedAt ? $previousEventHappenedAt->format('d-m-Y h:i:s A') : '',
                    'back_from_break' => $counterUpdateEventHappenedAt ? $counterUpdateEventHappenedAt->format(
                        'd-m-Y h:i:s A'
                    ) : '',
                    'duration' => $duration,
                ];
            }

            $previousEvent = $counterUpdateEvent;
        }

        $totalDurationFormatted = CarbonInterval::seconds($totalDuration)->cascade()->format('%H:%I:%S');
        $breakDetails['total_duration'] = $totalDurationFormatted;
        $breakDetails['total_break'] = $totalTakeBreak;

        return collect($breakDetails);
    }

    private function drawerDetails(Collection $counterUpdateEvents): Collection
    {
        $drawerDetails = [];
        $previousEvent = null;
        $totalDuration = 0;
        $totalDrawerOpen = $counterUpdateEvents->where('type_id', CounterUpdateEventTypes::DRAWER_OPEN->value)->count();
        foreach ($counterUpdateEvents as $counterUpdateEvent) {
            if (null !== $previousEvent && ($previousEvent->type_id === CounterUpdateEventTypes::DRAWER_OPEN->value && $counterUpdateEvent->type_id === CounterUpdateEventTypes::DRAWER_CLOSE->value)) {
                $previousEventDate = Carbon::createFromFormat('Y-m-d H:i:s', $previousEvent->happened_at);
                $counterUpdateEventDate = Carbon::createFromFormat('Y-m-d H:i:s', $counterUpdateEvent->happened_at);
                $durationInSeconds = 0;
                if (false !== $previousEventDate && false !== $counterUpdateEventDate) {
                    $durationInSeconds = $previousEventDate->diffInSeconds($counterUpdateEventDate);
                }

                $totalDuration += $durationInSeconds;

                $duration = CarbonInterval::seconds($durationInSeconds)->cascade()->format('%H:%I:%S');
                $previousEventHappenedAt = Carbon::createFromFormat('Y-m-d H:i:s', $previousEvent->happened_at);
                $counterUpdateEventHappenedAt = Carbon::createFromFormat(
                    'Y-m-d H:i:s',
                    $counterUpdateEvent->happened_at
                );

                $drawerDetails['data'][] = [
                    'drawer_open' => $previousEventHappenedAt ? $previousEventHappenedAt->format('d-m-Y h:i:s A') : '',
                    'drawer_close' => $counterUpdateEventHappenedAt ? $counterUpdateEventHappenedAt->format(
                        'd-m-Y h:i:s A'
                    ) : '',
                    'duration' => $duration,
                ];
            }

            $previousEvent = $counterUpdateEvent;
        }

        $totalDurationFormatted = CarbonInterval::seconds($totalDuration)->cascade()->format('%H:%I:%S');
        $drawerDetails['total_duration'] = $totalDurationFormatted;
        $drawerDetails['total_drawer_open'] = $totalDrawerOpen;

        return collect($drawerDetails);
    }

    private function getCounterUpdateDeclarativeAttempt(Collection $counterUpdateDeclarationAttempts): Collection
    {
        return $counterUpdateDeclarationAttempts->map(
            function (CounterUpdateDeclarationAttempt $counterUpdateDeclarationAttempt): array {
                /** @var Collection $counterUpdateDeclarationAttemptPayments */
                $counterUpdateDeclarationAttemptPayments = $counterUpdateDeclarationAttempt->counterUpdateDeclarationAttemptPayments;
                $happenedAt = Carbon::createFromFormat('Y-m-d H:i:s', $counterUpdateDeclarationAttempt->happened_at);

                return [
                    'happened_at' => $happenedAt ? $happenedAt->format('d-m-Y h:i:s A') : '',
                    'counter_update_declaration_attempt_payments' => $this->getCounterUpdateDeclarationAttemptPayments(
                        $counterUpdateDeclarationAttemptPayments
                    ),
                ];
            }
        );
    }

    /**
     * @return mixed[]
     */
    private function getCounterUpdateDeclarationAttemptPayments(
        Collection $counterUpdateDeclarationAttemptPayments
    ): array {
        return $counterUpdateDeclarationAttemptPayments->map(
            function (CounterUpdateDeclarationAttemptPayment $counterUpdateDeclarationAttemptPayment): array {
                /** @var PaymentType $paymentType */
                $paymentType = $counterUpdateDeclarationAttemptPayment->paymentType;

                return [
                    'payment_type' => $paymentType->name,
                    'declared_amount' => $counterUpdateDeclarationAttemptPayment->declared_amount,
                    'calculated_amount' => $counterUpdateDeclarationAttemptPayment->calculated_amount,
                    'denominations' => $counterUpdateDeclarationAttemptPayment->denominations ? json_decode(
                        $counterUpdateDeclarationAttemptPayment->denominations,
                        null,
                        512,
                        JSON_THROW_ON_ERROR
                    ) : null,
                ];
            }
        )->toArray();
    }

    private function getTrackOfflineModeDetails(Collection $counterUpdateEvents): Collection
    {
        $trackDetails = [];
        $previousEvent = null;
        $totalDuration = 0;
        $totalOffline = $counterUpdateEvents->where('type_id', CounterUpdateEventTypes::GOES_OFFLINE->value)->count();
        foreach ($counterUpdateEvents as $counterUpdateEvent) {
            if (null !== $previousEvent && ($previousEvent->type_id === CounterUpdateEventTypes::GOES_OFFLINE->value && $counterUpdateEvent->type_id === CounterUpdateEventTypes::BACK_ONLINE->value)) {
                $previousEventDate = Carbon::createFromFormat('Y-m-d H:i:s', $previousEvent->happened_at);
                $counterUpdateEventDate = Carbon::createFromFormat('Y-m-d H:i:s', $counterUpdateEvent->happened_at);
                $durationInSeconds = 0;
                if (false !== $previousEventDate && false !== $counterUpdateEventDate) {
                    $durationInSeconds = $previousEventDate->diffInSeconds($counterUpdateEventDate);
                }

                $totalDuration += $durationInSeconds;

                $duration = CarbonInterval::seconds($durationInSeconds)->cascade()->format('%H:%I:%S');
                $previousEventHappenedAt = Carbon::createFromFormat('Y-m-d H:i:s', $previousEvent->happened_at);
                $counterUpdateEventHappenedAt = Carbon::createFromFormat(
                    'Y-m-d H:i:s',
                    $counterUpdateEvent->happened_at
                );

                $trackDetails['data'][] = [
                    'goes_offline' => $previousEventHappenedAt ? $previousEventHappenedAt->format('d-m-Y h:i:s A') : '',
                    'back_online' => $counterUpdateEventHappenedAt ? $counterUpdateEventHappenedAt->format(
                        'd-m-Y h:i:s A'
                    ) : '',
                    'duration' => $duration,
                ];
            }

            $previousEvent = $counterUpdateEvent;
        }

        $totalDurationFormatted = CarbonInterval::seconds($totalDuration)->cascade()->format('%H:%I:%S');
        $trackDetails['total_duration'] = $totalDurationFormatted;
        $trackDetails['total_offline'] = $totalOffline;

        return collect($trackDetails);
    }
}

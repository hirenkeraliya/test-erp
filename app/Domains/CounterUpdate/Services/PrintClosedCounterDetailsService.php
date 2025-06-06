<?php

declare(strict_types=1);

namespace App\Domains\CounterUpdate\Services;

use App\Domains\CounterUpdateEvent\Enums\CounterUpdateEventTypes;
use App\Domains\Currency\CurrencyQueries;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\CounterUpdateDeclarationAttempt;
use App\Models\CounterUpdateDeclarationAttemptPayment;
use App\Models\CounterUpdateEvent;
use App\Models\Location;
use App\Models\PaymentType;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class PrintClosedCounterDetailsService
{
    public function printCloseCounterAttempts(Model $counterUpdateDetails): string
    {
        /** @var CounterUpdate $counterUpdate */
        $counterUpdate = $counterUpdateDetails;

        /** @var Counter $counter */
        $counter = $counterUpdate->counter;

        /** @var Location $location */
        $location = $counter->location;

        $currencyQueries = resolve(CurrencyQueries::class);
        $currency = $currencyQueries->getByCompanyId($location->company_id);

        return view('prints.closed_counter_attempts', [
            'closedCounterAttempts' => $this->getCounterUpdateDeclarationAttempt(
                $counterUpdate->counterUpdateDeclarationAttempts
            ),
            'counter' => $counter->name,
            'location' => $location,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'currencySymbol' => $currency->getSymbol(),
        ])->render();
    }

    public function printCloseCounterTills(Model $counterUpdateDetails): string
    {
        /** @var CounterUpdate $counterUpdate */
        $counterUpdate = $counterUpdateDetails;

        /** @var Counter $counter */
        $counter = $counterUpdate->counter;

        /** @var Location $location */
        $location = $counter->location;

        return view('prints.closed_counter_till', [
            'closedCounterTills' => $this->getCounterUpdateEvents($counterUpdate->counterUpdateEvents),
            'counter' => $counter->name,
            'location' => $location,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
        ])->render();
    }

    public function printCloseCounterTakeBreak(Model $counterUpdateDetails): string
    {
        /** @var CounterUpdate $counterUpdate */
        $counterUpdate = $counterUpdateDetails;

        /** @var Counter $counter */
        $counter = $counterUpdate->counter;

        /** @var Location $location */
        $location = $counter->location;

        return view('prints.take_break', [
            'takeBreakDetails' => $this->getTakeBreakDetails($counterUpdate->counterUpdateEvents),
            'counter' => $counter->name,
            'location' => $location,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
        ])->render();
    }

    public function printCloseCounterDrawerDetails(Model $counterUpdateDetails): string
    {
        /** @var CounterUpdate $counterUpdate */
        $counterUpdate = $counterUpdateDetails;

        /** @var Counter $counter */
        $counter = $counterUpdate->counter;

        /** @var Location $location */
        $location = $counter->location;

        return view('prints.drawer_detail', [
            'drawerDetails' => $this->getDrawerDetails($counterUpdate->counterUpdateEvents),
            'counter' => $counter->name,
            'location' => $location,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
        ])->render();
    }

    public function printCloseCounterTrackOfflineMode(Model $counterUpdateDetails): string
    {
        /** @var CounterUpdate $counterUpdate */
        $counterUpdate = $counterUpdateDetails;

        /** @var Counter $counter */
        $counter = $counterUpdate->counter;

        /** @var Location $location */
        $location = $counter->location;

        return view('prints.track_offline_mode', [
            'trackDetails' => $this->getTrackOfflineModeDetails($counterUpdate->counterUpdateEvents),
            'counter' => $counter->name,
            'location' => $location,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
        ])->render();
    }

    private function getCounterUpdateEvents(Collection $counterUpdateEvents): Collection
    {
        return $counterUpdateEvents->map(function (CounterUpdateEvent $counterUpdateEvent): array {
            $happenedAt = Carbon::createFromFormat('Y-m-d H:i:s', $counterUpdateEvent->happened_at);

            return [
                'type' => CounterUpdateEventTypes::getFormattedCaseName($counterUpdateEvent->type_id),
                'happened_at' => $happenedAt ? $happenedAt->format('d-m-Y h:i:s A') : '',
            ];
        });
    }

    /**
     * @return mixed[]
     */
    private function getCounterUpdateDeclarationAttempt(Collection $counterUpdateDeclarationAttempts): array
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
        )->toArray();
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

    private function getTakeBreakDetails(Collection $counterUpdateEvents): Collection
    {
        $breakDetails = [];
        $previousEvent = null;
        $totalDuration = 0;
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

        return collect($breakDetails);
    }

    private function getDrawerDetails(Collection $counterUpdateEvents): Collection
    {
        $drawerDetails = [];
        $previousEvent = null;
        $totalDuration = 0;
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

        return collect($drawerDetails);
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

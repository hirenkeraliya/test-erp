<?php

declare(strict_types=1);

namespace App\Domains\CounterUpdate\Resources;

use App\Models\Cashier;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\CounterUpdateDeclarationAttemptPayment;
use App\Models\Employee;
use App\Models\Location;
use App\Models\PaymentType;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class ClosedCounterPrintDetailsResource extends JsonResource
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
        $counterUpdate = $this->resource;

        /** @var Collection $counterUpdateDeclarationAttempts */
        $counterUpdateDeclarationAttempts = $counterUpdate->counterUpdateDeclarationAttempts;

        /** @var Counter $counter */
        $counter = $counterUpdate->getCounter();

        /** @var Cashier $cashier */
        $cashier = $counterUpdate->getCashier();

        /** @var Employee $employee */
        $employee = $cashier->getEmployee();

        /** @var Location $location */
        $location = $counter->getLocation();

        $counterAttemptDetails = $counterUpdateDeclarationAttempts->isNotEmpty() ? $this->getCounterUpdateDeclarationAttemptPayments(
            $counterUpdateDeclarationAttempts->last()->counterUpdateDeclarationAttemptPayments
        ) : null;

        return [
            'id' => $counterUpdate->id,
            'counter' => $counter->name,
            'cashier' => $employee->getFullName(),
            'counter_attempt_details' => $counterAttemptDetails instanceof Collection ? $counterAttemptDetails->toArray() : [],
            'grand_total' => $counterAttemptDetails instanceof Collection ? $counterAttemptDetails->sum(
                'calculated_amount'
            ) : 0,
            'location' => $location,
            'date' => Carbon::now()->format('D, j M, Y, g:i A'),
        ];
    }

    private function getCounterUpdateDeclarationAttemptPayments(
        Collection $counterUpdateDeclarationAttemptPayments
    ): Collection {
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
        );
    }
}

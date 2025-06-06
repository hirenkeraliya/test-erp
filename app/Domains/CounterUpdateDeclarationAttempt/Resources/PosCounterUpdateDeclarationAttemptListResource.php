<?php

declare(strict_types=1);

namespace App\Domains\CounterUpdateDeclarationAttempt\Resources;

use App\Models\Cashier;
use App\Models\CounterUpdate;
use App\Models\CounterUpdateDeclarationAttempt;
use App\Models\Employee;
use App\Models\PaymentType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class PosCounterUpdateDeclarationAttemptListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var CounterUpdateDeclarationAttempt $counterUpdateDeclarationAttempt */
        $counterUpdateDeclarationAttempt = $this;

        /** @var Collection $counterUpdateDeclarationAttemptPayments */
        $counterUpdateDeclarationAttemptPayments = $counterUpdateDeclarationAttempt->counterUpdateDeclarationAttemptPayments;

        /** @var CounterUpdate $counterUpdate */
        $counterUpdate = $counterUpdateDeclarationAttempt->counterUpdate;

        /** @var Cashier $cashier */
        $cashier = $counterUpdate->cashier;

        /** @var Employee $employee */
        $employee = $cashier->employee;

        return [
            'counter_name' => $employee->getFullName(),
            'offline_id' => $counterUpdateDeclarationAttempt->offline_id,
            'happened_at' => $counterUpdateDeclarationAttempt->happened_at,
            'payments' => $counterUpdateDeclarationAttemptPayments->map(
                function ($counterUpdateDeclarationAttemptPayment): array {
                    /** @var PaymentType $paymentType */
                    $paymentType = $counterUpdateDeclarationAttemptPayment->paymentType;

                    return [
                        'payment_type_name' => $paymentType->name,
                        'declared_amount' => $counterUpdateDeclarationAttemptPayment->declared_amount,
                        'calculated_amount' => $counterUpdateDeclarationAttemptPayment->calculated_amount,
                        'denominations' => $counterUpdateDeclarationAttemptPayment->denominations ? json_decode(
                            $counterUpdateDeclarationAttemptPayment->denominations,
                            true,
                            512,
                            JSON_THROW_ON_ERROR
                        ) : null,
                    ];
                }
            ),
        ];
    }
}

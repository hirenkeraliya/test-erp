<?php

declare(strict_types=1);

namespace App\Domains\CashMovement\Resources;

use App\Domains\CashMovement\Enums\CashMovementTypes;
use App\Models\CashMovement;
use App\Models\CashMovementReason;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\Director;
use App\Models\Employee;
use App\Models\Location;
use App\Models\StoreManager;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminCashMovementReportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var CashMovement $cashMovement */
        $cashMovement = $this;

        /** @var CounterUpdate $counterUpdate */
        $counterUpdate = $cashMovement->getCounterUpdate();

        /** @var Counter $counter */
        $counter = $counterUpdate->getCounter();

        /** @var Location $location */
        $location = $counter->getLocation();

        $employee = null;

        /** @var StoreManager|Director|null $authorizer */
        $authorizer = $cashMovement->getAuthorizer();

        $employee = $authorizer?->getEmployee();

        /** @var CashMovementReason $cashMovementReason */
        $cashMovementReason = $cashMovement->getCashMovementReason();

        $happenedAt = '';

        if ($cashMovement->getHappenedAt()) {
            /** @var Carbon $happenedAtFormat */
            $happenedAtFormat = Carbon::createFromFormat('Y-m-d H:i:s', $cashMovement->getHappenedAt());
            $happenedAt = $happenedAtFormat->format('d-m-Y h:i:s A');
        }

        return [
            'id' => $cashMovement->getKey(),
            'cash_movement_reason' => $cashMovement->getCashMovementReasonId() ? $cashMovementReason->getReason() : 'N/A',
            'counter_name' => $counter->getName(),
            'location' => $location->getName(),
            'authorizer' => null !== $cashMovement->authorizer_type && $employee instanceof Employee ?
                $cashMovement->authorizer_type . ': ' . $employee->getFullName() : 'SYSTEM GENERATED',
            'other_reason' => $cashMovement->getOtherReason() ?? 'N/A',
            'remarks' => $cashMovement->remarks ?? 'N/A',
            'type' => CashMovementTypes::getFormattedCaseName($cashMovement->getCashMovementTypeId()),
            'amount' => $cashMovement->getAmount(),
            'happened_at' => $happenedAt,
        ];
    }
}

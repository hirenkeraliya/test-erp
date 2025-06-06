<?php

declare(strict_types=1);

namespace App\Domains\CashMovement\Resources;

use App\Domains\CashMovement\Enums\CashMovementTypes;
use App\Models\CashMovement;
use App\Models\CashMovementReason;
use App\Models\Director;
use App\Models\Employee;
use App\Models\StoreManager;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class PosCashMovementResource extends JsonResource
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

        $employee = null;

        /** @var StoreManager|Director|null $authorizer */
        $authorizer = $cashMovement->getAuthorizer();

        $employee = $authorizer?->getEmployee();

        /** @var CashMovementReason $cashMovementReason */
        $cashMovementReason = $cashMovement->getCashMovementReason();

        /** @var Collection $cashMovementMismatches */
        $cashMovementMismatches = $cashMovement->mismatches;
        $messages = $cashMovementMismatches->pluck('message')->toArray();

        /** @var Carbon $createdAt */
        $createdAt = $cashMovement->created_at;

        return [
            'id' => $cashMovement->getKey(),
            'offline_id' => $cashMovement->offline_id,
            'cash_movement_reason' => $cashMovement->getCashMovementReasonId() ? $cashMovementReason->getReason() : 'N/A',
            'authorizer' => null !== $cashMovement->authorizer_type && $employee instanceof Employee ?
                $cashMovement->authorizer_type . ': ' . $employee->getFullName() : null,
            'other_reason' => $cashMovement->getOtherReason() ?? 'N/A',
            'type' => CashMovementTypes::getCaseNameByValue($cashMovement->getCashMovementTypeId()),
            'amount' => $cashMovement->getAmount(),
            'remarks' => $cashMovement->remarks,
            'created_at' => $createdAt->format('Y-m-d H:i:s'),
            'happened_at' => $cashMovement->happened_at,
            'mismatches' => $messages,
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Domains\CashMovementReason\Resources;

use App\Domains\CashMovement\Enums\CashMovementTypes;
use App\Models\CashMovementReason;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PosCashMovementReasonListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var CashMovementReason $cashMovementReason */
        $cashMovementReason = $this;

        return [
            'id' => $cashMovementReason->id,
            'reason' => $cashMovementReason->reason,
            'type_id' => $cashMovementReason->type_id,
            'type' => CashMovementTypes::getCaseNameByValue($cashMovementReason->type_id),
        ];
    }
}

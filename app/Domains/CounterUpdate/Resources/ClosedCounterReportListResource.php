<?php

declare(strict_types=1);

namespace App\Domains\CounterUpdate\Resources;

use App\Models\Cashier;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\Employee;
use App\Models\Location;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class ClosedCounterReportListResource extends JsonResource
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

        /** @var Counter $counter */
        $counter = $counterUpdate->getCounter();
        $counter['store_id'] = $counter->location_id;
        $counter['store'] = $counter->location;

        /** @var Location $location */
        $location = $counter->getLocation();

        /** @var Cashier $cashier */
        $cashier = $counterUpdate->getCashier();

        /** @var Employee $employee */
        $employee = $cashier->getEmployee();

        /** @var Collection $counterUpdateDeclarationAttempts */
        $counterUpdateDeclarationAttempts = $counterUpdate->counterUpdateDeclarationAttempts;

        /** @var string $openedDate */
        $openedDate = $counterUpdate->created_at;
        if ($counterUpdate->opened_by_pos_at) {
            $openedDate = $counterUpdate->opened_by_pos_at;
        }

        /** @var Carbon $openedAt */
        $openedAt = Carbon::createFromFormat('Y-m-d H:i:s', $openedDate);

        /** @var Carbon|string $closedAt */
        $closedAt = 'N/A';

        if ($counterUpdate->closed_at) {
            /** @var Carbon $closedAtFormat */
            $closedAtFormat = Carbon::createFromFormat('Y-m-d H:i:s', $counterUpdate->closed_at);
            $closedAt = $closedAtFormat->format('d-m-Y h:i:s A');
        }

        return [
            'id' => $counterUpdate->id,
            'counter' => $counter,
            'cashier_name' => $employee->getFullName(),
            'store_name' => $location->name,
            'location_name' => $location->name,
            'opening_balance' => (float) $counterUpdate->opening_balance,
            'closing_balance' => (float) $counterUpdate->closing_balance,
            'mismatch_amount' => (float) $counterUpdate->mismatch_amount,
            'reason' => $counterUpdate->amount_mismatch_reason,
            'opened_at' => $openedAt->format('d-m-Y h:i:s A'),
            'closed_at' => $closedAt,
            'sales_collection_amount' => $counterUpdate->sales_collection_amount,
            'counter_update_declaration_attempt' => ! $counterUpdateDeclarationAttempts->isEmpty(),
            'attempt_count' => $counterUpdateDeclarationAttempts->count(),
        ];
    }
}

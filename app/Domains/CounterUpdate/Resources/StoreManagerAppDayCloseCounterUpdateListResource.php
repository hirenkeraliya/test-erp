<?php

declare(strict_types=1);

namespace App\Domains\CounterUpdate\Resources;

use App\Domains\CounterUpdate\Enums\CounterStatus;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\Location;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StoreManagerAppDayCloseCounterUpdateListResource extends JsonResource
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

        /** @var Location $location */
        $location = $counter->getLocation();

        $closedAt = 'N/A';

        $closedCounterAt = $counterUpdate->closed_by_pos_at ?? $counterUpdate->closed_at;
        $status = CounterStatus::OPEN->name;
        if ($closedCounterAt) {
            /** @var Carbon $closedAtFormat */
            $closedAtFormat = Carbon::createFromFormat('Y-m-d H:i:s', $closedCounterAt);
            $closedAt = $closedAtFormat->format('d-m-Y h:i:s A');
            $status = CounterStatus::CLOSE->name;
        }

        $openedAt = '';

        if ($counterUpdate->opened_by_pos_at) {
            /** @var Carbon $openedAtFormat */
            $openedAtFormat = Carbon::createFromFormat('Y-m-d H:i:s', $counterUpdate->opened_by_pos_at);
            $openedAt = $openedAtFormat->format('d-m-Y h:i:s A');
        }

        /** @var Carbon $createdAt */
        $createdAt = $counterUpdate->created_at;

        return [
            'id' => $counterUpdate->id,
            'counter_name' => $counter->getName(),
            'store_name' => $location->name,
            'location_name' => $location->name,
            'status' => $status,
            'opening_balance' => $counterUpdate->opening_balance,
            'closing_balance' => $counterUpdate->closing_balance,
            'closed_at' => $closedAt,
            'created_at' => $openedAt ?: $createdAt->format('d-m-Y h:i:s A'),
        ];
    }
}

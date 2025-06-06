<?php

declare(strict_types=1);

namespace App\Domains\CounterUpdateEvent\Resources;

use App\Domains\CounterUpdateEvent\Enums\CounterUpdateEventTypes;
use App\Models\CounterUpdateEvent;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PosCounterUpdateEventListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var CounterUpdateEvent $counterUpdateEvent */
        $counterUpdateEvent = $this;

        $happenedAtFormat = Carbon::createFromFormat('Y-m-d H:i:s', $counterUpdateEvent->happened_at);

        return [
            'offline_id' => $counterUpdateEvent->offline_id,
            'counter_update_id' => $counterUpdateEvent->counter_update_id,
            'type' => $counterUpdateEvent->type_id ? CounterUpdateEventTypes::getFormattedArrayForPos(
                $counterUpdateEvent->type_id
            ) : null,
            'happened_at' => $happenedAtFormat ? $happenedAtFormat->format('Y-m-d H:i:s') : '',
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Domains\CounterUpdateEvent;

use App\Domains\CounterUpdateEvent\DataObjects\CounterUpdateEventData;
use App\Models\CounterUpdateEvent;
use Illuminate\Support\Collection;

class CounterUpdateEventQueries
{
    public function getList(int $counterUpdateId, ?string $afterUpdatedAt = null): Collection
    {
        return CounterUpdateEvent::select('id', 'offline_id', 'counter_update_id', 'type_id', 'happened_at')
            ->where('counter_update_id', $counterUpdateId)
            ->when($afterUpdatedAt, function ($query) use ($afterUpdatedAt): void {
                $query->where('updated_at', '>=', $afterUpdatedAt);
            })
            ->get();
    }

    public function addNew(CounterUpdateEventData $counterUpdateEventData, int $counterUpdateId): CounterUpdateEvent
    {
        $data = $counterUpdateEventData->all();
        $data['counter_update_id'] = $counterUpdateId;
        unset($data['product_id']);

        $counterUpdateEvent = CounterUpdateEvent::create($data);

        $this->attachRelation($counterUpdateEventData, $counterUpdateEvent);

        return $counterUpdateEvent;
    }

    public function getBasicColumnNames(): string
    {
        return 'id,offline_id,counter_update_id,type_id,happened_at';
    }

    private function attachRelation(
        CounterUpdateEventData $counterUpdateEventData,
        CounterUpdateEvent $counterUpdateEvent
    ): void {
        if (! $counterUpdateEventData->product_id) {
            return;
        }

        $counterUpdateEvent->products()->attach($counterUpdateEventData->product_id);
    }
}

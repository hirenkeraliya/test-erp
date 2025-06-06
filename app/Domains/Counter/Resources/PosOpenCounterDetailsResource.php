<?php

declare(strict_types=1);

namespace App\Domains\Counter\Resources;

use App\Models\Counter;
use App\Models\CounterUpdate;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PosOpenCounterDetailsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var Counter $counter */
        $counter = $this;

        /** @var CounterUpdate $counterUpdate */
        $counterUpdate = $counter->counterUpdate;

        $openedAt = '';

        if ($counterUpdate->opened_by_pos_at) {
            /** @var Carbon $openedAtFormat */
            $openedAtFormat = Carbon::createFromFormat('Y-m-d H:i:s', $counterUpdate->opened_by_pos_at);
            $openedAt = $openedAtFormat->format('Y-m-d H:i:s');
        }

        return [
            'id' => $counter->getKey(),
            'counter_update_id' => $counterUpdate->getKey(),
            'name' => $counter->getName(),
            'is_locked' => $counter->is_locked,
            'opened_at' => $openedAt,
            'is_self_checkout' => $counter->is_self_checkout,
        ];
    }
}

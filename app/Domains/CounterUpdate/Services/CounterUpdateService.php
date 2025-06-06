<?php

declare(strict_types=1);

namespace App\Domains\CounterUpdate\Services;

use App\Domains\CounterUpdate\CounterUpdateQueries;
use App\Domains\CounterUpdate\Resources\ClosedCounterUpdateResource;
use App\Models\CounterUpdate;
use Carbon\Carbon;

class CounterUpdateService
{
    /**
     * @return mixed[]
     */
    public function prepareCounterDetails(CounterUpdate $counterUpdate, int $locationId): array
    {
        if (null === $counterUpdate->closed_at) {
            $closeCounterService = resolve(CloseCounterService::class);

            return array_merge([
                'closed_at' => 'N/A',
            ], $closeCounterService->prepareAndReturnCounterClosingDetails($counterUpdate));
        }

        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $counterUpdate = $counterUpdateQueries->findByIdWithRelationsFilterByStore($locationId, $counterUpdate->id);

        /** @var CounterUpdate $counterUpdate */
        /** @var Carbon|string $closedAt */
        $closedAt = 'N/A';

        if ($counterUpdate->closed_at) {
            /** @var Carbon $closedAtFormat */
            $closedAtFormat = Carbon::createFromFormat('Y-m-d H:i:s', $counterUpdate->closed_at);
            $closedAt = $closedAtFormat->format('d-m-Y h:i:s A');
        }

        $closeCounterDetails = new ClosedCounterUpdateResource($counterUpdate);
        $closeCounterDetails = json_decode($closeCounterDetails->toJson(), true, 512, JSON_THROW_ON_ERROR);

        return array_merge([
            'closed_at' => $closedAt,
            'denominations' => $counterUpdate->denominations->map(fn ($denomination): array => [
                'denomination' => $denomination->denomination,
                'denomination_quantity' => $denomination->quantity,
            ]),
        ], $closeCounterDetails);
    }
}

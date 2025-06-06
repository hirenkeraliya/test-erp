<?php

declare(strict_types=1);

namespace App\Domains\PromoterCommissionRegeneration;

use App\Models\PromoterCommissionRegeneration;

class PromoterCommissionRegenerationQueries
{
    public function addNew(array $promoterCommissionRegenerationDetails): PromoterCommissionRegeneration
    {
        return PromoterCommissionRegeneration::create($promoterCommissionRegenerationDetails);
    }

    public function entryExistsForPeriod(string $date): bool
    {
        return PromoterCommissionRegeneration::where('period', $date)->whereNull('completed_at')->exists();
    }

    public function markAsCompleted(string $completedAt): void
    {
        $promoterCommissionRegenerations = PromoterCommissionRegeneration::whereNull('completed_at')->get();

        foreach ($promoterCommissionRegenerations as $promoterCommissionRegeneration) {
            $promoterCommissionRegeneration->update([
                'completed_at' => $completedAt,
            ]);
        }
    }

    public function markAsStarted(int $promoterCommissionRegenerationId, string $startedAt): void
    {
        $promoterCommissionRegeneration = PromoterCommissionRegeneration::query()
            ->select('id', 'started_at')
            ->findOrFail($promoterCommissionRegenerationId);

        $promoterCommissionRegeneration->started_at = $startedAt;
        $promoterCommissionRegeneration->save();
    }
}

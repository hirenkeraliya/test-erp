<?php

declare(strict_types=1);

namespace App\Domains\SaleTargetTimeframe;

use App\Domains\Location\LocationQueries;
use App\Domains\Promoter\PromoterQueries;
use App\Domains\SaleAchievedTarget\SaleAchievedTargetQueries;
use App\Domains\SaleTarget\SaleTargetQueries;
use App\Models\SaleTarget;
use App\Models\SaleTargetTimeframe;
use Closure;
use Illuminate\Support\Collection;

class SaleTargetTimeframeQueries
{
    public function addNew(array $saleTargetTimeFrameData): void
    {
        SaleTargetTimeframe::create($saleTargetTimeFrameData);
    }

    public function deleteBySaleTarget(SaleTarget $saleTarget): void
    {
        $saleTarget->saleTargetTimeframes()->delete();
    }

    public function getByStartAndEndDate(): Collection
    {
        $saleTargetQueries = resolve(SaleTargetQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $promoterQueries = resolve(PromoterQueries::class);
        $saleAchievedTargetQueries = resolve(SaleAchievedTargetQueries::class);

        return SaleTargetTimeframe::query()
            ->select('id', 'sale_target_id', 'start_date', 'end_date', 'target_label', 'amount')
            ->with([
                'saleTarget:' . $saleTargetQueries->getBasicColumnNames(),
                'saleTarget.locations:' . $locationQueries->getNameColumnName(),
                'saleTarget.promoters:' . $promoterQueries->getBasicColumnNames(),
                'saleAchievedTargets:' . $saleAchievedTargetQueries->getBasicColumnNames(),
            ])
            ->where('start_date', '<', now()->format('Y-m-d'))
            ->where('end_date', '>=', now()->subDay()->format('Y-m-d'))
            ->whereHas('saleTarget', $saleTargetQueries->filterByStatus())
            ->get();
    }

    public function getById(int $id, int $companyId): SaleTargetTimeframe
    {
        $saleTargetQueries = resolve(SaleTargetQueries::class);

        return SaleTargetTimeframe::select('id', 'start_date', 'end_date', 'target_label')
            ->whereHas('saleTarget', $saleTargetQueries->filterByCompany($companyId))
            ->findOrFail($id);
    }

    public function getByIds(array $ids, int $companyId): Collection
    {
        $saleTargetQueries = resolve(SaleTargetQueries::class);

        return SaleTargetTimeframe::select('id', 'start_date', 'end_date', 'target_label')
            ->whereHas('saleTarget', $saleTargetQueries->filterByCompany($companyId))
            ->whereIntegerInRaw('id', $ids)
            ->orderBy('id')
            ->get();
    }

    public function getBySaleTargetId(int $saleTargetId): Collection
    {
        $saleTargetQueries = resolve(SaleTargetQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $promoterQueries = resolve(PromoterQueries::class);
        $saleAchievedTargetQueries = resolve(SaleAchievedTargetQueries::class);

        return SaleTargetTimeframe::query()
            ->select('id', 'sale_target_id', 'start_date', 'end_date', 'target_label', 'amount')
            ->with([
                'saleTarget:' . $saleTargetQueries->getBasicColumnNames(),
                'saleTarget.locations:' . $locationQueries->getNameColumnName(),
                'saleTarget.promoters:' . $promoterQueries->getBasicColumnNames(),
                'saleAchievedTargets:' . $saleAchievedTargetQueries->getBasicColumnNames(),
            ])
            ->whereHas('saleTarget', $saleTargetQueries->filterByIdAndStatus($saleTargetId))
            ->get();
    }

    public function getBasicColumnNames(): string
    {
        return 'id,sale_target_id,start_date,end_date,target_label,amount,percentage';
    }

    public function filterByCompany(int $companyId): Closure
    {
        $saleTargetQueries = resolve(SaleTargetQueries::class);

        return fn ($query) => $query->whereHas('saleTarget', $saleTargetQueries->filterByCompany($companyId));
    }

    public function refresh(SaleTargetTimeframe $saleTargetTimeframe): SaleTargetTimeframe
    {
        $saleTargetQueries = resolve(SaleTargetQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $promoterQueries = resolve(PromoterQueries::class);
        $saleAchievedTargetQueries = resolve(SaleAchievedTargetQueries::class);

        return $saleTargetTimeframe
            ->load([
                'saleTarget:' . $saleTargetQueries->getBasicColumnNames(),
                'saleTarget.locations:' . $locationQueries->getNameColumnName(),
                'saleTarget.promoters:' . $promoterQueries->getBasicColumnNames(),
                'saleAchievedTargets:' . $saleAchievedTargetQueries->getBasicColumnNames(),
            ]);
    }
}

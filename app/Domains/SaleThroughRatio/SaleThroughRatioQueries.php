<?php

declare(strict_types=1);

namespace App\Domains\SaleThroughRatio;

use App\Domains\SaleThroughRatio\DataObjects\SaleThroughRatioData;
use App\Models\SaleThroughRatio;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class SaleThroughRatioQueries
{
    public function listQuery(array $filterData, int $companyId): LengthAwarePaginator
    {
        return $this->saleThroughRatioQuery($filterData, $companyId)->paginate($filterData['per_page']);
    }

    public function filterByCompany(int $companyId): Closure
    {
        return fn ($query) => $query->where('company_id', $companyId);
    }

    public function addNew(SaleThroughRatioData $saleThroughRatioData, int $companyId): void
    {
        $saleThroughRatioDetails = $saleThroughRatioData->all();
        $saleThroughRatioDetails['company_id'] = $companyId;
        SaleThroughRatio::create($saleThroughRatioDetails);
    }

    public function getById(int $saleThroughRatioId, int $companyId): SaleThroughRatio
    {
        return SaleThroughRatio::select('id', 'name', 'percentage', 'description')
            ->where(function ($query) use ($companyId): void {
                $query->where('company_id', $companyId)
                    ->orWhereNull('company_id');
            })
            ->findOrFail($saleThroughRatioId);
    }

    public function getCachedPercentageAndName(int $companyId, bool $orderByPercentage = true): Collection
    {
        return Cache::remember(
            'key',
            now()->addMinutes(20),
            fn () => SaleThroughRatio::select('id', 'name', 'percentage', 'description')
                ->where(function ($query) use ($companyId): void {
                    $query->where('company_id', $companyId)
                        ->orWhereNull('company_id');
                })
                ->when($orderByPercentage, function ($query): void {
                    $query->orderBy('percentage');
                })
                ->get()
        );
    }

    public function update(
        SaleThroughRatioData $saleThroughRatioData,
        int $saleThroughRatioId,
        int $companyId
    ): void {
        $saleThroughRatio = $this->getById($saleThroughRatioId, $companyId);
        $saleThroughRatio->update($saleThroughRatioData->all());
    }

    public function getSaleThroughRatiosExport(array $filterData, int $companyId): Collection
    {
        return $this->saleThroughRatioQuery($filterData, $companyId)->get();
    }

    private function saleThroughRatioQuery(array $filterData, int $companyId): Builder
    {
        return SaleThroughRatio::query()
            ->select('id', 'name', 'percentage', 'company_id')
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query->where('name', 'like', '%' . $filterData['search_text'] . '%');
                });
            })
            ->where(function ($query) use ($companyId): void {
                $query->where('company_id', $companyId)
                    ->orWhereNull('company_id');
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            });
    }

    public function getGradeNameForFilter(int $id): ?string
    {
        $saleThroughRatio = SaleThroughRatio::select('name')
            ->where('id', $id)
            ->first();

        if ($saleThroughRatio) {
            return $saleThroughRatio->name;
        }

        return null;
    }
}

<?php

declare(strict_types=1);

namespace App\Domains\SaleSeason;

use App\Domains\SaleSeason\DataObjects\SaleSeasonData;
use App\Models\SaleSeason;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class SaleSeasonQueries
{
    public function listQuery(array $filterData, int $companyId): LengthAwarePaginator
    {
        return $this->getSaleSeason($filterData, $companyId)->paginate($filterData['per_page']);
    }

    public function addNew(SaleSeasonData $saleSeasonData, int $companyId): void
    {
        $data = $saleSeasonData->all();
        $data['company_id'] = $companyId;
        SaleSeason::create($data);
    }

    public function getById(int $saleSeasonId, int $companyId): SaleSeason
    {
        return SaleSeason::select('id', 'name', 'start_date', 'end_date')
            ->where('company_id', $companyId)
            ->findOrFail($saleSeasonId);
    }

    public function update(SaleSeasonData $saleSeasonData, int $saleSeasonId, int $companyId): void
    {
        $saleSeason = $this->getById($saleSeasonId, $companyId);
        $saleSeason->update($saleSeasonData->all());
    }

    public function filterByCompany(int $companyId): Closure
    {
        return fn ($query) => $query->where('company_id', $companyId);
    }

    public function delete(int $saleSeasonId, int $companyId): void
    {
        $saleSeason = $this->getById($saleSeasonId, $companyId);
        $saleSeason->delete();
    }

    public function getWithBasicColumns(int $companyId): Collection
    {
        return SaleSeason::query()
            ->select('id', 'name', 'start_date', 'end_date')
            ->where('company_id', $companyId)
            ->get();
    }

    private function getSaleSeason(array $filterData, int $companyId): Builder
    {
        return SaleSeason::query()
            ->select('id', 'name', 'start_date', 'end_date')
            ->where('company_id', $companyId)
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query->where('name', 'like', '%' . $filterData['search_text'] . '%');
                });
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            });
    }
}

<?php

declare(strict_types=1);

namespace App\Domains\Denomination;

use App\Domains\Denomination\DataObjects\DenominationData;
use App\Models\Denomination;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class DenominationQueries
{
    public function listQuery(array $filterData, int $companyId): LengthAwarePaginator
    {
        return $this->denominationQuery($filterData, $companyId)->paginate($filterData['per_page']);
    }

    public function addNew(DenominationData $denominationData, int $companyId): void
    {
        $data = $denominationData->all();
        $data['company_id'] = $companyId;

        Denomination::create($data);
    }

    public function getById(int $denominationId, int $companyId): Denomination
    {
        return Denomination::select('id', 'denomination')
            ->where('company_id', $companyId)
            ->findOrFail($denominationId);
    }

    public function update(DenominationData $denominationData, int $denominationId, int $companyId): void
    {
        $denomination = $this->getById($denominationId, $companyId);
        $denomination->update($denominationData->all());
    }

    public function getList(int $companyId, ?string $afterUpdatedAt = null): Collection
    {
        return Denomination::select('id', 'denomination')
            ->where('company_id', $companyId)
            ->when($afterUpdatedAt, function ($query) use ($afterUpdatedAt): void {
                $query->where('updated_at', '>=', $afterUpdatedAt);
            })
            ->get();
    }

    public function getByCompanyId(int $companyId): Collection
    {
        return Denomination::select('denomination')
            ->where('company_id', $companyId)
            ->get();
    }

    public function delete(int $denominationId, int $companyId): void
    {
        $denomination = $this->getById($denominationId, $companyId);
        $denomination->delete();
    }

    public function getDenominationsExport(array $filterData, int $companyId): Collection
    {
        return $this->denominationQuery($filterData, $companyId)->get();
    }

    private function denominationQuery(array $filterData, int $companyId): Builder
    {
        return Denomination::query()
            ->select('id', 'denomination')
            ->where('company_id', $companyId)
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query->where('denomination', 'like', '%' . $filterData['search_text'] . '%');
                });
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            });
    }
}

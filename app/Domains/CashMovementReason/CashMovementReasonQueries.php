<?php

declare(strict_types=1);

namespace App\Domains\CashMovementReason;

use App\Domains\CashMovementReason\DataObjects\CashMovementReasonData;
use App\Models\CashMovementReason;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class CashMovementReasonQueries
{
    public function listQuery(array $filterData, int $companyId): LengthAwarePaginator
    {
        return $this->cashMovementReasonQuery($filterData, $companyId)->paginate($filterData['per_page']);
    }

    public function addNew(CashMovementReasonData $cashMovementReasonData, int $companyId): void
    {
        $data = $cashMovementReasonData->all();
        $data['company_id'] = $companyId;

        CashMovementReason::create($data);
    }

    public function getById(int $cashMovementReasonId, int $companyId): CashMovementReason
    {
        return CashMovementReason::select('id', 'reason', 'type_id')
            ->where(function ($query) use ($companyId): void {
                $query->where('company_id', $companyId)
                    ->orWhereNull('company_id');
            })
            ->findOrFail($cashMovementReasonId);
    }

    public function update(
        CashMovementReasonData $cashMovementReasonData,
        int $cashMovementReasonId,
        int $companyId
    ): void {
        $cashMovementReason = $this->getById($cashMovementReasonId, $companyId);
        $cashMovementReason->update($cashMovementReasonData->all());
    }

    public function filterByCompany(int $companyId): Closure
    {
        return fn ($query) => $query->where('company_id', $companyId)->orWhereNull('company_id');
    }

    public function getList(int $companyId, ?string $afterUpdatedAt = null): Collection
    {
        return CashMovementReason::select('id', 'reason', 'type_id')
            ->where(function ($query) use ($companyId): void {
                $query->where('company_id', $companyId)
                    ->orWhereNull('company_id');
            })
            ->when($afterUpdatedAt, function ($query) use ($afterUpdatedAt): void {
                $query->where('updated_at', '>=', $afterUpdatedAt);
            })
            ->get();
    }

    public function getBasicColumnNames(): string
    {
        return 'id,reason';
    }

    public function searchByReason(string $searchText): Closure
    {
        return fn ($query) => $query->select('id')->where('reason', 'like', '%' . $searchText . '%');
    }

    public function getCashMovementReasonsExport(array $filterData, int $companyId): Collection
    {
        return $this->cashMovementReasonQuery($filterData, $companyId)->get();
    }

    private function cashMovementReasonQuery(array $filterData, int $companyId): Builder
    {
        return CashMovementReason::query()
            ->select('id', 'reason', 'type_id', 'company_id')
            ->where(function ($query) use ($companyId): void {
                $query->where('company_id', $companyId)
                    ->orWhereNull('company_id');
            })
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query->where('reason', 'like', '%' . $filterData['search_text'] . '%');
                });
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            });
    }
}

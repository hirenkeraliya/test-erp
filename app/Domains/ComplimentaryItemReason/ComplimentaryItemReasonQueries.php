<?php

declare(strict_types=1);

namespace App\Domains\ComplimentaryItemReason;

use App\Domains\ComplimentaryItemReason\DataObjects\ComplimentaryItemReasonData;
use App\Domains\SaleDiscount\SaleDiscountQueries;
use App\Domains\SaleItemDiscount\SaleItemDiscountQueries;
use App\Models\ComplimentaryItemReason;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ComplimentaryItemReasonQueries
{
    public function listQuery(array $filterData, int $companyId): LengthAwarePaginator
    {
        return $this->complimentaryItemReasonQuery($filterData, $companyId)->paginate($filterData['per_page']);
    }

    public function filterByCompany(int $companyId): Closure
    {
        return fn ($query) => $query->where('company_id', $companyId);
    }

    public function addNew(ComplimentaryItemReasonData $complimentaryItemReasonData, int $companyId): void
    {
        $complimentaryItemReasonDetails = $complimentaryItemReasonData->all();
        $complimentaryItemReasonDetails['company_id'] = $companyId;
        ComplimentaryItemReason::create($complimentaryItemReasonDetails);
    }

    public function getById(int $complimentaryItemReasonId, int $companyId): ComplimentaryItemReason
    {
        return ComplimentaryItemReason::select('id', 'reason')
            ->where('company_id', $companyId)
            ->findOrFail($complimentaryItemReasonId);
    }

    public function update(
        ComplimentaryItemReasonData $complimentaryItemReasonData,
        int $complimentaryItemReasonId,
        int $companyId
    ): void {
        $complimentaryItemReason = $this->getById($complimentaryItemReasonId, $companyId);
        $complimentaryItemReason->update($complimentaryItemReasonData->all());
    }

    public function getList(int $companyId, ?string $afterUpdatedAt = null): Collection
    {
        return ComplimentaryItemReason::select('id', 'reason')
            ->where('company_id', $companyId)
            ->when($afterUpdatedAt, function ($query) use ($afterUpdatedAt): void {
                $query->where('updated_at', '>=', $afterUpdatedAt);
            })
            ->get();
    }

    public function getByIdsAndCompanyId(array $ids, int $companyId): Collection
    {
        return ComplimentaryItemReason::select('id', 'reason')
            ->where('company_id', $companyId)
            ->whereIntegerInRaw('id', $ids)
            ->get();
    }

    public function getComplimentaryItemReasonsExport(array $filterData, int $companyId): Collection
    {
        return $this->complimentaryItemReasonQuery($filterData, $companyId)->get();
    }

    public function getBasicColumnNames(): string
    {
        return 'id,company_id,reason';
    }

    private function complimentaryItemReasonQuery(array $filterData, int $companyId): Builder
    {
        $saleDiscountQueries = resolve(SaleDiscountQueries::class);
        $saleItemDiscountQueries = resolve(SaleItemDiscountQueries::class);

        return ComplimentaryItemReason::query()
            ->select('id', 'reason')
            ->with([
                'saleDiscountComplimentaryItemReason:' . $saleDiscountQueries->getBasicColumnNames(),
                'saleItemDiscountComplimentaryItemReason:' . $saleItemDiscountQueries->getBasicColumnNames(),
            ])
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query->where('reason', 'like', '%' . $filterData['search_text'] . '%');
                });
            })
            ->where('company_id', $companyId)
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            });
    }

    public function getSeasonalSalesBasicColumns(): Closure
    {
        return fn ($query) => $query->select('id', 'reason');
    }
}

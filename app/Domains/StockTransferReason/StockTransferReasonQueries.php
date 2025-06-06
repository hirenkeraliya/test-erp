<?php

declare(strict_types=1);

namespace App\Domains\StockTransferReason;

use App\Domains\StockTransferReason\DataObjects\StockTransferReasonData;
use App\Models\StockTransferReason;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection as SupportCollection;

class StockTransferReasonQueries
{
    public function listQuery(array $filterData, int $companyId): LengthAwarePaginator
    {
        return $this->stockTransferReasonQuery($filterData, $companyId)->paginate($filterData['per_page']);
    }

    public function addNew(StockTransferReasonData $stockTransferReasonData, int $companyId): void
    {
        $data = $stockTransferReasonData->all();
        $data['company_id'] = $companyId;
        StockTransferReason::create($data);
    }

    public function getById(int $stockTransferReasonId, int $companyId): StockTransferReason
    {
        return StockTransferReason::select('id', 'name', 'code')
            ->where('company_id', $companyId)
            ->findOrFail($stockTransferReasonId);
    }

    public function update(
        StockTransferReasonData $stockTransferReasonData,
        int $stockTransferReasonId,
        int $companyId
    ): void {
        $stockTransferReason = $this->getById($stockTransferReasonId, $companyId);
        $stockTransferReason->update($stockTransferReasonData->all());
    }

    public function filterByCompany(int $companyId): Closure
    {
        return fn ($query) => $query->where('company_id', $companyId);
    }

    public function getStockTransferReasons(int $companyId): SupportCollection
    {
        return StockTransferReason::query()
            ->select('id', 'name')
            ->where('company_id', $companyId)
            ->get();
    }

    public function getBasicColumn(): string
    {
        return 'id,name';
    }

    public function getStockTransferReasonsExport(array $filterData, int $companyId): SupportCollection
    {
        return $this->stockTransferReasonQuery($filterData, $companyId)->get();
    }

    private function stockTransferReasonQuery(array $filterData, int $companyId): Builder
    {
        return StockTransferReason::query()
            ->select('id', 'name', 'code')
            ->where('company_id', $companyId)
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query
                        ->whereAny(['name', 'code'], 'LIKE', '%' . $filterData['search_text'] . '%');
                });
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            });
    }
}

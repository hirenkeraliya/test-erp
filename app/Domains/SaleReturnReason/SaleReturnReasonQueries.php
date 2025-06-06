<?php

declare(strict_types=1);

namespace App\Domains\SaleReturnReason;

use App\Domains\Common\Enums\SaleReturnOrVoidSaleReasonTypes;
use App\Domains\SaleReturnReason\DataObjects\SaleReturnReasonData;
use App\Models\SaleReturnReason;
use App\Models\SaleReturnReasonType;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class SaleReturnReasonQueries
{
    public function listQuery(array $filterData, int $companyId): LengthAwarePaginator
    {
        return $this->saleReturnReasonQuery($filterData, $companyId)->paginate($filterData['per_page']);
    }

    public function addNew(SaleReturnReasonData $saleReturnReasonData, int $companyId): void
    {
        $data = $saleReturnReasonData->all();

        $data['company_id'] = $companyId;
        unset($data['type_ids']);
        $saleReturnReason = SaleReturnReason::create($data);

        $this->updateSaleReturnReasonType($saleReturnReason, $saleReturnReasonData);
    }

    public function getById(int $saleReturnReasonId, int $companyId): SaleReturnReason
    {
        return SaleReturnReason::select('id', 'reason', 'location_id', 'put_back_in_inventory')
            ->with('saleReturnReasonTypes:id,sale_return_reason_id,type_id')
            ->where('company_id', $companyId)
            ->findOrFail($saleReturnReasonId);
    }

    public function getByIdsAndCompanyId(array $saleReturnReasonIds, int $companyId): Collection
    {
        return SaleReturnReason::select('id', 'reason', 'location_id', 'put_back_in_inventory')
            ->where('company_id', $companyId)
            ->whereIntegerInRaw('id', $saleReturnReasonIds)
            ->get();
    }

    public function update(
        SaleReturnReasonData $saleReturnReasonData,
        int $saleReturnReasonId,
        int $companyId
    ): void {
        $data = $saleReturnReasonData->all();
        unset($data['type_ids']);
        $saleReturnReason = $this->getById($saleReturnReasonId, $companyId);
        $saleReturnReason->update($data);
        $this->updateSaleReturnReasonType($saleReturnReason, $saleReturnReasonData);
    }

    public function getListForPOSOrOrders(
        int $companyId,
        int $typeId = SaleReturnOrVoidSaleReasonTypes::POS->value,
        ?string $afterUpdatedAt = null
    ): Collection {
        return SaleReturnReason::select('id', 'reason')
            ->where('company_id', $companyId)
            ->whereHas('saleReturnReasonTypes', function ($query) use ($typeId): void {
                $query->select('id', 'type_id', 'sale_return_reason_id')
                    ->where('type_id', $typeId);
            })
            ->when($afterUpdatedAt, function ($query) use ($afterUpdatedAt): void {
                $query->where('updated_at', '>=', $afterUpdatedAt);
            })
            ->get();
    }

    public function filterByCompany(int $companyId): Closure
    {
        return fn ($query) => $query->where('company_id', $companyId);
    }

    public function getBasicColumnNames(): string
    {
        return 'id,reason,put_back_in_inventory';
    }

    public function getSaleReturnReasonsExport(array $filterData, int $companyId): Collection
    {
        return $this->saleReturnReasonQuery($filterData, $companyId)->get();
    }

    public function getByIdsAndCompanyIdForOrderReturn(array $saleReturnReasonIds, int $companyId): Collection
    {
        return SaleReturnReason::select('id', 'reason', 'location_id', 'put_back_in_inventory')
            ->where('company_id', $companyId)
            ->whereHas('saleReturnReasonTypes', function ($query): void {
                $query->select('id', 'type_id', 'sale_return_reason_id')
                    ->where('type_id', SaleReturnOrVoidSaleReasonTypes::ORDERS->value);
            })
            ->whereIntegerInRaw('id', $saleReturnReasonIds)
            ->get();
    }

    private function saleReturnReasonQuery(array $filterData, int $companyId): Builder
    {
        return SaleReturnReason::query()
            ->select('id', 'reason', 'put_back_in_inventory')
            ->where('company_id', $companyId)
            ->with('saleReturnReasonTypes:id,sale_return_reason_id,type_id')
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

    private function updateSaleReturnReasonType(
        SaleReturnReason $saleReturnReason,
        SaleReturnReasonData $saleReturnReasonData
    ): void {
        $saleReturnReason->saleReturnReasonTypes()->delete();
        foreach ($saleReturnReasonData->type_ids as $typeId) {
            SaleReturnReasonType::create([
                'sale_return_reason_id' => $saleReturnReason->id,
                'type_id' => $typeId,
            ]);
        }
    }
}

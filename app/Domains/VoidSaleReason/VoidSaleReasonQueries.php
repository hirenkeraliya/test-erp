<?php

declare(strict_types=1);

namespace App\Domains\VoidSaleReason;

use App\Domains\Common\Enums\SaleReturnOrVoidSaleReasonTypes;
use App\Domains\VoidSaleReason\DataObjects\VoidSaleReasonData;
use App\Models\VoidSaleReason;
use App\Models\VoidSaleReasonType;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class VoidSaleReasonQueries
{
    public function listQuery(array $filterData, int $companyId): LengthAwarePaginator
    {
        return $this->voidSaleReasonQuery($filterData, $companyId)->paginate($filterData['per_page']);
    }

    public function filterByCompany(int $companyId): Closure
    {
        return fn ($query) => $query->select('id')->where('company_id', $companyId);
    }

    public function addNew(VoidSaleReasonData $voidSaleReasonData, int $companyId): void
    {
        $data = $voidSaleReasonData->all();
        $data['company_id'] = $companyId;

        unset($data['type_ids']);

        $voidSaleReason = VoidSaleReason::create($data);

        $this->updateVoidSaleReasonType($voidSaleReason, $voidSaleReasonData);
    }

    public function getById(int $voidSaleReasonId, int $companyId): VoidSaleReason
    {
        return VoidSaleReason::select('id', 'reason')
            ->with('voidSaleReasonTypes:id,void_sale_reason_id,type_id')
            ->where('company_id', $companyId)
            ->findOrFail($voidSaleReasonId);
    }

    public function update(VoidSaleReasonData $voidSaleReasonData, int $voidSaleReasonId, int $companyId): void
    {
        $data = $voidSaleReasonData->all();

        unset($data['type_ids']);

        $voidSaleReason = $this->getById($voidSaleReasonId, $companyId);
        $voidSaleReason->update($data);

        $this->updateVoidSaleReasonType($voidSaleReason, $voidSaleReasonData);
    }

    public function getListForPOSOrOrders(
        int $companyId,
        int $typeId = SaleReturnOrVoidSaleReasonTypes::POS->value,
        ?string $afterUpdatedAt = null
    ): Collection {
        return VoidSaleReason::select('id', 'reason')
            ->where('company_id', $companyId)
            ->whereHas('voidSaleReasonTypes', function ($query) use ($typeId): void {
                $query->select('id', 'type_id', 'void_sale_reason_id')
                    ->where('type_id', $typeId);
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

    public function getVoidSaleReasonsExport(array $filterData, int $companyId): Collection
    {
        return $this->voidSaleReasonQuery($filterData, $companyId)->get();
    }

    private function voidSaleReasonQuery(array $filterData, int $companyId): Builder
    {
        return VoidSaleReason::query()
            ->select('id', 'reason')
            ->with('voidSaleReasonTypes:id,void_sale_reason_id,type_id')
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

    private function updateVoidSaleReasonType(
        VoidSaleReason $voidSaleReason,
        VoidSaleReasonData $voidSaleReasonData
    ): void {
        $voidSaleReason->voidSaleReasonTypes()->delete();
        foreach ($voidSaleReasonData->type_ids as $typeId) {
            VoidSaleReasonType::create([
                'void_sale_reason_id' => $voidSaleReason->id,
                'type_id' => $typeId,
            ]);
        }
    }
}

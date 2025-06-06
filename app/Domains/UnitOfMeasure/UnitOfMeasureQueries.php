<?php

declare(strict_types=1);

namespace App\Domains\UnitOfMeasure;

use App\Domains\UnitOfMeasure\DataObjects\UnitOfMeasureData;
use App\Domains\UnitOfMeasureDerivative\UnitOfMeasureDerivativeQueries;
use App\Models\UnitOfMeasure;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection as SupportCollection;

class UnitOfMeasureQueries
{
    public function listQuery(array $filterData, int $companyId): LengthAwarePaginator
    {
        return $this->unitOfMeasureQuery($filterData, $companyId)->paginate($filterData['per_page']);
    }

    public function filterByCompany(int $companyId): Closure
    {
        return fn ($query) => $query->select('id')->where('company_id', $companyId);
    }

    public function addNew(UnitOfMeasureData $unitOfMeasureData, int $companyId): void
    {
        $data = $unitOfMeasureData->all();
        $data['company_id'] = $companyId;

        UnitOfMeasure::create($data);
    }

    public function getById(int $unitOfMeasureId, int $companyId): UnitOfMeasure
    {
        return UnitOfMeasure::select('id', 'name', 'allow_decimal_qty')
            ->where('company_id', $companyId)
            ->findOrFail($unitOfMeasureId);
    }

    public function update(UnitOfMeasureData $unitOfMeasureData, int $unitOfMeasureId, int $companyId): void
    {
        $unitOfMeasure = $this->getById($unitOfMeasureId, $companyId);
        $unitOfMeasure->update($unitOfMeasureData->toArray());
    }

    public function getWithBasicColumns(int $companyId): Collection
    {
        return UnitOfMeasure::select('id', 'name')
            ->where('company_id', $companyId)
            ->get();
    }

    public function existsByName(string $name, int $companyId): bool
    {
        return UnitOfMeasure::whereCaseSensitive('name', $name)->where('company_id', $companyId)->exists();
    }

    public function getIdByName(string $name, int $companyId): int
    {
        return UnitOfMeasure::firstOrCreate([
            'name' => $name,
            'company_id' => $companyId,
        ])->id;
    }

    public function getBasicColumnNames(): string
    {
        return 'id,name,allow_decimal_qty';
    }

    public function doUnitOfMeasureExists(int $unitOfMeasureId, int $companyId): bool
    {
        return UnitOfMeasure::where('id', $unitOfMeasureId)->where('company_id', $companyId)->exists();
    }

    public function getAllowDecimalQty(int $unitOfMeasureId, int $companyId): ?UnitOfMeasure
    {
        return UnitOfMeasure::select('id', 'name', 'allow_decimal_qty')
            ->where('company_id', $companyId)
            ->where('id', $unitOfMeasureId)
            ->firstOrFail();
    }

    public function getWithBasicColumnsAndDerivatives(int $companyId, ?string $afterUpdatedAt = null): Collection
    {
        $unitOfMeasureDerivativesQueries = resolve(UnitOfMeasureDerivativeQueries::class);

        return UnitOfMeasure::select('id', 'name', 'allow_decimal_qty')
            ->with('derivatives:' . $unitOfMeasureDerivativesQueries->getBasicColumnNames())
            ->where('company_id', $companyId)
            ->when($afterUpdatedAt, function ($query) use ($afterUpdatedAt): void {
                $query->where('updated_at', '>=', $afterUpdatedAt);
            })
            ->get();
    }

    public function searchByName(string $searchText): Closure
    {
        return fn ($query) => $query->select('id')->where('name', 'like', '%' . $searchText . '%');
    }

    public function getUnitOfMeasuresExport(array $filterData, int $companyId): SupportCollection
    {
        return $this->unitOfMeasureQuery($filterData, $companyId)->get();
    }

    public function delete(int $unitOfMeasureId, int $companyId): void
    {
        $unitOfMeasure = $this->getById($unitOfMeasureId, $companyId);
        $unitOfMeasure->derivatives()->delete();
        $unitOfMeasure->delete();
    }

    private function unitOfMeasureQuery(array $filterData, int $companyId): Builder
    {
        return UnitOfMeasure::query()
            ->select('id', 'name')
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query->where('name', 'like', '%' . $filterData['search_text'] . '%');
                });
            })
            ->where('company_id', $companyId)
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            });
    }

    public function getIdByNameAndCompanyId(string $name, int $companyId): int
    {
        return UnitOfMeasure::firstOrCreate([
            'name' => $name,
            'company_id' => $companyId,
        ])->id;
    }
}

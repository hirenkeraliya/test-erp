<?php

declare(strict_types=1);

namespace App\Domains\UnitOfMeasureDerivative;

use App\Domains\UnitOfMeasure\UnitOfMeasureQueries;
use App\Domains\UnitOfMeasureDerivative\DataObjects\UnitOfMeasureDerivativeData;
use App\Models\UnitOfMeasureDerivative;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class UnitOfMeasureDerivativeQueries
{
    public function listQuery(array $filterData, int $unitOfMeasureId, int $companyId): LengthAwarePaginator
    {
        return $this->unitOfMeasureDerivativeQuery($filterData, $unitOfMeasureId, $companyId)->paginate(
            $filterData['per_page']
        );
    }

    public function filterByUnitOfMeasure(int $unitOfMeasureId): Closure
    {
        return fn ($query) => $query->where('unit_of_measure_id', $unitOfMeasureId);
    }

    public function addNew(UnitOfMeasureDerivativeData $unitOfMeasureDerivativesData, int $unitOfMeasureId): void
    {
        $data = $unitOfMeasureDerivativesData->all();
        $data['unit_of_measure_id'] = $unitOfMeasureId;

        UnitOfMeasureDerivative::create($data);
    }

    public function getById(int $unitOfMeasureId, int $derivativeId): UnitOfMeasureDerivative
    {
        return UnitOfMeasureDerivative::select('id', 'name', 'ratio')
            ->where('unit_of_measure_id', $unitOfMeasureId)
            ->findOrFail($derivativeId);
    }

    public function getByOnlyId(int $derivativeId): UnitOfMeasureDerivative
    {
        return UnitOfMeasureDerivative::select('id', 'name', 'ratio')
            ->findOrFail($derivativeId);
    }

    public function getByIds(array $derivativeIds): Collection
    {
        return UnitOfMeasureDerivative::select('id', 'unit_of_measure_id', 'name')
            ->whereIntegerInRaw('id', $derivativeIds)
            ->get();
    }

    public function update(
        UnitOfMeasureDerivativeData $unitOfMeasureDerivativesData,
        int $unitOfMeasureId,
        int $derivativeId
    ): void {
        $unitOfMeasureDerivative = $this->getById($unitOfMeasureId, $derivativeId);
        $unitOfMeasureDerivative->update($unitOfMeasureDerivativesData->toArray());
    }

    public function getList(int $unitOfMeasureId): Collection
    {
        return UnitOfMeasureDerivative::where('unit_of_measure_id', $unitOfMeasureId)->get();
    }

    public function getBasicColumnNames(): string
    {
        return 'id,unit_of_measure_id,name,ratio';
    }

    public function getNameColumn(): string
    {
        return 'id,name';
    }

    public function getDerivativesExport(array $filterData, int $unitOfMeasureId, int $companyId): Collection
    {
        return $this->unitOfMeasureDerivativeQuery($filterData, $unitOfMeasureId, $companyId)->get();
    }

    public function getDerivativesWithUnitsByNames(array $derivativeNames, int $companyId): Collection
    {
        $unitOfMeasureQueries = resolve(UnitOfMeasureQueries::class);

        return UnitOfMeasureDerivative::query()
            ->select('id', 'unit_of_measure_id', 'name', 'ratio')
            ->with('unitOfMeasure:' . $unitOfMeasureQueries->getBasicColumnNames())
            ->whereHas('unitOfMeasure', function ($query) use ($unitOfMeasureQueries, $companyId): void {
                $query->select('id', 'company_id')
                    ->where($unitOfMeasureQueries->filterByCompany($companyId));
            })
            ->whereIn('name', $derivativeNames)
            ->get();
    }

    public function getDerivativesWithUnitsByName(string $derivativeName, int $companyId): ?UnitOfMeasureDerivative
    {
        $unitOfMeasureQueries = resolve(UnitOfMeasureQueries::class);

        return UnitOfMeasureDerivative::query()
            ->select('id', 'unit_of_measure_id', 'name', 'ratio')
            ->with('unitOfMeasure:' . $unitOfMeasureQueries->getBasicColumnNames())
            ->whereHas('unitOfMeasure', function ($query) use ($unitOfMeasureQueries, $companyId): void {
                $query->select('id', 'company_id')
                    ->where($unitOfMeasureQueries->filterByCompany($companyId));
            })
            ->where('name', $derivativeName)
            ->first();
    }

    public function getByName(string $name): ?UnitOfMeasureDerivative
    {
        return UnitOfMeasureDerivative::select('id', 'name', 'ratio')
            ->where('name', $name)
            ->first();
    }

    public function getByUnitOfMeasureIds(array $unitOfMeasureIds): Collection
    {
        return UnitOfMeasureDerivative::select('id', 'unit_of_measure_id', 'name', 'ratio')
            ->whereIntegerInRaw('unit_of_measure_id', $unitOfMeasureIds)
            ->get();
    }

    private function unitOfMeasureDerivativeQuery(array $filterData, int $unitOfMeasureId, int $companyId): Builder
    {
        $unitOfMeasureQueries = new UnitOfMeasureQueries();

        return UnitOfMeasureDerivative::query()
            ->select('id', 'name', 'ratio')
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query
                        ->whereAny(['name', 'ratio'], 'LIKE', '%' . $filterData['search_text'] . '%');
                });
            })
            ->where('unit_of_measure_id', $unitOfMeasureId)
            ->whereHas('unitOfMeasure', $unitOfMeasureQueries->filterByCompany($companyId))
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            });
    }
}

<?php

declare(strict_types=1);

namespace App\Domains\PackageType;

use App\Domains\PackageType\DataObjects\PackageTypeData;
use App\Models\PackageType;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class PackageTypeQueries
{
    public function listQuery(array $filterData, int $companyId): LengthAwarePaginator
    {
        return $this->packageTypeQuery($filterData, $companyId)->paginate($filterData['per_page']);
    }

    public function filterByCompany(int $companyId): Closure
    {
        return fn ($query) => $query->where('company_id', $companyId);
    }

    public function addNew(PackageTypeData $packageTypeData, int $companyId): void
    {
        $data = $packageTypeData->all();
        $data['company_id'] = $companyId;

        PackageType::create($data);
    }

    public function getById(int $packageTypeId, int $companyId): PackageType
    {
        return PackageType::select('id', 'name')
            ->where('company_id', $companyId)
            ->findOrFail($packageTypeId);
    }

    public function getLists(int $companyId): Collection
    {
        return PackageType::select('id', 'name')
            ->where('company_id', $companyId)
            ->get();
    }

    public function update(PackageTypeData $packageTypeData, int $packageTypeId, int $companyId): void
    {
        $packageType = $this->getById($packageTypeId, $companyId);
        $packageType->update($packageTypeData->toArray());
    }

    public function getWithBasicColumns(int $companyId): Collection
    {
        return PackageType::select('id', 'name')
            ->where('company_id', $companyId)
            ->get();
    }

    public function getBasicColumnNames(): string
    {
        return 'id,name';
    }

    public function getPackageTypeExport(array $filterData, int $companyId): Collection
    {
        return $this->packageTypeQuery($filterData, $companyId)->get();
    }

    public function fetchOrCreate(string $name, int $companyId): PackageType
    {
        return PackageType::firstOrCreate([
            'name' => $name,
            'company_id' => $companyId,
        ]);
    }

    public function existsByName(string $name, int $companyId): bool
    {
        return PackageType::select('id')
            ->whereCaseSensitive('name', $name)
            ->where('company_id', $companyId)
            ->exists();
    }

    public function getIdByName(string $name, int $companyId): ?int
    {
        return PackageType::select('id')
            ->whereCaseSensitive('name', $name)
            ->where('company_id', $companyId)
            ->first()?->id;
    }

    private function packageTypeQuery(array $filterData, int $companyId): Builder
    {
        return PackageType::query()
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
}

<?php

declare(strict_types=1);

namespace App\Domains\Season;

use App\Domains\Season\DataObjects\SeasonData;
use App\Models\Season;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class SeasonQueries
{
    public function listQuery(array $filterData, int $companyId): LengthAwarePaginator
    {
        return $this->getSeasonsQuery($filterData, $companyId)->paginate($filterData['per_page']);
    }

    public function addNew(SeasonData $seasonData, int $companyId): Season
    {
        $data = $seasonData->all();
        $data['company_id'] = $companyId;

        return Season::create($data);
    }

    public function getById(int $seasonId, int $companyId): Season
    {
        return Season::select('id', 'company_id', 'name', 'code')
            ->where('company_id', $companyId)
            ->findOrFail($seasonId);
    }

    public function update(SeasonData $seasonData, int $seasonId, int $companyId): void
    {
        $season = $this->getById($seasonId, $companyId);
        $season->update($seasonData->all());
    }

    public function filterByCompany(int $companyId): Closure
    {
        return fn ($query) => $query->where('company_id', $companyId);
    }

    public function getWithBasicColumns(int $companyId): Collection
    {
        return Season::select('id', 'name')->where('company_id', $companyId)->get();
    }

    public function existsByName(string $name, int $companyId): bool
    {
        return Season::whereCaseSensitive('name', $name)->where('company_id', $companyId)->exists();
    }

    public function getIdByName(string $name, int $companyId): int
    {
        return Season::firstOrCreate([
            'name' => $name,
            'company_id' => $companyId,
        ])->id;
    }

    public function getBasicColumnNames(): string
    {
        return 'id,name,code';
    }

    public function getBasicColumnNamesForEcommerce(): string
    {
        return 'id,name,code,created_at,updated_at';
    }

    public function getBasicColumnNamesForProductCollection(): string
    {
        return 'id,name';
    }

    public function searchByColumns(string $searchText): Closure
    {
        return fn ($query) => $query->select('id')
            ->whereAny(['name', 'code'], 'LIKE', '%' . $searchText . '%');
    }

    public function getSeasonsExport(array $filterData, int $companyId): Collection
    {
        return $this->getSeasonsQuery($filterData, $companyId)->get();
    }

    public function getAllSeasonByCompanyId(int $companyId): Collection
    {
        return Season::select('id', 'company_id', 'name')
            ->where('company_id', $companyId)
            ->get();
    }

    private function getSeasonsQuery(array $filterData, int $companyId): Builder
    {
        return Season::query()
            ->select('id', 'name', 'code', 'company_id')
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

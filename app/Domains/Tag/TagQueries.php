<?php

declare(strict_types=1);

namespace App\Domains\Tag;

use App\Domains\Tag\DataObjects\TagData;
use App\Models\Tag;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection as SupportCollection;

class TagQueries
{
    public function getWithBasicColumns(int $companyId): Collection
    {
        return Tag::select('id', 'name')->where('company_id', $companyId)->get();
    }

    public function getByIds(array $tagIds): SupportCollection
    {
        return Tag::select('name')
            ->whereIntegerInRaw('id', $tagIds)
            ->get();
    }

    public function getTagNamesByIds(int $companyId, array $tagIds): ?Tag
    {
        return Tag::selectRaw("GROUP_CONCAT(CONCAT(name) SEPARATOR ', ') AS names")
            ->whereIntegerInRaw('id', $tagIds)
            ->where('company_id', $companyId)
            ->first();
    }

    public function addNew(TagData $tagData, int $companyId): Tag
    {
        $tagDetails = $tagData->all();
        $tagDetails['company_id'] = $companyId;

        return Tag::create($tagDetails);
    }

    public function getBasicColumnNames(): string
    {
        return 'id,name';
    }

    public function filterByCompany(int $companyId): Closure
    {
        return fn ($query) => $query->where('company_id', $companyId);
    }

    public function searchByName(string $searchText): Closure
    {
        return fn ($query) => $query->select('id')->where('name', 'like', '%' . $searchText . '%');
    }

    public function getFilteredTagsByCompanyId(string $searchText, int $companyId): SupportCollection
    {
        return Tag::select('id', 'name')
            ->where('company_id', $companyId)
            ->where('name', 'like', '%' . $searchText . '%')
            ->limit(5)
            ->get();
    }

    public function fetchTags(array $filterData, int $companyId): Builder
    {
        return Tag::query()
            ->select('id', 'name')
            ->where('company_id', $companyId)
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query->where('name', 'like', '%' . $filterData['search_text'] . '%');
                });
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            });
    }

    public function listQuery(array $filterData, int $companyId): LengthAwarePaginator
    {
        return $this->fetchTags($filterData, $companyId)->paginate($filterData['per_page']);
    }

    public function getById(int $tagId, int $companyId): Tag
    {
        return Tag::select('id', 'name')
            ->where('company_id', $companyId)
            ->findOrFail($tagId);
    }

    public function update(TagData $tagData, int $tagId, int $companyId): void
    {
        $tag = $this->getById($tagId, $companyId);

        $tag->update($tagData->all());
    }

    public function getTagsExport(array $filterData, int $companyId): SupportCollection
    {
        return $this->fetchTags($filterData, $companyId)->get();
    }

    public function doAllTagsExist(int $companyId, array $tagIds): bool
    {
        $totalRecords = Tag::whereIntegerInRaw('id', $tagIds)->where('company_id', $companyId)->count();

        return count($tagIds) === $totalRecords;
    }

    public function filterByIds(array $tagIds): Closure
    {
        return fn ($query) => $query->select('id')->whereIntegerInRaw('id', $tagIds);
    }

    public function getIdAndNameByNames(array $tagNames, int $companyId): Collection
    {
        return Tag::select('id', 'name')
            ->whereInCaseSensitive('name', $tagNames)
            ->where('company_id', $companyId)
            ->get();
    }

    public function newProductTag(string $tagName, int $companyId): int
    {
        return Tag::create([
            'name' => $tagName,
            'company_id' => $companyId,
        ])->id;
    }

    public function existsByNames(array $names, int $companyId): SupportCollection
    {
        $existingTagIds = collect([]);

        foreach ($names as $name) {
            $newTag = Tag::firstOrCreate([
                'name' => $name,
                'company_id' => $companyId,
            ]);

            $existingTagIds->push($newTag->getKey());
        }

        return $existingTagIds;
    }

    public function getIdByNameAndCompanyId(string $name, int $companyId): int
    {
        return Tag::firstOrCreate([
            'name' => $name,
            'company_id' => $companyId,
        ])->id;
    }

    public function getTagsNameForFilter(array $tagIds): string
    {
        $tagData = [];
        $tag = Tag::select('name')
            ->whereIntegerInRaw('id', values: $tagIds)
            ->get();

        if ($tag->isNotEmpty()) {
            $tagData = $tag->pluck('name')->toArray();
        }

        return implode(', ', $tagData);
    }
}

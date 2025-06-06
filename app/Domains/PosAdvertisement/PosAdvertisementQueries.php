<?php

declare(strict_types=1);

namespace App\Domains\PosAdvertisement;

use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\Media\MediaQueries;
use App\Domains\PosAdvertisement\DataObjects\PosAdvertisementData;
use App\Domains\PosAdvertisement\Enums\PosAdvertisementTypes;
use App\Models\PosAdvertisement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class PosAdvertisementQueries
{
    public function listQuery(array $filterData, int $companyId): LengthAwarePaginator
    {
        return $this->posAdvertisementQuery($filterData, $companyId)->paginate($filterData['per_page']);
    }

    public function addNew(PosAdvertisementData $posAdvertisementData, int $companyId): void
    {
        $posAdvertisementDataCollection = $posAdvertisementData->all();

        $posAdvertisementDataCollection = collect($posAdvertisementData)->forget(['photo', 'video', 'location_ids']);
        $posAdvertisementDataCollection['company_id'] = $companyId;

        $posAdvertisement = PosAdvertisement::create($posAdvertisementDataCollection->toArray());
        $this->uploadPhoto($posAdvertisement, $posAdvertisementData);

        $posAdvertisement->locations()->sync($posAdvertisementData->location_ids);
    }

    public function getById(int $posAdvertisementId, int $companyId): PosAdvertisement
    {
        $locationQueries = resolve(LocationQueries::class);
        $mediaQueries = resolve(MediaQueries::class);

        return PosAdvertisement::select('id', 'name', 'type_id', 'status')
            ->where('company_id', $companyId)
            ->with(
                [
                    'locations:' . $locationQueries->getBasicColumnNames(),
                    'media:' . $mediaQueries->getBasicColumnNames(),
                ]
            )
            ->findOrFail($posAdvertisementId);
    }

    public function update(PosAdvertisementData $posAdvertisementData, int $posAdvertisementId, int $companyId): void
    {
        $posAdvertisement = $this->getById($posAdvertisementId, $companyId);
        $this->clearMediaCollection($posAdvertisementData, $posAdvertisement);

        $posAdvertisementDataCollection = $posAdvertisementData->all();

        $posAdvertisementDataCollection = collect($posAdvertisementData)->forget(['photo', 'video', 'location_ids']);
        $posAdvertisementDataCollection['company_id'] = $companyId;

        $posAdvertisement->update($posAdvertisementDataCollection->toArray());
        $this->uploadPhoto($posAdvertisement, $posAdvertisementData);
        $this->setUpdatedAt($posAdvertisement);

        $posAdvertisement->locations()->sync($posAdvertisementData->location_ids);
    }

    public function getBasicColumnNames(): string
    {
        return 'id,type_id,name,status';
    }

    public function setUpdatedAt(PosAdvertisement $posAdvertisement): void
    {
        $posAdvertisement->touch();
    }

    public function adminSetStatus(int $posAdvertisementId, int $companyId, bool $status): void
    {
        $posAdvertisement = PosAdvertisement::query()
            ->where('company_id', $companyId)
            ->findOrFail($posAdvertisementId);
        $posAdvertisement->status = $status;
        $posAdvertisement->save();
    }

    public function getList(int $companyId, int $locationId, array $filterData): Collection
    {
        $locationQueries = resolve(LocationQueries::class);
        $mediaQueries = resolve(MediaQueries::class);

        return PosAdvertisement::select('id', 'company_id', 'type_id', 'name', 'status')
            ->with('media:' . $mediaQueries->getBasicColumnNames())
            ->where('company_id', $companyId)
            ->whereHas('locations', $locationQueries->filterById($locationId, LocationTypes::STORE->value))
            ->when($filterData['after_updated_at'], function ($query) use ($filterData): void {
                $query->where('updated_at', '>=', $filterData['after_updated_at']);
            }, function ($query): void {
                $query->where('status', true);
            })
            ->get();
    }

    public function getPosAdvertisementExport(array $filterData, int $companyId): Collection
    {
        return $this->posAdvertisementQuery($filterData, $companyId)->get();
    }

    private function posAdvertisementQuery(array $filterData, int $companyId): Builder
    {
        $locationQueries = resolve(LocationQueries::class);
        $mediaQueries = resolve(MediaQueries::class);

        return PosAdvertisement::query()
            ->select('id', 'name', 'type_id', 'status')
            ->where('company_id', $companyId)
            ->with(
                [
                    'locations:' . $locationQueries->getBasicColumnNames(),
                    'media:' . $mediaQueries->getBasicColumnNames(),
                ]
            )
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

    private function uploadPhoto(PosAdvertisement $posAdvertisement, PosAdvertisementData $posAdvertisementData): void
    {
        $posAdvertisementRecord = collect($posAdvertisementData)->toArray();

        if (null !== $posAdvertisementRecord['photo']) {
            $posAdvertisement->addMedia($posAdvertisementRecord['photo'])->toMediaCollection('photo');
        }

        if (null !== $posAdvertisementRecord['video']) {
            $posAdvertisement->addMedia($posAdvertisementRecord['video'])->toMediaCollection('video');
        }
    }

    private function clearMediaCollection(
        PosAdvertisementData $posAdvertisementData,
        PosAdvertisement $posAdvertisement
    ): void {
        if ($posAdvertisement->type_id === $posAdvertisementData->type_id) {
            return;
        }

        if ($posAdvertisementData->type_id === PosAdvertisementTypes::IMAGE->value) {
            $posAdvertisement->clearMediaCollection('video');

            return;
        }

        $posAdvertisement->clearMediaCollection('photo');
    }
}

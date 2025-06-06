<?php

declare(strict_types=1);

namespace App\Domains\ShippingZone;

use App\Domains\Country\CountryQueries;
use App\Domains\ShippingZone\DataObjects\ShippingZoneData;
use App\Domains\ShippingZone\Events\ShippingZoneCreateEvent;
use App\Domains\ShippingZone\Events\ShippingZoneUpdateEvent;
use App\Domains\State\StateQueries;
use App\Models\ShippingZone;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ShippingZoneQueries
{
    public function listQuery(array $filterData, int $companyId): LengthAwarePaginator
    {
        $countryQueries = resolve(CountryQueries::class);

        return ShippingZone::query()
            ->select('id', 'name', 'country_id')
            ->with(['country:' . $countryQueries->getBasicColumnNames()])
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where('name', 'LIKE', '%' . $filterData['search_text'] . '%');
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            })
            ->where('company_id', $companyId)
            ->paginate($filterData['per_page']);
    }

    public function addNew(ShippingZoneData $shippingZoneData, int $companyId): void
    {
        $stateIds = $shippingZoneData->state_ids;
        $requestData = $shippingZoneData->all();
        $requestData['company_id'] = $companyId;

        unset($requestData['state_ids']);

        $shippingZone = ShippingZone::create($requestData);

        $shippingZone->states()->sync($stateIds);

        event(new ShippingZoneCreateEvent($shippingZone));
    }

    public function getById(int $id, int $companyId): ShippingZone
    {
        $countryQueries = resolve(CountryQueries::class);
        $stateQueries = resolve(StateQueries::class);

        return ShippingZone::query()
            ->select('id', 'name', 'country_id')
            ->with([
                'country:' . $countryQueries->getBasicColumnNames(),
                'country.states:' . $stateQueries->getAllColumns(),
                'states:' . $stateQueries->getBasicColumnNames(),
            ])
            ->where('company_id', $companyId)
            ->findOrFail($id);
    }

    public function getRelationRecordByIdAndCompanyId(int $id, int $companyId): ShippingZone
    {
        $countryQueries = resolve(CountryQueries::class);
        $stateQueries = resolve(StateQueries::class);

        return ShippingZone::query()
            ->select('id', 'name', 'country_id')
            ->with([
                'country:' . $countryQueries->getBasicColumnNames(),
                'states:' . $stateQueries->getBasicColumnNames(),
            ])
            ->where('company_id', $companyId)
            ->findOrFail($id);
    }

    public function update(ShippingZoneData $shippingZoneData, int $id, int $companyId): void
    {
        $shippingZone = ShippingZone::query()
            ->select('id', 'name', 'country_id')
            ->where('company_id', $companyId)
            ->findOrFail($id);

        $stateIds = $shippingZoneData->state_ids;
        $requestData = $shippingZoneData->all();

        unset($requestData['state_ids']);

        $shippingZone->update($requestData);

        $shippingZone->states()->sync($stateIds);

        event(new ShippingZoneUpdateEvent($shippingZone));
    }

    public function getAll(): Collection
    {
        return ShippingZone::query()
            ->select('id', 'name')
            ->get();
    }

    public function getBasicColumns(): string
    {
        return 'id,name';
    }
}

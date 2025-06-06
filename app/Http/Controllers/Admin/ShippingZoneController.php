<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\Country\CountryQueries;
use App\Domains\ShippingZone\DataObjects\ShippingZoneData;
use App\Domains\ShippingZone\Resources\ShippingZoneEditResource;
use App\Domains\ShippingZone\ShippingZoneQueries;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShippingZoneController
{
    public function __construct(
        protected ShippingZoneQueries $shippingZoneQueries
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('shipping_zones/Index');
    }

    public function fetchShippingZones(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $lengthAwarePaginator = $this->shippingZoneQueries->listQuery($filterData, session('admin_company_id'));

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => $lengthAwarePaginator->getCollection(),
        ];
    }

    public function create(): Response
    {
        $countryQueries = resolve(CountryQueries::class);

        return Inertia::render('shipping_zones/Manage', [
            'countries' => $countryQueries->getList(),
        ]);
    }

    public function store(ShippingZoneData $shippingZoneData): RedirectResponse
    {
        $this->shippingZoneQueries->addNew($shippingZoneData, session('admin_company_id'));

        return to_route('admin.shipping_zones.index')->with('success', 'Shipping Zone added successfully.');
    }

    public function edit(int $shippingZoneId): Response
    {
        $shippingZone = $this->shippingZoneQueries->getById($shippingZoneId, session('admin_company_id'));

        $countryQueries = resolve(CountryQueries::class);

        return Inertia::render('shipping_zones/Manage', [
            'shippingZone' => new ShippingZoneEditResource($shippingZone),
            'countries' => $countryQueries->getList(),
        ]);
    }

    public function update(ShippingZoneData $shippingZoneData, int $shippingZoneId): RedirectResponse
    {
        $this->shippingZoneQueries->update($shippingZoneData, $shippingZoneId, session('admin_company_id'));

        return to_route('admin.shipping_zones.index')->with('success', 'Shipping zone updated successfully.');
    }
}

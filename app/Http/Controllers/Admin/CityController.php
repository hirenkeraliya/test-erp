<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\City\CityQueries;
use App\Domains\City\DataObjects\CityData;
use App\Domains\City\Exports\CityExport;
use App\Domains\Country\CountryQueries;
use App\Domains\Permission\Enums\PermissionList;
use App\Domains\State\StateQueries;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CityController extends Controller
{
    public function __construct(
        protected CityQueries $cityQueries
    ) {
    }

    public function getCitiesByStateId(int $stateId): array
    {
        $cities = $this->cityQueries->getByStateId($stateId);
        $cities = $cities->map(fn ($city): array => [
            'id' => $city->id,
            'name' => $city->name,
        ]);

        return [
            'cities' => $cities,
        ];
    }

    public function index(): Response
    {
        return Inertia::render('cities/Index', [
            'exportPermission' => PermissionList::getExportPermissionName('city'),
        ]);
    }

    public function fetchCities(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $lengthAwarePaginator = $this->cityQueries->listQuery($filterData);

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => $lengthAwarePaginator->getCollection(),
        ];
    }

    public function create(): Response
    {
        $countryQueries = resolve(CountryQueries::class);
        $countries = $countryQueries->getList();

        return Inertia::render('cities/Manage', [
            'countries' => $countries,
        ]);
    }

    public function store(CityData $cityData): RedirectResponse
    {
        $this->cityQueries->addNew($cityData);

        return to_route('admin.cities.index')
            ->with('success', 'City added successfully.');
    }

    public function edit(int $cityId): Response
    {
        $countryQueries = resolve(CountryQueries::class);
        $stateQueries = resolve(StateQueries::class);
        $city = $this->cityQueries->getById($cityId);
        $countries = $countryQueries->getList();

        $states = $stateQueries->getByCountryId($city->country_id);

        return Inertia::render('cities/Manage', [
            'city' => $city,
            'countries' => $countries,
            'states' => $states->map(fn ($state): array => [
                'id' => $state->id,
                'name' => $state->name,
            ]),
        ]);
    }

    public function update(CityData $cityData, int $cityId): RedirectResponse
    {
        $this->cityQueries->update($cityData, $cityId);

        return to_route('admin.cities.index')
            ->with('success', 'City updated successfully.');
    }

    public function exportCities(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $cities = $this->cityQueries->getCityExport($filterData);

        return Excel::download(new CityExport($cities), $filename);
    }
}

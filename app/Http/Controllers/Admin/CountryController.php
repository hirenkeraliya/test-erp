<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\Country\CountryQueries;
use App\Domains\Country\DataObjects\CountryData;
use App\Domains\Country\Exports\CountryExport;
use App\Domains\Permission\Enums\PermissionList;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CountryController extends Controller
{
    public function __construct(
        protected CountryQueries $countryQueries
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('countries/Index', [
            'exportPermission' => PermissionList::getExportPermissionName('country'),
        ]);
    }

    /**
     * @return array<string, Collection>|array<string, int>
     */
    public function fetchCountries(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $lengthAwarePaginator = $this->countryQueries->listQuery($filterData);

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => $lengthAwarePaginator->getCollection(),
        ];
    }

    public function store(CountryData $countryData): RedirectResponse
    {
        $this->countryQueries->addNew($countryData);

        return to_route('admin.countries.index')
            ->with('success', 'Country added successfully.');
    }

    public function edit(int $countryId): Response
    {
        $country = $this->countryQueries->getById($countryId);

        return Inertia::render('countries/Manage', [
            'country' => $country,
        ]);
    }

    public function update(CountryData $countryData, int $countryId): RedirectResponse
    {
        $this->countryQueries->update($countryData, $countryId);

        return to_route('admin.countries.index')
            ->with('success', 'Country updated successfully.');
    }

    public function exportCountries(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $countries = $this->countryQueries->getCountryExport($filterData);

        return Excel::download(new CountryExport($countries), $filename);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\Country\CountryQueries;
use App\Domains\Permission\Enums\PermissionList;
use App\Domains\State\DataObjects\StateData;
use App\Domains\State\Exports\StateExport;
use App\Domains\State\StateQueries;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StateController extends Controller
{
    public function __construct(
        protected StateQueries $stateQueries
    ) {
    }

    public function getStatesByCountryId(int $countryId): array
    {
        $states = $this->stateQueries->getByCountryId($countryId);
        $states = $states->map(fn ($state): array => [
            'id' => $state->id,
            'name' => $state->name,
        ]);

        return [
            'states' => $states,
        ];
    }

    public function index(): Response
    {
        return Inertia::render('states/Index', [
            'exportPermission' => PermissionList::getExportPermissionName('country'),
        ]);
    }

    public function fetchStates(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $lengthAwarePaginator = $this->stateQueries->listQuery($filterData);

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => $lengthAwarePaginator->getCollection(),
        ];
    }

    public function create(): Response
    {
        $countryQueries = resolve(CountryQueries::class);
        $countries = $countryQueries->getList();

        return Inertia::render('states/Manage', [
            'countries' => $countries,
        ]);
    }

    public function store(StateData $stateData): RedirectResponse
    {
        $this->stateQueries->addNew($stateData);

        return to_route('admin.states.index')
            ->with('success', 'State added successfully.');
    }

    public function edit(int $stateId): Response
    {
        $countryQueries = resolve(CountryQueries::class);
        $state = $this->stateQueries->getById($stateId);
        $countries = $countryQueries->getList();

        return Inertia::render('states/Manage', [
            'state' => $state,
            'countries' => $countries,
        ]);
    }

    public function update(StateData $stateData, int $stateId): RedirectResponse
    {
        $this->stateQueries->update($stateData, $stateId);

        return to_route('admin.states.index')
            ->with('success', 'State updated successfully.');
    }

    public function exportStates(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $states = $this->stateQueries->getStateExport($filterData);

        return Excel::download(new StateExport($states), $filename);
    }
}

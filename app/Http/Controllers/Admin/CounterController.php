<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\CommonFunctions;
use App\Domains\Counter\CounterQueries;
use App\Domains\Counter\DataObjects\CounterData;
use App\Domains\Counter\Exports\CounterExport;
use App\Domains\Counter\Resources\CounterListResource;
use App\Domains\Location\LocationQueries;
use App\Domains\Permission\Enums\PermissionList;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class CounterController extends Controller
{
    public function __construct(
        protected CounterQueries $counterQueries
    ) {
    }

    public function index(): Response
    {
        $companyId = session('admin_company_id');

        $locationQueries = resolve(LocationQueries::class);

        $locations = $locationQueries->getStoreWithBasicColumns($companyId);

        return Inertia::render('counters/Index', [
            'locations' => $locations,
            'exportPermission' => PermissionList::getExportPermissionName('counter'),
        ]);
    }

    public function fetchCounters(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'location_ids' => $request->get('location_ids'),
        ];

        $lengthAwarePaginator = $this->counterQueries->listQuery($filterData, session('admin_company_id'));
        $appVersionCounts = $this->counterQueries->getAppVersionCounts(session('admin_company_id'));

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => CounterListResource::collection($lengthAwarePaginator->getCollection()),
            'appVersionCounts' => $appVersionCounts,
        ];
    }

    public function create(LocationQueries $locationQueries): Response
    {
        return Inertia::render('counters/Manage', [
            'locations' => $locationQueries->getStoreWithBasicColumns(session('admin_company_id')),
        ]);
    }

    public function store(CounterData $counterData): RedirectResponse
    {
        DB::beginTransaction();
        try {
            $this->counterQueries->addNew($counterData);

            DB::commit();

            return to_route('admin.counters.index')->with('success', 'The counter has been added successfully.');
        } catch (Throwable $throwable) {
            CommonFunctions::logErrorDetails($throwable, 'Admin Counter Create');

            DB::rollBack();

            abort(412, 'An error occurred. Please try again.');
        }
    }

    public function edit(int $counterId, LocationQueries $locationQueries): Response
    {
        $counter = $this->counterQueries->getById($counterId, session('admin_company_id'));

        return Inertia::render('counters/Manage', [
            'counter' => $counter,
            'locations' => $locationQueries->getStoreWithBasicColumns(session('admin_company_id')),
        ]);
    }

    public function update(CounterData $counterData, int $counterId): RedirectResponse
    {
        DB::beginTransaction();
        try {
            $this->counterQueries->update($counterData, $counterId, session('admin_company_id'));

            DB::commit();

            return to_route('admin.counters.index')->with('success', 'Counter updated successfully.');
        } catch (Throwable $throwable) {
            CommonFunctions::logErrorDetails($throwable, 'Admin Counter Update');

            DB::rollBack();

            abort(412, 'An error occurred. Please try again.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function getLocationCounters(int $locationId): array
    {
        $counters = $this->counterQueries->getCounterListOfSelectedLocation($locationId, session('admin_company_id'));

        return [
            'counters' => $counters,
        ];
    }

    /**
     * @return array<string, Collection>
     */
    public function getCountersOfLocations(Request $request): array
    {
        $validatedData = $request->validate([
            'locations_ids' => ['required', 'array'],
        ]);

        $locationIds = $validatedData['locations_ids'];

        $counters = $this->counterQueries->getCountersOfLocations($locationIds, session('admin_company_id'));

        return [
            'counters' => $counters,
        ];
    }

    public function exportCounters(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'location_ids' => $request->get('location_ids'),
        ];

        $counters = $this->counterQueries->getCountersExport($filterData, session('admin_company_id'));

        return Excel::download(new CounterExport($counters), $filename);
    }
}

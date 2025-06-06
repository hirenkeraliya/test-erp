<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\Permission\Enums\PermissionList;
use App\Domains\Season\DataObjects\SeasonData;
use App\Domains\Season\Exports\SeasonExport;
use App\Domains\Season\SeasonQueries;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SeasonController extends Controller
{
    public function __construct(
        protected SeasonQueries $seasonQueries
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('seasons/Index', [
            'exportPermission' => PermissionList::getExportPermissionName('season'),
        ]);
    }

    /**
     * @return array<string, Collection>|array<string, int>
     */
    public function fetchSeasons(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $lengthAwarePaginator = $this->seasonQueries->listQuery($filterData, session('admin_company_id'));

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => $lengthAwarePaginator->getCollection(),
        ];
    }

    public function store(SeasonData $seasonData): RedirectResponse
    {
        $this->seasonQueries->addNew($seasonData, session('admin_company_id'));

        return to_route('admin.seasons.index')->with('success', 'Season added successfully.');
    }

    public function storeAndReturn(SeasonData $seasonData): array
    {
        $season = $this->seasonQueries->addNew($seasonData, session('admin_company_id'));

        return [
            'season' => $season,
        ];
    }

    public function edit(int $seasonId): Response
    {
        return Inertia::render('seasons/Manage', [
            'season' => $this->seasonQueries->getById($seasonId, session('admin_company_id')),
        ]);
    }

    public function update(SeasonData $seasonData, int $seasonId): RedirectResponse
    {
        $this->seasonQueries->update($seasonData, $seasonId, session('admin_company_id'));

        return to_route('admin.seasons.index')->with('success', 'Season updated successfully.');
    }

    public function exportSeasons(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $seasons = $this->seasonQueries->getSeasonsExport($filterData, session('admin_company_id'));

        return Excel::download(new SeasonExport($seasons), $filename);
    }
}

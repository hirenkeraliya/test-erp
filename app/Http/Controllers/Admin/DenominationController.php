<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\Denomination\DataObjects\DenominationData;
use App\Domains\Denomination\DenominationQueries;
use App\Domains\Denomination\Exports\DenominationExport;
use App\Domains\Permission\Enums\PermissionList;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DenominationController extends Controller
{
    public function __construct(
        protected DenominationQueries $denominationQueries
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('denominations/Index', [
            'exportPermission' => PermissionList::getExportPermissionName('denomination'),
        ]);
    }

    /**
     * @return array<string, Collection>|array<string, int>
     */
    public function fetchDenominations(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $lengthAwarePaginator = $this->denominationQueries->listQuery($filterData, session('admin_company_id'));

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => $lengthAwarePaginator->getCollection(),
        ];
    }

    public function store(DenominationData $denominationData): RedirectResponse
    {
        $this->denominationQueries->addNew($denominationData, session('admin_company_id'));

        return to_route('admin.denominations.index')->with('success', 'The denomination was added successfully.');
    }

    public function edit(int $denominationId): Response
    {
        return Inertia::render('denominations/Manage', [
            'denomination' => $this->denominationQueries->getById($denominationId, session('admin_company_id')),
        ]);
    }

    public function update(DenominationData $denominationData, int $denominationId): RedirectResponse
    {
        $this->denominationQueries->update($denominationData, $denominationId, session('admin_company_id'));

        return to_route('admin.denominations.index')->with('success', 'The denomination was updated successfully.');
    }

    public function delete(int $denominationId): RedirectResponse
    {
        $this->denominationQueries->delete($denominationId, session('admin_company_id'));

        return to_route('admin.denominations.index')->with(
            'success',
            'The denomination has been successfully deleted.'
        );
    }

    public function exportDenominations(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $denominations = $this->denominationQueries->getDenominationsExport($filterData, session('admin_company_id'));

        return Excel::download(new DenominationExport($denominations), $filename);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\Permission\Enums\PermissionList;
use App\Domains\UnitOfMeasure\DataObjects\UnitOfMeasureData;
use App\Domains\UnitOfMeasure\Exports\UnitOfMeasureExport;
use App\Domains\UnitOfMeasure\UnitOfMeasureQueries;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class UnitOfMeasureController extends Controller
{
    public function __construct(
        protected UnitOfMeasureQueries $unitOfMeasureQueries
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('unit_of_measures/Index', [
            'exportPermission' => PermissionList::getExportPermissionName('unit_of_measure'),
        ]);
    }

    /**
     * @return array<string, Collection>|array<string, int>
     */
    public function fetchUnitOfMeasures(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $lengthAwarePaginator = $this->unitOfMeasureQueries->listQuery($filterData, session('admin_company_id'));

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => $lengthAwarePaginator->getCollection(),
        ];
    }

    public function store(UnitOfMeasureData $unitOfMeasureData): RedirectResponse
    {
        $this->unitOfMeasureQueries->addNew($unitOfMeasureData, session('admin_company_id'));

        return to_route('admin.unit_of_measures.index')
            ->with('success', 'The Unit of Measure has been added successfully.');
    }

    public function edit(int $unitOfMeasureId): Response
    {
        return Inertia::render('unit_of_measures/Manage', [
            'unitOfMeasure' => $this->unitOfMeasureQueries->getById($unitOfMeasureId, session('admin_company_id')),
        ]);
    }

    public function update(UnitOfMeasureData $unitOfMeasureData, int $unitOfMeasureId): RedirectResponse
    {
        $this->unitOfMeasureQueries->update($unitOfMeasureData, $unitOfMeasureId, session('admin_company_id'));

        return to_route('admin.unit_of_measures.index')
            ->with('success', 'Unit of Measure updated successfully.');
    }

    public function exportUnitOfMeasures(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $unitOfMeasures = $this->unitOfMeasureQueries->getUnitOfMeasuresExport(
            $filterData,
            session('admin_company_id')
        );

        return Excel::download(new UnitOfMeasureExport($unitOfMeasures), $filename);
    }

    public function delete(int $unitOfMeasureId): RedirectResponse
    {
        $companyId = session('admin_company_id');
        $this->unitOfMeasureQueries->delete($unitOfMeasureId, $companyId);

        return to_route('admin.unit_of_measures.index')->with('success', 'UOM deleted successfully.');
    }
}

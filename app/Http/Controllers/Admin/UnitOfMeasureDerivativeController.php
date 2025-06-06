<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\Permission\Enums\PermissionList;
use App\Domains\UnitOfMeasure\UnitOfMeasureQueries;
use App\Domains\UnitOfMeasureDerivative\DataObjects\UnitOfMeasureDerivativeData;
use App\Domains\UnitOfMeasureDerivative\Exports\UnitOfMeasureDerivativeExport;
use App\Domains\UnitOfMeasureDerivative\UnitOfMeasureDerivativeQueries;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class UnitOfMeasureDerivativeController extends Controller
{
    public function __construct(
        protected UnitOfMeasureDerivativeQueries $unitOfMeasureDerivativeQueries
    ) {
    }

    public function index(int $unitOfMeasureId, UnitOfMeasureQueries $unitOfMeasureQueries): Response
    {
        [$unitOfMeasureName, $allowDecimalQty] = $this->getUnitOfMeasure(
            $unitOfMeasureQueries,
            $unitOfMeasureId,
            session('admin_company_id')
        );

        return Inertia::render('unit_of_measure_derivatives/Index', [
            'unitOfMeasureId' => $unitOfMeasureId,
            'unitOfMeasureName' => $unitOfMeasureName,
            'exportPermission' => PermissionList::getExportPermissionName('unit_of_measure_derivative'),
            'allowDecimalQty' => $allowDecimalQty,
        ]);
    }

    /**
     * @return array<string, Collection>|array<string, int>
     */
    public function fetchDerivatives(Request $request, int $unitOfMeasureId): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $lengthAwarePaginator = $this->unitOfMeasureDerivativeQueries->listQuery(
            $filterData,
            $unitOfMeasureId,
            session('admin_company_id')
        );

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => $lengthAwarePaginator->getCollection(),
        ];
    }

    public function create(int $unitOfMeasureId, UnitOfMeasureQueries $unitOfMeasureQueries): Response
    {
        [$unitOfMeasureName] = $this->getUnitOfMeasure(
            $unitOfMeasureQueries,
            $unitOfMeasureId,
            session('admin_company_id')
        );

        return Inertia::render('unit_of_measure_derivatives/Manage', [
            'unitOfMeasureId' => $unitOfMeasureId,
            'unitOfMeasureName' => $unitOfMeasureName,
        ]);
    }

    public function store(
        UnitOfMeasureDerivativeData $unitOfMeasureDerivativesData,
        int $unitOfMeasureId,
        UnitOfMeasureQueries $unitOfMeasureQueries
    ): RedirectResponse {
        $this->validateUnitOfMeasureWithCompany($unitOfMeasureQueries, $unitOfMeasureId, session('admin_company_id'));

        $this->unitOfMeasureDerivativeQueries->addNew($unitOfMeasureDerivativesData, $unitOfMeasureId);

        return to_route('admin.unit_of_measure_derivatives.index', $unitOfMeasureId)
            ->with('success', 'Derivative added successfully.');
    }

    public function edit(int $unitOfMeasureId, int $derivativeId, UnitOfMeasureQueries $unitOfMeasureQueries): Response
    {
        [$unitOfMeasureName] = $this->getUnitOfMeasure(
            $unitOfMeasureQueries,
            $unitOfMeasureId,
            session('admin_company_id')
        );

        return Inertia::render('unit_of_measure_derivatives/Manage', [
            'derivative' => $this->unitOfMeasureDerivativeQueries->getById($unitOfMeasureId, $derivativeId),
            'unitOfMeasureId' => $unitOfMeasureId,
            'unitOfMeasureName' => $unitOfMeasureName,
        ]);
    }

    public function update(
        UnitOfMeasureDerivativeData $unitOfMeasureDerivativesData,
        int $unitOfMeasureId,
        int $derivativeId,
        UnitOfMeasureQueries $unitOfMeasureQueries
    ): RedirectResponse {
        $this->validateUnitOfMeasureWithCompany($unitOfMeasureQueries, $unitOfMeasureId, session('admin_company_id'));

        $this->unitOfMeasureDerivativeQueries->update(
            $unitOfMeasureDerivativesData,
            $unitOfMeasureId,
            $derivativeId
        );

        return to_route('admin.unit_of_measure_derivatives.index', $unitOfMeasureId)
            ->with('success', 'Derivative updated successfully.');
    }

    public function exportDerivatives(Request $request, int $unitOfMeasureId, string $filename): BinaryFileResponse
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $unitOfMeasureDerivatives = $this->unitOfMeasureDerivativeQueries->getDerivativesExport(
            $filterData,
            $unitOfMeasureId,
            session('admin_company_id')
        );

        return Excel::download(new UnitOfMeasureDerivativeExport($unitOfMeasureDerivatives), $filename);
    }

    private function getUnitOfMeasure(
        UnitOfMeasureQueries $unitOfMeasureQueries,
        int $unitOfMeasureId,
        int $companyId
    ): array {
        $unitOfMeasure = $unitOfMeasureQueries->getById($unitOfMeasureId, $companyId);

        return [$unitOfMeasure->getName(), (bool) $unitOfMeasure->allow_decimal_qty];
    }

    private function validateUnitOfMeasureWithCompany(
        UnitOfMeasureQueries $unitOfMeasureQueries,
        int $unitOfMeasureId,
        int $companyId
    ): void {
        $unitOfMeasureQueries->getById($unitOfMeasureId, $companyId);
    }
}

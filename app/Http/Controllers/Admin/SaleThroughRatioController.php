<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\Permission\Enums\PermissionList;
use App\Domains\SaleThroughRatio\DataObjects\SaleThroughRatioData;
use App\Domains\SaleThroughRatio\Exports\SaleThroughRatioExport;
use App\Domains\SaleThroughRatio\SaleThroughRatioQueries;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SaleThroughRatioController extends Controller
{
    public function __construct(
        protected SaleThroughRatioQueries $saleThroughRatioQueries
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('sale_through_ratios/Index', [
            'exportPermission' => PermissionList::getExportPermissionName('sale_through_ratio'),
        ]);
    }

    /**
     * @return array<string, Collection>|array<string, int>
     */
    public function fetchSaleThroughRatios(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $lengthAwarePaginator = $this->saleThroughRatioQueries->listQuery($filterData, session('admin_company_id'));

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => $lengthAwarePaginator->getCollection(),
        ];
    }

    public function store(SaleThroughRatioData $saleThroughRatioData): RedirectResponse
    {
        $this->saleThroughRatioQueries->addNew($saleThroughRatioData, session('admin_company_id'));

        return to_route('admin.sale_through_ratios.index')
            ->with('success', 'Sale Through Ratio added successfully.');
    }

    public function edit(int $saleThroughRatioId): Response
    {
        $saleThroughRatio = $this->saleThroughRatioQueries->getById(
            $saleThroughRatioId,
            session('admin_company_id')
        );

        return Inertia::render('sale_through_ratios/Manage', [
            'saleThroughRatio' => $saleThroughRatio,
        ]);
    }

    public function update(SaleThroughRatioData $saleThroughRatioData, int $saleThroughRatioId): RedirectResponse
    {
        $this->saleThroughRatioQueries->update(
            $saleThroughRatioData,
            $saleThroughRatioId,
            session('admin_company_id')
        );

        return to_route('admin.sale_through_ratios.index')
            ->with('success', 'Sale Through Ratio has been updated successfully.');
    }

    public function exportSaleThroughRatios(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $saleThroughRatios = $this->saleThroughRatioQueries->getSaleThroughRatiosExport(
            $filterData,
            session('admin_company_id')
        );

        return Excel::download(new SaleThroughRatioExport($saleThroughRatios), $filename);
    }
}

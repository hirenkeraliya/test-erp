<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\ColorGroup\ColorGroupQueries;
use App\Domains\ColorGroup\DataObjects\ColorGroupData;
use App\Domains\ColorGroup\Exports\ColorGroupExport;
use App\Domains\Permission\Enums\PermissionList;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ColorGroupController extends Controller
{
    public function __construct(
        protected ColorGroupQueries $colorGroupQueries
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('color_group/Index', [
            'exportPermission' => PermissionList::getExportPermissionName('color_group'),
        ]);
    }

    /**
     * @return array<string, Collection>|array<string, int>
     */
    public function fetchColorGroups(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $lengthAwarePaginator = $this->colorGroupQueries->listQuery($filterData, session('admin_company_id'));

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => $lengthAwarePaginator->getCollection(),
        ];
    }

    public function store(ColorGroupData $colorGroupData): RedirectResponse
    {
        $this->colorGroupQueries->addNew($colorGroupData, session('admin_company_id'));

        return to_route('admin.color_groups.index')->with('success', 'The color group has been added successfully.');
    }

    public function edit(int $colorGroupId): Response
    {
        return Inertia::render('color_group/Manage', [
            'colorGroup' => $this->colorGroupQueries->getById($colorGroupId, session('admin_company_id')),
        ]);
    }

    public function update(ColorGroupData $colorGroupData, int $colorGroupId): RedirectResponse
    {
        $this->colorGroupQueries->update($colorGroupData, $colorGroupId, session('admin_company_id'));

        return to_route('admin.color_groups.index')->with('success', 'The color group has been updated successfully.');
    }

    public function exportColorGroups(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $colorGroups = $this->colorGroupQueries->getColorGroupsExport($filterData, session('admin_company_id'));

        return Excel::download(new ColorGroupExport($colorGroups), $filename);
    }

    public function getColorGroupSalesSummary(Request $request): array
    {
        $filterData = $request->all();
        $filterData['type'] = (int) $filterData['type'];
        $colorGroups = $this->colorGroupQueries->getColorGroupSalesSummary(
            $filterData,
            session('admin_company_id')
        );

        return [
            'color_groups' => $colorGroups,
            'total_sales' => $colorGroups->sum('total_sales'),
            'total_units_sold' => $colorGroups->sum('total_units_sold'),
        ];
    }
}

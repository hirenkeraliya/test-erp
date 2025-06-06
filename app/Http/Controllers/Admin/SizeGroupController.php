<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\Permission\Enums\PermissionList;
use App\Domains\SizeGroup\DataObjects\SizeGroupData;
use App\Domains\SizeGroup\Exports\SizeGroupExport;
use App\Domains\SizeGroup\SizeGroupQueries;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SizeGroupController extends Controller
{
    public function __construct(
        protected SizeGroupQueries $sizeGroupQueries
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('size_group/Index', [
            'exportPermission' => PermissionList::getExportPermissionName('size_group'),
        ]);
    }

    /**
     * @return array<string, Collection>|array<string, int>
     */
    public function fetchSizeGroups(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $lengthAwarePaginator = $this->sizeGroupQueries->listQuery($filterData, session('admin_company_id'));

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => $lengthAwarePaginator->getCollection(),
        ];
    }

    public function store(SizeGroupData $sizeGroupData): RedirectResponse
    {
        $this->sizeGroupQueries->addNew($sizeGroupData, session('admin_company_id'));

        return to_route('admin.size_groups.index')->with('success', 'The size group has been added successfully.');
    }

    public function edit(int $sizeGroupId): Response
    {
        return Inertia::render('size_group/Manage', [
            'sizeGroup' => $this->sizeGroupQueries->getById($sizeGroupId, session('admin_company_id')),
        ]);
    }

    public function update(SizeGroupData $sizeGroupData, int $sizeGroupId): RedirectResponse
    {
        $this->sizeGroupQueries->update($sizeGroupData, $sizeGroupId, session('admin_company_id'));

        return to_route('admin.size_groups.index')->with('success', 'The size group has been updated successfully.');
    }

    public function exportSizeGroups(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $sizeGroups = $this->sizeGroupQueries->getSizeGroupsExport($filterData, session('admin_company_id'));

        return Excel::download(new SizeGroupExport($sizeGroups), $filename);
    }
}

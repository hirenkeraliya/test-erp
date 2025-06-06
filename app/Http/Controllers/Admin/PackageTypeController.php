<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\PackageType\DataObjects\PackageTypeData;
use App\Domains\PackageType\Exports\PackageTypeExport;
use App\Domains\PackageType\PackageTypeQueries;
use App\Domains\Permission\Enums\PermissionList;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PackageTypeController extends Controller
{
    public function __construct(
        protected PackageTypeQueries $packageTypeQueries
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('package_types/Index', [
            'exportPermission' => PermissionList::getExportPermissionName('package_type'),
        ]);
    }

    /**
     * @return array<string, Collection>|array<string, int>
     */
    public function fetchPackageTypes(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $lengthAwarePaginator = $this->packageTypeQueries->listQuery($filterData, session('admin_company_id'));

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => $lengthAwarePaginator->getCollection(),
        ];
    }

    public function store(PackageTypeData $packageTypeData): RedirectResponse
    {
        $this->packageTypeQueries->addNew($packageTypeData, session('admin_company_id'));

        return to_route('admin.package_types.index')
            ->with('success', 'The package type has been added successfully.');
    }

    public function edit(int $packageTypeId): Response
    {
        return Inertia::render('package_types/Manage', [
            'packageType' => $this->packageTypeQueries->getById($packageTypeId, session('admin_company_id')),
        ]);
    }

    public function update(PackageTypeData $packageTypeData, int $packageTypeId): RedirectResponse
    {
        $this->packageTypeQueries->update($packageTypeData, $packageTypeId, session('admin_company_id'));

        return to_route('admin.package_types.index')
            ->with('success', 'The package type has been updated successfully.');
    }

    public function exportPackageType(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $packageType = $this->packageTypeQueries->getPackageTypeExport($filterData, session('admin_company_id'));

        return Excel::download(new PackageTypeExport($packageType), $filename);
    }
}

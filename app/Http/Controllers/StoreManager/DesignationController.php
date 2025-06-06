<?php

declare(strict_types=1);

namespace App\Http\Controllers\StoreManager;

use App\Domains\Designation\DataObjects\DesignationData;
use App\Domains\Designation\DesignationQueries;
use App\Domains\Designation\Exports\DesignationExport;
use App\Domains\Permission\Enums\PermissionList;
use App\Http\Controllers\Controller;
use App\Models\StoreManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DesignationController extends Controller
{
    public function __construct(
        protected DesignationQueries $designationQueries
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('designations/Index', [
            'exportPermission' => PermissionList::getExportPermissionName('designation'),
        ]);
    }

    /**
     * @return array<string, Collection>|array<string, int>
     */
    public function fetchDesignations(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $lengthAwarePaginator = $this->designationQueries->listQuery(
            $filterData,
            session('store_manager_selected_location_company_id')
        );

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => $lengthAwarePaginator->getCollection(),
        ];
    }

    public function store(DesignationData $designationData, Request $request): RedirectResponse
    {
        /** @var StoreManager $user */
        $user = $request->user();

        $this->designationQueries->addNew(
            $designationData,
            session('store_manager_selected_location_company_id'),
            $user
        );

        return to_route('store_manager.designations.index')->with('success', 'The designation was added successfully.');
    }

    public function edit(int $designationId): Response
    {
        return Inertia::render('designations/Manage', [
            'designation' => $this->designationQueries->getById(
                $designationId,
                session('store_manager_selected_location_company_id')
            ),
        ]);
    }

    public function update(DesignationData $designationData, int $designationId): RedirectResponse
    {
        $this->designationQueries->update(
            $designationData,
            $designationId,
            session('store_manager_selected_location_company_id')
        );

        return to_route('store_manager.designations.index')->with('success', 'Designation updated successfully.');
    }

    public function exportDesignations(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $designations = $this->designationQueries->getDesignationsExport(
            $filterData,
            session('store_manager_selected_location_company_id')
        );

        return Excel::download(new DesignationExport($designations), $filename);
    }
}

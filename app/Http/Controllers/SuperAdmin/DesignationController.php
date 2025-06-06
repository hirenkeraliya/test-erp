<?php

declare(strict_types=1);

namespace App\Http\Controllers\SuperAdmin;

use App\Domains\Company\CompanyQueries;
use App\Domains\Designation\DataObjects\SuperAdminDesignationData;
use App\Domains\Designation\DesignationQueries;
use App\Http\Controllers\Controller;
use App\Models\SuperAdmin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DesignationController extends Controller
{
    public function __construct(
        protected DesignationQueries $designationQueries
    ) {
    }

    /**
     * @return array<string, Collection>
     */
    public function getByCompanyId(int $companyId): array
    {
        $designations = $this->designationQueries->getByCompanyId($companyId);

        $designations->transform(fn ($designation): array => [
            'id' => $designation->id,
            'name' => $designation->name,
        ]);

        return [
            'data' => $designations,
        ];
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

        $lengthAwarePaginator = $this->designationQueries->listQueryForSuperAdmin($filterData);

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => $lengthAwarePaginator->getCollection(),
        ];
    }

    public function create(): Response
    {
        $companyQueries = new CompanyQueries();

        return Inertia::render('designations/Manage', [
            'companies' => $companyQueries->getWithBasicColumns(),
        ]);
    }

    public function store(SuperAdminDesignationData $designationData): RedirectResponse
    {
        /** @var SuperAdmin $superAdmin */
        $superAdmin = Auth::guard('super_admin')->user();

        $this->designationQueries->addForSuperAdmin($designationData, $superAdmin);

        return to_route('super_admin.designations.index')->with('success', 'The designation was added successfully.');
    }

    public function edit(int $designationId): Response
    {
        $companyQueries = resolve(CompanyQueries::class);

        return Inertia::render('designations/Manage', [
            'designation' => $this->designationQueries->getByIdWithoutCompanyFilter($designationId),
            'companies' => $companyQueries->getWithBasicColumns(),
        ]);
    }

    public function update(SuperAdminDesignationData $designationData, int $designationId): RedirectResponse
    {
        $this->designationQueries->updateForSuperAdmin($designationData, $designationId);

        return to_route('super_admin.designations.index')->with('success', 'Designation updated successfully.');
    }
}

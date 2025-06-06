<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\Brand\BrandQueries;
use App\Domains\Common\Enums\PriceOverrideTypes;
use App\Domains\Company\CompanyQueries;
use App\Domains\Employee\EmployeeQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\Permission\Enums\PermissionList;
use App\Domains\Role\RoleQueries;
use App\Domains\StoreManager\DataObjects\ChangePasscodeData;
use App\Domains\StoreManager\DataObjects\ChangePasswordData;
use App\Domains\StoreManager\DataObjects\StoreManagerData;
use App\Domains\StoreManager\Exports\StoreManagerBulkUpdateExport;
use App\Domains\StoreManager\Exports\StoreManagerExport;
use App\Domains\StoreManager\StoreManagerQueries;
use App\Exceptions\RedirectWithErrorException;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StoreManagerController extends Controller
{
    public function __construct(
        protected StoreManagerQueries $storeManagerQueries
    ) {
    }

    public function index(): Response
    {
        $companyId = session('admin_company_id');

        $locationQueries = resolve(LocationQueries::class);

        $locations = $locationQueries->getStoreWithBasicColumns($companyId);

        return Inertia::render('store_managers/Index', [
            'locations' => $locations,
            'exportPermission' => PermissionList::getExportPermissionName('store_manager'),
        ]);
    }

    /**
     * @return array<string, Collection>|array<string, int>
     */
    public function fetchStoreManagers(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'location_ids' => $request->get('location_ids'),
        ];

        $lengthAwarePaginator = $this->storeManagerQueries->listQuery($filterData, session('admin_company_id'));

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => $lengthAwarePaginator->getCollection(),
        ];
    }

    public function create(): Response
    {
        [$locations, $employees, $brands] = $this->fetchCommonRecords(session('admin_company_id'));
        $companyQueries = resolve(CompanyQueries::class);

        $allowPriceOverrideCartLevel = $companyQueries->getAllowPriceOverrideCartLevel(
            session('admin_company_id')
        );

        $roleQueries = resolve(RoleQueries::class);

        return Inertia::render('store_managers/Manage', [
            'locations' => $locations,
            'employees' => $employees,
            'brands' => $brands,
            'allowPriceOverrideCartLevel' => $allowPriceOverrideCartLevel,
            'priceOverrideTypes' => PriceOverrideTypes::getList(),
            'priceOverridePercentage' => PriceOverrideTypes::PERCENTAGE->value,
            'roles' => $roleQueries->getRoles('store_manager'),
        ]);
    }

    public function store(StoreManagerData $storeManagerData): RedirectResponse
    {
        $this->validateSelectedRecordsWithCompany(session('admin_company_id'), $storeManagerData);
        $this->storeManagerQueries->addNew($storeManagerData);

        return to_route('admin.store_managers.index')
            ->with('success', 'Store Manager added successfully.');
    }

    public function edit(int $storeManagerId): Response
    {
        [$locations, $employees, $brands] = $this->fetchCommonRecords(session('admin_company_id'));

        $companyQueries = resolve(CompanyQueries::class);

        $allowPriceOverrideCartLevel = $companyQueries->getAllowPriceOverrideCartLevel(
            session('admin_company_id')
        );

        $roleQueries = resolve(RoleQueries::class);

        return Inertia::render('store_managers/Manage', [
            'storeManager' => $this->storeManagerQueries->getByIdWithStores(
                $storeManagerId,
                session('admin_company_id')
            ),
            'locations' => $locations,
            'employees' => $employees,
            'brands' => $brands,
            'allowPriceOverrideCartLevel' => $allowPriceOverrideCartLevel,
            'priceOverrideTypes' => PriceOverrideTypes::getList(),
            'priceOverridePercentage' => PriceOverrideTypes::PERCENTAGE->value,
            'roles' => $roleQueries->getRoles('store_manager'),
        ]);
    }

    public function update(StoreManagerData $storeManagerData, int $storeManagerId): RedirectResponse
    {
        $this->validateSelectedRecordsWithCompany(session('admin_company_id'), $storeManagerData);

        $this->storeManagerQueries->update($storeManagerData, $storeManagerId, session('admin_company_id'));

        return to_route('admin.store_managers.index')
            ->with('success', 'Store Manager updated successfully.');
    }

    public function changePassword(int $storeManagerId): Response
    {
        return Inertia::render('store_managers/ChangePassword', [
            'storeManagerId' => $storeManagerId,
        ]);
    }

    public function updatePassword(ChangePasswordData $changePasswordData, int $storeManagerId): RedirectResponse
    {
        $storeManager = $this->storeManagerQueries->getById($storeManagerId, session('admin_company_id'));

        $this->storeManagerQueries->changePassword($storeManager, $changePasswordData);

        return to_route('admin.store_managers.index')
            ->with('success', 'Password updated successfully.');
    }

    public function changePasscode(int $storeManagerId): Response
    {
        return Inertia::render('store_managers/ChangePasscode', [
            'storeManagerId' => $storeManagerId,
        ]);
    }

    public function updatePasscode(ChangePasscodeData $changePasscodeData, int $storeManagerId): RedirectResponse
    {
        $storeManager = $this->storeManagerQueries->getById($storeManagerId, session('admin_company_id'));

        $this->storeManagerQueries->changePasscode($storeManager, $changePasscodeData);

        return to_route('admin.store_managers.index')
            ->with('success', 'Passcode updated successfully.');
    }

    public function getStoresStoreManagers(Request $request): array
    {
        $locationIds = $request->get('location_ids') ?: [];

        $storeManagers = $this->storeManagerQueries->getByStoreIdsWithEmployee($locationIds);
        $storeManagers->transform(function ($storeManager): array {
            /** @var Employee $employee */
            $employee = $storeManager->employee;

            return [
                'id' => $employee->id,
                'name' => $employee->getFullName(),
            ];
        });

        return [
            'store_managers' => $storeManagers,
        ];
    }

    public function getLocationsStoreManagers(Request $request): array
    {
        $locationIds = $request->get('location_ids');

        $storeManagers = $this->storeManagerQueries->getByLocationIdsWithEmployee($locationIds);
        $storeManagers->transform(function ($storeManager): array {
            /** @var Employee $employee */
            $employee = $storeManager->employee;

            return [
                'id' => $employee->id,
                'name' => $employee->getFullName(),
            ];
        });

        return [
            'store_managers' => $storeManagers,
        ];
    }

    public function getStoresOfStoreManagerId(int $storeManagerId): array
    {
        $storeManager = $this->storeManagerQueries->getByIdWithStores($storeManagerId, session('admin_company_id'));

        return [
            'locations' => $storeManager->locations,
        ];
    }

    public function exportStoreManagers(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'location_ids' => $request->get('location_ids'),
        ];

        $storeManagers = $this->storeManagerQueries->getStoreManagersExport($filterData, session('admin_company_id'));

        return Excel::download(new StoreManagerExport($storeManagers), $filename);
    }

    public function exportBulkUpdateStoreManagers(): BinaryFileResponse
    {
        $storeManagers = $this->storeManagerQueries->getStoreManagersForBulkUpdate(session('admin_company_id'));

        return Excel::download(new StoreManagerBulkUpdateExport($storeManagers), 'store-manager-bulk-update.xlsx');
    }

    /**
     * @return mixed[]
     */
    private function fetchCommonRecords(int $companyId): array
    {
        $locationQueries = resolve(LocationQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $companyQueries = resolve(CompanyQueries::class);

        $locations = $locationQueries->getStoreWithBasicColumns($companyId);
        $company = $companyQueries->getByIdWithBrands($companyId);
        $brands = $company->brands;

        $employees = $employeeQueries->getFormattedEmployeesOf($companyId);

        return [$locations, $employees, $brands];
    }

    private function validateSelectedRecordsWithCompany(int $companyId, StoreManagerData $storeManagerData): void
    {
        $locationQueries = resolve(LocationQueries::class);
        $brandQueries = resolve(BrandQueries::class);

        $allStoresExist = $locationQueries->doAllStoresExist($companyId, $storeManagerData->location_ids);
        $allBrandsExist = [];
        if ($storeManagerData->brand_ids) {
            $allBrandsExist = $brandQueries->doExistsById($companyId, $storeManagerData->brand_ids);
        }

        if (! $allStoresExist) {
            throw new RedirectWithErrorException(
                'admin.store_managers.index',
                'One of the selected stores does not match the current company.'
            );
        }

        if ($allBrandsExist) {
            return;
        }

        if ([] === $storeManagerData->brand_ids) {
            return;
        }

        throw new RedirectWithErrorException(
            'admin.store_managers.index',
            'One of the selected brands does not match the current company.'
        );
    }
}

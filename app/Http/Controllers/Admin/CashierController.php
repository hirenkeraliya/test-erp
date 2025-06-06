<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\Cashier\CashierQueries;
use App\Domains\Cashier\DataObjects\CashierChangePinData;
use App\Domains\Cashier\DataObjects\CashierData;
use App\Domains\Cashier\Exports\CashierBulkUpdateExport;
use App\Domains\Cashier\Exports\CashierExport;
use App\Domains\CashierGroup\CashierGroupQueries;
use App\Domains\Employee\EmployeeQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\Permission\Enums\PermissionList;
use App\Exceptions\RedirectBackWithErrorException;
use App\Exceptions\RedirectWithErrorException;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class CashierController extends Controller
{
    public function __construct(
        protected CashierQueries $cashierQueries
    ) {
    }

    public function index(): Response
    {
        $companyId = session('admin_company_id');

        $locationQueries = resolve(LocationQueries::class);

        $locations = $locationQueries->getStoreWithBasicColumns($companyId);

        return Inertia::render('cashiers/Index', [
            'locations' => $locations,
            'exportPermission' => PermissionList::getExportPermissionName('cashier'),
        ]);
    }

    /**
     * @return array<string, Collection>|array<string, int>
     */
    public function fetchCashiers(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'location_ids' => $request->get('location_ids'),
        ];

        $lengthAwarePaginator = $this->cashierQueries->listQuery($filterData, session('admin_company_id'));

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => $lengthAwarePaginator->getCollection(),
        ];
    }

    public function create(): Response
    {
        [$locations, $employees, $cashierGroups] = $this->fetchCommonRecords(session('admin_company_id'));

        return Inertia::render('cashiers/Manage', [
            'employees' => $employees,
            'cashierGroups' => $cashierGroups,
            'locations' => $locations,
        ]);
    }

    public function store(CashierData $cashierData, Request $request): RedirectResponse
    {
        $this->validateSelectedRecordsWithCompany(session('admin_company_id'), $cashierData);

        DB::beginTransaction();

        try {
            /** @var Admin $user */
            $user = $request->user();

            $this->cashierQueries->addNew($cashierData, $user);

            DB::commit();

            return to_route('admin.cashiers.index')
                ->with('success', 'Cashier added successfully.');
        } catch (Throwable $throwable) {
            Log::error('Add Cashier', [
                'error_message' => $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);

            DB::rollBack();

            throw new RedirectBackWithErrorException('An error occurred. Please try again.');
        }
    }

    public function edit(int $cashierId): Response
    {
        $cashier = $this->cashierQueries->getByIdWithLocations($cashierId, session('admin_company_id'));
        [$locations, $employees, $cashierGroups] = $this->fetchCommonRecords(session('admin_company_id'));

        return Inertia::render('cashiers/Manage', [
            'employees' => $employees,
            'cashierGroups' => $cashierGroups,
            'locations' => $locations,
            'cashier' => $cashier,
        ]);
    }

    public function update(CashierData $cashierData, int $cashierId): RedirectResponse
    {
        $this->validateSelectedRecordsWithCompany(session('admin_company_id'), $cashierData);

        DB::beginTransaction();

        try {
            $this->cashierQueries->update($cashierData, $cashierId, session('admin_company_id'));

            DB::commit();

            return to_route('admin.cashiers.index')
                ->with('success', 'Cashier updated successfully.');
        } catch (Throwable $throwable) {
            Log::error('Update Cashier', [
                'error_message' => $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);

            DB::rollBack();

            throw new RedirectBackWithErrorException('An error occurred. Please try again.');
        }
    }

    public function changePin(int $cashierId): Response
    {
        return Inertia::render('cashiers/ChangePin', [
            'cashierId' => $cashierId,
        ]);
    }

    public function updatePin(CashierChangePinData $cashierChangePinData, int $cashierId): RedirectResponse
    {
        $cashier = $this->cashierQueries->getById($cashierId, session('admin_company_id'));

        $this->cashierQueries->changePin($cashier, $cashierChangePinData);

        return to_route('admin.cashiers.index')
            ->with('success', 'Pin updated successfully.');
    }

    public function exportCashiers(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'location_ids' => $request->get('location_ids'),
        ];

        $cashiers = $this->cashierQueries->getCashiersExport($filterData, session('admin_company_id'));

        return Excel::download(new CashierExport($cashiers), $filename);
    }

    public function exportBulkUpdateCashiers(): BinaryFileResponse
    {
        $cashiers = $this->cashierQueries->getCashiersForBulkUpdate(session('admin_company_id'));

        return Excel::download(new CashierBulkUpdateExport($cashiers), 'cashiers-bulk-update.xlsx');
    }

    /**
     * @return array<string, mixed>
     */
    public function getStoreCashiers(int $locationId): array
    {
        $cashiers = $this->cashierQueries->getCashierListOfSelectedLocation($locationId, session('admin_company_id'));

        return [
            'cashiers' => $this->preparedCashier($cashiers),
        ];
    }

    /**
     * @return array<string, Collection>
     */
    public function getCashiersOfStores(Request $request): array
    {
        $locationIds = $request->get('location_ids');

        $cashiers = $this->cashierQueries->getCashiersOfLocations([$locationIds], session('admin_company_id'));

        return [
            'cashiers' => $this->preparedCashier($cashiers),
        ];
    }

    private function preparedCashier(Collection $cashiers): Collection
    {
        return $cashiers->transform(function ($cashier): array {
            /** @var Employee $employee */
            $employee = $cashier->employee;

            return [
                'id' => $cashier->id,
                'name' => $employee->getFullName(),
            ];
        });
    }

    /**
     * @return array<int, mixed[]>|Collection[]
     */
    private function fetchCommonRecords(int $companyId): array
    {
        $locationQueries = resolve(LocationQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $cashierGroupQueries = resolve(CashierGroupQueries::class);

        $employees = $employeeQueries->getFormattedEmployeesOf($companyId);

        $locations = $locationQueries->getStoreWithBasicColumns($companyId);

        $cashierGroups = $cashierGroupQueries->getWithBasicColumns($companyId);

        return [$locations, $employees, $cashierGroups];
    }

    private function validateSelectedRecordsWithCompany(int $companyId, CashierData $cashierData): void
    {
        $locationQueries = resolve(LocationQueries::class);

        $allStoresExist = $locationQueries->doAllStoresExist($companyId, $cashierData->location_ids);

        if (! $allStoresExist) {
            throw new RedirectWithErrorException(
                'admin.cashiers.index',
                'One of the selected stores does not match the current company.'
            );
        }
    }
}

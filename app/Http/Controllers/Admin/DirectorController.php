<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\Common\Enums\PriceOverrideTypes;
use App\Domains\Company\CompanyQueries;
use App\Domains\Director\DataObjects\ChangePasscodeData;
use App\Domains\Director\DataObjects\DirectorData;
use App\Domains\Director\DirectorQueries;
use App\Domains\Director\Exports\DirectorExport;
use App\Domains\Employee\EmployeeQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\Permission\Enums\PermissionList;
use App\Exceptions\RedirectBackWithErrorException;
use App\Exceptions\RedirectWithErrorException;
use App\Http\Controllers\Controller;
use App\Models\Admin;
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

class DirectorController extends Controller
{
    public function __construct(
        protected DirectorQueries $directorQueries
    ) {
    }

    public function index(): Response
    {
        $companyId = session('admin_company_id');

        $locationQueries = resolve(LocationQueries::class);

        $locations = $locationQueries->getStoreWithBasicColumns($companyId);

        return Inertia::render('directors/Index', [
            'locations' => $locations,
            'exportPermission' => PermissionList::getExportPermissionName('director'),
        ]);
    }

    /**
     * @return array<string, Collection>|array<string, int>
     */
    public function fetchDirectors(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'location_ids' => $request->get('location_ids'),
        ];

        $lengthAwarePaginator = $this->directorQueries->listQuery($filterData, session('admin_company_id'));

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => $lengthAwarePaginator->getCollection(),
        ];
    }

    public function create(): Response
    {
        [$locations, $employees] = $this->fetchCommonRecords(session('admin_company_id'));

        $companyQueries = resolve(CompanyQueries::class);
        $allowPriceOverrideCartLevel = $companyQueries->getAllowPriceOverrideCartLevel(session('admin_company_id'));

        return Inertia::render('directors/Manage', [
            'locations' => $locations,
            'employees' => $employees,
            'allowPriceOverrideCartLevel' => $allowPriceOverrideCartLevel,
            'priceOverrideTypes' => PriceOverrideTypes::getList(),
            'priceOverridePercentage' => PriceOverrideTypes::PERCENTAGE->value,
        ]);
    }

    public function store(DirectorData $directorData, Request $request): RedirectResponse
    {
        $this->validateSelectedRecordsWithCompany(session('admin_company_id'), $directorData);

        DB::beginTransaction();

        try {
            /** @var Admin $user */
            $user = $request->user();

            $this->directorQueries->addNew($directorData, $user);

            DB::commit();

            return to_route('admin.directors.index')
                ->with('success', 'Director added successfully.');
        } catch (Throwable $throwable) {
            Log::error('Add Director', [
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

    public function edit(int $directorId): Response
    {
        $director = $this->directorQueries->getByIdWithEmployeeAndLocations($directorId, session('admin_company_id'));

        [$locations, $employees] = $this->fetchCommonRecords(session('admin_company_id'));

        $companyQueries = resolve(CompanyQueries::class);
        $allowPriceOverrideCartLevel = $companyQueries->getAllowPriceOverrideCartLevel(session('admin_company_id'));

        return Inertia::render('directors/Manage', [
            'director' => $director,
            'locations' => $locations,
            'employees' => $employees,
            'allowPriceOverrideCartLevel' => $allowPriceOverrideCartLevel,
            'priceOverrideTypes' => PriceOverrideTypes::getList(),
            'priceOverridePercentage' => PriceOverrideTypes::PERCENTAGE->value,
        ]);
    }

    public function update(DirectorData $directorData, int $directorId): RedirectResponse
    {
        $this->validateSelectedRecordsWithCompany(session('admin_company_id'), $directorData);

        DB::beginTransaction();

        try {
            $this->directorQueries->update($directorData, $directorId, session('admin_company_id'));

            DB::commit();

            return to_route('admin.directors.index')
                ->with('success', 'The director was successfully updated.');
        } catch (Throwable $throwable) {
            Log::error('Update Director', [
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

    public function changePasscode(int $directorId): Response
    {
        return Inertia::render('directors/ChangePasscode', [
            'directorId' => $directorId,
        ]);
    }

    public function updatePasscode(ChangePasscodeData $changePasscodeData, int $directorId): RedirectResponse
    {
        $director = $this->directorQueries->getById($directorId, session('admin_company_id'));

        $this->directorQueries->changePasscode($director, $changePasscodeData);

        return to_route('admin.directors.index')
            ->with('success', 'Passcode updated successfully.');
    }

    public function exportDirectors(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'location_ids' => $request->get('location_ids'),
        ];

        $directors = $this->directorQueries->getDirectorsExport($filterData, session('admin_company_id'));

        return Excel::download(new DirectorExport($directors), $filename);
    }

    private function validateSelectedRecordsWithCompany(int $companyId, DirectorData $directorData): void
    {
        $locationQueries = resolve(LocationQueries::class);

        $allLocationsExist = $locationQueries->doAllStoresExist($companyId, $directorData->location_ids);

        if (! $allLocationsExist) {
            throw new RedirectWithErrorException(
                'admin.directors.index',
                'One of the selected locations does not match the current company.'
            );
        }
    }

    /**
     * @return array<int, mixed[]>|Collection[]
     */
    private function fetchCommonRecords(int $companyId): array
    {
        $locationQueries = resolve(LocationQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);

        $employees = $employeeQueries->getFormattedEmployeesOf($companyId);

        $locations = $locationQueries->getStoreWithBasicColumns($companyId);

        return [$locations, $employees];
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\StoreManager;

use App\Domains\CashierGroup\CashierGroupQueries;
use App\Domains\CashierGroup\DataObjects\CashierGroupData;
use App\Domains\CashierGroup\Enums\PermissionTypes;
use App\Domains\CashierGroup\Exports\CashierGroupExport;
use App\Domains\Common\Enums\PriceOverrideTypes;
use App\Domains\Company\CompanyQueries;
use App\Domains\Permission\Enums\PermissionList;
use App\Exceptions\RedirectBackWithErrorException;
use App\Http\Controllers\Controller;
use App\Models\CashierGroup;
use App\Models\StoreManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class CashierGroupController extends Controller
{
    public function __construct(
        protected CashierGroupQueries $cashierGroupQueries
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('cashier_groups/Index', [
            'exportPermission' => PermissionList::getExportPermissionName('cashier_group'),
        ]);
    }

    /**
     * @return array<string, Collection>|array<string, int>
     */
    public function fetchCashierGroups(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $lengthAwarePaginator = $this->cashierGroupQueries->listQuery(
            $filterData,
            session('store_manager_selected_location_company_id')
        );

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => $lengthAwarePaginator->getCollection(),
        ];
    }

    public function create(): Response
    {
        $companyQueries = resolve(CompanyQueries::class);
        $allowPriceOverrideCartLevel = $companyQueries->getAllowPriceOverrideCartLevel(
            session('store_manager_selected_location_company_id')
        );

        return Inertia::render('cashier_groups/Manage', [
            'permissionTypes' => PermissionTypes::formattedForSelection(),
            'allowPriceOverrideCartLevel' => $allowPriceOverrideCartLevel,
            'priceOverrideTypes' => PriceOverrideTypes::getList(),
            'priceOverridePercentage' => PriceOverrideTypes::PERCENTAGE->value,
        ]);
    }

    public function store(CashierGroupData $cashierGroupData, Request $request): RedirectResponse
    {
        DB::beginTransaction();

        try {
            /** @var StoreManager $user */
            $user = $request->user();

            $this->cashierGroupQueries->addNew(
                $cashierGroupData,
                session('store_manager_selected_location_company_id'),
                $user
            );

            DB::commit();

            return to_route('store_manager.cashier_groups.index')->with('success', 'Cashier group added successfully.');
        } catch (Throwable $throwable) {
            Log::error('Store Manager Add Cashier Group', [
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

    public function edit(int $cashierGroupId): Response
    {
        $cashierGroup = $this->cashierGroupQueries->getByIdWithPermissions(
            $cashierGroupId,
            session('store_manager_selected_location_company_id')
        );

        $companyQueries = resolve(CompanyQueries::class);
        $allowPriceOverrideCartLevel = $companyQueries->getAllowPriceOverrideCartLevel(
            session('store_manager_selected_location_company_id')
        );

        return Inertia::render('cashier_groups/Manage', [
            'cashierGroup' => $this->prepareCashierGroupPermission($cashierGroup),
            'permissionTypes' => PermissionTypes::formattedForSelection(),
            'allowPriceOverrideCartLevel' => $allowPriceOverrideCartLevel,
            'priceOverrideTypes' => PriceOverrideTypes::getList(),
            'priceOverridePercentage' => PriceOverrideTypes::PERCENTAGE->value,
        ]);
    }

    public function update(CashierGroupData $cashierGroupData, int $cashierGroupId): RedirectResponse
    {
        DB::beginTransaction();

        try {
            $this->cashierGroupQueries->update(
                $cashierGroupData,
                $cashierGroupId,
                session('store_manager_selected_location_company_id')
            );

            DB::commit();

            $this->removeFromCache($cashierGroupId);

            return to_route('store_manager.cashier_groups.index')->with(
                'success',
                'Cashier group updated successfully.'
            );
        } catch (Throwable $throwable) {
            Log::error('Store Manager Update Cashier Group', [
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

    public function exportCashierGroups(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $cashierGroups = $this->cashierGroupQueries->getCashierGroupsExport(
            $filterData,
            session('store_manager_selected_location_company_id')
        );

        return Excel::download(new CashierGroupExport($cashierGroups), $filename);
    }

    private function prepareCashierGroupPermission(CashierGroup $cashierGroup): CashierGroup
    {
        $cashierGroup['permission_details'] = $cashierGroup->permissions->map(fn ($permission): array => [
            'id' => $permission->getPermissionId(),
            'name' => PermissionTypes::getFormattedCaseName($permission->getPermissionId()),
        ])->toArray();

        return $cashierGroup;
    }

    private function removeFromCache(int $cashierGroupId): void
    {
        Cache::forget('cashier_group_permission_' . $cashierGroupId);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\StoreManager;

use App\CommonFunctions;
use App\Domains\Employee\EmployeeQueries;
use App\Domains\Promoter\PromoterQueries;
use App\Domains\Promoter\Resources\StoreManagerPromoterListResource;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Promoter;
use App\Models\StoreManager;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PromoterController extends Controller
{
    public function getLists(Request $request): array
    {
        $validatedData = $request->validate([
            'store_id' => ['required_without_all:location_id', 'integer', 'exists:locations,id'],
            'location_id' => ['required_without_all:store_id', 'integer', 'exists:locations,id'],
            'search_text' => ['sometimes', 'nullable', 'string'],
        ]);

        $filterData = [
            'location_id' => $validatedData['store_id'] ?? $validatedData['location_id'],
            'search_text' => $validatedData['search_text'] ?? null,
        ];

        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);
        /** @var int $companyId */
        $companyId = $employeeQueries->getEmployeeCompanyId($storeManager->employee_id);

        $promoterQueries = resolve(PromoterQueries::class);
        $promoters = $promoterQueries->getPromoterListWithLocationsForStoreManagerAPI($companyId, $filterData);

        return [
            'promoters' => StoreManagerPromoterListResource::collection($promoters),
        ];
    }

    public function getTopPromoters(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'store_id' => ['required_without_all:location_id', 'integer'],
            'location_id' => ['required_without_all:store_id', 'integer'],
            'date' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'start_date' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'end_date' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
        ]);

        $dateRang = [Carbon::now()->format('Y-m-d'), Carbon::now()->format('Y-m-d')];

        if (! $validator->fails() && $request->get('start_date') && $request->get('end_date')) {
            $dateRang = [$request->get('start_date'), $request->get('end_date')];
        }

        if (! $validator->fails() && $request->get('date')) {
            $dateRang = [$request->get('date'), $request->get('date')];
        }

        $locationId = $request->get('store_id') ?? $request->get('location_id');

        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);
        /** @var int $companyId */
        $companyId = $employeeQueries->getEmployeeCompanyId($storeManager->employee_id);

        return [
            'topPromoters' => $this->getSalesByPromoter($companyId, (int) $locationId, $dateRang),
        ];
    }

    public function updateStatus(Request $request): void
    {
        $validatedData = $request->validate([
            'promoter_id' => ['required', 'integer', Rule::exists('promoters', 'id')],
            'status' => ['required', 'boolean'],
        ]);

        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);
        /** @var int $companyId */
        $companyId = $employeeQueries->getEmployeeCompanyId($storeManager->employee_id);

        $promoterId = (int) $validatedData['promoter_id'];
        $status = (bool) $validatedData['status'];

        $promoterQueries = resolve(PromoterQueries::class);
        $promoter = $promoterQueries->getPromoterById($promoterId, $companyId, $status);
        $this->validateStatus($promoter, $status);

        if ($promoter) {
            /** @var Employee $employee */
            $employee = $promoter->employee;

            $employeeQueries->statusChange($employee, $status);
        }
    }

    private function validateStatus(?Promoter $promoter, bool $activate): void
    {
        if (! $promoter instanceof Promoter) {
            abort(412, 'Specified Promoter ID Status is already ' . ($activate ? 'activated' : 'deactivated') . '.');
        }
    }

    private function getSalesByPromoter(int $companyId, int $locationId, array $dateRange): Collection
    {
        $promoterQueries = resolve(PromoterQueries::class);
        $promoters = $promoterQueries->getSalesByPromotersForDashboard(
            $companyId,
            $locationId,
            null,
            $dateRange[0],
            $dateRange[1],
            false
        );

        return $promoters->map(function (Promoter $promoter): array {
            /** @var Employee $employee */
            $employee = $promoter->employee;

            return [
                'id' => $promoter['id'],
                'name' => $employee->getFullName() . '(' . $employee->staff_id . ')',
                'total_units_sold' => CommonFunctions::truncateDecimal((float) $promoter['units_sold']),
                'net_sales' => CommonFunctions::numberFormat((float) $promoter['amount_sold']),
                'total_units_returned' => CommonFunctions::truncateDecimal((float) $promoter['total_units_returned']),
            ];
        });
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\PosAdmin;

use App\Domains\Common\Services\DashboardService;
use App\Domains\Company\CompanyQueries;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DashboardController extends Controller
{
    public function getCompanyDailyTotals(Request $request): array
    {
        Validator::make($request->all(), [
            'from_date' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'to_date' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
        ]);

        $fromDate = Carbon::now()->format('Y-m-d');
        $toDate = Carbon::now()->format('Y-m-d');

        if ($request->input('from_date')) {
            $fromDate = $request->input('from_date');
        }

        if ($request->input('to_date')) {
            $toDate = $request->input('to_date');
        }

        $companyId = (string) $request->input('company_id');

        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getCompanyIdsByUuid($companyId);

        $dashboardService = resolve(DashboardService::class);

        return $dashboardService->getAllSalesDetailsByCompanyId($company->id, $fromDate, $toDate);
    }
}

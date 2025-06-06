<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Promoter;

use App\Domains\Cashback\CashbackQueries;
use App\Domains\Cashback\Resources\ApplicationCashbackListResource;
use App\Domains\Employee\EmployeeQueries;
use App\Http\Controllers\Controller;
use App\Models\Promoter;
use Illuminate\Http\Request;

class CashbackController extends Controller
{
    public function getStoreWiseCashbacks(Request $request, int $locationId): array
    {
        /** @var Promoter $promoter */
        $promoter = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);

        /** @var int $companyId */
        $companyId = $employeeQueries->getEmployeeCompanyId($promoter->employee_id);

        $cashbackQueries = resolve(CashbackQueries::class);
        $cashbacks = $cashbackQueries->getCashbacksStoreWiseForApplication($companyId, $locationId);

        return [
            'data' => ApplicationCashbackListResource::collection($cashbacks),
        ];
    }
}

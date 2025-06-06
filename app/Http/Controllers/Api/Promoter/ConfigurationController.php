<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Promoter;

use App\Domains\Currency\CurrencyQueries;
use App\Domains\Employee\EmployeeQueries;
use App\Http\Controllers\Controller;
use App\Models\Promoter;
use Illuminate\Http\Request;

class ConfigurationController extends Controller
{
    public function getConfiguration(Request $request): array
    {
        /** @var Promoter $promoter */
        $promoter = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);

        /** @var int $companyId */
        $companyId = $employeeQueries->getEmployeeCompanyId($promoter->employee_id);

        $currencyQueries = resolve(CurrencyQueries::class);
        $currency = $currencyQueries->getByCompanyId($companyId);

        return [
            'currency_symbol' => $currency->getSymbol(),
        ];
    }
}

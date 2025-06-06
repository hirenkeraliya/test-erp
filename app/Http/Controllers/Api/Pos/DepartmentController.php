<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Pos;

use App\CommonFunctions;
use App\Domains\Department\DepartmentQueries;
use App\Http\Controllers\Controller;
use App\Models\Cashier;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function getList(Request $request): array
    {
        /** @var Cashier $cashier */
        $cashier = $request->user();

        $validatedData = $request->validate([
            'after_updated_at' => ['sometimes', 'nullable', 'string', 'date_format:Y-m-d H:i:s'],
        ]);

        $afterUpdatedAt = $validatedData['after_updated_at'] ?? null;

        $companyId = CommonFunctions::getCashierCompanyId($cashier);

        $departmentQueries = resolve(DepartmentQueries::class);
        $departments = $departmentQueries->getWithBasicColumns($companyId, $afterUpdatedAt);

        return [
            'departments' => $departments,
        ];
    }
}

<?php

namespace App\Http\Controllers\Api\WarehouseManager;

use App\Domains\Employee\EmployeeQueries;
use App\Domains\PackageType\PackageTypeQueries;
use App\Models\WarehouseManager;
use Illuminate\Http\Request;

class PackageTypeController
{
    public function __construct(
        protected PackageTypeQueries $packageTypeQueries
    ) {
    }

    public function getList(Request $request): array
    {
        /** @var WarehouseManager $warehouseManager */
        $warehouseManager = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);

        /** @var int $companyId */
        $companyId = $employeeQueries->getEmployeeCompanyId($warehouseManager->employee_id);

        return [
            'package_type' => $this->packageTypeQueries->getLists($companyId),
        ];
    }
}

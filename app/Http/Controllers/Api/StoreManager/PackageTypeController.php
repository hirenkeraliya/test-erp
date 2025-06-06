<?php

namespace App\Http\Controllers\Api\StoreManager;

use App\Domains\Employee\EmployeeQueries;
use App\Domains\PackageType\PackageTypeQueries;
use App\Models\StoreManager;
use Illuminate\Http\Request;

class PackageTypeController
{
    public function __construct(
        protected PackageTypeQueries $packageTypeQueries
    ) {
    }

    public function getList(Request $request): array
    {
        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);

        /** @var int $companyId */
        $companyId = $employeeQueries->getEmployeeCompanyId($storeManager->employee_id);

        return [
            'package_type' => $this->packageTypeQueries->getLists($companyId),
        ];
    }
}

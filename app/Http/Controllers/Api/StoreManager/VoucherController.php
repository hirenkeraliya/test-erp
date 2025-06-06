<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\StoreManager;

use App\Domains\Employee\EmployeeQueries;
use App\Domains\Voucher\Resources\AppVoucherListResource;
use App\Domains\Voucher\VoucherQueries;
use App\Http\Controllers\Controller;
use App\Models\StoreManager;
use Illuminate\Http\Request;

class VoucherController extends Controller
{
    public function getStoreWiseVouchers(Request $request, int $locationId): array
    {
        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);
        /** @var int $companyId */
        $companyId = $employeeQueries->getEmployeeCompanyId($storeManager->employee_id);

        $voucherQueries = resolve(VoucherQueries::class);
        $voucherList = $voucherQueries->getVoucherStoreWiseForApplication($companyId, $locationId);

        return [
            'vouchers' => AppVoucherListResource::collection($voucherList),
        ];
    }
}

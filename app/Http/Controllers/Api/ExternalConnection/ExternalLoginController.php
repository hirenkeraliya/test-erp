<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\ExternalConnection;

use App\Domains\Admin\AdminQueries;
use App\Domains\ExternalCompany\ExternalCompanyQueries;
use App\Domains\WarehouseManager\WarehouseManagerQueries;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class ExternalLoginController extends Controller
{
    public function verifyExternalToken(Request $request): array
    {
        $decryptedToken = Crypt::decryptString($request->token);
        $tokenData = explode('|', $decryptedToken);

        $warehouseManagerId = (int) $tokenData[0];
        $warehouseManagerToken = $tokenData[1];
        $externalCompanyId = (int) $tokenData[2];

        $warehouseManagerQueries = resolve(WarehouseManagerQueries::class);
        $warehouseManager = $warehouseManagerQueries->getByIdAndExternalLoginToken(
            $warehouseManagerId,
            $warehouseManagerToken
        );

        /** @var Employee $employee */
        $employee = $warehouseManager->employee;

        $externalCompanyQueries = resolve(ExternalCompanyQueries::class);
        $externalCompany = $externalCompanyQueries->getByIdWithExternalCompanyId($externalCompanyId);

        return [
            'staff_id' => $employee->staff_id,
            'company_id' => $externalCompany->external_company_id,
        ];
    }

    public function adminVerifyExternalToken(Request $request): array
    {
        $decryptedToken = Crypt::decryptString($request->token);
        $tokenData = explode('|', $decryptedToken);

        $adminId = (int) $tokenData[0];
        $adminToken = $tokenData[1];
        $externalCompanyId = (int) $tokenData[2];

        $adminQueries = resolve(AdminQueries::class);
        $admin = $adminQueries->getByIdAndExternalLoginToken($adminId, $adminToken);

        /** @var Employee $employee */
        $employee = $admin->employee;

        $externalCompanyQueries = resolve(ExternalCompanyQueries::class);
        $externalCompany = $externalCompanyQueries->getByIdWithExternalCompanyId($externalCompanyId);

        return [
            'staff_id' => $employee->staff_id,
            'company_id' => $externalCompany->external_company_id,
        ];
    }
}

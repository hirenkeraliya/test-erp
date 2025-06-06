<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\ExternalConnection;

use App\Domains\ExternalCompany\ExternalCompanyQueries;
use App\Domains\ExternalConnection\ExternalConnectionQueries;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ExternalCompanyController extends Controller
{
    public function externalCompanyArchive(Request $request): void
    {
        $externalConnectionQueries = resolve(ExternalConnectionQueries::class);
        $externalConnection = $externalConnectionQueries->getByToken($request->token);
        $externalCompanyId = (int) $request->external_company_id;

        $externalCompanyQueries = resolve(ExternalCompanyQueries::class);
        $externalCompanyQueries->delete($externalConnection->id, $externalCompanyId);
    }

    public function externalCompanyRestore(Request $request): void
    {
        $externalConnectionQueries = resolve(ExternalConnectionQueries::class);
        $externalConnection = $externalConnectionQueries->getByToken($request->token);
        $externalCompanyId = (int) $request->external_company_id;

        $externalCompanyQueries = resolve(ExternalCompanyQueries::class);
        $externalCompanyQueries->restore($externalConnection->id, $externalCompanyId);
    }
}

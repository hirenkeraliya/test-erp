<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\PosAdmin;

use App\Domains\Company\CompanyQueries;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function getCompanyByUuid(Request $request): array
    {
        $validatedData = $request->validate([
            'uuid' => ['required', 'string', 'uuid'],
        ]);

        $companyQueries = resolve(CompanyQueries::class);
        $companyExists = $companyQueries->doesCompanyExist($validatedData['uuid']);

        return [
            'status' => $companyExists,
        ];
    }
}

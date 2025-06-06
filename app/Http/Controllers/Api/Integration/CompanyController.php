<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Integration;

use App\Domains\Company\CompanyQueries;
use App\Http\Controllers\Controller;

class CompanyController extends Controller
{
    public function getAllCompanies(): array
    {
        $companyQueries = resolve(CompanyQueries::class);

        return [
            'companies' => $companyQueries->getAllCompanies(),
        ];
    }
}

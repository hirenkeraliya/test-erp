<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\ExternalConnection;

use App\Domains\Company\CompanyQueries;
use App\Http\Controllers\Controller;
use Illuminate\Support\Collection;

class CompanyController extends Controller
{
    public function getCompanies(): Collection
    {
        $companyQueries = resolve(CompanyQueries::class);
        $companies = $companyQueries->getList();

        return $companies->map(fn ($company): array => [
            'id' => $company->id,
            'name' => $company->name,
            'code' => $company->code,
            'email' => $company->email,
            'fax' => $company->fax,
            'address' => $company->address,
            'social_security_number' => $company->social_security_number,
            'light_logo' => $company->getDiskBasedFirstMediaUrl('light_logo'),
            'dark_logo' => $company->getDiskBasedFirstMediaUrl('dark_logo'),
            'email_footer_logo' => $company->getDiskBasedFirstMediaUrl('email_footer_logo'),
        ]);
    }
}

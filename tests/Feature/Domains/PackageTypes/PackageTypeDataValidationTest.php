<?php

declare(strict_types=1);

use App\Domains\PackageType\DataObjects\PackageTypeData;
use App\Models\Company;
use App\Models\PackageType;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

test('package type with the same name cannot be added for the same company', function (): void {
    $companyId = Company::factory()->create()->id;

    setCompanyIdInSession($companyId);

    PackageType::factory()->create([
        'company_id' => $companyId,
        'name' => 'package type',
    ]);

    $request = new Request([
        'company_id' => $companyId,
        'name' => 'package type',
    ]);

    PackageTypeData::validate($request);
})->throws(ValidationException::class);

test('package type with the same name can be added for a different company', function (): void {
    $companyId = Company::factory()->create()->id;

    setCompanyIdInSession($companyId);

    PackageType::factory()->create([
        'name' => 'package type',
    ]);

    $request = new Request([
        'company_id' => $companyId,
        'name' => 'different name',
    ]);

    PackageTypeData::validate($request);
    $this->assertTrue(true);
});

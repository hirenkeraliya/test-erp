<?php

declare(strict_types=1);

use App\Domains\UnitOfMeasure\DataObjects\UnitOfMeasureData;
use App\Models\Company;
use App\Models\UnitOfMeasure;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

test('unit of measure with the same name cannot be added for the same company', function (): void {
    $companyId = Company::factory()->create()->id;

    setCompanyIdInSession($companyId);

    UnitOfMeasure::factory()->create([
        'company_id' => $companyId,
        'name' => 'unit of measure',
        'allow_decimal_qty' => true,
    ]);

    $request = new Request([
        'company_id' => $companyId,
        'name' => 'unit of measure',
    ]);

    UnitOfMeasureData::validate($request);
})->throws(ValidationException::class);

test('unit of measure with the same name can be added for a different company', function (): void {
    $companyId = Company::factory()->create()->id;

    setCompanyIdInSession($companyId);

    UnitOfMeasure::factory()->create([
        'name' => 'unit of measure',
        'allow_decimal_qty' => true,
    ]);

    $request = new Request([
        'company_id' => $companyId,
        'name' => 'different name',
        'allow_decimal_qty' => true,
    ]);

    UnitOfMeasureData::validate($request);
    $this->assertTrue(true);
});

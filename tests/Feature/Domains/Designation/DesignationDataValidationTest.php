<?php

declare(strict_types=1);

use App\Domains\Designation\DataObjects\DesignationData;
use App\Models\Company;
use App\Models\Designation;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

beforeEach(function (): void {
    $this->companyAId = Company::factory()->create()->id;
    $this->companyBId = Company::factory()->create()->id;

    $this->designationA = Designation::factory()->create([
        'company_id' => $this->companyAId,
        'name' => 'ABC',
    ]);

    setCompanyIdInSession($this->companyAId);
});

test('admin cannot add same name with same company.', function (): void {
    $request = new Request([
        'name' => $this->designationA->name,
        'code' => $this->designationA->code,
    ]);

    DesignationData::validate($request);
})->throws(ValidationException::class);

test('admin can add same name with different company.', function (): void {
    setCompanyIdInSession($this->companyBId);

    $request = new Request([
        'company_id' => $this->companyBId,
        'name' => $this->designationA->name,
        'code' => $this->designationA->code,
    ]);

    DesignationData::validate($request);
    $this->assertTrue(true);
});

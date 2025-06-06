<?php

declare(strict_types=1);

use App\Domains\Common\Enums\DiscountTypes;
use App\Domains\Department\DataObjects\DepartmentData;
use App\Models\Company;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

beforeEach(function (): void {
    $this->companyAId = Company::factory()->create()->id;
    $this->companyBId = Company::factory()->create()->id;

    $this->departmentA = Department::factory()->create([
        'company_id' => $this->companyAId,
        'name' => 'Department 1',
        'code' => 'Department001',
        'discount_type' => DiscountTypes::FLAT->value,
    ]);
    $this->departmentB = Department::factory()->create([
        'company_id' => $this->companyBId,
        'name' => 'Department 2',
        'code' => 'Department002',
    ]);

    setCompanyIdInSession($this->companyAId);
});

test('user cannot add same department name with same company.', function (): void {
    $request = new Request([
        'name' => $this->departmentA->name,
        'code' => $this->departmentA->code,
        'discount_type' => $this->departmentA->discount_type,
        'flat_commission' => $this->departmentA->flat_commission,
    ]);

    DepartmentData::validate($request);
})->throws(ValidationException::class);

test('user can add same department name with different company.', function (): void {
    setCompanyIdInSession($this->companyBId);

    $request = new Request([
        'name' => $this->departmentA->name,
        'code' => $this->departmentA->code,
        'discount_type' => $this->departmentA->discount_type,
        'flat_commission' => $this->departmentA->flat_commission,
    ]);

    DepartmentData::validate($request);
    $this->assertTrue(true);
});

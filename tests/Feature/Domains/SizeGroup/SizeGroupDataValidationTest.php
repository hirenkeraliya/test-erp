<?php

declare(strict_types=1);

use App\Domains\SizeGroup\DataObjects\SizeGroupData;
use App\Models\Company;
use App\Models\SizeGroup;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

beforeEach(function (): void {
    $this->companyAId = Company::factory()->create()->id;
    $this->companyBId = Company::factory()->create()->id;

    $this->sizeGroupA = SizeGroup::factory()->create([
        'company_id' => $this->companyAId,
        'name' => 'ABC',
    ]);

    setCompanyIdInSession($this->companyAId);
});

test('admin cannot add same name with same company.', function (): void {
    $request = new Request([
        'name' => $this->sizeGroupA->name,
        'code' => $this->sizeGroupA->code,
    ]);

    SizeGroupData::validate($request);
})->throws(ValidationException::class);

test('admin can add same name with different company.', function (): void {
    setCompanyIdInSession($this->companyBId);

    $request = new Request([
        'name' => $this->sizeGroupA->name,
        'code' => $this->sizeGroupA->code,
    ]);

    SizeGroupData::validate($request);
    $this->assertTrue(true);
});

<?php

declare(strict_types=1);

use App\Domains\MemberGroup\DataObjects\MemberGroupData;
use App\Models\Company;
use App\Models\MemberGroup;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

beforeEach(function (): void {
    $this->companyAId = Company::factory()->create()->id;
    $this->companyBId = Company::factory()->create()->id;

    $this->MemberGroupA = MemberGroup::factory()->create([
        'company_id' => $this->companyAId,
        'name' => 'Member Group 1',
    ]);
    $this->MemberGroupB = MemberGroup::factory()->create([
        'company_id' => $this->companyBId,
        'name' => 'Member Group 2',
    ]);

    setCompanyIdInSession($this->companyAId);
});

test('user cannot add same cashier group name with same company.', function (): void {
    $request = new Request([
        'name' => $this->MemberGroupA->name,
        'code' => $this->MemberGroupA->code,
    ]);

    MemberGroupData::validate($request);
})->throws(ValidationException::class);

test('user can add same cashier group name with different company.', function (): void {
    setCompanyIdInSession($this->companyBId);

    $request = new Request([
        'name' => $this->MemberGroupA->name,
        'code' => $this->MemberGroupA->code,
    ]);

    MemberGroupData::validate($request);
    $this->assertTrue(true);
});

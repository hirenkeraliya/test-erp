<?php

declare(strict_types=1);

use App\Domains\PromoterGroup\DataObjects\PromoterGroupData;
use App\Models\Company;
use App\Models\PromoterGroup;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

beforeEach(function (): void {
    $this->companyAId = Company::factory()->create()->id;
    $this->companyBId = Company::factory()->create()->id;

    $this->promoterGroupA = PromoterGroup::factory()->create([
        'company_id' => $this->companyAId,
        'name' => 'ABC',
    ]);

    setCompanyIdInSession($this->companyAId);
});

test('admin cannot add same name with same company.', function (): void {
    $request = new Request([
        'name' => $this->promoterGroupA->name,
        'code' => $this->promoterGroupA->code,
        'type_id' => $this->promoterGroupA->type_id->value,
    ]);

    PromoterGroupData::validate($request);
})->throws(ValidationException::class);

test('admin can add same name with different company.', function (): void {
    setCompanyIdInSession($this->companyBId);

    $request = new Request([
        'name' => $this->promoterGroupA->name,
        'code' => $this->promoterGroupA->code,
        'type_id' => $this->promoterGroupA->type_id->value,
    ]);
    PromoterGroupData::validate($request);
    $this->assertTrue(true);
});

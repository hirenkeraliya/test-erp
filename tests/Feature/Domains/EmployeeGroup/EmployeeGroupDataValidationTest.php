<?php

declare(strict_types=1);

use App\Domains\EmployeeGroup\DataObjects\EmployeeGroupData;
use App\Domains\EmployeeGroup\Enums\LimitResetTypes;
use App\Domains\EmployeeGroup\Enums\PurchaseLimitTypes;
use App\Models\Company;
use App\Models\EmployeeGroup;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

beforeEach(function (): void {
    $this->companyAId = Company::factory()->create()->id;
    $this->companyBId = Company::factory()->create()->id;

    $this->EmployeeGroupA = EmployeeGroup::factory()->create([
        'company_id' => $this->companyAId,
        'name' => 'Employee Group 1',
        'item_purchase_limit' => 0,
        'purchase_limit_type_id' => PurchaseLimitTypes::BY_ITEMS->value,
        'limit_reset_type_id' => LimitResetTypes::BY_DAYS->value,
        'limit_reset' => 1,
    ]);
    $this->EmployeeGroupB = EmployeeGroup::factory()->create([
        'company_id' => $this->companyBId,
        'name' => 'Employee Group 2',
        'item_purchase_limit' => 0,
        'purchase_limit_type_id' => PurchaseLimitTypes::BY_ITEMS->value,
        'limit_reset_type_id' => LimitResetTypes::BY_DAYS->value,
        'limit_reset' => 1,
    ]);

    setCompanyIdInSession($this->companyAId);
});

test('user cannot add same employee group name with same company.', function (): void {
    $request = new Request([
        'name' => $this->EmployeeGroupA->name,
        'code' => $this->EmployeeGroupA->code,
        'item_purchase_limit' => $this->EmployeeGroupA->item_purchase_limit,
    ]);

    EmployeeGroupData::validate($request);
})->throws(ValidationException::class);

test('user can add same employee group name with different company.', function (): void {
    setCompanyIdInSession($this->companyBId);

    $request = new Request([
        'name' => $this->EmployeeGroupA->name,
        'code' => $this->EmployeeGroupA->code,
        'item_purchase_limit' => $this->EmployeeGroupA->item_purchase_limit,
        'purchase_limit_type_id' => $this->EmployeeGroupA->purchase_limit_type_id,
        'limit_reset_type_id' => $this->EmployeeGroupA->limit_reset_type_id,
        'limit_reset' => $this->EmployeeGroupA->limit_reset,
    ]);

    EmployeeGroupData::validate($request);
    $this->assertTrue(true);
});

<?php

declare(strict_types=1);

use App\Domains\CashierGroup\DataObjects\CashierGroupData;
use App\Domains\Common\Enums\PriceOverrideTypes;
use App\Models\CashierGroup;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

beforeEach(function (): void {
    $this->companyAId = Company::factory()->create()->id;
    $this->companyBId = Company::factory()->create()->id;

    $this->CashierGroupA = CashierGroup::factory()->create([
        'company_id' => $this->companyAId,
        'name' => 'Cashier Group 1',
    ]);
    $this->CashierGroupB = CashierGroup::factory()->create([
        'company_id' => $this->companyBId,
        'name' => 'Cashier Group 2',
    ]);

    setCompanyIdInSession($this->companyAId);
});

test('user cannot add same cashier group name with same company.', function (): void {
    $request = new Request([
        'name' => $this->CashierGroupA->name,
        'permission_ids' => [1, 2],
        'price_override_limit_percentage_for_item' => 7.2,
        'price_override_limit_percentage_for_cart' => 10,
    ]);

    CashierGroupData::validate($request);
})->throws(ValidationException::class);

test('user can add same cashier group name with different company.', function (): void {
    setCompanyIdInSession($this->companyBId);

    $request = new Request([
        'name' => $this->CashierGroupA->name,
        'permission_ids' => [3],
        'price_override_type' => PriceOverrideTypes::PERCENTAGE->value,
        'price_override_limit_percentage_for_item' => 7.2,
        'price_override_limit_percentage_for_cart' => 10,
    ]);

    CashierGroupData::validate($request);
    $this->assertTrue(true);
});

<?php

declare(strict_types=1);

use App\Domains\Membership\DataObjects\MembershipData;
use App\Models\Company;
use App\Models\Membership;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

beforeEach(function (): void {
    $this->companyAId = Company::factory()->create()->id;

    $this->membershipA = Membership::factory()->create([
        'company_id' => $this->companyAId,
        'name' => 'ABCD',
    ]);
});

test('user cannot add same membership with same company.', function (): void {
    setCompanyIdInSession($this->companyAId);

    $request = new Request([
        'name' => $this->membershipA->name,
        'lifetime_value' => 10.10,
        'loyalty_points_per_currency_unit' => 10,
    ]);

    MembershipData::validate($request);
})->throws(ValidationException::class);

test('user can add same membership with different company.', function (): void {
    $companyBId = Company::factory()->create()->id;
    setCompanyIdInSession($companyBId);

    $request = new Request([
        'name' => $this->membershipA->name,
        'lifetime_value' => 10.10,
        'loyalty_points_per_currency_unit' => 10,
        'min_loyalty_points_for_redemption' => 200,
        'max_loyalty_points_for_redemption' => 40000,
    ]);

    MembershipData::validate($request);
    $this->assertTrue(true);
});

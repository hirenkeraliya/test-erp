<?php

declare(strict_types=1);

use App\Domains\LoyaltyCampaign\DataObjects\LoyaltyCampaignData;
use App\Models\Brand;
use App\Models\Company;
use App\Models\LoyaltyCampaign;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

beforeEach(function (): void {
    $this->companyAId = Company::factory()->create()->id;
    $this->companyBId = Company::factory()->create()->id;

    $this->loyaltyCampaignA = LoyaltyCampaign::factory()->create([
        'company_id' => $this->companyAId,
        'name' => 'ABCD',
        'start_date' => '2022-05-10',
        'end_date' => '2022-05-11',
    ]);
    $this->loyaltyCampaignB = LoyaltyCampaign::factory()->create([
        'company_id' => $this->companyBId,
        'name' => 'XYZW',
        'start_date' => '2022-05-10',
        'end_date' => '2022-05-11',
    ]);

    setCompanyIdInSession($this->companyAId);
});

test('user cannot add same loyaltyCampaign with same company.', function (): void {
    $brand = Brand::factory()->create();

    $request = new Request([
        'name' => $this->loyaltyCampaignA->name,
        'minimum_spend_amount' => 10.10,
        'loyalty_points' => 10,
        'excluded_brand_ids' => [$brand->id],
        'start_date' => '2022-05-10',
        'end_date' => '2022-05-11',
    ]);

    LoyaltyCampaignData::validate($request);
})->throws(ValidationException::class);

test('user can add same loyaltyCampaign with different company.', function (): void {
    setCompanyIdInSession($this->companyBId);
    $brand = Brand::factory()->create();

    $request = new Request([
        'name' => $this->loyaltyCampaignA->name,
        'minimum_spend_amount' => 10.10,
        'loyalty_points' => 10,
        'excluded_brand_ids' => [$brand->id],
        'start_date' => '2022-05-10',
        'end_date' => '2022-05-11',
        'loyalty_point_expiration_days' => 10,
    ]);

    LoyaltyCampaignData::validate($request);
    $this->assertTrue(true);
});

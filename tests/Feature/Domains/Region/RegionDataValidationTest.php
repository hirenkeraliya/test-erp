<?php

declare(strict_types=1);

use App\Domains\Region\DataObjects\RegionData;
use App\Models\Company;
use App\Models\Region;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

beforeEach(function (): void {
    $this->companyAId = Company::factory()->create()->id;
    $this->companyBId = Company::factory()->create()->id;

    $this->regionA = Region::factory()->create([
        'company_id' => $this->companyAId,
        'name' => 'ABC',
        'manager_name' => 'abc_manager',
        'manager_email' => 'abc@gmail.com',
    ]);

    setCompanyIdInSession($this->companyAId);
});

test('admin cannot add same name with same company.', function (): void {
    $request = new Request([
        'name' => $this->regionA->name,
        'code' => $this->regionA->code,
    ]);

    RegionData::validate($request);
})->throws(ValidationException::class);

test('admin can add same name with different company.', function (): void {
    setCompanyIdInSession($this->companyBId);

    $request = new Request([
        'name' => $this->regionA->name,
        'code' => $this->regionA->code,
    ]);

    RegionData::validate($request);
    $this->assertTrue(true);
});

<?php

declare(strict_types=1);

use App\Domains\SaleSeason\DataObjects\SaleSeasonData;
use App\Models\Company;
use App\Models\SaleSeason;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

beforeEach(function (): void {
    $this->companyAId = Company::factory()->create()->id;
    $this->companyBId = Company::factory()->create()->id;

    $this->saleSeason = SaleSeason::factory()->create([
        'company_id' => $this->companyAId,
        'name' => 'ABC',
    ]);

    setCompanyIdInSession($this->companyAId);
});

test('admin cannot add same name with same company.', function (): void {
    $request = new Request([
        'name' => $this->saleSeason->name,
        'start_date' => $this->saleSeason->start_date,
        'end_date' => $this->saleSeason->end_date,
    ]);

    SaleSeasonData::validate($request);
})->throws(ValidationException::class);

test('admin can add same name with different company.', function (): void {
    setCompanyIdInSession($this->companyBId);

    $request = new Request([
        'name' => $this->saleSeason->name,
        'start_date' => $this->saleSeason->start_date,
        'end_date' => $this->saleSeason->end_date,
    ]);
    SaleSeasonData::validate($request);
    $this->assertTrue(true);
});

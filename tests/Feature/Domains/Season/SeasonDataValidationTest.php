<?php

declare(strict_types=1);

use App\Domains\Season\DataObjects\SeasonData;
use App\Models\Company;
use App\Models\Season;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

beforeEach(function (): void {
    $this->companyAId = Company::factory()->create()->id;
    $this->companyBId = Company::factory()->create()->id;

    $this->seasonA = Season::factory()->create([
        'company_id' => $this->companyAId,
        'name' => 'ABC',
    ]);

    setCompanyIdInSession($this->companyAId);
});

test('admin cannot add same season name with same company.', function (): void {
    $request = new Request([
        'name' => $this->seasonA->name,
        'code' => $this->seasonA->code,
    ]);

    SeasonData::validate($request);
})->throws(ValidationException::class);

test('admin can add same season name with different company.', function (): void {
    setCompanyIdInSession($this->companyBId);

    $request = new Request([
        'name' => $this->seasonA->name,
        'code' => $this->seasonA->code,
    ]);

    SeasonData::validate($request);
    $this->assertTrue(true);
});

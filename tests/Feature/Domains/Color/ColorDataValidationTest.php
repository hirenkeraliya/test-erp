<?php

declare(strict_types=1);

use App\Domains\Color\DataObjects\ColorData;
use App\Models\Color;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

beforeEach(function (): void {
    $this->companyAId = Company::factory()->create()->id;
    $this->companyBId = Company::factory()->create()->id;

    $this->colorA = Color::factory()->create([
        'company_id' => $this->companyAId,
        'name' => 'ABC',
    ]);

    setCompanyIdInSession($this->companyAId);
});

test('admin cannot add same name with same company.', function (): void {
    $request = new Request([
        'name' => $this->colorA->name,
        'code' => $this->colorA->code,
    ]);

    ColorData::validate($request);
})->throws(ValidationException::class);

test('admin can add same name with different company.', function (): void {
    setCompanyIdInSession($this->companyBId);

    $request = new Request([
        'name' => $this->colorA->name,
        'code' => $this->colorA->code,
    ]);

    ColorData::validate($request);
    $this->assertTrue(true);
});

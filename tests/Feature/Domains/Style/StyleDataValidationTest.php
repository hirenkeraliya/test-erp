<?php

declare(strict_types=1);

use App\Domains\Style\DataObjects\StyleData;
use App\Models\Company;
use App\Models\Style;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

beforeEach(function (): void {
    $this->companyAId = Company::factory()->create()->id;
    $this->companyBId = Company::factory()->create()->id;

    $this->styleA = Style::factory()->create([
        'company_id' => $this->companyAId,
        'name' => 'ABC',
    ]);

    setCompanyIdInSession($this->companyAId);
});

test('admin cannot add same style name with same company.', function (): void {
    $request = new Request([
        'name' => $this->styleA->name,
        'code' => $this->styleA->code,
    ]);

    StyleData::validate($request);
})->throws(ValidationException::class);

test('admin can add same style name with different company.', function (): void {
    setCompanyIdInSession($this->companyBId);

    $request = new Request([
        'name' => $this->styleA->name,
        'code' => $this->styleA->code,
    ]);

    StyleData::validate($request);
    $this->assertTrue(true);
});

<?php

declare(strict_types=1);

use App\Domains\Size\DataObjects\SizeData;
use App\Models\Company;
use App\Models\Size;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

beforeEach(function (): void {
    $this->companyAId = Company::factory()->create()->id;
    $this->companyBId = Company::factory()->create()->id;

    $this->sizeA = Size::factory()->create([
        'company_id' => $this->companyAId,
        'name' => 'ABCD',
        'code' => 'ABCD',
    ]);
    $this->sizeB = Size::factory()->create([
        'company_id' => $this->companyBId,
        'name' => 'XYZW',
        'code' => 'XYZW',
    ]);

    setCompanyIdInSession($this->companyAId);
});

test('user cannot add same size with same company.', function (): void {
    $request = new Request([
        'name' => $this->sizeA->name,
        'code' => $this->sizeA->code,
        'sort_order' => $this->sizeA->sort_order,
    ]);

    SizeData::validate($request);
})->throws(ValidationException::class);

test('user can add same size with different company.', function (): void {
    setCompanyIdInSession($this->companyBId);

    $request = new Request([
        'name' => $this->sizeA->name,
        'code' => $this->sizeA->code,
        'sort_order' => $this->sizeA->sort_order,
    ]);

    SizeData::validate($request);
    $this->assertTrue(true);
});

<?php

declare(strict_types=1);

use App\Domains\Category\DataObjects\CategoryData;
use App\Models\Category;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

beforeEach(function (): void {
    $this->companyAId = Company::factory()->create()->id;
    $this->companyBId = Company::factory()->create()->id;

    $this->categoryA = Category::factory()->create([
        'company_id' => $this->companyAId,
        'name' => 'ABC',
    ]);
    $this->categoryB = Category::factory()->create([
        'company_id' => $this->companyBId,
        'name' => 'XYZ',
    ]);

    setCompanyIdInSession($this->companyAId);
});

test('user cannot add same name with same company.', function (): void {
    $request = new Request([
        'name' => $this->categoryA->name,
        'code' => $this->categoryA->code,
    ]);

    CategoryData::validate($request);
})->throws(ValidationException::class);

test('user can add same name with different company.', function (): void {
    setCompanyIdInSession($this->companyBId);

    $request = new Request([
        'name' => $this->categoryA->name,
        'code' => $this->categoryA->code,
        'status' => $this->categoryA->status,
        'is_available_in_ecommerce' => $this->categoryA->is_available_in_ecommerce,
        'is_display_on_menu' => $this->categoryA->is_display_on_menu,
    ]);

    CategoryData::validate($request);
    $this->assertTrue(true);
});

<?php

declare(strict_types=1);

use App\Domains\ExternalCategories\ExternalCategoryQueries;
use App\Models\Company;
use App\Models\ExternalCategory;

beforeEach(function (): void {
    $this->company = Company::factory()->create();
    $this->companyId = $this->company->id;

    $this->categoryA = ExternalCategory::factory()->make([
        'company_id' => $this->companyId,
    ]);

    $this->externalCategoryQueries = new ExternalCategoryQueries();

    session()->put('admin_company_id', $this->companyId);
});

test('New category can be added', function (): void {
    $categoryData = [
        'name' => $this->categoryA->name,
        'parent_category_id' => $this->categoryA->parent_category_id,
        'company_id' => $this->categoryA->company_id,
        'sale_channel_id' => $this->categoryA->sale_channel_id,
        'external_category_id' => $this->categoryA->external_category_id,
    ];

    $this->externalCategoryQueries->addNew($categoryData);

    $this->assertDatabaseHas('external_categories', [
        'company_id' => $this->companyId,
        'parent_category_id' => 0,
        'name' => $this->categoryA->name,
    ]);
});

test('call getParentCategoryId', function (): void {
    $this->categoryA = ExternalCategory::factory()->create([
        'company_id' => $this->companyId,
    ]);
    $this->categoryB = ExternalCategory::factory()->create([
        'parent_category_id' => $this->categoryA->external_category_id,
        'company_id' => $this->companyId,
    ]);

    $response = $this->externalCategoryQueries->getParentCategoryId($this->categoryB->parent_category_id);

    expect($response)->toBe($this->categoryA->id);
});

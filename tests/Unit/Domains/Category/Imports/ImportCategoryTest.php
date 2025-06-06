<?php

declare(strict_types=1);

use App\Domains\Category\CategoryQueries;
use App\Domains\Category\Imports\ImportCategory;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\ImportRecord\Enums\ImportTypes;
use App\Models\ImportRecord;

test('the validate method returns blank array when no error in given details', function (): void {
    $companyId = 1;

    $categoryData = [
        'name' => 'Abc',
        'code' => 'def',
    ];

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'created_by_id' => 1,
    ]);

    $this->mock(CategoryQueries::class, function ($mock) use ($companyId, $categoryData): void {
        $mock->shouldReceive('existsByName')
            ->once()
            ->with($categoryData['name'], $companyId)
            ->andReturn(false);

        $mock->shouldReceive('existsByCode')
            ->once()
            ->with($categoryData['code'], $companyId)
            ->andReturn(false);
    });

    $importCategory = new ImportCategory();
    $redirectResponse = $importCategory->validate($categoryData, $importRecord);
    expect($redirectResponse)->toBeArray();
});

test('name is required for import record', function (): void {
    $companyId = 1;

    $categoryData = [
        'name' => '',
        'code' => 'abcd',
    ];

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'created_by_id' => 1,
    ]);

    $this->mock(CategoryQueries::class, function ($mock) use ($companyId, $categoryData): void {
        $mock->shouldReceive('existsByCode')
            ->once()
            ->with($categoryData['code'], $companyId)
            ->andReturn(false);
    });

    $importCategory = new ImportCategory();
    $redirectResponse = $importCategory->validate($categoryData, $importRecord);
    expect($redirectResponse)->toContain('A name is required.');
});

test('It calls addNew method to store category details', function (): void {
    $companyId = 1;

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'type_id' => ImportTypes::CATEGORIES->value,
        'created_by_id' => 1,
        'created_by_type' => ModelMapping::ADMIN->name,
    ]);

    $categoryData = [
        'name' => 'category-1',
        'code' => 'category-code',
        'parent_category_id' => null,
        'description' => null,
        'status' => null,
        'is_available_in_ecommerce' => null,
        'is_display_on_menu' => null,
        'square_image' => null,
        'portrait_images' => [],
        'landscape_images' => [],
    ];

    $this->mock(CategoryQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    $importCategory = new ImportCategory();
    $importCategory->save($categoryData, $importRecord);
    $this->assertTrue(true);
});

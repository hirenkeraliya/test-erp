<?php

declare(strict_types=1);

use App\Domains\DynamicMenus\DataObjects\DynamicMenuData;
use App\Domains\DynamicMenus\DynamicMenuQueries;
use App\Domains\DynamicMenus\Enums\DynamicMenuTypesEnum;
use App\Models\Company;
use App\Models\DynamicMenu;
use Illuminate\Support\Str;

beforeEach(function (): void {
    $this->company = Company::factory()->create();
    $this->companyId = $this->company->id;

    $this->dynamicMenuA = DynamicMenu::factory()->create([
        'company_id' => $this->companyId,
    ]);

    $this->dynamicMenuB = DynamicMenu::factory()->create([
        'company_id' => $this->companyId,
        'parent_id' => $this->dynamicMenuA->id,
    ]);

    $this->dynamicMenuQueries = new DynamicMenuQueries();

    session()->put('admin_company_id', $this->companyId);
});

test('It can fetch dynamic menu with children', function (): void {
    $filterData = [
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => null,
    ];
    $response = $this->dynamicMenuQueries->listQuery($filterData, $this->companyId);

    expect($response->first()->toArray())
        ->toHaveKey('parent_id', $this->dynamicMenuA->parent_id)
        ->toHaveKey('company_id', $this->dynamicMenuA->company_id)
        ->toHaveKey('children.0.id', $this->dynamicMenuB->id)
        ->toHaveKey('children.0.title', $this->dynamicMenuB->title);
});

test('New dynamic menu can be added', function (): void {
    $requestData = [
        'parent_id' => null,
        'title' => 'new title',
        'type' => DynamicMenuTypesEnum::STATIC_PAGE->value,
        'module_id' => null,
        'content' => '<p>Hello</p>',
        'status' => true,
    ];

    $this->dynamicMenuQueries->addNew(new DynamicMenuData(...$requestData), $this->companyId);

    $requestData['slug'] = Str::slug($requestData['title']);
    $requestData['company_id'] = $this->companyId;

    $this->assertDatabaseHas('dynamic_menus', $requestData);
});

test('New sub dynamic menu can be added', function (): void {
    $requestData = [
        'parent_id' => $this->dynamicMenuB->id,
        'title' => 'new child title',
        'type' => DynamicMenuTypesEnum::STATIC_PAGE->value,
        'module_id' => null,
        'content' => '<p>Hello</p>',
        'status' => true,
    ];

    $this->dynamicMenuQueries->addNew(new DynamicMenuData(...$requestData), $this->companyId);

    $requestData['slug'] = Str::slug($requestData['title']);
    $requestData['company_id'] = $this->companyId;

    $this->assertDatabaseHas('dynamic_menus', $requestData);
});

test('A dynamic menu can be updated', function (): void {
    $requestData = [
        'parent_id' => null,
        'title' => 'new child title',
        'type' => DynamicMenuTypesEnum::STATIC_PAGE->value,
        'module_id' => null,
        'content' => '<p>Hello</p>',
        'status' => true,
    ];

    $this->dynamicMenuQueries->update(new DynamicMenuData(...$requestData), $this->dynamicMenuA->id, $this->companyId);

    $requestData['slug'] = Str::slug($requestData['title']);
    $requestData['company_id'] = $this->companyId;
    $requestData['id'] = $this->dynamicMenuA->id;

    $this->assertDatabaseHas('dynamic_menus', $requestData);
});

test('Disabling a parent dynamic menu will automatically disable all its child dynamic menu.', function (): void {
    $this->dynamicMenuA->update([
        'status' => true,
    ]);
    $this->dynamicMenuB->update([
        'parent_id' => $this->dynamicMenuA->id,
        'status' => true,
    ]);

    $requestData = [
        'parent_id' => null,
        'title' => 'Disabled Parent',
        'type' => DynamicMenuTypesEnum::STATIC_PAGE->value,
        'module_id' => null,
        'content' => '<p>Disabled</p>',
        'status' => false,
    ];

    $this->dynamicMenuQueries->update(new DynamicMenuData(...$requestData), $this->dynamicMenuA->id, $this->companyId);

    $this->assertDatabaseHas('dynamic_menus', [
        'id' => $this->dynamicMenuA->id,
        'status' => false,
    ]);

    $this->assertDatabaseHas('dynamic_menus', [
        'id' => $this->dynamicMenuB->id,
        'status' => false,
    ]);
});

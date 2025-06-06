<?php

declare(strict_types=1);

use App\Domains\DynamicMenus\DataObjects\DynamicMenuData;
use App\Domains\DynamicMenus\DynamicMenuQueries;
use App\Domains\DynamicMenus\Enums\DynamicMenuTypesEnum;
use App\Http\Controllers\Admin\DynamicMenuController;
use App\Models\DynamicMenu;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

test(
    'It calls the listQuery method of the dynamic menu queries class and returns proper response',
    function (): void {
        $companyId = 1;

        setCompanyIdInSession($companyId);

        $this->mock(DynamicMenuQueries::class, function ($mock): void {
            $mock->shouldReceive('listQuery')
                ->once()
                ->andReturn(new Collection([]));
        });

        $dynamicMenuController = new DynamicMenuController();
        $response = $dynamicMenuController->fetchDynamicMenus(new Request([
            'search_text' => null,
            'sort_by' => null,
            'sort_direction' => null,
            'per_page' => null,
        ]));

        expect($response['data'])->toBeArray();
    }
);

test('User can not add child menu under the static menu.', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $dynamicMenu = DynamicMenu::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'parent_id' => null,
        'title' => 'new title',
        'slug' => 'new-title',
        'type' => DynamicMenuTypesEnum::STATIC_PAGE->value,
        'module_id' => null,
        'content' => null,
    ]);

    $this->mock(DynamicMenuQueries::class, function ($mock) use ($dynamicMenu): void {
        $mock->shouldReceive('getById')
            ->once()
            ->with(1)
            ->andReturn($dynamicMenu);
    });

    $dynamicMenuController = new DynamicMenuController();
    $redirectResponse = $dynamicMenuController->create(1);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Can not add child menu in static page.', $redirectResponse->getSession()->all()['error']);
    $this->assertStringContainsString('admin/dynamic-menus', $redirectResponse->getTargetUrl());
});

test('It calls the add dynamic menu method of dynamic menu queries class', function (): void {
    $requestData = [
        'parent_id' => null,
        'title' => 'new title',
        'type' => DynamicMenuTypesEnum::STATIC_PAGE->value,
        'module_id' => null,
        'content' => '<p>Hello</p>',
        'status' => true,
    ];

    $dynamicMenuData = new DynamicMenuData(...$requestData);

    $companyId = 1;

    setCompanyIdInSession($companyId);

    $this->mock(DynamicMenuQueries::class, function ($mock) use ($dynamicMenuData, $companyId): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->with($dynamicMenuData, $companyId);
    });

    $dynamicMenuController = new DynamicMenuController();
    $redirectResponse = $dynamicMenuController->store($dynamicMenuData);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Dynamic Menu added successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/dynamic-menus', $redirectResponse->getTargetUrl());
});

test('It calls the update dynamic menu method of dynamic menu queries class', function (): void {
    $companyId = 1;

    $requestData = [
        'parent_id' => null,
        'title' => 'new title',
        'type' => DynamicMenuTypesEnum::BRAND->value,
        'module_id' => 1,
        'content' => '<p>Hello</p>',
        'status' => true,
    ];

    setCompanyIdInSession($companyId);

    $dynamicMenuData = new DynamicMenuData(...$requestData);

    $this->mock(DynamicMenuQueries::class, function ($mock) use ($dynamicMenuData, $companyId): void {
        $mock->shouldReceive('update')
            ->once()
            ->with($dynamicMenuData, 1, $companyId);
    });

    $dynamicMenuController = new DynamicMenuController();
    $redirectResponse = $dynamicMenuController->update($dynamicMenuData, $companyId);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Dynamic Menu updated successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/dynamic-menus', $redirectResponse->getTargetUrl());
});

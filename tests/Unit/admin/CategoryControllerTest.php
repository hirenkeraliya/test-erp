<?php

declare(strict_types=1);

use App\Domains\Category\CategoryQueries;
use App\Domains\Category\DataObjects\CategoryData;
use App\Domains\Category\Jobs\CategorySyncMainJob;
use App\Domains\SaleChannel\Services\SaleChannelService;
use App\Domains\SyncTransaction\Enums\SyncTypes;
use App\Http\Controllers\Admin\CategoryController;
use App\Models\Admin;
use App\Models\Category;
use Illuminate\Database\Eloquent\Collection as DatabaseCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test('It calls the list query method of the category queries class and returns proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $categoryQueries = $this->mock(CategoryQueries::class, function ($mock) use ($companyId): void {
        $mock->shouldReceive('listQuery')
            ->once()
            ->with($companyId)
            ->andReturn(new Collection([]));
    });

    $categoryController = new CategoryController($categoryQueries);

    $response = $categoryController->fetchCategories();

    $this->assertEquals([], $response['data']);
});

test('It calls the add category method of category queries class', function (): void {
    $categoryData = commonCategoryControllerData();

    $categoryData = new CategoryData(...$categoryData);

    $companyId = 1;

    setCompanyIdInSession($companyId);

    $categoryQueries = $this->mock(CategoryQueries::class, function ($mock) use ($categoryData, $companyId): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->with($categoryData, $companyId);
    });

    $categoryController = new CategoryController($categoryQueries);
    $redirectResponse = $categoryController->store($categoryData);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Category added successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/categories', $redirectResponse->getTargetUrl());
});

test('It calls the get by id method of the category queries class and returns proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $requestParameter = [
        'name' => 'xyz',
        'code' => 'xyz123',
        'company_id' => $companyId,
    ];

    $categoryQueries = $this->mock(CategoryQueries::class, function ($mock) use ($requestParameter, $companyId): void {
        $mock->shouldReceive('getById')
            ->once()
            ->with(1, $companyId)
            ->andReturn(new Category($requestParameter));
    });

    $categoryController = new CategoryController($categoryQueries);
    $response = $categoryController->edit(1);
    $response->rootView('admin.index');

    $newResponse = new TestResponse($response->toResponse(new Request()));

    $newResponse->assertInertia(
        fn (Assert $inertia): Assert => $inertia
        ->has(
            'category',
            fn (Assert $category): Assert => $category->where('name', 'xyz')->where(
                'code',
                'xyz123'
            )->where('company_id', $companyId)->etc()
        )
    );
});

test('It calls the update category method of category queries class', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $categoryData = commonCategoryControllerData();

    $categoryData = new CategoryData(...$categoryData);

    $categoryQueries = $this->mock(CategoryQueries::class, function ($mock) use ($categoryData, $companyId): void {
        $mock->shouldReceive('update')
            ->once()
            ->with($categoryData, 1, $companyId);
    });

    $categoryController = new CategoryController($categoryQueries);
    $redirectResponse = $categoryController->update($categoryData, $companyId);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Category updated successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/categories', $redirectResponse->getTargetUrl());
});

test(
    'It calls the get Child Categories method of the category queries class and returns proper response',
    function (): void {
        setCompanyIdInSession();

        $categoryQueries = $this->mock(CategoryQueries::class, function ($mock): void {
            $mock->shouldReceive('getChildCategoriesWithBasicColumns')
                ->once()
                ->with(1, 1)
                ->andReturn(new DatabaseCollection([]));
        });

        $categoryController = new CategoryController($categoryQueries);
        $databaseCollection = $categoryController->getChildCategories(1);
        $this->assertEquals(new DatabaseCollection([]), $databaseCollection);
    }
);

test(
    'It calls the getFilteredCategoriesByCompanyId method of the category queries class and returns proper response',
    function (): void {
        setCompanyIdInSession();

        $categoryQueries = $this->mock(CategoryQueries::class, function ($mock): void {
            $mock->shouldReceive('getFilteredCategoriesByCompanyId')
                ->once()
                ->with('ab', 1)
                ->andReturn(new Collection([]));
        });

        $categoryController = new CategoryController($categoryQueries);
        $response = $categoryController->getFilteredCategories(new Request([
            'search_text' => 'ab',
        ]));

        expect($response['categories'])->toBeInstanceOf(Collection::class);
    }
);

test(
    'It calls the exportCategories method and export the data',
    function (): void {
        $companyId = 1;

        setCompanyIdInSession($companyId);

        Category::factory()->make([
            'company_id' => $companyId,
        ]);

        $categoryQueries = $this->mock(CategoryQueries::class, function ($mock) use ($companyId): void {
            $mock->shouldReceive('listQuery')
                ->once()
                ->with($companyId)
                ->andReturn(new Collection([]));
        });

        $categoryController = new CategoryController($categoryQueries);

        $response = $categoryController->exportCategories('filename.csv');

        $this->assertEquals(200, $response->getStatusCode());

        expect($response)->toBeInstanceOf(BinaryFileResponse::class);
    }
);

test(
    'It calls the getParentByCompanyId method of the category queries class and returns proper response',
    function (): void {
        setCompanyIdInSession();

        $categoryQueries = $this->mock(CategoryQueries::class, function ($mock): void {
            $mock->shouldReceive('getParentByCompanyId')
                ->once()
                ->with(1)
                ->andReturn(new Collection([]));
        });

        $categoryController = new CategoryController($categoryQueries);
        $response = $categoryController->getParentCategories();

        expect($response['categories'])->toBeInstanceOf(Collection::class);
    }
);

test(
    'It calls the removeSquareImage method of the category queries class and returns proper response',
    function (): void {
        setCompanyIdInSession();

        $categoryQueries = $this->mock(CategoryQueries::class, function ($mock): void {
            $mock->shouldReceive('removeSquareImage')
                ->once()
                ->with(1, 1);
        });

        $categoryController = new CategoryController($categoryQueries);
        $categoryController->removeSquareImage(1);
    }
);

test(
    'It calls the syncData method and returns proper response',
    function (): void {
        Queue::fake();
        setCompanyIdInSession();

        $admin = Admin::factory()->make([
            'id' => 1,
            'employee_id' => 1,
        ]);

        $request = new Request();

        $request->setUserResolver(fn (): Admin => $admin);

        $this->mock(SaleChannelService::class, function ($mock) use ($admin): void {
            $mock->shouldReceive('updateSyncData')
                ->once()
                ->with(1, SyncTypes::CATEGORY->value, $admin, 1);
        });

        $categoryController = new CategoryController(new CategoryQueries());
        $categoryController->syncData(1, $request);

        Queue::assertPushed(CategorySyncMainJob::class);
    }
);

function commonCategoryControllerData(): array
{
    return [
        'name' => 'XYZ',
        'code' => 'XYZ123',
        'description' => null,
        'status' => true,
        'is_available_in_ecommerce' => false,
        'is_display_on_menu' => false,
        'parent_category_id' => 1,
        'square_image' => null,
        'portrait_images' => [],
        'landscape_images' => [],
    ];
}

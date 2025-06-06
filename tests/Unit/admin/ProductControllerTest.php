<?php

declare(strict_types=1);

use App\Domains\BoxProduct\BoxProductQueries;
use App\Domains\Category\CategoryQueries;
use App\Domains\Color\ColorQueries;
use App\Domains\Company\CompanyQueries;
use App\Domains\Department\DepartmentQueries;
use App\Domains\Membership\MembershipQueries;
use App\Domains\PackageType\PackageTypeQueries;
use App\Domains\Product\DataObjects\ProductArticleData;
use App\Domains\Product\DataObjects\ProductData;
use App\Domains\Product\DataObjects\ProductImageUploadByArticleNumberData;
use App\Domains\Product\DataObjects\ProductImageUploadData;
use App\Domains\Product\Enums\ProductSyncTypes;
use App\Domains\Product\Enums\ProductTypes;
use App\Domains\Product\Enums\Statuses;
use App\Domains\Product\Jobs\ProductMergeJob;
use App\Domains\Product\Jobs\ProductSyncMainJob;
use App\Domains\Product\ProductQueries;
use App\Domains\Product\Resources\AdminProductListResource;
use App\Domains\Product\Services\ProductService;
use App\Domains\ProductCollection\Jobs\ProductCollectionUpdateByProductJob;
use App\Domains\ProductCollectionProduct\ProductCollectionProductQueries;
use App\Domains\ProductLoyaltyPoint\ProductLoyaltyPointQueries;
use App\Domains\Promotion\PromotionQueries;
use App\Domains\SaleChannel\SaleChannelQueries;
use App\Domains\SaleChannel\Services\SaleChannelService;
use App\Domains\Season\SeasonQueries;
use App\Domains\Size\SizeQueries;
use App\Domains\Style\StyleQueries;
use App\Domains\SyncTransaction\Enums\SyncTypes;
use App\Domains\Tag\TagQueries;
use App\Domains\Template\TemplateQueries;
use App\Domains\UnitOfMeasure\UnitOfMeasureQueries;
use App\Domains\Vendor\VendorQueries;
use App\Exceptions\RedirectBackWithErrorException;
use App\Http\Controllers\Admin\ProductController;
use App\Models\Admin;
use App\Models\BoxProduct;
use App\Models\Company;
use App\Models\Product;
use App\Models\ProductLoyaltyPoint;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

test(
    'It calls the List query method of the product queries class and returns proper response',
    function (bool $productVariant): void {
        Config::set('app.product_variant', $productVariant);
        $companyId = 1;

        setCompanyIdInSession($companyId);

        $requestParameter = [
            'search_text' => 'abc',
            'sort_by' => 'name',
            'sort_direction' => 'desc',
            'per_page' => 1,
            'status' => null,
            'batch' => null,
            'date_range' => 'null',
            'product_type_id' => 'null',
            'category_ids' => 'null',
            'brand_ids' => 'null',
            'color_ids' => 'null',
            'size_ids' => 'null',
            'department_ids' => 'null',
            'article_numbers' => 'null',
            'tag_ids' => 'null',
            'style_ids' => null,
            'product_collection_ids' => null,
            'product_sync_type_id' => ProductSyncTypes::ALL_PRODUCT->value,
            'attributes' => [],
        ];

        $productQueries = $this->mock(ProductQueries::class, function ($mock) use (
            $requestParameter,
            $companyId
        ): void {
            $mock->shouldReceive('listQuery')
                ->once()
                ->with($requestParameter, $companyId)
                ->andReturn(new LengthAwarePaginator([], 20, 15));
        });

        $productController = new ProductController($productQueries);

        $response = $productController->fetchProducts(new Request($requestParameter));

        $this->assertEquals(20, $response['total_records']);
        $this->assertEquals(AdminProductListResource::collection(collect([])), $response['data']);
    }
)->with([[true], [false]]);

test('the product will not be added when the selected brand is from a different company', function (): void {
    $productRecord = Product::factory()->make([
        'unit_of_measure_id' => null,
        'season_id' => null,
        'department_id' => null,
        'color_id' => null,
        'size_id' => null,
        'style_id' => null,
        'brand_id' => 1,
        'company_id' => 1,
        'vendor_id' => null,
    ])->toArray();

    $productRecord['images'] = [];
    $productRecord['videos'] = [];
    $productRecord['thumbnail'] = null;
    $productRecord['category_ids'] = [];
    $productRecord['tag_ids'] = [];
    $productRecord['tiers'] = [];
    $productRecord['assembly_child_products'] = [];
    $productRecord['boxes'] = [];
    $productRecord['attached_templates'] = [];
    $productRecord['custom_field_values'] = null;
    $productRecord['retail_planning_hierarchy_id'] = null;
    $productRecord['warranty_month'] = null;
    $productRecord['vendor_id'] = null;
    $productRecord['is_warranty'] = false;
    $productRecord['original_created_at'] = null;
    $productRecord['type_id'] = (string) ProductTypes::REGULAR_PRODUCT->value;
    unset($productRecord['company_id']);

    setCompanyIdInSession();

    $admin = Admin::factory()->make([
        'employee_id' => 1,
    ]);

    $request = new Request();

    $request->setUserResolver(fn (): Admin => $admin);

    $productData = new ProductData(...$productRecord);

    $companyQueries = $this->mock(CompanyQueries::class, function ($mock): void {
        $mock->shouldReceive('hasAllBrandsAttached')
            ->once()
            ->with(1, [1]);
    });

    $productQueries = $this->mock(ProductQueries::class, function ($mock) use ($productData, $admin): void {
        $mock->shouldReceive('addNew')
            ->times(0)
            ->with($productData, 1, $admin);
    });

    $productController = new ProductController($productQueries);
    $productController->store($productData, $request);
})->throws(RedirectBackWithErrorException::class);

test('It calls the addNew method of the product queries class and returns proper response', function (): void {
    $productRecord = Product::factory()->make([
        'unit_of_measure_id' => null,
        'season_id' => null,
        'department_id' => null,
        'color_id' => null,
        'size_id' => null,
        'style_id' => null,
        'brand_id' => 1,
        'company_id' => 1,
        'vendor_id' => null,
    ])->toArray();

    $productRecord['images'] = [];
    $productRecord['videos'] = [];
    $productRecord['thumbnail'] = null;
    $productRecord['category_ids'] = [];
    $productRecord['tag_ids'] = [];
    $productRecord['tiers'] = [];
    $productRecord['assembly_child_products'] = [];
    $productRecord['boxes'] = [];
    $productRecord['attached_templates'] = [];
    $productRecord['custom_field_values'] = null;
    $productRecord['retail_planning_hierarchy_id'] = null;
    $productRecord['warranty_month'] = null;
    $productRecord['vendor_id'] = null;
    $productRecord['is_warranty'] = false;
    $productRecord['original_created_at'] = null;
    $productRecord['type_id'] = (string) ProductTypes::REGULAR_PRODUCT->value;
    unset($productRecord['company_id']);

    setCompanyIdInSession();

    $productData = new ProductData(...$productRecord);

    $admin = Admin::factory()->make([
        'employee_id' => 1,
    ]);

    $request = new Request();

    $request->setUserResolver(fn (): Admin => $admin);

    $companyQueries = $this->mock(CompanyQueries::class, function ($mock) use ($productData): void {
        $mock->shouldReceive('hasAllBrandsAttached')
            ->once()
            ->with(1, [$productData->brand_id])
            ->andReturn(true);
    });

    $productQueries = $this->mock(ProductQueries::class, function ($mock) use ($productData, $admin): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->with($productData, 1, $admin);
    });

    $productController = new ProductController($productQueries);
    $redirectResponse = $productController->store($productData, $request);
    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Product added successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/products', $redirectResponse->getTargetUrl());
});

test('create product method returns required data', function (): void {
    setCompanyIdInSession();

    $returnData = [
        'id' => '1',
        'name' => 'ABC',
    ];

    $this->mock(UnitOfMeasureQueries::class, function ($mock) use ($returnData): void {
        getWithBasicColumns($mock, $returnData);
    });

    $this->mock(SeasonQueries::class, function ($mock) use ($returnData): void {
        getWithBasicColumns($mock, $returnData);
    });

    $this->mock(DepartmentQueries::class, function ($mock) use ($returnData): void {
        getWithBasicColumns($mock, $returnData);
    });

    $this->mock(ColorQueries::class, function ($mock) use ($returnData): void {
        getWithBasicColumns($mock, $returnData);
    });

    $this->mock(SizeQueries::class, function ($mock) use ($returnData): void {
        getWithBasicColumns($mock, $returnData);
    });

    $this->mock(VendorQueries::class, function ($mock) use ($returnData): void {
        getWithBasicColumns($mock, $returnData);
    });

    $this->mock(CompanyQueries::class, function ($mock): void {
        $mock->shouldReceive('getByIdWithBrands')
            ->once()
            ->with(1)
            ->andReturn(new Company());
        $mock->shouldReceive('getByIdWithPromoterCommissionDetails')
            ->once()
            ->with(1)
            ->andReturn(new Company());
    });

    $this->mock(StyleQueries::class, function ($mock) use ($returnData): void {
        getWithBasicColumns($mock, $returnData);
    });
    $this->mock(PackageTypeQueries::class, function ($mock) use ($returnData): void {
        getWithBasicColumns($mock, $returnData);
    });

    $this->mock(CategoryQueries::class, function ($mock) use ($returnData): void {
        $mock->shouldReceive('getMainCategoriesWithBasicColumns')
            ->once()
            ->with(1)
            ->andReturn(new Collection([$returnData]));
    });

    $this->mock(TagQueries::class, function ($mock) use ($returnData): void {
        getWithBasicColumns($mock, $returnData);
    });

    $this->mock(SaleChannelQueries::class, function ($mock): void {
        $mock->shouldReceive('getAllByCompanyId')
        ->once()
        ->andReturn(collect([]));
    });

    $this->mock(MembershipQueries::class, function ($mock) use ($returnData): void {
        getWithBasicColumns($mock, $returnData);
    });

    $this->mock(TemplateQueries::class, function ($mock) use ($returnData): void {
        $mock->shouldReceive('fetchForDropdown')
        ->once()
        ->with(1)
        ->andReturn(new Collection([$returnData]));
    });

    $productController = new ProductController(new ProductQueries());
    $response = $productController->create();
    $response->rootView('admin.index');

    $newResponse = new TestResponse($response->toResponse(new Request()));

    $newResponse->assertInertia(
        fn (Assert $inertia): Assert => $inertia
            ->has(
                'unitOfMeasures',
                fn (Assert $unitOfMeasures): Assert => $unitOfMeasures
                    ->has(
                        '0',
                        fn (Assert $unitOfMeasure): Assert => $unitOfMeasure->where('id', '1')->where('name', 'ABC')
                    )
            )
            ->has(
                'seasons',
                fn (Assert $seasons): Assert => $seasons
                    ->has('0', fn (Assert $season): Assert => $season->where('id', '1')->where('name', 'ABC'))
            )
            ->has(
                'departments',
                fn (Assert $departments): Assert => $departments
                    ->has(
                        '0',
                        fn (Assert $department): Assert => $department->where('id', '1')->where('name', 'ABC')
                    )
            )
            ->has(
                'subDepartments',
                fn (Assert $subDepartments): Assert => $subDepartments
                    ->has(
                        '0',
                        fn (Assert $subDepartment): Assert => $subDepartment->where('id', 1)->where('name', 'Gds')
                    )
                    ->has(
                        '1',
                        fn (Assert $subDepartment): Assert => $subDepartment->where('id', 2)->where('name', 'Ops')
                    )
            )
            ->has(
                'colors',
                fn (Assert $colors): Assert => $colors
                    ->has('0', fn (Assert $color): Assert => $color->where('id', '1')->where('name', 'ABC'))
            )
            ->has(
                'sizes',
                fn (Assert $sizes): Assert => $sizes
                    ->has('0', fn (Assert $size): Assert => $size->where('id', '1')->where('name', 'ABC'))
            )
            ->has(
                'styles',
                fn (Assert $styles): Assert => $styles
                    ->has('0', fn (Assert $style): Assert => $style->where('id', '1')->where('name', 'ABC'))
            )
            ->has(
                'categories',
                fn (Assert $categories): Assert => $categories
                    ->has('0', fn (Assert $category): Assert => $category->where('id', '1')->where('name', 'ABC'))
            )
            ->has(
                'tags',
                fn (Assert $tags): Assert => $tags
                    ->has('0', fn (Assert $tag): Assert => $tag->where('id', '1')->where('name', 'ABC'))
            )
    );
});

test('It calls the get by id method of the product queries class and returns proper response', function (): void {
    $companyId = 1;
    setCompanyIdInSession();

    $productRecord = Product::factory()->make([
        'unit_of_measure_id' => null,
        'season_id' => null,
        'department_id' => null,
        'color_id' => null,
        'size_id' => null,
        'style_id' => null,
        'brand_id' => 1,
        'company_id' => $companyId,
    ])->toArray();

    unset($productRecord['company_id']);

    $productQueries = $this->mock(ProductQueries::class, function ($mock) use ($productRecord, $companyId): void {
        $mock->shouldReceive('getByIdWithMediaCategoriesAndTags')
            ->once()
            ->with(1, $companyId)
            ->andReturn(new Product($productRecord));
    });

    $returnData = [
        'id' => '1',
        'name' => 'ABC',
    ];

    $this->mock(UnitOfMeasureQueries::class, function ($mock) use ($returnData): void {
        getWithBasicColumns($mock, $returnData);
    });

    $this->mock(SeasonQueries::class, function ($mock) use ($returnData): void {
        getWithBasicColumns($mock, $returnData);
    });

    $this->mock(DepartmentQueries::class, function ($mock) use ($returnData): void {
        getWithBasicColumns($mock, $returnData);
    });

    $this->mock(ColorQueries::class, function ($mock) use ($returnData): void {
        getWithBasicColumns($mock, $returnData);
    });

    $this->mock(SizeQueries::class, function ($mock) use ($returnData): void {
        getWithBasicColumns($mock, $returnData);
    });
    $this->mock(PackageTypeQueries::class, function ($mock) use ($returnData): void {
        getWithBasicColumns($mock, $returnData);
    });

    $this->mock(CompanyQueries::class, function ($mock): void {
        $mock->shouldReceive('getByIdWithBrands')
            ->once()
            ->with(1)
            ->andReturn(new Company());
        $mock->shouldReceive('getByIdWithPromoterCommissionDetails')
            ->once()
            ->with(1)
            ->andReturn(new Company());
    });

    $this->mock(SaleChannelQueries::class, function ($mock): void {
        $mock->shouldReceive('getAllByCompanyId')
        ->once()
        ->andReturn(collect([]));
    });

    $this->mock(StyleQueries::class, function ($mock) use ($returnData): void {
        getWithBasicColumns($mock, $returnData);
    });

    $this->mock(CategoryQueries::class, function ($mock) use ($returnData): void {
        $mock->shouldReceive('getMainCategoriesWithBasicColumns')
            ->once()
            ->with(1)
            ->andReturn(new Collection([$returnData]));
    });

    $this->mock(TagQueries::class, function ($mock) use ($returnData): void {
        getWithBasicColumns($mock, $returnData);
    });

    $this->mock(VendorQueries::class, function ($mock) use ($returnData): void {
        getWithBasicColumns($mock, $returnData);
    });

    $this->mock(MembershipQueries::class, function ($mock) use ($returnData): void {
        getWithBasicColumns($mock, $returnData);
    });

    $this->mock(TemplateQueries::class, function ($mock) use ($returnData): void {
        $mock->shouldReceive('fetchForDropdown')
        ->once()
        ->with(1)
        ->andReturn(new Collection([$returnData]));
    });

    $productController = new ProductController($productQueries);
    $response = $productController->edit(1);
    $response->rootView('admin.index');

    $newResponse = new TestResponse($response->toResponse(new Request()));

    $newResponse->assertInertia(
        fn (Assert $inertia): Assert => $inertia
            ->has('product', fn (Assert $product): Assert => $product->where('name', $productRecord['name'])->etc())
            ->has(
                'unitOfMeasures',
                fn (Assert $unitOfMeasures): Assert => $unitOfMeasures
                    ->has(
                        '0',
                        fn (Assert $unitOfMeasure): Assert => $unitOfMeasure->where('id', '1')->where('name', 'ABC')
                    )
            )
            ->has(
                'seasons',
                fn (Assert $seasons): Assert => $seasons
                    ->has('0', fn (Assert $season): Assert => $season->where('id', '1')->where('name', 'ABC'))
            )
            ->has(
                'departments',
                fn (Assert $departments): Assert => $departments
                    ->has(
                        '0',
                        fn (Assert $department): Assert => $department->where('id', '1')->where('name', 'ABC')
                    )
            )
            ->has(
                'subDepartments',
                fn (Assert $subDepartments): Assert => $subDepartments
                    ->has(
                        '0',
                        fn (Assert $subDepartment): Assert => $subDepartment->where('id', 1)->where('name', 'Gds')
                    )
                    ->has(
                        '1',
                        fn (Assert $subDepartment): Assert => $subDepartment->where('id', 2)->where('name', 'Ops')
                    )
            )
            ->has(
                'colors',
                fn (Assert $colors): Assert => $colors
                    ->has('0', fn (Assert $color): Assert => $color->where('id', '1')->where('name', 'ABC'))
            )
            ->has(
                'sizes',
                fn (Assert $sizes): Assert => $sizes
                    ->has('0', fn (Assert $size): Assert => $size->where('id', '1')->where('name', 'ABC'))
            )
            ->has(
                'styles',
                fn (Assert $styles): Assert => $styles
                    ->has('0', fn (Assert $style): Assert => $style->where('id', '1')->where('name', 'ABC'))
            )
            ->has(
                'categories',
                fn (Assert $categories): Assert => $categories
                    ->has('0', fn (Assert $category): Assert => $category->where('id', '1')->where('name', 'ABC'))
            )
            ->has(
                'tags',
                fn (Assert $tags): Assert => $tags
                    ->has('0', fn (Assert $tag): Assert => $tag->where('id', '1')->where('name', 'ABC'))
            )
    );
});

test('It calls the update method of the product queries class and returns proper response', function (): void {
    $companyId = 1;
    setCompanyIdInSession();

    $productRecord = Product::factory()->make([
        'unit_of_measure_id' => null,
        'season_id' => null,
        'department_id' => null,
        'color_id' => null,
        'size_id' => null,
        'style_id' => null,
        'brand_id' => 1,
        'company_id' => $companyId,
    ])->toArray();

    $productRecord['images'] = [];
    $productRecord['videos'] = [];
    $productRecord['thumbnail'] = null;
    $productRecord['vendor_id'] = null;
    $productRecord['category_ids'] = [];
    $productRecord['tag_ids'] = [];
    $productRecord['tiers'] = [];
    $productRecord['assembly_child_products'] = [];
    $productRecord['boxes'] = [];
    $productRecord['attached_templates'] = [];
    $productRecord['custom_field_values'] = null;
    $productRecord['retail_planning_hierarchy_id'] = null;
    $productRecord['warranty_month'] = null;
    $productRecord['vendor_id'] = null;
    $productRecord['original_created_at'] = null;
    $productRecord['is_warranty'] = false;
    $productRecord['type_id'] = (string) ProductTypes::REGULAR_PRODUCT->value;
    unset($productRecord['company_id']);

    $productData = new ProductData(...$productRecord);

    $admin = Admin::factory()->make([
        'employee_id' => 1,
    ]);

    $productQueries = $this->mock(ProductQueries::class, function ($mock) use ($productData, $companyId, $admin): void {
        $mock->shouldReceive('update')
            ->once()
            ->with($productData, 1, $companyId, $admin);
        $mock->shouldReceive('getById')
          ->once()
          ->with(1, $companyId)
          ->andReturn(new Product());
    });

    $companyQueries = $this->mock(CompanyQueries::class, function ($mock) use ($productData): void {
        $mock->shouldReceive('hasAllBrandsAttached')
            ->once()
            ->with(1, [$productData->brand_id])
            ->andReturn(true);
    });

    $this->mock(PromotionQueries::class, function ($mock): void {
        $mock->shouldReceive('promotionExistsForProduct')
            ->once()
            ->with(1, 1)
            ->andReturn(false);
    });

    $this->mock(ProductCollectionProductQueries::class, function ($mock): void {
        $mock->shouldReceive('removeByProductId')
            ->once();
    });

    $request = new Request();

    $request->setUserResolver(fn (): Admin => $admin);

    $productController = new ProductController($productQueries);
    $redirectResponse = $productController->update($productData, 1, $request);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Product updated successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/products', $redirectResponse->getTargetUrl());
});

test('It calls the upload image method of the product queries class and returns proper response', function (): void {
    $companyId = 1;
    setCompanyIdInSession();

    $product = Product::factory()->make([
        'id' => 1,
        'unit_of_measure_id' => null,
        'season_id' => null,
        'department_id' => null,
        'color_id' => null,
        'size_id' => null,
        'style_id' => null,
        'brand_id' => 1,
        'company_id' => $companyId,
    ]);

    $uploadedFile = UploadedFile::fake()->image('avatar.jpg', 500, 500)->size(100);

    $productImageUploadData = new ProductImageUploadData(...[
        'image' => $uploadedFile,
        'product_id' => $product->id,
    ]);

    $productQueries = $this->mock(ProductQueries::class, function ($mock) use (
        $productImageUploadData,
        $companyId
    ): void {
        $mock->shouldReceive('uploadImage')
            ->once()
            ->with($productImageUploadData, $companyId);
    });

    $request = new Request();
    $productController = new ProductController($productQueries);
    $redirectResponse = $productController->uploadImage($productImageUploadData);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Product image uploaded successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/products', $redirectResponse->getTargetUrl());
});

test('An exception is thrown if all brands are not attached', function (): void {
    $companyId = 1;
    setCompanyIdInSession();

    $productRecord = Product::factory()->make([
        'unit_of_measure_id' => null,
        'season_id' => null,
        'department_id' => null,
        'color_id' => null,
        'size_id' => null,
        'style_id' => null,
        'brand_id' => 0,
        'company_id' => $companyId,
        'vendor_id' => null,
    ])->toArray();

    $productRecord['images'] = [];
    $productRecord['videos'] = [];
    $productRecord['thumbnail'] = null;
    $productRecord['category_ids'] = [];
    $productRecord['tag_ids'] = [];
    $productRecord['tiers'] = [];
    $productRecord['assembly_child_products'] = [];
    $productRecord['boxes'] = [];
    $productRecord['attached_templates'] = [];
    $productRecord['custom_field_values'] = null;
    $productRecord['retail_planning_hierarchy_id'] = null;
    $productRecord['warranty_month'] = null;
    $productRecord['vendor_id'] = null;
    $productRecord['is_warranty'] = false;
    $productRecord['original_created_at'] = null;

    $productRecord['type_id'] = (string) ProductTypes::REGULAR_PRODUCT->value;
    unset($productRecord['company_id']);

    $productData = new ProductData(...$productRecord);

    $productQueries = resolve(ProductQueries::class);

    $companyQueries = $this->mock(CompanyQueries::class, function ($mock) use ($productData): void {
        $mock->shouldReceive('hasAllBrandsAttached')
            ->once()
            ->with(1, [$productData->brand_id])
            ->andReturn(false);
    });

    $admin = Admin::factory()->make([
        'employee_id' => 1,
    ]);

    $request = new Request();

    $request->setUserResolver(fn (): Admin => $admin);

    $productController = new ProductController($productQueries);
    $productController->update($productData, 1, $request);
})->throws(RedirectBackWithErrorException::class);

test(
    'It calls the getActiveProductsByUpc method of the product queries class and returns proper response',
    function (): void {
        setCompanyIdInSession();

        $product = commonGetProductDetails();

        $productsUpc = [
            'import_products' => [$product->upc],
        ];

        $request = new Request($productsUpc);

        $productQueries = $this->mock(ProductQueries::class, function ($mock) use ($productsUpc, $product): void {
            $mock->shouldReceive('getActiveProductsByUpc')
                ->once()
                ->with($productsUpc['import_products'], 1)
                ->andReturn(new Collection([$product]));
        });

        $productController = new ProductController($productQueries);
        $response = $productController->getMatchingUpcProducts($request);
        expect($response['products']->resource)->toBeInstanceOf(SupportCollection::class);
        $this->assertEquals(1, $response['products_count']);
    }
);

test('It calls the markAsArchived method of the product queries class as expected', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $product = Product::factory()->make([
        'id' => 1,
        'unit_of_measure_id' => null,
        'season_id' => null,
        'department_id' => null,
        'color_id' => null,
        'size_id' => null,
        'style_id' => null,
        'brand_id' => 1,
        'company_id' => 1,
        'status' => true,
    ]);

    $productQueries = $this->mock(ProductQueries::class, function ($mock): void {
        $mock->shouldReceive('markAsArchived')
            ->once();
    });

    $this->mock(ProductCollectionProductQueries::class, function ($mock): void {
        $mock->shouldReceive('removeByProductId')
            ->once();
    });

    $productController = new ProductController($productQueries);
    $redirectResponse = $productController->archive($product->id);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Product archived successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/products', $redirectResponse->getTargetUrl());
});

test('It calls the exportProducts method and returns a proper response', function (bool $productVariant): void {
    setCompanyIdInSession();

    Config::set('app.product_variant', $productVariant);

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'date_range' => 'null',
        'status' => null,
        'batch' => null,
        'product_type_id' => 'null',
        'category_ids' => 'null',
        'brand_ids' => 'null',
        'color_ids' => 'null',
        'size_ids' => 'null',
        'department_ids' => 'null',
        'article_numbers' => 'null',
        'tag_ids' => 'null',
        'style_ids' => 'null',
        'product_collection_ids' => null,
        'export_columns' => null,
        'attributes' => [],
    ];

    $request = new Request($requestParameter);

    $admin = Admin::factory()->make([
        'employee_id' => 1,
    ]);
    $admin->roles = collect([]);
    $request->setUserResolver(fn (): Admin => $admin);

    $productQueries = $this->mock(ProductQueries::class, function ($mock) use ($requestParameter): void {
        $mock->shouldReceive('getProductsWithRelationsForExport')
            ->once()
            ->with($requestParameter, 1)
            ->andReturn(collect(new Product()));
    });

    $productController = new ProductController($productQueries);

    $response = $productController->exportProducts('filename.csv', $request);

    $this->assertEquals(200, $response->getStatusCode());

    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
})->with([[true], [false]]);

test('It calls the restore method of the product queries class as expected', function (): void {
    Bus::fake();
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $product = Product::factory()->make([
        'id' => 1,
        'unit_of_measure_id' => null,
        'season_id' => null,
        'department_id' => null,
        'color_id' => null,
        'size_id' => null,
        'style_id' => null,
        'brand_id' => 1,
        'company_id' => 1,
        'status' => false,
    ]);

    $productQueries = $this->mock(ProductQueries::class, function ($mock): void {
        $mock->shouldReceive('restore')
            ->once();
    });

    $productController = new ProductController($productQueries);
    $redirectResponse = $productController->restore($product->id);

    Bus::assertDispatched(ProductCollectionUpdateByProductJob::class);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Product restored successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/products', $redirectResponse->getTargetUrl());
});

test(
    'products that are not of the regular type and have a minimum price does not specified.',
    function (): void {
        $productRecord = Product::factory()->make([
            'unit_of_measure_id' => null,
            'season_id' => null,
            'department_id' => null,
            'color_id' => null,
            'size_id' => null,
            'style_id' => null,
            'brand_id' => 1,
            'company_id' => 1,
            'minimum_price' => 0,
            'vendor_id' => null,
        ])->toArray();
        $productRecord['images'] = [];
        $productRecord['videos'] = [];
        $productRecord['thumbnail'] = null;
        $productRecord['category_ids'] = [];
        $productRecord['tag_ids'] = [];
        $productRecord['tiers'] = [];
        $productRecord['assembly_child_products'] = [];
        $productRecord['boxes'] = [];
        $productRecord['attached_templates'] = [];
        $productRecord['custom_field_values'] = null;
        $productRecord['retail_planning_hierarchy_id'] = null;
        $productRecord['warranty_month'] = null;
        $productRecord['vendor_id'] = null;
        $productRecord['is_warranty'] = false;
        $productRecord['original_created_at'] = null;

        $productRecord['type_id'] = (string) ProductTypes::SPECIAL_ORDER->value;
        unset($productRecord['company_id']);

        setCompanyIdInSession();

        $admin = Admin::factory()->make([
            'employee_id' => 1,
        ]);

        $request = new Request();

        $request->setUserResolver(fn (): Admin => $admin);

        $productData = new ProductData(...$productRecord);

        $productController = new ProductController(new ProductQueries());
        $productController->store($productData, $request);
    }
)->throws(RedirectBackWithErrorException::class);

test('It calls the searchByArticleNumber method of the product queries class as expected', function (): void {
    $companyId = 1;
    $locationOne = 1;
    $locationTwo = 2;

    setCompanyIdInSession($companyId);
    $productArticleData = new ProductArticleData('123456', (string) $locationOne, (string) $locationTwo);
    $returnData = [
        'products' => [
            [
                'id' => 1,
                'has_batch' => 1,
                'color' => 'Red',
                'size' => 'Xl',
                'stock' => null,
                'combination' => 'Red Xl',
            ],
        ],
        'colors' => ['red', 'blue'],
        'sizes' => ['XL', 'XXl'],
    ];

    $this->mock(ProductService::class, function ($mock) use ($productArticleData, $returnData): void {
        $mock->shouldReceive('getActiveInventoryProductDetailsForArticleNumber')
            ->with($productArticleData, 1)
            ->once()
            ->andReturn($returnData);
    });

    $productController = new ProductController(new ProductQueries());
    $redirectResponse = $productController->searchByArticleNumber($productArticleData);

    $this->assertEquals($redirectResponse, $returnData);
});

function getWithBasicColumns($mockClass, $returnData): void
{
    $mockClass->shouldReceive('getWithBasicColumns')
        ->once()
        ->with(1)
        ->andReturn(new Collection([$returnData]));
}

test(
    'It calls the searchProductsByOnlyArticleNumber method of the product queries class as expected',
    function (): void {
        $companyId = 1;

        setCompanyIdInSession($companyId);
        $returnData = [
            'products' => [
                [
                    'id' => 1,
                    'has_batch' => 1,
                    'color' => 'Red',
                    'size' => 'Xl',
                    'stock' => null,
                    'combination' => 'Red Xl',
                ],
            ],
            'colors' => ['red', 'blue'],
            'sizes' => ['XL', 'XXl'],
        ];

        $requestParameter = [
            'article_number' => '123456',
        ];

        $this->mock(ProductService::class, function ($mock) use (
            $requestParameter,
            $companyId,
            $returnData
        ): void {
            $mock->shouldReceive('getProductDetailsByArticleNumber')
                ->with($requestParameter, $companyId)
                ->once()
                ->andReturn($returnData);
        });

        $productController = new ProductController(new ProductQueries());
        $redirectResponse = $productController->searchProductsByOnlyArticleNumber(new Request($requestParameter));

        $this->assertEquals($redirectResponse, $returnData);
    }
);

test(
    'When Product Is Archived And Try To Merge It, It Throws An Error.',
    function (): void {
        $companyId = 1;

        $admin = Admin::factory()->make([
            'employee_id' => 1,
        ]);

        setCompanyIdInSession($companyId);

        $request = new Request();

        $request->setUserResolver(fn (): Admin => $admin);

        $productQueries = $this->mock(ProductQueries::class, function ($mock) use ($companyId): void {
            $mock->shouldReceive('checkProductIsActive')
                ->with($companyId, 1)
                ->once()
                ->andReturn(Statuses::ARCHIVED->value);
        });

        $productController = new ProductController($productQueries);
        $productController->mergeAndDeleteProduct(1, 1, $request);
    }
)->throws(HttpException::class, 'The new selected product is not active.');

test(
    'You Can Not Merge With Same Product.',
    function (): void {
        $companyId = 1;

        $admin = Admin::factory()->make([
            'employee_id' => 1,
        ]);

        setCompanyIdInSession($companyId);

        $request = new Request();

        $request->setUserResolver(fn (): Admin => $admin);

        $productQueries = $this->mock(ProductQueries::class, function ($mock) use ($companyId): void {
            $mock->shouldReceive('checkProductIsActive')
                ->with($companyId, 1)
                ->once()
                ->andReturn(Statuses::ACTIVE->value);
        });

        $productController = new ProductController($productQueries);
        $productController->mergeAndDeleteProduct(1, 1, $request);
    }
)->throws(
    HttpException::class,
    'Make sure the merged product is not the same as its opposite. You can do this by using the archive product feature.'
);

test(
    'You can not merge with different type of the Product.',
    function (): void {
        $companyId = 1;

        $admin = Admin::factory()->make([
            'employee_id' => 1,
        ]);

        setCompanyIdInSession($companyId);

        $oldProduct = Product::factory()->make([
            'id' => 1,
            'unit_of_measure_id' => null,
            'season_id' => null,
            'department_id' => null,
            'color_id' => null,
            'size_id' => null,
            'style_id' => null,
            'brand_id' => 1,
            'company_id' => $companyId,
            'vendor_id' => null,
            'status' => Statuses::ACTIVE->value,
            'type_id' => ProductTypes::CUSTOM_ORDER->value,
        ]);
        $newProduct = Product::factory()->make([
            'id' => 2,
            'unit_of_measure_id' => null,
            'season_id' => null,
            'department_id' => null,
            'color_id' => null,
            'size_id' => null,
            'style_id' => null,
            'brand_id' => 1,
            'company_id' => $companyId,
            'status' => Statuses::ACTIVE->value,
            'vendor_id' => null,
        ]);

        $request = new Request();

        $request->setUserResolver(fn (): Admin => $admin);

        $productQueries = $this->mock(ProductQueries::class, function ($mock) use (
            $oldProduct,
            $newProduct,
            $companyId
        ): void {
            $mock->shouldReceive('getProductTypeAndArticleNumber')
                ->with($oldProduct->id, $companyId)
                ->andReturn($oldProduct);

            $mock->shouldReceive('getProductTypeAndArticleNumber')
                ->with($newProduct->id, $companyId)
                ->andReturn($newProduct);

            $mock->shouldReceive('checkProductIsActive')
                ->once()
                ->andReturn(Statuses::ACTIVE->value);
        });

        $productController = new ProductController($productQueries);
        $productController->mergeAndDeleteProduct($oldProduct->id, $newProduct->id, $request);
    }
)->throws(HttpException::class);

test(
    'You can not merge with different article number of the Product.',
    function (): void {
        $companyId = 1;

        $admin = Admin::factory()->make([
            'employee_id' => 1,
        ]);

        setCompanyIdInSession($companyId);

        $oldProduct = Product::factory()->make([
            'id' => 1,
            'unit_of_measure_id' => null,
            'season_id' => null,
            'department_id' => null,
            'color_id' => null,
            'size_id' => null,
            'style_id' => null,
            'brand_id' => 1,
            'company_id' => $companyId,
            'vendor_id' => null,
            'status' => Statuses::ACTIVE->value,
            'article_number' => 'A111',
        ]);
        $newProduct = Product::factory()->make([
            'id' => 2,
            'unit_of_measure_id' => null,
            'season_id' => null,
            'department_id' => null,
            'color_id' => null,
            'size_id' => null,
            'style_id' => null,
            'brand_id' => 1,
            'company_id' => $companyId,
            'status' => Statuses::ACTIVE->value,
            'vendor_id' => null,
            'article_number' => 'A222',
        ]);

        $request = new Request();

        $request->setUserResolver(fn (): Admin => $admin);

        $productQueries = $this->mock(ProductQueries::class, function ($mock) use (
            $oldProduct,
            $newProduct,
            $companyId
        ): void {
            $mock->shouldReceive('getProductTypeAndArticleNumber')
                ->with($oldProduct->id, $companyId)
                ->andReturn($oldProduct);

            $mock->shouldReceive('getProductTypeAndArticleNumber')
                ->with($newProduct->id, $companyId)
                ->andReturn($newProduct);

            $mock->shouldReceive('checkProductIsActive')
                ->once()
                ->andReturn(Statuses::ACTIVE->value);
        });

        $productController = new ProductController($productQueries);
        $productController->mergeAndDeleteProduct($oldProduct->id, $newProduct->id, $request);
    }
)->throws(HttpException::class);

test(
    'You can not merge with only one product have article number and other product article number is null.',
    function (): void {
        $companyId = 1;

        $admin = Admin::factory()->make([
            'employee_id' => 1,
        ]);

        setCompanyIdInSession($companyId);

        $oldProduct = Product::factory()->make([
            'id' => 1,
            'unit_of_measure_id' => null,
            'season_id' => null,
            'department_id' => null,
            'color_id' => null,
            'size_id' => null,
            'style_id' => null,
            'brand_id' => 1,
            'company_id' => $companyId,
            'vendor_id' => null,
            'status' => Statuses::ACTIVE->value,
            'article_number' => 'A111',
        ]);
        $newProduct = Product::factory()->make([
            'id' => 2,
            'unit_of_measure_id' => null,
            'season_id' => null,
            'department_id' => null,
            'color_id' => null,
            'size_id' => null,
            'style_id' => null,
            'brand_id' => 1,
            'company_id' => $companyId,
            'status' => Statuses::ACTIVE->value,
            'vendor_id' => null,
            'article_number' => null,
        ]);

        $request = new Request();

        $request->setUserResolver(fn (): Admin => $admin);

        $productQueries = $this->mock(ProductQueries::class, function ($mock) use (
            $oldProduct,
            $newProduct,
            $companyId
        ): void {
            $mock->shouldReceive('getProductTypeAndArticleNumber')
                ->with($oldProduct->id, $companyId)
                ->andReturn($oldProduct);

            $mock->shouldReceive('getProductTypeAndArticleNumber')
                ->with($newProduct->id, $companyId)
                ->andReturn($newProduct);

            $mock->shouldReceive('checkProductIsActive')
                ->once()
                ->andReturn(Statuses::ACTIVE->value);
        });

        $productController = new ProductController($productQueries);
        $productController->mergeAndDeleteProduct($oldProduct->id, $newProduct->id, $request);
    }
)->throws(HttpException::class);

test(
    'Validating Product Merge and Deletion when product variant is false.',
    function (): void {
        Config::set('app.product_variant', false);
        Bus::fake();
        $companyId = 1;

        $admin = Admin::factory()->make([
            'employee_id' => 1,
        ]);

        $product = commonGetProductDetails();

        setCompanyIdInSession($companyId);

        $request = new Request();

        $request->setUserResolver(fn (): Admin => $admin);

        $productQueries = $this->mock(ProductQueries::class, function ($mock) use ($product): void {
            $mock->shouldReceive('checkProductIsActive')
                ->once()
                ->andReturn(Statuses::ACTIVE->value);
            $mock->shouldReceive('markAsArchived')
                ->times(2);
            $mock->shouldReceive('getProductTypeAndArticleNumber')
                ->times(2)
                ->andReturn($product);
        });

        $this->mock(ProductCollectionProductQueries::class, function ($mock): void {
            $mock->shouldReceive('removeByProductId')
                ->once();
        });

        $productController = new ProductController($productQueries);
        $redirectResponse = $productController->mergeAndDeleteProduct(1, 2, $request);

        Bus::assertDispatched(ProductMergeJob::class);
        Bus::assertDispatched(ProductCollectionUpdateByProductJob::class);

        expect($redirectResponse)->toHaveKey(
            'message',
            'Merged Product Is In Progress, It Will Take Some Time To Process The Inventories.'
        );
    }
);

test(
    'Validating Product Merge and Deletion when product variant is true throw exception when master product is not same.',
    function (): void {
        Config::set('app.product_variant', true);
        Bus::fake();
        $companyId = 1;

        $admin = Admin::factory()->make([
            'employee_id' => 1,
        ]);

        $product = commonGetProductDetails();

        setCompanyIdInSession($companyId);

        $request = new Request();

        $request->setUserResolver(fn (): Admin => $admin);

        $productQueries = $this->mock(ProductQueries::class, function ($mock) use ($product): void {
            $mock->shouldReceive('checkProductIsActive')
                ->once()
                ->andReturn(Statuses::ACTIVE->value);

            $mock->shouldReceive('getProductTypeAndArticleNumber')
                ->times(2)
                ->andReturn($product);
        });

        $productController = new ProductController($productQueries);
        $productController->mergeAndDeleteProduct(1, 2, $request);

        Bus::assertDispatched(ProductMergeJob::class);
        Bus::assertDispatched(ProductCollectionUpdateByProductJob::class);
    }
)->throws(HttpException::class, 'Same Master Product only can be merge');

test(
    'It calls the getProductSalesSummary method of the ProductQueries class as expected',
    function (): void {
        $companyId = 1;

        setCompanyIdInSession($companyId);
        $filterData = [
            'locationId' => null,
            'id' => null,
            'type' => null,
            'date' => '',
        ];

        $productQueries = $this->mock(ProductQueries::class, function ($mock): void {
            $mock->shouldReceive('getProductSalesSummary')
                ->once()
                ->andReturn(collect([]));
        });

        $productController = new ProductController($productQueries);
        $redirectResponse = $productController->getProductSalesSummary(new Request($filterData));

        expect($redirectResponse)
            ->toHaveKeys(['products', 'total_sales', 'total_units_sold']);
    }
);

test('the product will not be added when Bundle Product Membership field is duplicate values', function (): void {
    $productRecord = Product::factory()->make([
        'unit_of_measure_id' => null,
        'season_id' => null,
        'department_id' => null,
        'color_id' => null,
        'size_id' => null,
        'style_id' => null,
        'brand_id' => 1,
        'company_id' => 1,
        'vendor_id' => null,
    ])->toArray();

    $productRecord['images'] = [];
    $productRecord['videos'] = [];
    $productRecord['thumbnail'] = null;
    $productRecord['category_ids'] = [];
    $productRecord['tag_ids'] = [];
    $productRecord['tiers'] = [];
    $productRecord['assembly_child_products'] = [];
    $productRecord['boxes'] = [];
    $productRecord['attached_templates'] = [];
    $productRecord['custom_field_values'] = null;
    $productRecord['retail_planning_hierarchy_id'] = null;
    $productRecord['warranty_month'] = null;
    $productRecord['vendor_id'] = null;
    $productRecord['is_warranty'] = false;
    $productRecord['original_created_at'] = null;
    $productRecord['type_id'] = (string) ProductTypes::REGULAR_PRODUCT->value;
    unset($productRecord['company_id']);

    $productRecord['boxes'][]['box_product_loyalty_points'] = [
        [
            'membership_id' => 1,
        ],
        [
            'membership_id' => 1,
        ],
    ];

    setCompanyIdInSession();

    $admin = Admin::factory()->make([
        'employee_id' => 1,
    ]);

    $request = new Request();

    $request->setUserResolver(fn (): Admin => $admin);

    $productData = new ProductData(...$productRecord);

    $companyQueries = $this->mock(CompanyQueries::class, function ($mock): void {
        $mock->shouldReceive('hasAllBrandsAttached')
            ->once()
            ->with(1, [1])
            ->andReturn(true);
    });

    $productQueries = $this->mock(ProductQueries::class, function ($mock) use ($productData, $admin): void {
        $mock->shouldReceive('addNew')
            ->times(0)
            ->with($productData, 1, $admin);
    });

    $productController = new ProductController($productQueries);
    $productController->store($productData, $request);
})->throws(RedirectBackWithErrorException::class);

test('It calls the checkProductExportLimit method of the ProductQueries class as expected', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $request = new Request();
    $request->merge([
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'date_range' => 'null',
        'status' => null,
        'batch' => null,
        'product_type_id' => 'null',
        'category_ids' => 'null',
        'brand_ids' => 'null',
        'color_ids' => 'null',
        'size_ids' => 'null',
        'department_ids' => 'null',
        'article_numbers' => 'null',
        'tag_ids' => 'null',
        'style_ids' => 'null',
        'product_collection_ids' => null,
    ]);
    $admin = Admin::factory()->make([
        'id' => 1,
        'employee_id' => 1,
    ]);

    $admin->roles = collect([]);
    $request->setUserResolver(fn (): Admin => $admin);

    $productQueries = $this->mock(ProductQueries::class, function ($mock): void {
        $mock->shouldReceive('checkProductExportLimit')
            ->andReturn([
                'exceeds_limit' => false,
                'message' => 'You can export the products.',
            ]);
    });

    $this->mock(ProductService::class, function ($mock): void {
        $mock->shouldReceive('exportProductWithJob')
            ->once()
            ->andReturn([]);
    });

    $productController = new ProductController($productQueries);
    expect($productController->checkProductExportLimit($request))
        ->toHaveKeys([]);
});

test('It calls the exportLoyaltyPointProducts method and returns a proper response', function (): void {
    setCompanyIdInSession();

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'date_range' => 'null',
        'status' => null,
        'batch' => null,
        'product_type_id' => 'null',
        'category_ids' => 'null',
        'brand_ids' => 'null',
        'color_ids' => 'null',
        'size_ids' => 'null',
        'department_ids' => 'null',
        'article_numbers' => 'null',
        'tag_ids' => 'null',
        'style_ids' => 'null',
        'product_collection_ids' => null,
        'attributes' => [],
    ];

    $request = new Request($requestParameter);

    $this->mock(ProductLoyaltyPointQueries::class, function ($mock) use ($requestParameter): void {
        $mock->shouldReceive('getLoyaltyPointProducts')
            ->once()
            ->with($requestParameter, 1)
            ->andReturn(collect(new ProductLoyaltyPoint()));
    });

    $productQueries = resolve(ProductQueries::class);
    $productController = new ProductController($productQueries);

    $response = $productController->exportLoyaltyPointProducts('filename.csv', $request);

    $this->assertEquals(200, $response->getStatusCode());

    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});

test('It calls the exportBoxProducts method and returns a proper response', function (): void {
    setCompanyIdInSession();

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'date_range' => 'null',
        'status' => null,
        'batch' => null,
        'product_type_id' => 'null',
        'category_ids' => 'null',
        'brand_ids' => 'null',
        'color_ids' => 'null',
        'size_ids' => 'null',
        'department_ids' => 'null',
        'article_numbers' => 'null',
        'tag_ids' => 'null',
        'style_ids' => 'null',
        'product_collection_ids' => null,
        'attributes' => [],
    ];

    $request = new Request($requestParameter);

    $this->mock(BoxProductQueries::class, function ($mock) use ($requestParameter): void {
        $mock->shouldReceive('getBoxProducts')
            ->once()
            ->with($requestParameter, 1)
            ->andReturn(collect(new BoxProduct()));
    });

    $productQueries = resolve(ProductQueries::class);
    $productController = new ProductController($productQueries);

    $response = $productController->exportBoxProducts('filename.csv', $request);

    $this->assertEquals(200, $response->getStatusCode());

    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});

test('It calls the checkBoxProductExportLimit method of the ProductQueries class as expected', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $request = new Request();
    $request->merge([
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'date_range' => 'null',
        'status' => null,
        'batch' => null,
        'product_type_id' => 'null',
        'category_ids' => 'null',
        'brand_ids' => 'null',
        'color_ids' => 'null',
        'size_ids' => 'null',
        'department_ids' => 'null',
        'article_numbers' => 'null',
        'tag_ids' => 'null',
        'style_ids' => 'null',
        'product_collection_ids' => null,
    ]);
    $admin = Admin::factory()->make([
        'id' => 1,
        'employee_id' => 1,
    ]);

    $admin->roles = collect([]);
    $request->setUserResolver(fn (): Admin => $admin);

    $productQueries = $this->mock(ProductQueries::class, function ($mock): void {
        $mock->shouldReceive('checkBoxProductExportLimit')
            ->andReturn([
                'exceeds_limit' => false,
                'message' => 'You can export the products.',
            ]);
    });

    $this->mock(ProductService::class, function ($mock): void {
        $mock->shouldReceive('exportBoxProductWithJob')
            ->once()
            ->andReturn([]);
    });

    $productController = new ProductController($productQueries);
    expect($productController->checkBoxProductExportLimit($request))
        ->toHaveKeys([]);
});

test(
    'It calls the checkProductLoyaltyPointExportLimit method of the ProductQueries class as expected',
    function (): void {
        $companyId = 1;

        setCompanyIdInSession($companyId);

        $request = new Request();
        $request->merge([
            'search_text' => 'test',
            'sort_by' => 'test',
            'sort_direction' => 'test',
            'date_range' => 'null',
            'status' => null,
            'batch' => null,
            'product_type_id' => 'null',
            'category_ids' => 'null',
            'brand_ids' => 'null',
            'color_ids' => 'null',
            'size_ids' => 'null',
            'department_ids' => 'null',
            'article_numbers' => 'null',
            'tag_ids' => 'null',
            'style_ids' => 'null',
            'product_collection_ids' => null,
        ]);
        $admin = Admin::factory()->make([
            'id' => 1,
            'employee_id' => 1,
        ]);

        $admin->roles = collect([]);
        $request->setUserResolver(fn (): Admin => $admin);

        $productQueries = $this->mock(ProductQueries::class, function ($mock): void {
            $mock->shouldReceive('checkProductLoyaltyPointExportLimit')
                ->andReturn([
                    'exceeds_limit' => false,
                    'message' => 'You can export the products.',
                ]);
        });

        $this->mock(ProductService::class, function ($mock): void {
            $mock->shouldReceive('exportProductLoyaltyPointWithJob')
                ->once()
                ->andReturn([]);
        });

        $productController = new ProductController($productQueries);
        expect($productController->checkProductLoyaltyPointExportLimit($request))
            ->toHaveKeys([]);
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
                ->with(1, SyncTypes::PRODUCT->value, $admin, 1);
        });

        $productController = new ProductController(new ProductQueries());
        $productController->syncData(1, $request);

        Queue::assertPushed(ProductSyncMainJob::class);
    }
);

test(
    'It calls the uploadImagesByArticleNumber method of the product queries class and returns proper response',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession();

        $product = Product::factory()->make([
            'id' => 1,
            'unit_of_measure_id' => null,
            'article_number' => '123456',
            'season_id' => null,
            'department_id' => null,
            'color_id' => null,
            'size_id' => null,
            'style_id' => null,
            'brand_id' => 1,
            'company_id' => $companyId,
        ]);

        $uploadedFile = UploadedFile::fake()->image('avatar.jpg', 500, 500)->size(100);

        $productImageUploadByArticleNumberData = new ProductImageUploadByArticleNumberData(...[
            'thumbnail' => $uploadedFile,
            'article_number' => $product->article_number,
        ]);

        $productQueries = $this->mock(ProductQueries::class, function ($mock) use (
            $productImageUploadByArticleNumberData,
            $companyId
        ): void {
            $mock->shouldReceive('uploadImagesByArticleNumber')
                ->once()
                ->with($productImageUploadByArticleNumberData, $companyId);
        });

        $productController = new ProductController($productQueries);
        $redirectResponse = $productController->uploadImagesByArticleNumber($productImageUploadByArticleNumberData);

        $this->assertEquals(302, $redirectResponse->getStatusCode());
        $this->assertEquals('Product image uploaded successfully.', $redirectResponse->getSession()->all()['success']);
        $this->assertStringContainsString('admin/products', $redirectResponse->getTargetUrl());
    }
);

test(
    'It calls the fetchProductDetailsByArticleNumber query method of the product queries class and returns proper response',
    function (): void {
        $companyId = 1;

        setCompanyIdInSession($companyId);

        $requestParameter = [
            'article_number' => '123456789',
        ];

        $productQueries = $this->mock(ProductQueries::class, function ($mock): void {
            $mock->shouldReceive('getProductDetailsByArticleNumberForUploadImages')
                ->once()
                ->andReturn(new Product());
        });

        $productController = new ProductController($productQueries);

        $response = $productController->fetchProductDetailsByArticleNumber(new Request($requestParameter));

        expect($response['product']->resource);
    }
);

test(
    'It calls the exportProductsForImportBulkUpdate method and returns a proper response',
    function (bool $productVariant): void {
        Config::set('app.product_variant', $productVariant);

        setCompanyIdInSession();

        $requestParameter = [
            'search_text' => 'test',
            'sort_by' => 'test',
            'sort_direction' => 'test',
            'date_range' => 'null',
            'status' => null,
            'batch' => null,
            'product_type_id' => 'null',
            'category_ids' => 'null',
            'brand_ids' => 'null',
            'color_ids' => 'null',
            'size_ids' => 'null',
            'department_ids' => 'null',
            'article_numbers' => 'null',
            'tag_ids' => 'null',
            'style_ids' => 'null',
            'product_collection_ids' => null,
            'attributes' => [],
        ];

        $request = new Request($requestParameter);

        $admin = Admin::factory()->make([
            'employee_id' => 1,
        ]);
        $admin->roles = collect([]);
        $request->setUserResolver(fn (): Admin => $admin);

        $productQueries = $this->mock(ProductQueries::class, function ($mock) use ($requestParameter): void {
            $mock->shouldReceive('getProductsWithRelationsForExport')
                ->once()
                ->with($requestParameter, 1)
                ->andReturn(collect(new Product()));
        });

        $productController = new ProductController($productQueries);

        $response = $productController->exportProductsForImportBulkUpdate('filename.csv', $request);

        $this->assertEquals(200, $response->getStatusCode());

        expect($response)->toBeInstanceOf(BinaryFileResponse::class);
    }
)->with([[true], [false]]);

test(
    'It calls the checkProductExportLimitForImportBulkUpdate method of the ProductQueries class as expected',
    function (): void {
        $companyId = 1;

        setCompanyIdInSession($companyId);

        $request = new Request();
        $request->merge([
            'search_text' => 'test',
            'sort_by' => 'test',
            'sort_direction' => 'test',
            'date_range' => 'null',
            'status' => null,
            'batch' => null,
            'product_type_id' => 'null',
            'category_ids' => 'null',
            'brand_ids' => 'null',
            'color_ids' => 'null',
            'size_ids' => 'null',
            'department_ids' => 'null',
            'article_numbers' => 'null',
            'tag_ids' => 'null',
            'style_ids' => 'null',
            'product_collection_ids' => null,
        ]);
        $admin = Admin::factory()->make([
            'id' => 1,
            'employee_id' => 1,
        ]);

        $admin->roles = collect([]);
        $request->setUserResolver(fn (): Admin => $admin);

        $productQueries = $this->mock(ProductQueries::class, function ($mock): void {
            $mock->shouldReceive('checkProductExportLimit')
                ->andReturn([
                    'exceeds_limit' => false,
                    'message' => 'You can export the products.',
                ]);
        });

        $this->mock(ProductService::class, function ($mock): void {
            $mock->shouldReceive('exportProductWithJobForImportBulkUpdate')
                ->once()
                ->andReturn([]);
        });

        $productController = new ProductController($productQueries);
        expect($productController->checkProductExportLimitForImportBulkUpdate($request))
            ->toHaveKeys([]);
    }
);

test(
    'It calls the getProductIds method of the product queries class and returns proper response',
    function (bool $productVariant): void {
        Config::set('app.product_variant', $productVariant);
        $companyId = 1;

        setCompanyIdInSession($companyId);

        $requestParameter = [
            'search_text' => 'abc',
            'sort_by' => 'name',
            'sort_direction' => 'desc',
            'per_page' => 1,
            'status' => null,
            'batch' => null,
            'date_range' => 'null',
            'product_type_id' => 'null',
            'category_ids' => 'null',
            'brand_ids' => 'null',
            'color_ids' => 'null',
            'size_ids' => 'null',
            'department_ids' => 'null',
            'article_numbers' => 'null',
            'tag_ids' => 'null',
            'style_ids' => null,
            'product_collection_ids' => null,
            'product_sync_type_id' => ProductSyncTypes::ALL_PRODUCT->value,
            'attributes' => [],
        ];

        $productQueries = $this->mock(ProductQueries::class, function ($mock) use (
            $requestParameter,
            $companyId
        ): void {
            $mock->shouldReceive('getProductIds')
                ->once()
                ->with($requestParameter, $companyId)
                ->andReturn(collect([]));
        });

        $productController = new ProductController($productQueries);

        $response = $productController->getSelectAllProductIds(new Request($requestParameter));
        expect($response)->toBe([]);
    }
)->with([[true], [false]]);

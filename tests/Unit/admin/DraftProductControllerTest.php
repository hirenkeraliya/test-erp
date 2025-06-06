<?php

declare(strict_types=1);

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Company\CompanyQueries;
use App\Domains\Company\Enums\CommissionTypes;
use App\Domains\MasterProduct\DataObjects\MasterProductData;
use App\Domains\MasterProduct\MasterProductQueries;
use App\Domains\Product\DataObjects\DraftProductListData;
use App\Domains\Product\DataObjects\ProductData;
use App\Domains\Product\DataObjects\ProductVariantData;
use App\Domains\Product\Enums\ProductTypes;
use App\Domains\Product\Enums\Statuses;
use App\Domains\Product\ProductQueries;
use App\Domains\Product\Resources\DraftProductListResource;
use App\Domains\Product\Resources\MatchActiveProductsListResource;
use App\Exceptions\RedirectWithErrorException;
use App\Http\Controllers\Admin\DraftProductController;
use App\Models\Admin;
use App\Models\Company;
use App\Models\MasterProduct;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\LaravelData\DataCollection;

test(
    'It calls the List query method of the product queries class and returns only draft product list',
    function (): void {
        $companyId = 1;

        setCompanyIdInSession($companyId);

        $requestParameter = [
            'per_page' => 10,
            'page' => 1,
            'search_text' => 'abc',
            'sort_by' => 'name',
            'sort_direction' => 'desc',
            'batch' => null,
            'date_range' => null,
            'product_type_id' => null,
            'category_ids' => null,
            'brand_ids' => null,
            'color_ids' => null,
            'size_ids' => null,
            'department_ids' => null,
            'article_numbers' => null,
            'tag_ids' => null,
            'style_ids' => null,
            'employee_id' => null,
            'attributes' => null,
        ];

        $DraftProductListData = new DraftProductListData(...$requestParameter);

        $this->mock(ProductQueries::class, function ($mock) use ($requestParameter, $companyId): void {
            $mock->shouldReceive('fetchDraftList')
                ->once()
                ->with($requestParameter, $companyId)
                ->andReturn(new LengthAwarePaginator([], 20, 15));
        });

        $draftProductController = new DraftProductController();

        $response = $draftProductController->fetchDraftProducts($DraftProductListData);

        $this->assertEquals(20, $response['total_records']);
        $this->assertEquals(DraftProductListResource::collection(collect([])), $response['data']);
    }
);

test('It calls the approved method then approve product as expected', function (): void {
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
        'status' => Statuses::DRAFT->value,
    ]);

    $company = Company::factory()->make([
        'id' => 1,
        'commission_type_id' => CommissionTypes::BY_PROMOTER->value,
        'creator_can_approve_draft_product' => true,
        'default_country_id' => 1,
    ]);

    setCompanyIdInSession($company->id);

    $admin = Admin::factory()->make([
        'id' => 1,
        'employee_id' => 1,
    ]);

    $request = new Request([
        'selectedRecords' => [$product->id],
    ]);
    $request->setUserResolver(fn (): Admin => $admin);

    $productQueries = $this->mock(ProductQueries::class, function ($mock): void {
        $mock->shouldReceive('markAsApproved')
        ->once();
        $mock->shouldReceive('getCurrentUserProductCount')
        ->once()
        ->andReturn(0);
    });

    $this->mock(CompanyQueries::class, function ($mock) use ($company): void {
        $mock->shouldReceive('getWithCreatorCanApproveDraftProductById')
        ->once()
        ->with($company->id)
        ->andReturn($company);
    });

    $draftProductController = new DraftProductController($productQueries);
    $redirectResponse = $draftProductController->approved($request);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Product approved successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/draft-products', $redirectResponse->getTargetUrl());
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
        'original_created_at' => null,
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
    $productRecord['type_id'] = (string) ProductTypes::REGULAR_PRODUCT->value;
    unset($productRecord['company_id']);

    $productData = new ProductData(...$productRecord);

    $productQueries = $this->mock(ProductQueries::class, function ($mock) use ($productData, $companyId): void {
        $mock->shouldReceive('update')
            ->once()
            ->with($productData, 1, $companyId);

        $mock->shouldReceive('getById')
          ->with(1, $companyId)
          ->andReturn(new Product());

        $mock->shouldReceive('checkDraftProduct')
            ->once()
            ->with(1, $companyId)
            ->andReturn(true);
    });

    $companyQueries = $this->mock(CompanyQueries::class, function ($mock) use ($productData): void {
        $mock->shouldReceive('hasAllBrandsAttached')
            ->once()
            ->with(1, [$productData->brand_id])
            ->andReturn(true);
    });

    $draftProductController = new DraftProductController($productQueries);
    $redirectResponse = $draftProductController->update($productData, 1);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Draft product updated successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/draft-products', $redirectResponse->getTargetUrl());
});

test(
    'It calls the updateMasterProduct method of the product queries class and returns proper response',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession();

        $masterProductRecord = MasterProduct::factory()->make([
            'unit_of_measure_id' => null,
            'department_id' => null,
            'brand_id' => 1,
            'variant_template_id' => 1,
            'vendor_id' => null,
            'company_id' => $companyId,
        ])->toArray();

        $masterProductRecord['images'] = [];
        $masterProductRecord['videos'] = [];
        $masterProductRecord['thumbnail'] = null;
        $masterProductRecord['vendor_id'] = null;
        $masterProductRecord['category_ids'] = [];
        $masterProductRecord['tag_ids'] = [];
        $masterProductRecord['variants'] = new DataCollection(ProductVariantData::class, null);
        $masterProductRecord['type_id'] = (string) ProductTypes::REGULAR_PRODUCT->value;

        unset($masterProductRecord['company_id']);

        $masterProductData = new MasterProductData(...$masterProductRecord);

        $this->mock(MasterProductQueries::class, function ($mock) use ($masterProductData, $companyId): void {
            $mock->shouldReceive('update')
                ->once()
                ->with($masterProductData, 1, $companyId);

            $mock->shouldReceive('getById')
              ->with(1, $companyId)
              ->andReturn(new Product());
        });

        $this->mock(CompanyQueries::class, function ($mock) use ($masterProductData): void {
            $mock->shouldReceive('hasAllBrandsAttached')
                ->once()
                ->with(1, [$masterProductData->brand_id])
                ->andReturn(true);
        });

        $draftProductController = new DraftProductController();
        $redirectResponse = $draftProductController->updateMasterProduct($masterProductData, 1);

        $this->assertEquals(302, $redirectResponse->getStatusCode());
        $this->assertEquals('Draft product updated successfully.', $redirectResponse->getSession()->all()['success']);
        $this->assertStringContainsString('admin/draft-products', $redirectResponse->getTargetUrl());
    });

test(
    'If creator_can_approve_draft_product = true then call the getDraftProductIdsByCompanyLevel method',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession();

        $company = Company::factory()->make([
            'creator_can_approve_draft_product' => true,
            'default_country_id' => 1,
        ]);

        $admin = Admin::factory()->make([
            'id' => 1,
            'employee_id' => 1,
        ]);
        $request = new Request();
        $request->setUserResolver(fn (): Admin => $admin);

        $requestParameter = [
            'per_page' => 10,
            'page' => 1,
            'search_text' => 'abc',
            'sort_by' => 'name',
            'sort_direction' => 'desc',
            'batch' => null,
            'date_range' => null,
            'product_type_id' => null,
            'category_ids' => null,
            'brand_ids' => null,
            'color_ids' => null,
            'size_ids' => null,
            'department_ids' => null,
            'article_numbers' => null,
            'tag_ids' => null,
            'style_ids' => null,
            'employee_id' => null,
            'attributes' => null,
        ];

        $draftProductListData = new DraftProductListData(...$requestParameter);

        $productQueries = $this->mock(ProductQueries::class, function ($mock) use (
            $requestParameter,
            $companyId
        ): void {
            $mock->shouldReceive('getDraftProductIdsByCompanyLevel')
                ->once()
                ->with($requestParameter, $companyId)
                ->andReturn(collect([]));
        });

        $this->mock(CompanyQueries::class, function ($mock) use ($company): void {
            $mock->shouldReceive('getWithCreatorCanApproveDraftProductById')
                ->once()
                ->andReturn($company);
        });

        $draftProductController = new DraftProductController($productQueries);
        $redirectResponse = $draftProductController->getDraftProductIdsByExceptLoginUser(
            $draftProductListData,
            $request
        );

        expect($redirectResponse)->toBeArray();
    }
);

test(
    'If creator_can_approve_draft_product = false then call the getDraftProductIdsByExceptLoginUser method',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession();

        $company = Company::factory()->make([
            'creator_can_approve_draft_product' => false,
            'default_country_id' => 1,
        ]);

        $admin = Admin::factory()->make([
            'id' => 1,
            'employee_id' => 1,
        ]);
        $request = new Request();
        $request->setUserResolver(fn (): Admin => $admin);

        $requestParameter = [
            'per_page' => 10,
            'page' => 1,
            'search_text' => 'abc',
            'sort_by' => 'name',
            'sort_direction' => 'desc',
            'batch' => null,
            'date_range' => null,
            'product_type_id' => null,
            'category_ids' => null,
            'brand_ids' => null,
            'color_ids' => null,
            'size_ids' => null,
            'department_ids' => null,
            'article_numbers' => null,
            'tag_ids' => null,
            'style_ids' => null,
            'employee_id' => null,
            'attributes' => null,
        ];

        $draftProductListData = new DraftProductListData(...$requestParameter);

        $productQueries = $this->mock(ProductQueries::class, function ($mock) use (
            $requestParameter,
            $companyId,
            $admin
        ): void {
            $mock->shouldReceive('getDraftProductIdsByExceptLoginUser')
                ->once()
                ->with($requestParameter, $companyId, $admin->id)
                ->andReturn(collect([]));
        });

        $this->mock(CompanyQueries::class, function ($mock) use ($company): void {
            $mock->shouldReceive('getWithCreatorCanApproveDraftProductById')
                ->once()
                ->andReturn($company);
        });

        $draftProductController = new DraftProductController($productQueries);
        $redirectResponse = $draftProductController->getDraftProductIdsByExceptLoginUser(
            $draftProductListData,
            $request
        );

        expect($redirectResponse)->toBeArray();
    }
);

test(
    'It calls the getMatchActiveProducts method of the product queries class and returns proper response',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession();

        $product = Product::factory()->make([
            'unit_of_measure_id' => null,
            'season_id' => null,
            'department_id' => null,
            'color_id' => null,
            'size_id' => null,
            'style_id' => null,
            'brand_id' => 1,
            'company_id' => $companyId,
            'status' => Statuses::DRAFT->value,
            'id' => 1,
        ]);

        $productQueries = $this->mock(ProductQueries::class, function ($mock): void {
            $mock->shouldReceive('getMatchActiveProductsByDraftIdAndCompanyId')
                ->once()
                ->andReturn(collect([]));
        });

        $draftProductController = new DraftProductController($productQueries);
        $response = $draftProductController->getMatchActiveProducts($product->id);
        expect($response)->toBeArray();
        $this->assertEquals(MatchActiveProductsListResource::collection(collect([])), $response['data']);
    }
);

test(
    'the approved method throw an exception if selected product ids creator & approval user is same.',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession();

        $admin = Admin::factory()->make([
            'id' => 1,
            'employee_id' => 1,
        ]);

        $company = Company::factory()->make([
            'creator_can_approve_draft_product' => false,
            'default_country_id' => 1,
        ]);

        $product = Product::factory()->make([
            'unit_of_measure_id' => null,
            'season_id' => null,
            'department_id' => null,
            'color_id' => null,
            'size_id' => null,
            'style_id' => null,
            'brand_id' => 1,
            'company_id' => $companyId,
            'status' => Statuses::DRAFT->value,
            'id' => 1,
            'created_by_id' => $admin->id,
            'created_by_type' => ModelMapping::ADMIN->name,
        ]);

        $request = new Request([
            'selectedRecords' => [$product->id],
        ]);
        $request->setUserResolver(fn (): Admin => $admin);

        $productQueries = $this->mock(ProductQueries::class, function ($mock): void {
            $mock->shouldReceive('getCurrentUserProductCount')
                ->once()
                ->andReturn(1);
        });

        $this->mock(CompanyQueries::class, function ($mock) use ($company): void {
            $mock->shouldReceive('getWithCreatorCanApproveDraftProductById')
                ->once()
                ->andReturn($company);
        });

        $draftProductController = new DraftProductController($productQueries);
        $draftProductController->approved($request);
    }
)->throws(RedirectWithErrorException::class);

test(
    'It calls the deleteProducts method of the product queries class and returns proper response',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession();

        $product = Product::factory()->make([
            'unit_of_measure_id' => null,
            'season_id' => null,
            'department_id' => null,
            'color_id' => null,
            'size_id' => null,
            'style_id' => null,
            'brand_id' => 1,
            'company_id' => $companyId,
            'status' => Statuses::DRAFT->value,
            'id' => 1,
        ]);

        $request = new Request([
            'selectedRecords' => [$product->id],
        ]);

        $productQueries = $this->mock(ProductQueries::class, function ($mock): void {
            $mock->shouldReceive('deleteDraftProducts')
                ->once();
        });

        $draftProductController = new DraftProductController($productQueries);
        $redirectResponse = $draftProductController->deleteProducts($request);
        $this->assertEquals(302, $redirectResponse->getStatusCode());
        $this->assertEquals('Product(s) deleted successfully.', $redirectResponse->getSession()->all()['success']);
        $this->assertStringContainsString('admin/draft-products', $redirectResponse->getTargetUrl());
    }
);

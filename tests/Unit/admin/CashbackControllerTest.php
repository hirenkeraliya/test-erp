<?php

declare(strict_types=1);

use App\Domains\Cashback\CashbackQueries;
use App\Domains\Cashback\DataObjects\CashbackData;
use App\Domains\Cashback\Enums\ExcludeByTypes;
use App\Domains\Category\CategoryQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\Product\ProductQueries;
use App\Exceptions\RedirectWithErrorException;
use App\Http\Controllers\Admin\CashbackController;
use App\Models\Attribute;
use App\Models\Cashback;
use App\Models\MasterProduct;
use App\Models\Product;
use App\Models\ProductVariantValue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Config;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test('It calls the list query method of the cashback queries class and returns proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 'test',
        'location_ids' => '',
        'date_range' => '',
    ];

    $cashbackQueries = $this->mock(CashbackQueries::class, function ($mock) use (
        $requestParameter,
        $companyId
    ): void {
        $mock->shouldReceive('listQuery')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(new LengthAwarePaginator([], 50, 15));
    });

    $cashbackController = new CashbackController($cashbackQueries);

    $response = $cashbackController->fetchCashbacks(new Request($requestParameter));

    $this->assertEquals(50, $response['total_records']);
    $this->assertEquals(collect([]), $response['data']->resource);
});

test('create cashback method returns required data', function (): void {
    $companyId = 1;
    setCompanyIdInSession();

    $returnData = [
        'id' => '1',
        'name' => 'ABC',
    ];

    $this->mock(LocationQueries::class, function ($mock) use ($returnData, $companyId): void {
        $mock->shouldReceive('getStoreWithBasicColumns')
            ->once()
            ->with($companyId)
            ->andReturn(new SupportCollection([$returnData]));
    });

    $this->mock(CategoryQueries::class, function ($mock) use ($returnData, $companyId): void {
        $mock->shouldReceive('getMainCategoriesWithBasicColumns')
            ->once()
            ->with($companyId)
            ->andReturn(new Collection([$returnData]));
    });

    $cashbackController = new CashbackController(new CashbackQueries());
    $response = $cashbackController->create();
    $response->rootView('admin.index');

    $newResponse = new TestResponse($response->toResponse(new Request()));

    $newResponse->assertInertia(
        fn (Assert $inertia): Assert => $inertia
        ->has(
            'categories',
            fn (Assert $categories): Assert => $categories
            ->has('0', fn (Assert $category): Assert => $category->where('id', '1')->where('name', 'ABC'))
        )
        ->has(
            'locations',
            fn (Assert $locations): Assert => $locations
            ->has('0', fn (Assert $locations): Assert => $locations->where('id', '1')->where('name', 'ABC'))
        )
        ->has(
            'excludeByTypes',
            fn (Assert $excludeByTypes): Assert => $excludeByTypes
            ->has('0', fn (Assert $excludeByType): Assert => $excludeByType->where('id', 0)->where('name', 'None'))
            ->has(
                '1',
                fn (Assert $excludeByType): Assert => $excludeByType->where('id', 1)->where('name', 'Products')
            )
            ->has(
                '2',
                fn (Assert $excludeByType): Assert => $excludeByType->where('id', 2)->where('name', 'Categories')
            )
            ->has(
                '3',
                fn (Assert $excludeByType): Assert => $excludeByType->where('id', 3)->where(
                    'name',
                    'Original Item Price'
                )
            )
            ->has(
                '4',
                fn (Assert $excludeByType): Assert => $excludeByType->where('id', 4)->where(
                    'name',
                    'Discount Item Price'
                )
            )
        )
        ->has(
            'excludeByTypeOptions',
            fn (Assert $excludeByTypeOptions): Assert => $excludeByTypeOptions
                ->where('none', ExcludeByTypes::NONE->value)
                ->where('products', ExcludeByTypes::PRODUCTS->value)
                ->where('categories', ExcludeByTypes::CATEGORIES->value)
                ->where('originalItemPrice', ExcludeByTypes::ORIGINAL_ITEM_PRICE->value)
                ->where('discountItemPrice', ExcludeByTypes::DISCOUNT_ITEM_PRICE->value)
        )
    );
});

test('It calls the addNew method of the cashback queries class and returns proper response', function (): void {
    $companyId = 1;
    $cashbackRecord = Cashback::factory()->make([
        'company_id' => $companyId,
    ])->toArray();

    $cashbackRecord['category_ids'] = [];
    $cashbackRecord['product_ids'] = [];
    $cashbackRecord['location_ids'] = [];
    $cashbackRecord['tiers'] = [];
    unset($cashbackRecord['company_id']);

    setCompanyIdInSession();

    $cashbackData = new CashbackData(...$cashbackRecord);

    $cashbackQueries = $this->mock(CashbackQueries::class, function ($mock) use ($cashbackData, $companyId): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->with($cashbackData, $companyId);
    });

    $this->mock(LocationQueries::class, function ($mock) use ($cashbackData, $companyId): void {
        $mock->shouldReceive('doAllStoresExist')
            ->once()
            ->with($companyId, $cashbackData->location_ids)
            ->andReturn(true);
    });

    $cashbackController = new CashbackController($cashbackQueries);
    $redirectResponse = $cashbackController->store($cashbackData);
    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Cashback added successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/cashbacks', $redirectResponse->getTargetUrl());
});

test('An exception is thrown if store_id does not match the company_id', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);
    $cashbackRecord = Cashback::factory()->make([
        'company_id' => $companyId,
    ])->toArray();

    $cashbackRecord['category_ids'] = [];
    $cashbackRecord['product_ids'] = [];
    $cashbackRecord['location_ids'] = [];
    $cashbackRecord['tiers'] = [];
    unset($cashbackRecord['company_id']);

    $cashbackData = new CashbackData(...$cashbackRecord);

    $this->mock(LocationQueries::class, function ($mock) use ($cashbackData, $companyId): void {
        $mock->shouldReceive('doAllStoresExist')
            ->once()
            ->with($companyId, $cashbackData->location_ids)
            ->andReturn(false);
    });

    $cashbackController = new CashbackController(new CashbackQueries());
    $cashbackController->store($cashbackData);
})->throws(RedirectWithErrorException::class);

test('An exception is thrown if product_id does not match the company_id', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);
    $cashbackRecord = Cashback::factory()->make([
        'company_id' => $companyId,
    ])->toArray();

    $cashbackRecord['category_ids'] = [1];
    $cashbackRecord['product_ids'] = [1];
    $cashbackRecord['location_ids'] = [];
    $cashbackRecord['tiers'] = [];
    unset($cashbackRecord['company_id']);

    $cashbackData = new CashbackData(...$cashbackRecord);

    $this->mock(LocationQueries::class, function ($mock) use ($cashbackData, $companyId): void {
        $mock->shouldReceive('doAllStoresExist')
            ->once()
            ->with($companyId, $cashbackData->location_ids)
            ->andReturn(true);
    });

    $this->mock(ProductQueries::class, function ($mock) use ($cashbackData, $companyId): void {
        $mock->shouldReceive('doAllActiveProductsExist')
            ->once()
            ->with($companyId, $cashbackData->product_ids)
            ->andReturn(false);
    });

    $cashbackController = new CashbackController(new CashbackQueries());
    $cashbackController->store($cashbackData);
})->throws(RedirectWithErrorException::class);

test('An exception is thrown if category_id does not match the company_id', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);
    $cashbackRecord = Cashback::factory()->make([
        'company_id' => $companyId,
    ])->toArray();

    $cashbackRecord['category_ids'] = [1];
    $cashbackRecord['product_ids'] = [1];
    $cashbackRecord['location_ids'] = [];
    $cashbackRecord['tiers'] = [];
    unset($cashbackRecord['company_id']);

    $cashbackData = new CashbackData(...$cashbackRecord);

    $this->mock(LocationQueries::class, function ($mock) use ($cashbackData, $companyId): void {
        $mock->shouldReceive('doAllStoresExist')
            ->once()
            ->with($companyId, $cashbackData->location_ids)
            ->andReturn(true);
    });

    $this->mock(ProductQueries::class, function ($mock) use ($cashbackData, $companyId): void {
        $mock->shouldReceive('doAllActiveProductsExist')
            ->once()
            ->with($companyId, $cashbackData->product_ids)
            ->andReturn(true);
    });

    $this->mock(CategoryQueries::class, function ($mock) use ($cashbackData, $companyId): void {
        $mock->shouldReceive('doAllParentCategoriesExist')
            ->once()
            ->with($companyId, $cashbackData->category_ids)
            ->andReturn(false);
    });

    $cashbackController = new CashbackController(new CashbackQueries());
    $cashbackController->store($cashbackData);
})->throws(RedirectWithErrorException::class);

test(
    'It calls getByIdWithStoresProductsAndCategories method of the cashier queries class and return proper',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession();

        $returnData = [
            'id' => '1',
            'name' => 'ABC',
        ];

        $this->mock(LocationQueries::class, function ($mock) use ($returnData, $companyId): void {
            $mock->shouldReceive('getStoreWithBasicColumns')
                ->once()
                ->with($companyId)
                ->andReturn(new SupportCollection([$returnData]));
        });

        $this->mock(CategoryQueries::class, function ($mock) use ($returnData, $companyId): void {
            $mock->shouldReceive('getMainCategoriesWithBasicColumns')
                ->once()
                ->with($companyId)
                ->andReturn(new Collection([$returnData]));
        });

        unset($returnData['id']);

        $cashbackQueries = $this->mock(CashbackQueries::class, function ($mock) use (
            $returnData,
            $companyId
        ): void {
            $mock->shouldReceive('getByIdWithStoresProductsAndCategories')
                ->once()
                ->with(1, $companyId)
                ->andReturn(new Cashback($returnData));
        });

        $cashbackController = new CashbackController($cashbackQueries);
        $response = $cashbackController->edit(1);
        $response->rootView('admin.index');

        $newResponse = new TestResponse($response->toResponse(new Request()));

        $newResponse->assertInertia(
            fn (Assert $inertia): Assert => $inertia
            ->has('cashback', fn (Assert $cashback): Assert => $cashback->where('name', 'ABC')->etc())
            ->has(
                'categories',
                fn (Assert $categories): Assert => $categories
                ->has('0', fn (Assert $category): Assert => $category->where('id', '1')->where('name', 'ABC'))
            )
            ->has(
                'locations',
                fn (Assert $locations): Assert => $locations
                ->has('0', fn (Assert $location): Assert => $location->where('id', '1')->where('name', 'ABC'))
            )
            ->has(
                'excludeByTypes',
                fn (Assert $excludeByTypes): Assert => $excludeByTypes
                ->has(
                    '0',
                    fn (Assert $excludeByType): Assert => $excludeByType->where('id', 0)->where('name', 'None')
                )
                ->has(
                    '1',
                    fn (Assert $excludeByType): Assert => $excludeByType->where('id', 1)->where('name', 'Products')
                )
                ->has(
                    '2',
                    fn (Assert $excludeByType): Assert => $excludeByType->where('id', 2)->where(
                        'name',
                        'Categories'
                    )
                )
                ->has(
                    '3',
                    fn (Assert $excludeByType): Assert => $excludeByType->where('id', 3)->where(
                        'name',
                        'Original Item Price'
                    )
                )
                ->has(
                    '4',
                    fn (Assert $excludeByType): Assert => $excludeByType->where('id', 4)->where(
                        'name',
                        'Discount Item Price'
                    )
                )
            )
            ->has(
                'excludeByTypeOptions',
                fn (Assert $excludeByTypeOptions): Assert => $excludeByTypeOptions
                    ->where('none', ExcludeByTypes::NONE->value)
                    ->where('products', ExcludeByTypes::PRODUCTS->value)
                    ->where('categories', ExcludeByTypes::CATEGORIES->value)
                    ->where('originalItemPrice', ExcludeByTypes::ORIGINAL_ITEM_PRICE->value)
                    ->where('discountItemPrice', ExcludeByTypes::DISCOUNT_ITEM_PRICE->value)
            )
        );
    }
);

test('It calls the update method of the cashback queries class and returns proper response', function (): void {
    $cashbackRecord = Cashback::factory()->make([
        'company_id' => 1,
    ])->toArray();

    $cashbackRecord['category_ids'] = [];
    $cashbackRecord['product_ids'] = [];
    $cashbackRecord['location_ids'] = [];
    $cashbackRecord['tiers'] = [];
    unset($cashbackRecord['company_id']);

    setCompanyIdInSession();

    $cashbackData = new CashbackData(...$cashbackRecord);

    $cashbackQueries = $this->mock(CashbackQueries::class, function ($mock) use ($cashbackData): void {
        $mock->shouldReceive('update')
            ->once()
            ->with($cashbackData, 1, 1);
    });

    $cashbackController = new CashbackController($cashbackQueries);
    $redirectResponse = $cashbackController->update($cashbackData, 1);
    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Cashback updated successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/cashbacks', $redirectResponse->getTargetUrl());
});

test('It calls the exportCashbacks method and returns a proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 'test',
        'location_ids' => '',
        'date_range' => '',
    ];

    $cashbackQueries = $this->mock(CashbackQueries::class, function ($mock) use (
        $requestParameter,
        $companyId
    ): void {
        $mock->shouldReceive('getCashbacksExport')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(collect(new Cashback()));
    });

    $cashbackController = new CashbackController($cashbackQueries);

    $response = $cashbackController->exportCashbacks('filename.csv', new Request($requestParameter));

    $this->assertEquals(200, $response->getStatusCode());
    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});

test(
    'It calls the exportCashBackProducts method and returns a proper response when product variant is false',
    function (): void {
        Config::set('app.product_variant', false);

        $companyId = 1;

        setCompanyIdInSession($companyId);

        $cashback = Cashback::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
        ]);

        $product = Product::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
            'name' => 'ABC',
            'unit_of_measure_id' => 1,
            'season_id' => 1,
            'department_id' => 1,
            'color_id' => 1,
            'size_id' => 1,
            'brand_id' => 1,
            'style_id' => 1,
            'upc' => 'abd123',
        ]);

        $product->color = null;
        $product->size = null;

        $cashback->products = collect([$product]);

        $cashBackQueries = $this->mock(CashbackQueries::class, function ($mock) use ($cashback, $companyId): void {
            $mock->shouldReceive('getByIdWithCashbackProducts')
                ->once()
                ->with($cashback->id, $companyId)
                ->andReturn($cashback);
        });

        $cashBackController = new CashbackController($cashBackQueries);

        $response = $cashBackController->exportCashBackProducts(
            $cashback->id,
            'filename.csv',
            new Request([
                'id' => $cashback->id,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
        expect($response)->toBeInstanceOf(BinaryFileResponse::class);
    }
);

test(
    'It calls the exportCashBackProducts method and returns a proper response when product variant is true',
    function (): void {
        Config::set('app.product_variant', true);

        $companyId = 1;

        setCompanyIdInSession($companyId);

        $cashback = Cashback::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
        ]);

        $masterProduct = MasterProduct::factory()->make([
            'unit_of_measure_id' => null,
            'department_id' => null,
            'brand_id' => 1,
            'company_id' => 1,
            'variant_template_id' => 1,
            'vendor_id' => null,
        ]);

        $product = Product::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
            'name' => 'ABC',
            'unit_of_measure_id' => 1,
            'season_id' => 1,
            'department_id' => 1,
            'color_id' => 1,
            'size_id' => 1,
            'brand_id' => 1,
            'style_id' => 1,
            'upc' => 'abd123',
            'master_product_id' => $masterProduct->id,
        ]);

        $attribute = Attribute::factory()->make([
            'id' => 1,
            'template_id' => 1,
            'company_id' => $companyId,
        ]);

        $productVariantValue = ProductVariantValue::factory()->make([
            'id' => 1,
            'product_id' => $product->id,
            'attribute_id' => $attribute->id,
        ]);

        $productVariantValue->attribute = $attribute;

        $product->productVariantValues = collect([$productVariantValue]);

        $masterProduct->productVariants = collect([$product]);

        $product->masterProduct = $masterProduct;

        $cashback->products = collect([$product]);

        $cashBackQueries = $this->mock(CashbackQueries::class, function ($mock) use ($cashback, $companyId): void {
            $mock->shouldReceive('getByIdWithCashbackProducts')
                ->once()
                ->with($cashback->id, $companyId)
                ->andReturn($cashback);
        });

        $cashBackController = new CashbackController($cashBackQueries);

        $response = $cashBackController->exportCashBackProducts(
            $cashback->id,
            'filename.csv',
            new Request([
                'id' => $cashback->id,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
        expect($response)->toBeInstanceOf(BinaryFileResponse::class);
    }
);

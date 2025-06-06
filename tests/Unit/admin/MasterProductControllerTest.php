<?php

declare(strict_types=1);

use App\Domains\Company\CompanyQueries;
use App\Domains\MasterProduct\DataObjects\MasterProductData;
use App\Domains\MasterProduct\DataObjects\MasterProductImageUploadData;
use App\Domains\MasterProduct\MasterProductQueries;
use App\Domains\MasterProduct\Resources\MasterProductListResource;
use App\Domains\Product\DataObjects\ProductVariantData;
use App\Domains\Product\Enums\ProductTypes;
use App\Http\Controllers\Admin\MasterProductController;
use App\Models\Admin;
use App\Models\MasterProduct;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\LaravelData\DataCollection;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test(
    'It calls the List query method of the master product queries class and returns proper response',
    function (): void {
        $companyId = 1;

        setCompanyIdInSession($companyId);

        $requestParameter = [
            'search_text' => 'abc',
            'sort_by' => 'name',
            'sort_direction' => 'desc',
            'per_page' => 1,
            'status' => null,
            'batch' => null,
            'date_range' => null,
            'product_type_id' => null,
            'article_numbers' => null,
            'brand_ids' => null,
            'category_ids' => null,
            'department_ids' => null,
            'product_sync_type_id' => null,
        ];

        $masterProductQueries = $this->mock(MasterProductQueries::class, function ($mock) use (
            $requestParameter,
            $companyId
        ): void {
            $mock->shouldReceive('listQuery')
                ->once()
                ->with($requestParameter, $companyId)
                ->andReturn(new LengthAwarePaginator([], 20, 15));
        });

        $masterProductController = new MasterProductController($masterProductQueries);

        $response = $masterProductController->fetchMasterProducts(new Request($requestParameter));

        $this->assertEquals(20, $response['total_records']);
        $this->assertEquals(MasterProductListResource::collection(collect([])), $response['data']);
    }
);

test('It calls the addNew method of the  master product queries class and returns proper response', function (): void {
    $masterProductRecord = MasterProduct::factory()->make([
        'unit_of_measure_id' => null,
        'department_id' => null,
        'brand_id' => 1,
        'company_id' => 1,
        'variant_template_id' => 1,
        'vendor_id' => null,
    ])->toArray();

    $masterProductRecord['images'] = [];
    $masterProductRecord['videos'] = [];
    $masterProductRecord['thumbnail'] = null;
    $masterProductRecord['category_ids'] = [];
    $masterProductRecord['variants'] = new DataCollection(ProductVariantData::class, null);
    $masterProductRecord['type_id'] = (string) ProductTypes::REGULAR_PRODUCT->value;

    unset($masterProductRecord['company_id']);

    setCompanyIdInSession();

    $masterProductData = new MasterProductData(...$masterProductRecord);

    $admin = Admin::factory()->make([
        'employee_id' => 1,
    ]);
    $request = new Request();

    $request->setUserResolver(fn (): Admin => $admin);

    $this->mock(CompanyQueries::class, function ($mock) use ($masterProductData): void {
        $mock->shouldReceive('hasAllBrandsAttached')
            ->once()
            ->with(1, [$masterProductData->brand_id])
            ->andReturn(true);
    });

    $masterProductQueries = $this->mock(MasterProductQueries::class, function ($mock) use (
        $masterProductData,
        $admin
    ): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->with($masterProductData, 1, $admin);
    });

    $masterProductController = new MasterProductController($masterProductQueries);
    $redirectResponse = $masterProductController->store($masterProductData, $request);
    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Master Product added successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/master-products', $redirectResponse->getTargetUrl());
});

test('It calls the update method of the master product queries class and returns proper response', function (): void {
    $companyId = 1;
    setCompanyIdInSession();

    $masterProductRecord = MasterProduct::factory()->make([
        'unit_of_measure_id' => null,
        'department_id' => null,
        'brand_id' => 1,
        'company_id' => 1,
        'variant_template_id' => 1,
        'vendor_id' => null,
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

    $masterProductQueries = $this->mock(MasterProductQueries::class, function ($mock) use (
        $masterProductData,
        $companyId
    ): void {
        $mock->shouldReceive('update')
            ->once()
            ->with($masterProductData, 1, $companyId);
    });

    $this->mock(CompanyQueries::class, function ($mock) use ($masterProductData): void {
        $mock->shouldReceive('hasAllBrandsAttached')
            ->once()
            ->with(1, [$masterProductData->brand_id])
            ->andReturn(true);
    });

    $masterProductController = new MasterProductController($masterProductQueries);
    $redirectResponse = $masterProductController->update($masterProductData, 1);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Master Product updated successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/master-products', $redirectResponse->getTargetUrl());
});

test('It calls the exportMasterProducts method and returns a proper response', function (): void {
    setCompanyIdInSession();

    $requestParameter = [
        'search_text' => 'abc',
        'sort_by' => 'name',
        'sort_direction' => 'desc',
        'status' => null,
        'batch' => null,
        'date_range' => null,
        'category_ids' => null,
        'brand_ids' => null,
        'product_type_id' => null,
        'article_numbers' => null,
        'department_ids' => null,
        'product_sync_type_id' => null,
        'export_columns' => null,
    ];

    $request = new Request($requestParameter);

    $admin = Admin::factory()->make([
        'employee_id' => 1,
    ]);
    $admin->roles = collect([]);
    $request->setUserResolver(fn (): Admin => $admin);

    $masterProductQueries = $this->mock(MasterProductQueries::class, function ($mock) use ($requestParameter): void {
        $mock->shouldReceive('getMasterProductsWithRelationsForExport')
            ->once()
            ->with($requestParameter, 1)
            ->andReturn(collect(new MasterProduct()));
    });

    $masterProductController = new MasterProductController($masterProductQueries);

    $response = $masterProductController->exportMasterProducts('filename.csv', $request);

    $this->assertEquals(200, $response->getStatusCode());

    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});

test(
    'It calls the upload image method of the master product queries class and returns proper response',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession();

        $masterProduct = MasterProduct::factory()->make([
            'id' => 1,
            'unit_of_measure_id' => null,
            'department_id' => null,
            'brand_id' => 1,
            'variant_template_id' => 1,
            'vendor_id' => null,
            'company_id' => $companyId,
        ]);

        $uploadedFile = UploadedFile::fake()->image('avatar.jpg', 500, 500)->size(100);

        $masterProductImageUploadData = new MasterProductImageUploadData(...[
            'master_product_id' => $masterProduct->id,
            'image' => $uploadedFile,
        ]);

        $masterProductQueries = $this->mock(MasterProductQueries::class, function ($mock) use (
            $masterProductImageUploadData,
            $companyId
        ): void {
            $mock->shouldReceive('uploadImage')
                ->once()
                ->with($masterProductImageUploadData, $companyId);
        });

        $masterProductController = new MasterProductController($masterProductQueries);
        $redirectResponse = $masterProductController->uploadImage($masterProductImageUploadData);

        $this->assertEquals(302, $redirectResponse->getStatusCode());
        $this->assertEquals(
            'Master product image uploaded successfully.',
            $redirectResponse->getSession()->all()['success']
        );
        $this->assertStringContainsString('admin/master-products', $redirectResponse->getTargetUrl());
    }
);

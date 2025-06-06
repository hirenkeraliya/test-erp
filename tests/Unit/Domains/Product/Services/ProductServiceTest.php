<?php

declare(strict_types=1);

use App\Domains\Category\CategoryQueries;
use App\Domains\Color\ColorQueries;
use App\Domains\Company\CompanyQueries;
use App\Domains\Department\DepartmentQueries;
use App\Domains\Inventory\InventoryQueries;
use App\Domains\MasterProduct\MasterProductQueries;
use App\Domains\Membership\MembershipQueries;
use App\Domains\PackageType\PackageTypeQueries;
use App\Domains\Product\DataObjects\ProductArticleData;
use App\Domains\Product\DataObjects\ProductData;
use App\Domains\Product\Enums\ProductTypes;
use App\Domains\Product\ProductQueries;
use App\Domains\Product\Services\ProductService;
use App\Domains\Promotion\PromotionQueries;
use App\Domains\RetailPlanningHierarchy\RetailPlanningHierarchyQueries;
use App\Domains\SaleChannel\SaleChannelQueries;
use App\Domains\Season\SeasonQueries;
use App\Domains\Size\SizeQueries;
use App\Domains\Style\StyleQueries;
use App\Domains\Tag\TagQueries;
use App\Domains\Template\TemplateQueries;
use App\Domains\UnitOfMeasure\UnitOfMeasureQueries;
use App\Domains\Vendor\VendorQueries;
use App\Exceptions\RedirectBackWithErrorException;
use App\Models\Admin;
use App\Models\Attribute;
use App\Models\Color;
use App\Models\Company;
use App\Models\Inventory;
use App\Models\MasterProduct;
use App\Models\Product;
use App\Models\ProductVariantValue;
use App\Models\Size;
use App\Models\UnitOfMeasure;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Config;

test(
    'It calls getActiveInventoryProductDetailsForArticleNumber method and returns proper response when product variant is false',
    function (): void {
        Config::set('app.product_variant', false);

        $storeOne = 1;
        $storeTwo = 2;
        $companyId = 1;
        $product = Product::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
            'code' => '1546',
            'unit_of_measure_id' => 1,
            'season_id' => 1,
            'department_id' => 1,
            'sub_department_id' => 1,
            'color_id' => 1,
            'size_id' => 1,
            'brand_id' => 1,
            'style_id' => 1,
            'article_number' => '123456',
            'is_non_inventory' => false,
            'status' => true,
        ]);

        $product->color = Color::factory()->make([
            'company_id' => $companyId,
            'name' => 'Color A',
        ]);

        $product->size = Size::factory()->make([
            'company_id' => $companyId,
            'name' => 'Size A',
        ]);

        $product->unitOfMeasure = UnitOfMeasure::factory()->make([
            'company_id' => $companyId,
        ]);

        $this->mock(ProductQueries::class, function ($mock) use ($product, $companyId): void {
            $mock->shouldReceive('searchActiveInventoryProductsByArticleNumber')
                ->once()
                ->with($product->article_number, $companyId)
                ->andReturn(collect([$product]));
        });

        $this->mock(InventoryQueries::class, function ($mock): void {
            $mock->shouldReceive('fetchOrCreate')
                ->twice()
                ->andReturn(new Inventory());
        });

        $productArticleData = [
            'article_number' => $product->article_number,
            'source_location_id' => (string) $storeOne,
            'destination_location_id' => (string) $storeTwo,
        ];

        $productService = new ProductService();
        $response = $productService->getActiveInventoryProductDetailsForArticleNumber(
            new ProductArticleData(...$productArticleData),
            $companyId
        );

        expect($response['products']->first())
            ->toHaveKey('color')
            ->toHaveKey('size')
            ->toHaveKey('unit_of_measure')
            ->toHaveKey('stock', null);

        expect($response['yNames'])
            ->toHaveKey('0', 'Color A');

        expect($response['xNames'])
            ->toHaveKey('0', 'Size A');
    }
);

test(
    'It calls getActiveInventoryProductDetailsForArticleNumber method and returns proper response when product variant is true',
    function (): void {
        Config::set('app.product_variant', true);

        $companyId = 1;
        $storeOne = 1;
        $storeTwo = 2;

        $unitOfMeasure = UnitOfMeasure::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
            'name' => 'Unit A',
            'allow_decimal_qty' => true,
        ]);

        $masterProduct = MasterProduct::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
            'code' => '1546',
            'unit_of_measure_id' => $unitOfMeasure->id,
            'department_id' => 1,
            'brand_id' => 1,
            'variant_template_id' => 1,
            'article_number' => '123456',
        ]);

        $masterProduct->unitOfMeasure = $unitOfMeasure;

        $product = Product::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
            'code' => '1546',
            'unit_of_measure_id' => 1,
            'season_id' => 1,
            'department_id' => 1,
            'sub_department_id' => 1,
            'color_id' => 1,
            'size_id' => 1,
            'brand_id' => 1,
            'style_id' => 1,
            'article_number' => '123456',
            'master_product_id' => $masterProduct->id,
        ]);

        $attribute = Attribute::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
            'template_id' => 1,
        ]);

        $productVariantValue = ProductVariantValue::factory()->make([
            'id' => 1,
            'product_id' => $product->id,
            'attribute_id' => 1,
        ]);

        $productVariantValue->attribute = $attribute;

        $product->productVariantValues = collect([$productVariantValue]);

        $masterProduct->productVariants = collect([$product]);

        $this->mock(MasterProductQueries::class, function ($mock) use ($masterProduct, $companyId): void {
            $mock->shouldReceive('searchByArticleNumberWithNonInventory')
                ->once()
                ->with($masterProduct->article_number, $companyId)
                ->andReturn($masterProduct);
        });

        $this->mock(InventoryQueries::class, function ($mock): void {
            $mock->shouldReceive('fetchOrCreate')
                ->twice()
                ->andReturn(new Inventory());
        });

        $productArticleData = [
            'article_number' => $masterProduct->article_number,
            'source_location_id' => (string) $storeOne,
            'destination_location_id' => (string) $storeTwo,
        ];

        $productService = new ProductService();
        $response = $productService->getActiveInventoryProductDetailsForArticleNumber(
            new ProductArticleData(...$productArticleData),
            $companyId
        );

        expect($response)->toHaveKeys(['products', 'attributeNames', 'xNames', 'yNames']);
    }
);

test(
    'It calls getProductDetailsByArticleNumber method and returns proper response when product_variant false',
    function (): void {
        Config::set('app.product_variant', false);

        $companyId = 1;
        $product = Product::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
            'code' => '1546',
            'unit_of_measure_id' => 1,
            'season_id' => 1,
            'department_id' => 1,
            'sub_department_id' => 1,
            'color_id' => 1,
            'size_id' => 1,
            'brand_id' => 1,
            'style_id' => 1,
            'article_number' => '123456',
        ]);

        $product->color = Color::factory()->make([
            'company_id' => $companyId,
            'name' => 'Color A',
        ]);

        $product->size = Size::factory()->make([
            'company_id' => $companyId,
            'name' => 'Size A',
        ]);

        $this->mock(ProductQueries::class, function ($mock) use ($product, $companyId): void {
            $mock->shouldReceive('searchByArticleNumber')
                ->once()
                ->with($product->article_number, $companyId)
                ->andReturn(collect([$product]));
        });

        $productArticleData = [
            'article_number' => $product->article_number,
        ];

        $productService = new ProductService();
        $response = $productService->getProductDetailsByArticleNumber($productArticleData, $companyId);

        expect($response['products']->first())
            ->toHaveKey('color')
            ->toHaveKey('size')
            ->toHaveKey('stock', null);

        expect($response['xNames'])
            ->toHaveKey('0', 'Size A');

        expect($response['yNames'])
            ->toHaveKey('0', 'Color A');
    }
);

test(
    'It calls getProductDetailsByArticleNumber method and returns proper response when product_variant true',
    function (): void {
        Config::set('app.product_variant', true);

        $companyId = 1;
        $masterProduct = MasterProduct::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
            'code' => '1546',
            'unit_of_measure_id' => 1,
            'department_id' => 1,
            'brand_id' => 1,
            'variant_template_id' => 1,
            'article_number' => '123456',
        ]);

        $product = Product::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
            'code' => '1546',
            'unit_of_measure_id' => 1,
            'season_id' => 1,
            'department_id' => 1,
            'sub_department_id' => 1,
            'color_id' => 1,
            'size_id' => 1,
            'brand_id' => 1,
            'style_id' => 1,
            'article_number' => '123456',
            'master_product_id' => $masterProduct->id,
        ]);

        $attribute = Attribute::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
            'template_id' => 1,
        ]);

        $productVariantValue = ProductVariantValue::factory()->make([
            'id' => 1,
            'product_id' => $product->id,
            'attribute_id' => 1,
        ]);

        $productVariantValue->attribute = $attribute;

        $product->productVariantValues = collect([$productVariantValue]);

        $masterProduct->productVariants = collect([$product]);

        $this->mock(MasterProductQueries::class, function ($mock) use ($masterProduct, $companyId): void {
            $mock->shouldReceive('searchByArticleNumber')
                ->once()
                ->with($masterProduct->article_number, $companyId)
                ->andReturn($masterProduct);
        });

        $productArticleData = [
            'article_number' => $masterProduct->article_number,
        ];

        $productService = new ProductService();
        $response = $productService->getProductDetailsByArticleNumber($productArticleData, $companyId);

        expect($response)->toHaveKeys(['products', 'attributeNames', 'xNames', 'yNames']);
    }
);

test('It calls checkRequestDetails method and validate request', function (): void {
    $companyId = 1;
    setCompanyIdInSession();
    $productRecord = commonDataFileRecord();

    $this->mock(CompanyQueries::class, function ($mock): void {
        $mock->shouldReceive('hasAllBrandsAttached')
            ->once()
            ->andReturn(true);
    });

    $productData = new ProductData(...$productRecord);

    $productService = new ProductService();
    $productService->checkRequestDetails(1, $companyId, $productData);
});

test('It calls checkRequestDetails method throw exception brand does not match with the company', function (): void {
    $companyId = 1;
    setCompanyIdInSession();
    $productRecord = commonDataFileRecord();

    $this->mock(CompanyQueries::class, function ($mock): void {
        $mock->shouldReceive('hasAllBrandsAttached')
            ->once()
            ->andReturn(false);
    });

    $productData = new ProductData(...$productRecord);

    $productService = new ProductService();
    $productService->checkRequestDetails(1, $companyId, $productData);
})->throws(RedirectBackWithErrorException::class);

test('It calls validateRetailPriceForPromotion method and return when retail price is same', function (): void {
    $companyId = 1;
    setCompanyIdInSession();
    $productRecord = commonDataFileRecord($retailPrice = 10);

    $product = Product::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'code' => '1546',
        'unit_of_measure_id' => 1,
        'season_id' => 1,
        'department_id' => 1,
        'sub_department_id' => 1,
        'color_id' => 1,
        'size_id' => 1,
        'brand_id' => 1,
        'style_id' => 1,
        'article_number' => '123456',
        'retail_price' => 10,
    ]);

    $this->mock(ProductQueries::class, function ($mock) use ($product): void {
        $mock->shouldReceive('getById')
            ->once()
            ->andReturn($product);
    });

    $productData = new ProductData(...$productRecord);

    $productService = new ProductService();
    $productService->validateRetailPriceForPromotion($productData, $product->id, $companyId);
});

test(
    'It calls validateRetailPriceForPromotion method and return when promotion is not exists For Product',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession();

        $productRecord = commonDataFileRecord($retailPrice = 10);

        $product = Product::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
            'code' => '1546',
            'unit_of_measure_id' => 1,
            'season_id' => 1,
            'department_id' => 1,
            'sub_department_id' => 1,
            'color_id' => 1,
            'size_id' => 1,
            'brand_id' => 1,
            'style_id' => 1,
            'article_number' => '123456',
            'retail_price' => 100,
        ]);

        $this->mock(ProductQueries::class, function ($mock) use ($product): void {
            $mock->shouldReceive('getById')
                ->once()
                ->andReturn($product);
        });

        $this->mock(PromotionQueries::class, function ($mock): void {
            $mock->shouldReceive('promotionExistsForProduct')
                ->once()
                ->andReturn(false);
        });

        $productData = new ProductData(...$productRecord);

        $productService = new ProductService();
        $productService->validateRetailPriceForPromotion($productData, $product->id, $companyId);
    }
);

test(
    'It calls validateRetailPriceForPromotion method throw exception when promotion is exists For Product',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession();
        $productRecord = commonDataFileRecord($retailPrice = 10);

        $product = Product::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
            'code' => '1546',
            'unit_of_measure_id' => 1,
            'season_id' => 1,
            'department_id' => 1,
            'sub_department_id' => 1,
            'color_id' => 1,
            'size_id' => 1,
            'brand_id' => 1,
            'style_id' => 1,
            'article_number' => '123456',
            'retail_price' => 100,
        ]);

        $this->mock(ProductQueries::class, function ($mock) use ($product): void {
            $mock->shouldReceive('getById')
                ->once()
                ->andReturn($product);
        });

        $this->mock(PromotionQueries::class, function ($mock): void {
            $mock->shouldReceive('promotionExistsForProduct')
                ->once()
                ->andReturn(true);
        });

        $productData = new ProductData(...$productRecord);

        $productService = new ProductService();
        $productService->validateRetailPriceForPromotion($productData, $product->id, $companyId);
    }
)->throws(RedirectBackWithErrorException::class);

test(
    'It calls validateBoxProductLoyaltyPointMembership method and return when Product has no any bundle',
    function (): void {
        setCompanyIdInSession();

        $productRecord = commonDataFileRecord($retailPrice = 10);

        $productData = new ProductData(...$productRecord);

        $productService = new ProductService();
        $productService->validateBoxProductLoyaltyPointMembership($productData);
        $this->assertTrue(true);
    }
);

test(
    'It calls validateBoxProductLoyaltyPointMembership method throw exception when Product Membership field is duplicate values',
    function (): void {
        setCompanyIdInSession();
        $productRecord = commonDataFileRecord($retailPrice = 10);
        $productRecord['boxes'][]['box_product_loyalty_points'] = [
            [
                'membership_id' => 1,
            ],
            [
                'membership_id' => 1,
            ],
        ];
        $productData = new ProductData(...$productRecord);
        $productService = new ProductService();
        $productService->validateBoxProductLoyaltyPointMembership($productData);
    }
)->throws(RedirectBackWithErrorException::class);

test(
    'It call getCommonRecords method return proper response',
    function (): void {
        Config::set([
            'services.retail_planning.is_enabled' => false,
        ]);

        setCompanyIdInSession();

        $this->mock(UnitOfMeasureQueries::class, function ($mock): void {
            $mock->shouldReceive('getWithBasicColumns')
                ->once()
                ->andReturn(new Collection([]));
        });

        $this->mock(SeasonQueries::class, function ($mock): void {
            $mock->shouldReceive('getWithBasicColumns')
                ->once()
                ->andReturn(collect([]));
        });

        $this->mock(VendorQueries::class, function ($mock): void {
            $mock->shouldReceive('getWithBasicColumns')
                ->once()
                ->andReturn(collect([]));
        });

        $this->mock(SaleChannelQueries::class, function ($mock): void {
            $mock->shouldReceive('getAllByCompanyId')
            ->once()
            ->andReturn(collect([]));
        });

        $this->mock(DepartmentQueries::class, function ($mock): void {
            $mock->shouldReceive('getWithBasicColumns')
                ->once()
                ->andReturn(new Collection([]));
        });

        $this->mock(ColorQueries::class, function ($mock): void {
            $mock->shouldReceive('getWithBasicColumns')
                ->once()
                ->andReturn(new Collection([]));
        });

        $this->mock(SizeQueries::class, function ($mock): void {
            $mock->shouldReceive('getWithBasicColumns')
                ->once()
                ->andReturn(new Collection([]));
        });

        $this->mock(CompanyQueries::class, function ($mock): void {
            $mock->shouldReceive('getByIdWithPromoterCommissionDetails')
                ->once()
                ->andReturn(new Company());
            $mock->shouldReceive('getByIdWithBrands')
                ->once()
                ->andReturn(new Company());
        });

        $this->mock(StyleQueries::class, function ($mock): void {
            $mock->shouldReceive('getWithBasicColumns')
                ->once()
                ->andReturn(collect([]));
        });

        $this->mock(CategoryQueries::class, function ($mock): void {
            $mock->shouldReceive('getMainCategoriesWithBasicColumns')
                ->once()
                ->andReturn(collect([]));
        });

        $this->mock(TagQueries::class, function ($mock): void {
            $mock->shouldReceive('getWithBasicColumns')
                ->once()
                ->andReturn(new Collection([]));
        });

        $this->mock(MembershipQueries::class, function ($mock): void {
            $mock->shouldReceive('getWithBasicColumns')
                ->once()
                ->andReturn(collect([]));
        });

        $this->mock(PackageTypeQueries::class, function ($mock): void {
            $mock->shouldReceive('getWithBasicColumns')
                ->once()
                ->andReturn(collect([]));
        });

        $this->mock(TemplateQueries::class, function ($mock): void {
            $mock->shouldReceive('fetchForDropdown')
                ->once()
                ->andReturn(collect([]));
        });

        $productService = new ProductService();
        $response = $productService->getCommonRecords(1);

        expect($response)
            ->toHaveKey('unitOfMeasures')
            ->toHaveKey('seasons')
            ->toHaveKey('departments')
            ->toHaveKey('subDepartments')
            ->toHaveKey('colors')
            ->toHaveKey('sizes')
            ->toHaveKey('brands')
            ->toHaveKey('styles')
            ->toHaveKey('categories')
            ->toHaveKey('tags')
            ->toHaveKey('types')
            ->toHaveKey('memberships')
            ->toHaveKey('discountTypes')
            ->toHaveKey('commissionTypes')
            ->toHaveKey('company')
            ->toHaveKey('assemblyProductTypeStatic')
            ->toHaveKey('purchaseCost')
            ->toHaveKey('defaultTypeStatic')
            ->toHaveKey('packageTypes')
            ->toHaveKey('templates')
            ->toHaveKey('fieldTypes')
            ->toHaveKey('retailPlanningServiceConfigured');

        expect($response)
            ->not()
            ->toHaveKey('retailPlanningHierarchies');
    }
);

test(
    'It call getCommonRecords method return proper response based on retail planning configuration',
    function (): void {
        Config::set([
            'services.retail_planning.is_enabled' => true,
        ]);

        setCompanyIdInSession();

        $this->mock(RetailPlanningHierarchyQueries::class, function ($mock): void {
            $mock->shouldReceive('getTopLevelHierarchies')
                ->once()
                ->andReturn(new Collection([]));
        });

        $this->mock(UnitOfMeasureQueries::class, function ($mock): void {
            $mock->shouldReceive('getWithBasicColumns')
                ->once()
                ->andReturn(new Collection([]));
        });

        $this->mock(SeasonQueries::class, function ($mock): void {
            $mock->shouldReceive('getWithBasicColumns')
                ->once()
                ->andReturn(collect([]));
        });

        $this->mock(SaleChannelQueries::class, function ($mock): void {
            $mock->shouldReceive('getAllByCompanyId')
                ->once()
                ->andReturn(collect([]));
        });

        $this->mock(VendorQueries::class, function ($mock): void {
            $mock->shouldReceive('getWithBasicColumns')
                ->once()
                ->andReturn(collect([]));
        });

        $this->mock(DepartmentQueries::class, function ($mock): void {
            $mock->shouldReceive('getWithBasicColumns')
                ->once()
                ->andReturn(new Collection([]));
        });

        $this->mock(ColorQueries::class, function ($mock): void {
            $mock->shouldReceive('getWithBasicColumns')
                ->once()
                ->andReturn(new Collection([]));
        });

        $this->mock(SizeQueries::class, function ($mock): void {
            $mock->shouldReceive('getWithBasicColumns')
                ->once()
                ->andReturn(new Collection([]));
        });

        $this->mock(CompanyQueries::class, function ($mock): void {
            $mock->shouldReceive('getByIdWithPromoterCommissionDetails')
                ->once()
                ->andReturn(new Company());
            $mock->shouldReceive('getByIdWithBrands')
                ->once()
                ->andReturn(new Company());
        });

        $this->mock(StyleQueries::class, function ($mock): void {
            $mock->shouldReceive('getWithBasicColumns')
                ->once()
                ->andReturn(collect([]));
        });

        $this->mock(CategoryQueries::class, function ($mock): void {
            $mock->shouldReceive('getMainCategoriesWithBasicColumns')
                ->once()
                ->andReturn(collect([]));
        });

        $this->mock(TagQueries::class, function ($mock): void {
            $mock->shouldReceive('getWithBasicColumns')
                ->once()
                ->andReturn(new Collection([]));
        });

        $this->mock(MembershipQueries::class, function ($mock): void {
            $mock->shouldReceive('getWithBasicColumns')
                ->once()
                ->andReturn(collect([]));
        });

        $this->mock(PackageTypeQueries::class, function ($mock): void {
            $mock->shouldReceive('getWithBasicColumns')
                ->once()
                ->andReturn(collect([]));
        });

        $this->mock(TemplateQueries::class, function ($mock): void {
            $mock->shouldReceive('fetchForDropdown')
                ->once()
                ->andReturn(collect([]));
        });

        $productService = new ProductService();
        $response = $productService->getCommonRecords(1);

        expect($response)
            ->toHaveKey('unitOfMeasures')
            ->toHaveKey('seasons')
            ->toHaveKey('departments')
            ->toHaveKey('subDepartments')
            ->toHaveKey('colors')
            ->toHaveKey('sizes')
            ->toHaveKey('brands')
            ->toHaveKey('styles')
            ->toHaveKey('categories')
            ->toHaveKey('tags')
            ->toHaveKey('types')
            ->toHaveKey('memberships')
            ->toHaveKey('discountTypes')
            ->toHaveKey('commissionTypes')
            ->toHaveKey('company')
            ->toHaveKey('assemblyProductTypeStatic')
            ->toHaveKey('purchaseCost')
            ->toHaveKey('defaultTypeStatic')
            ->toHaveKey('packageTypes')
            ->toHaveKey('templates')
            ->toHaveKey('fieldTypes')
            ->toHaveKey('retailPlanningServiceConfigured')
            ->toHaveKey('retailPlanningHierarchies');
    }
);

test(
    'It calls getVendorCommissionPercentages method and return blank array',
    function (): void {
        $productService = new ProductService();
        $response = $productService->getVendorCommissionPercentages(collect());
        $this->assertEquals([], $response);
    }
);

test(
    'It calls getVendorCommissionPercentages method and return commission percentage',
    function (): void {
        $vendor = Vendor::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'is_consignment' => true,
            'commission_percentage' => 10,
        ]);

        $product = Product::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'code' => '1546',
            'unit_of_measure_id' => 1,
            'season_id' => 1,
            'department_id' => 1,
            'sub_department_id' => 1,
            'color_id' => 1,
            'size_id' => 1,
            'brand_id' => 1,
            'style_id' => 1,
            'article_number' => '123456',
            'is_non_inventory' => false,
            'status' => true,
        ]);

        $product->vendor = $vendor;

        $productService = new ProductService();
        $response = $productService->getVendorCommissionPercentages(collect([$product]));
        $this->assertEquals($vendor->commission_percentage, $response[$product->id]);
    }
);

test(
    'It calls getColorAndSize method and returns color and size response when product variant false',
    function (): void {
        Config::set('app.product_variant', false);

        $companyId = 1;

        $product = Product::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
            'code' => '1546',
            'unit_of_measure_id' => 1,
            'season_id' => 1,
            'department_id' => 1,
            'sub_department_id' => 1,
            'color_id' => 1,
            'size_id' => 1,
            'brand_id' => 1,
            'style_id' => 1,
            'article_number' => '123456',
        ]);

        $product->color = Color::factory()->make([
            'company_id' => $companyId,
            'name' => 'Color A',
        ]);

        $product->size = Size::factory()->make([
            'company_id' => $companyId,
            'name' => 'Size A',
        ]);

        $productService = new ProductService();
        $response = $productService->getColorAndSize($product);

        $this->assertEquals([$product->color->name, $product->size->name], $response);
    }
);

test(
    'It calls getColorAndSize method and returns color and size response when product variant true',
    function (): void {
        Config::set('app.product_variant', true);

        $companyId = 1;
        $masterProduct = MasterProduct::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
            'code' => '1546',
            'unit_of_measure_id' => 1,
            'department_id' => 1,
            'brand_id' => 1,
            'variant_template_id' => 1,
            'article_number' => '123456',
        ]);

        $product = Product::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
            'code' => '1546',
            'unit_of_measure_id' => 1,
            'season_id' => 1,
            'department_id' => 1,
            'sub_department_id' => 1,
            'color_id' => 1,
            'size_id' => 1,
            'brand_id' => 1,
            'style_id' => 1,
            'article_number' => '123456',
            'master_product_id' => $masterProduct->id,
        ]);

        $attributeSize = Attribute::factory()->make([
            'id' => 1,
            'template_id' => 1,
            'name' => 'size',
            'company_id' => $companyId,
        ]);

        $attributeColor = Attribute::factory()->make([
            'id' => 1,
            'template_id' => 1,
            'name' => 'color',
            'company_id' => $companyId,
        ]);

        $productVariantValue1 = ProductVariantValue::factory()->make([
            'id' => 1,
            'product_id' => $product->id,
            'attribute_id' => $attributeSize->id,
            'value' => 'sizeA',
        ]);

        $productVariantValue2 = ProductVariantValue::factory()->make([
            'id' => 1,
            'product_id' => $product->id,
            'attribute_id' => $attributeColor->id,
            'value' => 'colorA',
        ]);

        $productVariantValue1->attribute = $attributeSize;
        $productVariantValue2->attribute = $attributeColor;

        $product->productVariantValues = collect([$productVariantValue1, $productVariantValue2]);

        $masterProduct->productVariants = collect([$product]);

        $productService = new ProductService();
        $response = $productService->getColorAndSize($product);

        $this->assertEquals([$productVariantValue2->value, $productVariantValue1->value], $response);
    }
);

function commonDataFileRecord(?int $retailPrice = null): array
{
    return [
        'name' => 'abc',
        'description' => null,
        'code' => null,
        'unit_of_measure_id' => null,
        'season_id' => null,
        'department_id' => null,
        'sub_department_id' => null,
        'color_id' => null,
        'size_id' => null,
        'brand_id' => 1,
        'style_id' => null,
        'upc' => 'acammksmsf',
        'verification_qr_code' => 'ABCD1234XYZ',
        'ean' => null,
        'custom_sku' => null,
        'manufacturer_sku' => null,
        'article_number' => null,
        'retail_price' => $retailPrice,
        'franchise_price_1' => null,
        'franchise_price_2' => null,
        'franchise_price_3' => null,
        'wholesale_price' => null,
        'company_or_tender_price' => null,
        'branch_price' => null,
        'minimum_price' => null,
        'original_capital_price' => null,
        'capital_price' => null,
        'staff_price' => null,
        'purchase_cost' => null,
        'online_price' => null,
        'is_temporarily_unavailable' => false,
        'has_batch' => false,
        'type_id' => (string) ProductTypes::REGULAR_PRODUCT->value,
        'category_ids' => [1, 2],
        'is_non_inventory' => false,
        'is_non_selling_item' => false,
        'thumbnail' => null,
        'is_available_in_pos' => false,
        'is_available_in_ecommerce' => false,
        'is_warranty' => false,
        'original_created_at' => null,
        'images' => null,
        'tag_ids' => null,
        'tiers' => null,
        'assembly_child_products' => null,
        'boxes' => null,
        'videos' => null,
        'attached_templates' => null,
        'custom_field_values' => null,
        'retail_planning_hierarchy_id' => null,
        'warranty_month' => null,
        'vendor_id' => null,
        'is_sold_as_single_item' => false,
        'sell_item_via_derivative' => false,
    ];
}

test(
    'exportMemberWithJob method call and return exceeds_limit to false',
    function (): void {
        $companyId = 1;
        $admin = Admin::factory()->make([
            'id' => 1,
            'employee_id' => 1,
        ]);

        Config::set('app.excel.export.job_limit', 1000);

        $this->mock(ProductQueries::class, function ($mock): void {
            $mock->shouldReceive('getProductsExportCount')
                ->once()
                ->andReturn(100);
        });

        $filterData = [
            'search_text' => null,
            'sort_by' => null,
            'sort_direction' => null,
            'per_page' => null,
            'store_ids' => null,
            'membership_ids' => null,
            'member_group_ids' => null,
            'date_range' => null,
            'status' => null,
        ];

        $productService = resolve(ProductService::class);
        $response = $productService->exportProductWithJob($admin, $filterData, $companyId, collect([]));

        expect($response)->toHaveKey('exceeds_limit', false);
    }
);

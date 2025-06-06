<?php

declare(strict_types=1);

use App\Domains\CategoryChannelReference\CategoryChannelReferenceQueries;
use App\Domains\Common\Enums\WebhookUrls;
use App\Domains\Product\Enums\Statuses;
use App\Domains\Product\ProductQueries;
use App\Domains\Product\Services\ProductWebspertService;
use App\Domains\ProductChannelReference\ProductChannelReferenceQueries;
use App\Domains\SaleChannel\Enums\SaleChannelTypes;
use App\Domains\SaleChannel\SaleChannelQueries;
use App\Models\Brand;
use App\Models\Category;
use App\Models\CategoryChannelReference;
use App\Models\Color;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductChannelReference;
use App\Models\SaleChannel;
use App\Models\SaleChannelWebhookUrl;
use App\Models\Size;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    $this->saleChannel = SaleChannel::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'default_location_id' => 1,
        'type_id' => SaleChannelTypes::WEBSPERT_ECOMMERCE->value,
        'url' => 'https://example.com',
    ]);

    $this->saleChannel->saleChannelWebhookUrls = SaleChannelWebhookUrl::factory(2)->make([
        'id' => 1,
        'sale_channel_id' => $this->saleChannel->getKey(),
        'webhook_url_type_id' => WebhookUrls::PRODUCT_CREATE->value,
    ]);

    $this->productWebspertService = new ProductWebspertService();
});

test('it calls the searchProduct from the webspert api', function (): void {
    Http::fake();

    $response = $this->productWebspertService->searchProduct($this->saleChannel, 'product_upc');

    expect($response)->toBeArray();
    Http::assertSentCount(1);
});

test('it calls the getCategoryId to fetch the category id', function (): void {
    $category = Category::factory()->make([
        'id' => 1,
        'company_id' => 1,
    ]);

    $categoryChannelReference = CategoryChannelReference::factory()->make([
        'id' => 1,
        'category_id' => 1,
        'sale_channel_id' => 1,
        'external_category_id' => 1,
    ]);

    $this->mock(CategoryChannelReferenceQueries::class, function ($mock) use ($categoryChannelReference): void {
        $mock->shouldReceive('getExternalCategoryIdFromCategoryId')
            ->once()
            ->andReturn($categoryChannelReference);
    });

    $response = $this->productWebspertService->getCategoryId($category);

    expect($response)->toBe(1);
});

test('it calls the createProductOnWebspert creates the product to webspert', function (): void {
    $apiResponse['data']['products'] = [];
    Http::fake([
        'https://example.com/product/search_product_detail' => Http::response($apiResponse, 200),
    ]);

    $saleChannelProductCreate = SaleChannelWebhookUrl::factory()->make([
        'id' => 1,
        'sale_channel_id' => $this->saleChannel->getKey(),
        'webhook_url_type_id' => WebhookUrls::PRODUCT_CREATE->value,
        'url' => 'https://example.com',
    ]);

    $saleChannelImageUpload = SaleChannelWebhookUrl::factory()->make([
        'id' => 2,
        'sale_channel_id' => $this->saleChannel->getKey(),
        'webhook_url_type_id' => WebhookUrls::UPLOAD_IMAGE->value,
        'url' => 'https://example.com',
    ]);

    $this->saleChannel->saleChannelWebhookUrls = collect([$saleChannelProductCreate, $saleChannelImageUpload]);

    $product = Product::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'unit_of_measure_id' => 1,
        'season_id' => 1,
        'department_id' => 1,
        'color_id' => 1,
        'size_id' => 1,
        'brand_id' => 1,
        'style_id' => 1,
        'article_number' => '111',
        'status' => Statuses::ACTIVE->value,
    ]);

    $product->categories = collect([]);
    $product->inventory = Inventory::factory()->make([
        'product_id' => 1,
        'location_id' => 1,
    ]);
    $product->brand = Brand::factory()->make();
    $product->color = Color::factory()->make([
        'company_id' => 1,
    ]);
    $product->size = Size::factory()->make([
        'company_id' => 1,
    ]);

    $product->saleChannels = collect([$this->saleChannel]);

    $this->mock(ProductChannelReferenceQueries::class, function ($mock): void {
        $mock->shouldReceive('getProductForWebspert')
            ->once()
            ->andReturn(collect([]));
    });

    $this->mock(ProductQueries::class, function ($mock) use ($product): void {
        $mock->shouldReceive('getProductByIdWithRelations')
            ->once()
            ->andReturn($product);
    });

    $this->mock(SaleChannelQueries::class, function ($mock): void {
        $mock->shouldReceive('getSaleChannelsByCompany')
            ->once()
            ->andReturn(collect([$this->saleChannel]));

        $mock->shouldReceive('getAllByCompanyIdAndTypeId')
            ->once()
            ->andReturn(collect([$this->saleChannel]));
    });

    $this->productWebspertService->createProductOnWebspert($product);
});

test('it calls the updateProductOnWebspert to update the product on webspert', function (): void {
    $apiResponse['data']['products'] = [];
    Http::fake([
        'https://example.com/product/search_product_detail' => Http::response($apiResponse, 200),
    ]);

    $product = Product::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'unit_of_measure_id' => 1,
        'season_id' => 1,
        'department_id' => 1,
        'color_id' => 1,
        'size_id' => 1,
        'brand_id' => 1,
        'style_id' => 1,
        'article_number' => '111',
        'status' => Statuses::ACTIVE->value,
    ]);

    $product->categories = Category::factory(2)->make([
        'id' => 1,
        'company_id' => 1,
    ]);
    $product->inventory = Inventory::factory()->make([
        'product_id' => 1,
        'location_id' => 1,
    ]);
    $product->brand = Brand::factory()->make();
    $product->color = Color::factory()->make([
        'company_id' => 1,
    ]);
    $product->size = Size::factory()->make([
        'company_id' => 1,
    ]);

    $product->saleChannels = collect([$this->saleChannel]);

    $productChannelReference = ProductChannelReference::factory()->make([
        'id' => 1,
        'product_id' => 1,
        'sale_channel_id' => 1,
        'external_product_id' => 1,
        'external_variant_id' => 1,
    ]);

    $saleChannel = SaleChannel::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'default_location_id' => 1,
        'type_id' => SaleChannelTypes::WEBSPERT_ECOMMERCE,
    ]);

    $saleChannelWebhookUrl = SaleChannelWebhookUrl::factory()->make([
        'id' => 1,
        'sale_channel_id' => $saleChannel->id,
        'webhook_url_type_id' => WebhookUrls::PRODUCT_UPDATES->value,
    ]);

    $saleChannel->saleChannelWebhookUrls = collect([$saleChannelWebhookUrl]);

    $this->mock(ProductChannelReferenceQueries::class, function ($mock) use ($productChannelReference): void {
        $mock->shouldReceive('getProductIdForWebspert')
            ->once()
            ->andReturn($productChannelReference);
    });

    $this->mock(ProductQueries::class, function ($mock) use ($product): void {
        $mock->shouldReceive('getProductByIdWithRelations')
        ->once()
        ->andReturn($product);
    });

    $this->mock(SaleChannelQueries::class, function ($mock): void {
        $mock->shouldReceive('getSaleChannelsByCompany')
            ->once()
            ->andReturn(collect([$this->saleChannel]));

        $mock->shouldReceive('getAllByCompanyIdAndTypeId')
            ->once()
            ->andReturn(collect([$this->saleChannel]));
    });

    $this->productWebspertService->updateProductOnWebspert($product, false, false);

    Http::assertSentCount(2);
});

test(
    'it calls the updateProductOnWebspert to create the product on webspert when the product is not on webspert',
    function (): void {
        $apiResponse['data']['products'] = [];
        Http::fake([
            'https://example.com/product/search_product_detail' => Http::response($apiResponse, 200),
        ]);

        $saleChannelProductCreate = SaleChannelWebhookUrl::factory()->make([
            'id' => 1,
            'sale_channel_id' => $this->saleChannel->getKey(),
            'webhook_url_type_id' => WebhookUrls::PRODUCT_CREATE->value,
        ]);

        $saleChannelImageUpload = SaleChannelWebhookUrl::factory()->make([
            'id' => 2,
            'sale_channel_id' => $this->saleChannel->getKey(),
            'webhook_url_type_id' => WebhookUrls::UPLOAD_IMAGE->value,
        ]);

        $this->saleChannel->saleChannelWebhookUrls = collect([$saleChannelProductCreate, $saleChannelImageUpload]);

        $product = Product::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'unit_of_measure_id' => 1,
            'season_id' => 1,
            'department_id' => 1,
            'color_id' => 1,
            'size_id' => 1,
            'brand_id' => 1,
            'style_id' => 1,
            'article_number' => '111',
            'status' => Statuses::ACTIVE->value,
        ]);

        $product->categories = Category::factory(2)->make([
            'id' => 1,
            'company_id' => 1,
        ]);
        $product->inventory = Inventory::factory()->make([
            'product_id' => 1,
            'location_id' => 1,
        ]);
        $product->brand = Brand::factory()->make();
        $product->color = Color::factory()->make([
            'company_id' => 1,
        ]);
        $product->size = Size::factory()->make([
            'company_id' => 1,
        ]);

        $product->saleChannels = collect([$this->saleChannel]);

        $saleChannel = SaleChannel::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'default_location_id' => 1,
            'type_id' => SaleChannelTypes::WEBSPERT_ECOMMERCE,
        ]);

        $saleChannelWebhookUrl = SaleChannelWebhookUrl::factory()->make([
            'id' => 1,
            'sale_channel_id' => $saleChannel->id,
            'webhook_url_type_id' => WebhookUrls::PRODUCT_UPDATES->value,
        ]);

        $categoryChannelReference = CategoryChannelReference::factory()->make([
            'id' => 1,
            'category_id' => 1,
            'sale_channel_id' => 1,
            'external_category_id' => 1,
        ]);

        $saleChannel->saleChannelWebhookUrls = collect([$saleChannelWebhookUrl]);

        $this->mock(ProductChannelReferenceQueries::class, function ($mock): void {
            $mock->shouldReceive('getProductIdForWebspert')
                ->once();

            $mock->shouldReceive('getProductForWebspert')
                ->once()
                ->andReturn(collect([]));
        });

        $this->mock(ProductQueries::class, function ($mock) use ($product): void {
            $mock->shouldReceive('getProductByIdWithRelations')
                ->twice()
                ->andReturn($product);
        });

        $this->mock(SaleChannelQueries::class, function ($mock): void {
            $mock->shouldReceive('getSaleChannelsByCompany')
                ->once()
                ->andReturn(collect([$this->saleChannel]));

            $mock->shouldReceive('getAllByCompanyIdAndTypeId')
                ->times(2)
                ->andReturn(collect([$this->saleChannel]));
        });

        $this->mock(CategoryChannelReferenceQueries::class, function ($mock) use ($categoryChannelReference): void {
            $mock->shouldReceive('getExternalCategoryIdFromCategoryId')
                ->once()
                ->andReturn($categoryChannelReference);
        });

        $this->productWebspertService->updateProductOnWebspert($product, false, false);
    }
);

test('it does not call the updateProductOnWebspert when the sale channel is not webspert', function (): void {
    $apiResponse['data']['products'] = [];
    Http::fake([
        'https://example.com/product/search_product_detail' => Http::response($apiResponse, 200),
    ]);

    $product = Product::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'unit_of_measure_id' => 1,
        'season_id' => 1,
        'department_id' => 1,
        'color_id' => 1,
        'size_id' => 1,
        'brand_id' => 1,
        'style_id' => 1,
        'article_number' => '111',
        'status' => Statuses::ACTIVE->value,
    ]);

    $productChannelReference = ProductChannelReference::factory()->make([
        'id' => 1,
        'product_id' => 1,
        'sale_channel_id' => 1,
        'external_product_id' => 1,
        'external_variant_id' => 1,
    ]);

    $product->categories = Category::factory(2)->make([
        'id' => 1,
        'company_id' => 1,
    ]);
    $product->inventory = Inventory::factory()->make([
        'product_id' => 1,
        'location_id' => 1,
    ]);
    $product->brand = Brand::factory()->make();
    $product->color = Color::factory()->make([
        'company_id' => 1,
    ]);
    $product->size = Size::factory()->make([
        'company_id' => 1,
    ]);

    $product->saleChannels = collect([$this->saleChannel]);

    $this->mock(ProductChannelReferenceQueries::class, function ($mock) use ($productChannelReference): void {
        $mock->shouldReceive('getProductIdForWebspert')
            ->once()
            ->andReturn($productChannelReference);
    });

    $this->mock(SaleChannelQueries::class, function ($mock): void {
        $mock->shouldReceive('getSaleChannelsByCompany')
            ->once()
            ->andReturn(collect([]));

        $mock->shouldReceive('getAllByCompanyIdAndTypeId')
            ->once()
            ->andReturn(collect([$this->saleChannel]));
    });

    $this->mock(ProductQueries::class, function ($mock) use ($product): void {
        $mock->shouldReceive('getProductByIdWithRelations')
        ->once()
        ->andReturn($product);
    });

    $this->productWebspertService->updateProductOnWebspert($product, false, false);
});

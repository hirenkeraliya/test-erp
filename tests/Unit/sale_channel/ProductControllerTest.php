<?php

declare(strict_types=1);

use App\Domains\Inventory\InventoryQueries;
use App\Domains\Order\Enums\OrderStatus;
use App\Domains\Product\ProductQueries;
use App\Domains\ProductChannelReference\ProductChannelReferenceQueries;
use App\Http\Controllers\Api\SaleChannel\Product\ProductController;
use App\Models\SaleChannel;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;

it('returns a list of regular active products by companyId.', function (): void {
    $filterData = [
        'per_page' => 1,
        'page' => 1,
        'sort_by' => 'id',
        'sort_direction' => 'desc',
        'after_updated_at' => null,
        'article_number' => null,
    ];

    $productQueries = $this->mock(ProductQueries::class, function ($mock): void {
        $mock->shouldReceive('getActivePaginatedRegularProductsForEcommerce')
            ->once()
            ->andReturn(new LengthAwarePaginator([], 10, 10));
    });

    [$saleChannel, $request] = setRequestUserForSaleChannel($filterData);

    $productController = new ProductController($productQueries);
    $response = $productController->getPaginatedList($request);

    $this->assertEquals(10, $response['total_records']);
    $this->assertEquals(collect([]), $response['products']->resource);
});

it('returns a list of product stock.', function (): void {
    $filterData = [
        'per_page' => 1,
        'page' => 1,
        'sort_direction' => 'desc',
        'after_updated_at' => null,
    ];

    $this->mock(InventoryQueries::class, function ($mock): void {
        $mock->shouldReceive('getInventoriesByLocation')
            ->once()
            ->andReturn(new LengthAwarePaginator([], 10, 10));
    });

    [$saleChannel, $request] = setRequestUserForSaleChannel($filterData);

    $productController = new ProductController(new ProductQueries());
    $response = $productController->getProductStock($request);

    $this->assertEquals(10, $response['total_records']);
    $this->assertEquals(collect([]), $response['products']->resource);
});

it('it calls the getArticleNumbers method and returns product records', function (): void {
    $this->mock(ProductQueries::class, function ($mock): void {
        $mock->shouldReceive('getProductsArticleNumberForEcommerce')
            ->once()
            ->andReturn(new Collection([]));
    });

    [$saleChannel, $request] = setRequestUserForSaleChannel();

    $productController = new ProductController(new ProductQueries());
    $response = $productController->getArticleNumbers($request);

    expect($response['article_numbers'])->toBeCollection();
});

it('saveProductChannelReference calls and store the product channel reference', function (): void {
    $saleChannelData = [
        'id' => 1,
        'company_id' => 1,
        'default_location_id' => 1,
        'inventory_deduct_order_status' => OrderStatus::PLACED,
    ];
    $saleChannel = SaleChannel::factory()->make($saleChannelData);
    $requestData = [
        'product_id' => 1,
        'external_product_id' => '1',
        'external_variant_id' => null,
    ];

    $request = new Request($requestData);
    $request->setUserResolver(fn (): SaleChannel => $saleChannel);
    [$saleChannel, $request] = setRequestUserForSaleChannel();
    Validator::shouldReceive('make')
         ->once()
         ->andReturn(Mockery::mock(Illuminate\Validation\Validator::class, function ($mock) use (
             $requestData
         ): void {
             $mock->shouldReceive('validate')
                 ->once()
                 ->andReturn($requestData);
         }));
    $this->mock(ProductChannelReferenceQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    $productController = new ProductController(new ProductQueries());
    $response = $productController->saveProductChannelReference($request);
    expect($response)->toBe(null);
});

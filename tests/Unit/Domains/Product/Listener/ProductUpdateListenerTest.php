<?php

declare(strict_types=1);

use App\Domains\Product\Events\ProductUpdateEvent;
use App\Domains\Product\Listeners\ProductUpdateListener;
use App\Domains\Product\Services\ProductEcommerceService;
use App\Domains\Product\Services\ProductWebspertService;
use App\Models\Brand;
use App\Models\Color;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Size;
use Illuminate\Support\Facades\Http;

test(
    'Product Update Listener Handles Event Gracefully When E-commerce Location Found and Triggers HTTP Request',
    function (): void {
        $companyId = 1;
        Http::fake();

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
            'updated_at' => now(),
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

        $this->mock(ProductWebspertService::class, function ($mock): void {
            $mock->shouldReceive('updateProductOnWebspert')
                ->once();
        });

        $this->mock(ProductEcommerceService::class, function ($mock): void {
            $mock->shouldReceive('updateProduct');
        });

        $productUpdateListener = new ProductUpdateListener();
        $productUpdateEvent = new ProductUpdateEvent($product);
        $productUpdateListener->handle($productUpdateEvent);
    }
);

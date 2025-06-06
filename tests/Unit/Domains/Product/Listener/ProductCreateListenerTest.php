<?php

declare(strict_types=1);

use App\Domains\Product\Events\ProductCreateEvent;
use App\Domains\Product\Listeners\ProductCreateListener;
use App\Domains\Product\Services\ProductEcommerceService;
use App\Domains\Product\Services\ProductWebspertService;
use App\Models\Product;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

test(
    'Product Create Listener Handles Event Gracefully When E-commerce Store Found and Triggers HTTP Request',
    function (): void {
        $companyId = 1;
        Http::fake();
        Queue::fake();

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

        $productCreateListener = new ProductCreateListener();
        $productCreateEvent = new ProductCreateEvent($product);

        $this->mock(ProductWebspertService::class, static function ($mock): void {
            $mock->shouldReceive('createProductOnWebspert')
                ->once();
        });

        $this->mock(ProductEcommerceService::class, static function ($mock): void {
            $mock->shouldReceive('createProduct')
                ->once();
        });

        $productCreateListener->handle($productCreateEvent);
    }
);

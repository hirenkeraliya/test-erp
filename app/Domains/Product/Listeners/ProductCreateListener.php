<?php

declare(strict_types=1);

namespace App\Domains\Product\Listeners;

use App\Domains\Product\Events\ProductCreateEvent;
use App\Domains\Product\Services\ProductEcommerceService;
use App\Domains\Product\Services\ProductWebspertService;

class ProductCreateListener
{
    /**
     * Handle the event.
     */
    public function handle(ProductCreateEvent $productCreateEvent): void
    {
        $product = $productCreateEvent->product;

        $product->refresh();

        $productWebspertService = resolve(ProductWebspertService::class);
        $productWebspertService->createProductOnWebspert($product);

        $productEcommerceService = resolve(ProductEcommerceService::class);
        $productEcommerceService->createProduct($product);
    }
}

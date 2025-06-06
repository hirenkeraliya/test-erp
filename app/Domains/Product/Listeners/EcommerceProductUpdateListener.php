<?php

namespace App\Domains\Product\Listeners;

use App\Domains\Product\Events\EcommerceProductUpdateEvent;
use App\Domains\Product\Services\ProductEcommerceService;
use App\Models\MasterProduct;

class EcommerceProductUpdateListener
{
    /**
     * Handle the event.
     */
    public function handle(EcommerceProductUpdateEvent $ecommerceProductUpdateEvent): void
    {
        $product = $ecommerceProductUpdateEvent->product;

        $product->refresh();
        $product->load('masterProduct');

        /** @var ?MasterProduct $masterProduct */
        $masterProduct = $product->masterProduct;

        if ($masterProduct && $masterProduct->canSyncToEcommerce()) {
            $productEcommerceService = resolve(ProductEcommerceService::class);
            $productEcommerceService->updateProduct($product);
        }
    }
}

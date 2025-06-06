<?php

namespace App\Domains\Product\Listeners;

use App\Domains\Product\Events\ProductUpdateEvent;
use App\Domains\Product\Services\ProductWebspertService;

class ProductUpdateListener
{
    /**
     * Handle the event.
     */
    public function handle(ProductUpdateEvent $productUpdateEvent): void
    {
        $product = $productUpdateEvent->product;

        $product->refresh();

        $productWebspertService = resolve(ProductWebspertService::class);
        $productWebspertService->updateProductOnWebspert(
            $product,
            $productUpdateEvent->isSizeIdChanged,
            $productUpdateEvent->isColorIdChanged
        );
    }
}

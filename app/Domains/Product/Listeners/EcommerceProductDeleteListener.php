<?php

declare(strict_types=1);

namespace App\Domains\Product\Listeners;

use App\Domains\Product\Events\EcommerceProductDeleteEvent;
use App\Domains\Product\Services\ProductEcommerceService;
use App\Models\MasterProduct;

class EcommerceProductDeleteListener
{
    public function handle(EcommerceProductDeleteEvent $ecommerceProductDeleteEvent): void
    {
        $product = $ecommerceProductDeleteEvent->product;
        $product->refresh();

        /** @var ?MasterProduct $masterProduct */
        $masterProduct = $product->masterProduct;

        if ($masterProduct && $masterProduct->canSyncToEcommerce()) {
            $productEcommerceService = resolve(ProductEcommerceService::class);
            $productEcommerceService->deleteProduct($product);
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Domains\ExternalPurchaseOrderItem\Services;

use App\Domains\ExternalPurchaseOrder\DataObjects\ExternalPurchaseOrderData;
use App\Exceptions\RedirectBackWithErrorException;
use Illuminate\Support\Collection;

class ExternalPurchaseOrderItemCheckRequestService
{
    public function checkRequestDetails(
        ExternalPurchaseOrderData $externalPurchaseOrderData,
        Collection $products,
    ): void {
        $transferItems = $externalPurchaseOrderData->transfer_items;
        if (collect($transferItems)->sum('received_quantity') <= 0) {
            throw new RedirectBackWithErrorException(
                'Please ensure at least one received quantity is requested for adding to the External Purchase Order.'
            );
        }

        foreach ($transferItems as $transferItem) {
            $receivedQuantity = $transferItem['received_quantity'];

            if ($receivedQuantity <= 0) {
                continue;
            }

            $product = $products->firstWhere('id', $transferItem['product_id']);

            if (! $product) {
                throw new RedirectBackWithErrorException(
                    'product ID: ' . $transferItem['product_id'] . ' is not available.'
                );
            }
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Domains\ComplimentaryItemReason\Services;

use App\CommonFunctions;
use App\Domains\Order\Services\CheckOrderDetailsService;
use App\Domains\Product\Enums\ProductTypes;
use App\Models\ComplimentaryItemReason;
use App\Models\Product;

class OrderComplimentaryItemService
{
    public function checkForApplicability(
        CheckOrderDetailsService $checkOrderDetailsService,
        ?ComplimentaryItemReason $complimentaryItemReason,
        array $orderItem,
    ): void {
        if (! $complimentaryItemReason instanceof ComplimentaryItemReason) {
            abort(412, 'Specified Complimentary Item Reason is not available in our records.');
        }

        $this->checkNonRegularProduct($checkOrderDetailsService, (int) $orderItem['id']);

        $itemSubtotal = $checkOrderDetailsService->getItemSubtotal($orderItem);

        if (! array_key_exists('complimentary_item_discount', $orderItem)) {
            abort(412, 'Item discount amount is required for the complimentary item discount.');
        }

        if (! CommonFunctions::compareFloatNumbers((float) $orderItem['complimentary_item_discount'], $itemSubtotal)) {
            abort(
                412,
                'Specified discount amount does not match with our calculations. The actual discount amount is ' . $itemSubtotal . ' and requested discount amount is ' . $orderItem['complimentary_item_discount'] . '.'
            );
        }
    }

    public function getItemDiscountAmount(float $itemTotal): float
    {
        return $itemTotal;
    }

    public function checkNonRegularProduct(CheckOrderDetailsService $checkOrderDetailsService, int $orderItemId): void
    {
        /** @var Product $product */
        $product = $checkOrderDetailsService->products->firstWhere('id', $orderItemId);

        $productType = ProductTypes::getFormattedCaseName($product->type_id);

        if ($product->type_id !== ProductTypes::REGULAR_PRODUCT->value) {
            abort(
                412,
                'Complimentary is applicable on regular products only. The type of the product with the name ' . $product->name . ' is ' . $productType . '.'
            );
        }
    }
}

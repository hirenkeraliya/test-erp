<?php

declare(strict_types=1);

namespace App\Domains\Promotion\Services;

use App\Domains\Promotion\Enums\CartWidePromotionTypes;
use App\Domains\Promotion\Enums\ItemWisePromotionTypes;
use App\Domains\Promotion\Enums\PromotionApplicableTypes;
use App\Models\Promotion;

class PromotionService
{
    public function getPromotionTypeLabel(Promotion $promotion): string
    {
        $promotionType = PromotionApplicableTypes::getFormattedCaseName($promotion->promotion_applicable_type_id);
        $promotionName = null;

        if (
            $promotion->promotion_applicable_type_id === PromotionApplicableTypes::CART_WIDE->value
            && $promotion->cart_wide_promotion_type_id
        ) {
            $promotionName = CartWidePromotionTypes::getFormattedCaseName($promotion->cart_wide_promotion_type_id);
        }

        if (
            $promotion->promotion_applicable_type_id === PromotionApplicableTypes::ITEM_WISE->value
            && $promotion->item_wise_promotion_type_id
        ) {
            $promotionName = ItemWisePromotionTypes::getFormattedCaseName($promotion->item_wise_promotion_type_id);
        }

        return $promotionType . ' - ' . $promotionName;
    }
}

<?php

declare(strict_types=1);

use App\Domains\Promotion\Enums\CartWidePromotionTypes;
use App\Domains\Promotion\Enums\ItemWisePromotionTypes;
use App\Domains\Promotion\Enums\PromotionApplicableTypes;
use App\Domains\Promotion\Services\PromotionService;
use App\Models\Promotion;

beforeEach(function (): void {
    $this->promotionService = new PromotionService();
});

test('getPromotionTypeLabel method returns the promotion cart wide name', function (): void {
    $promotion = Promotion::factory()->make([
        'company_id' => 1,
        'promotion_applicable_type_id' => PromotionApplicableTypes::CART_WIDE->value,
        'cart_wide_promotion_type_id' => CartWidePromotionTypes::AS_PER_AMOUNT->value,
        'item_wise_promotion_type_id' => null,
    ]);

    $response = $this->promotionService->getPromotionTypeLabel($promotion);

    $this->assertEquals('Cart Wide - As Per Amount', $response);
});

test('getPromotionTypeLabel method returns the promotion item wise name', function (): void {
    $promotion = Promotion::factory()->make([
        'company_id' => 1,
        'promotion_applicable_type_id' => PromotionApplicableTypes::ITEM_WISE->value,
        'cart_wide_promotion_type_id' => null,
        'item_wise_promotion_type_id' => ItemWisePromotionTypes::BUNDLE_BUY->value,
    ]);

    $response = $this->promotionService->getPromotionTypeLabel($promotion);

    $this->assertEquals('Item Wise - Bundle Buy', $response);
});

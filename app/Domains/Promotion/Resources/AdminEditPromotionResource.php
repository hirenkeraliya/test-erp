<?php

declare(strict_types=1);

namespace App\Domains\Promotion\Resources;

use App\Models\Promotion;
use App\Models\PromotionMonthDate;
use App\Models\PromotionPromoCode;
use App\Models\PromotionWeekDay;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class AdminEditPromotionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var Promotion $promotion */
        $promotion = $this;

        /** @var Collection $promotionTiers */
        $promotionTiers = $promotion->promotionTiers;

        /** @var Collection $memberGroups */
        $memberGroups = $promotion->memberGroups;

        /** @var Collection $employeeGroups */
        $employeeGroups = $promotion->employeeGroups;

        /** @var Collection $saleChannels */
        $saleChannels = $promotion->saleChannels;

        /** @var PromotionMonthDate $monthly */
        $monthly = $promotion->monthly;

        /** @var PromotionWeekDay $weekly */
        $weekly = $promotion->weekly;

        /** @var Collection $categories */
        $categories = $promotion->categories;

        /** @var Collection $brands */
        $brands = $promotion->brands;

        /** @var Collection $tags */
        $tags = $promotion->tags;

        /** @var Collection $productCollections */
        $productCollections = $promotion->productCollections;

        /** @var Collection $regularProducts */
        $regularProducts = $promotion->regularProducts;

        /** @var Collection $buyProducts */
        $buyProducts = $promotion->buyProducts;

        /** @var Collection $getProducts */
        $getProducts = $promotion->getProducts;

        /** @var PromotionPromoCode $promoCode */
        $promoCode = $promotion->promotionPromoCodes;

        /** @var Collection $paymentTypes */
        $paymentTypes = $promotion->paymentTypes;

        /** @var Collection $memberships */
        $memberships = $promotion->memberships;

        return [
            'id' => $promotion->id,
            'name' => $promotion->name,
            'promotion_applicable_type_id' => $promotion->promotion_applicable_type_id,
            'discount_type_id' => $promotion->discount_type_id,
            'cart_wide_promotion_type_id' => $promotion->cart_wide_promotion_type_id,
            'item_wise_promotion_type_id' => $promotion->item_wise_promotion_type_id,
            'timeframe_type_id' => $promotion->timeframe_type_id,
            'percentage' => $promotion->percentage,
            'flat_amount' => $promotion->flat_amount,
            'start_date' => $promotion->start_date,
            'end_date' => $promotion->end_date,
            'start_time' => $promotion->start_time,
            'end_time' => $promotion->end_time,
            'allow_walk_in_member' => $promotion->allow_walk_in_member,
            'allow_registered_member' => $promotion->allow_registered_member,
            'allow_employee' => $promotion->allow_employee,
            'dream_price_applicable' => $promotion->dream_price_applicable,
            'is_automatic' => $promotion->is_automatic,
            'usage_type' => $promotion->usage_type,
            'is_available_in_pos' => $promotion->is_available_in_pos,
            'is_available_in_ecommerce' => $promotion->is_available_in_ecommerce,
            'is_membership_required' => $promotion->is_membership_required,

            'categories' => $categories->map(fn ($category): array => [
                'id' => $category->id,
                'name' => $category->name,
            ]),

            'brands' => $brands->map(fn ($brand): array => [
                'id' => $brand->id,
                'name' => $brand->name,
            ]),

            'tags' => $tags->map(fn ($tag): array => [
                'id' => $tag->id,
                'name' => $tag->name,
            ]),

            'productCollections' => $productCollections->map(fn ($productCollection): array => [
                'id' => $productCollection->id,
                'name' => $productCollection->name,
            ]),

            'member_groups' => $memberGroups->map(fn ($memberGroup): array => [
                'id' => $memberGroup->id,
                'name' => $memberGroup->name,
            ]),

            'employee_groups' => $employeeGroups->map(fn ($employeeGroup): array => [
                'id' => $employeeGroup->id,
                'name' => $employeeGroup->name,
            ]),

            'sale_channels' => $saleChannels->map(fn ($saleChannel): array => [
                'id' => $saleChannel->id,
                'name' => $saleChannel->name,
            ]),

            'payment_types' => $paymentTypes->map(fn ($paymentType): array => [
                'id' => $paymentType->id,
                'name' => $paymentType->name,
            ]),

            'memberships' => $memberships->map(fn ($membership): array => [
                'id' => $membership->id,
                'name' => $membership->name,
            ]),

            'month_dates' => $monthly->pluck('month_date')->toArray(),
            'week_days' => $weekly->pluck('week_day')->toArray(),
            'regular_products' => $this->getSelectedProductDetails($regularProducts),
            'buy_products' => $this->getSelectedProductDetails($buyProducts),
            'get_products' => $this->getSelectedProductDetails($getProducts),
            'tiers' => $promotionTiers->map(fn ($promotionTier): array => [
                'buy_value' => $promotionTier->buy_value,
                'get_value' => $promotionTier->get_value,
                'get_quantity' => $promotionTier->get_quantity,
                'max_value' => $promotionTier->max_value,
            ]),
            'promo_codes' => $promoCode->pluck('promo_code')->toArray(),
        ];
    }

    /**
     * @return mixed[]
     */
    public function getSelectedProductDetails(Collection $products): array
    {
        return $products->map(fn ($product): array => [
            'id' => $product->id,
            'name' => $product->name,
            'upc' => $product->upc,
            'color_name' => config('app.product_variant') ? 'N/A' : $product->color?->name ?? 'N/A',
            'size_name' => config('app.product_variant') ? 'N/A' : $product->size?->name ?? 'N/A',
            'product_variant_values' => config('app.product_variant') ? $product->productVariantValues ?? [] : [],
        ])->toArray();
    }
}

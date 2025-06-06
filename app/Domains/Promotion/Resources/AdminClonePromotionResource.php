<?php

declare(strict_types=1);

namespace App\Domains\Promotion\Resources;

use App\Models\Promotion;
use App\Models\PromotionMonthDate;
use App\Models\PromotionWeekDay;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class AdminClonePromotionResource extends JsonResource
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

        /** @var PromotionMonthDate $monthly */
        $monthly = $promotion->monthly;

        /** @var PromotionWeekDay $weekly */
        $weekly = $promotion->weekly;

        /** @var Collection $categories */
        $categories = $promotion->categories;

        /** @var Collection $brands */
        $brands = $promotion->brands;

        /** @var Collection $memberGroups */
        $memberGroups = $promotion->memberGroups;

        /** @var Collection $employeeGroups */
        $employeeGroups = $promotion->employeeGroups;

        /** @var Collection $locations */
        $locations = $promotion->locations;

        /** @var Collection $regularProducts */
        $regularProducts = $promotion->regularProducts;

        /** @var Collection $buyProducts */
        $buyProducts = $promotion->buyProducts;

        /** @var Collection $getProducts */
        $getProducts = $promotion->getProducts;

        /** @var Collection $paymentTypes */
        $paymentTypes = $promotion->paymentTypes;

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

            'categories' => $categories->map(fn ($category): array => [
                'id' => $category->id,
                'name' => $category->name,
            ]),

            'brands' => $brands->map(fn ($brand): array => [
                'id' => $brand->id,
                'name' => $brand->name,
            ]),

            'locations' => $locations->map(fn ($location): array => [
                'id' => $location->id,
                'name' => $location->name,
            ]),

            'member_groups' => $memberGroups->map(fn ($memberGroup): array => [
                'id' => $memberGroup->id,
                'name' => $memberGroup->name,
            ]),

            'employee_groups' => $employeeGroups->map(fn ($employeeGroup): array => [
                'id' => $employeeGroup->id,
                'name' => $employeeGroup->name,
            ]),

            'month_dates' => $monthly->pluck('month_date')->toArray(),
            'week_days' => $weekly->pluck('week_day')->toArray(),
            'regular_products' => $regularProducts->map(fn ($regularProduct): array => [
                'id' => $regularProduct->id,
                'name' => $regularProduct->name,
            ]),
            'buy_products' => $buyProducts->map(fn ($buyProduct): array => [
                'id' => $buyProduct->id,
                'name' => $buyProduct->name,
            ]),
            'get_products' => $getProducts->map(fn ($getProduct): array => [
                'id' => $getProduct->id,
                'name' => $getProduct->name,
            ]),
            'tiers' => $promotionTiers->map(fn ($promotionTier): array => [
                'buy_value' => $promotionTier->buy_value,
                'get_value' => $promotionTier->get_value,
                'get_quantity' => $promotionTier->get_quantity,
            ]),

            'payment_types' => $paymentTypes->map(fn ($paymentType): array => [
                'id' => $paymentType->id,
                'name' => $paymentType->name,
            ]),
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Domains\DreamPrice\Services;

use App\CommonFunctions;
use App\Domains\Order\Services\CheckOrderEcommerceDetailsService;
use App\Models\DreamPrice;
use App\Models\DreamPriceProduct;
use App\Models\Member;
use App\Models\Product;
use Carbon\Carbon;

class DreamPriceEcommerceCheckService
{
    public function checkForApplicability(
        CheckOrderEcommerceDetailsService $checkOrderEcommerceDetailsService,
        DreamPrice $dreamPrice,
        array $cartItem
    ): void {
        /** @var Product $product */
        $product = $checkOrderEcommerceDetailsService->products->firstWhere('id', $cartItem['id']);

        $this->checkMember($checkOrderEcommerceDetailsService, $dreamPrice);

        $this->checkDreamPriceDateRange($checkOrderEcommerceDetailsService, $dreamPrice);

        $this->checkDreamPriceAmount($checkOrderEcommerceDetailsService, $dreamPrice, $cartItem, $product);

        $this->checkDreamPriceStores($checkOrderEcommerceDetailsService, $dreamPrice);

        $this->checkDreamPriceIsActive($checkOrderEcommerceDetailsService, $dreamPrice);
    }

    public function getDiscountFor(array $cartItem): float
    {
        $itemPrice = $cartItem['price'] ?? $cartItem['open_price'];

        $discountAmount = CommonFunctions::numberFormat(
            (float) (($itemPrice - $cartItem['dream_price_amount']) * $cartItem['quantity'])
        );

        if ($discountAmount < 0) {
            return 0.00;
        }

        return $discountAmount;
    }

    public function checkDreamPriceDateRange(
        CheckOrderEcommerceDetailsService $checkOrderEcommerceDetailsService,
        DreamPrice $dreamPrice
    ): void {
        /** @var Carbon $happenedAtFormat */
        $happenedAtFormat = $checkOrderEcommerceDetailsService->orderECommerceData->happened_at
            ? Carbon::createFromFormat(
                'Y-m-d H:i:s',
                $checkOrderEcommerceDetailsService->orderECommerceData->happened_at
            )
            : Carbon::now();
        $happenedAt = $happenedAtFormat->format('Y-m-d');
        if ($dreamPrice->start_date > $happenedAt || $dreamPrice->end_date < $happenedAt) {
            $saleMismatchMessage = 'Specified dream price is available between ' . $dreamPrice->start_date . ' and ' . $dreamPrice->end_date . '. only. But the specified sale date is ' . $happenedAt . '.';
            CommonFunctions::addMismatchOrAbort(
                $checkOrderEcommerceDetailsService->orderMismatches,
                $saleMismatchMessage
            );
        }
    }

    public function checkDreamPriceAmount(
        CheckOrderEcommerceDetailsService $checkOrderEcommerceDetailsService,
        DreamPrice $dreamPrice,
        array $cartItem,
        Product $product,
    ): void {
        $dreamPriceProduct = $dreamPrice->dreamPriceProducts->firstWhere('product_id', $cartItem['id']);
        $discountAmount = $this->getDiscountFor($cartItem);

        if (! $dreamPriceProduct instanceof DreamPriceProduct) {
            $saleMismatchMessage = 'The dream price is not available for the product ' . $product->name . ' but is specified in the request as ' . $cartItem['dream_price_amount'] . '.';
            CommonFunctions::addMismatchOrAbort(
                $checkOrderEcommerceDetailsService->orderMismatches,
                $saleMismatchMessage
            );

            return;
        }

        if ((float) $dreamPriceProduct['price'] !== (float) $cartItem['dream_price_amount']) {
            $saleMismatchMessage = 'The dream price of the product ' . $product->name . ' is ' . $dreamPriceProduct['price'] . ' but the specified price is ' . $cartItem['dream_price_amount'] . '.';
            CommonFunctions::addMismatchOrAbort(
                $checkOrderEcommerceDetailsService->orderMismatches,
                $saleMismatchMessage
            );
        }

        if ((float) $cartItem['dream_price_discount_amount'] !== $discountAmount) {
            $saleMismatchMessage = 'The dream price discount amount of the product ' . $product->name . ' is ' . $discountAmount . ' but the specified dream price discount amount is ' . $cartItem['dream_price_discount_amount'] . '.';
            CommonFunctions::addMismatchOrAbort(
                $checkOrderEcommerceDetailsService->orderMismatches,
                $saleMismatchMessage
            );
        }
    }

    public function checkDreamPriceStores(
        CheckOrderEcommerceDetailsService $checkOrderEcommerceDetailsService,
        DreamPrice $dreamPrice,
    ): void {
        if ($dreamPrice->locations->isEmpty()) {
            return;
        }

        if ($dreamPrice->locations->firstWhere('id', $checkOrderEcommerceDetailsService->location->id)) {
            return;
        }

        $saleMismatchMessage = 'The dream price is not available for the location ' . $checkOrderEcommerceDetailsService->location->name;
        CommonFunctions::addMismatchOrAbort($checkOrderEcommerceDetailsService->orderMismatches, $saleMismatchMessage);
    }

    public function checkMember(
        CheckOrderEcommerceDetailsService $checkOrderEcommerceDetailsService,
        DreamPrice $dreamPrice
    ): void {
        if (! $dreamPrice->allow_registered_member && $this->isMemberAttached($checkOrderEcommerceDetailsService)) {
            $saleMismatchMessage = 'Specified dream price is not allowed for the registered members.';
            CommonFunctions::addMismatchOrAbort(
                $checkOrderEcommerceDetailsService->orderMismatches,
                $saleMismatchMessage
            );

            return;
        }

        if (! $this->isMemberAttached($checkOrderEcommerceDetailsService)) {
            return;
        }

        if ($dreamPrice->memberGroups->isEmpty()) {
            return;
        }

        if (
            $checkOrderEcommerceDetailsService->member instanceof Member
            && $checkOrderEcommerceDetailsService->member->memberGroupMembers->whereIn(
                'member_group_id',
                $dreamPrice->memberGroups->pluck('id')
            )->isNotEmpty()
        ) {
            return;
        }

        $saleMismatchMessage = 'Member is not valid for the specified dream price.';
        CommonFunctions::addMismatchOrAbort($checkOrderEcommerceDetailsService->orderMismatches, $saleMismatchMessage);
    }

    public function checkWalkInMember(
        CheckOrderEcommerceDetailsService $checkOrderEcommerceDetailsService,
        DreamPrice $dreamPrice
    ): void {
        if ($dreamPrice->allow_walk_in_member) {
            return;
        }

        if ($this->isMemberAttached($checkOrderEcommerceDetailsService)) {
            return;
        }

        $saleMismatchMessage = 'Specified dream price is not allowed for the walk in member.';
        CommonFunctions::addMismatchOrAbort($checkOrderEcommerceDetailsService->orderMismatches, $saleMismatchMessage);
    }

    public function isMemberAttached(CheckOrderEcommerceDetailsService $checkOrderEcommerceDetailsService): bool
    {
        if ($checkOrderEcommerceDetailsService->isMemberAttached()) {
            return true;
        }

        return $checkOrderEcommerceDetailsService->hasMemberDetails();
    }

    public function checkDreamPriceIsActive(
        CheckOrderEcommerceDetailsService $checkOrderEcommerceDetailsService,
        DreamPrice $dreamPrice
    ): void {
        if (false === $dreamPrice->status) {
            $saleMismatchMessage = 'Specified dream price is inactive.';
            CommonFunctions::addMismatchOrAbort(
                $checkOrderEcommerceDetailsService->orderMismatches,
                $saleMismatchMessage
            );
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Domains\Order\Services;

use App\CommonFunctions;
use App\Domains\ComplimentaryItemReason\ComplimentaryItemReasonQueries;
use App\Domains\ComplimentaryItemReason\Services\ComplimentaryItemService;
use App\Domains\ComplimentaryItemReason\Services\OrderComplimentaryItemService;
use App\Domains\OrderItem\Services\OrderItemPriceOverrideService;
use App\Models\ComplimentaryItemReason;
use App\Models\Product;
use Illuminate\Support\Collection;

class OrderDiscountService
{
    public CheckOrderDetailsService $checkOrderDetailsService;

    public Collection $complimentaryItemReasons;

    public Collection $storeManagers;

    public function setDetails(CheckOrderDetailsService $checkOrderDetailsService): void
    {
        $this->checkOrderDetailsService = $checkOrderDetailsService;
        $this->complimentaryItemReasons = $this->getComplimentaryItemReasons();
    }

    public function getComplimentaryItemReasons(): Collection
    {
        if ($this->getComplimentaryItemReasonIds() === []) {
            return collect([]);
        }

        $complimentaryItemReasonQueries = resolve(ComplimentaryItemReasonQueries::class);

        return $complimentaryItemReasonQueries->getByIdsAndCompanyId(
            $this->getComplimentaryItemReasonIds(),
            $this->checkOrderDetailsService->companyId,
        );
    }

    /**
     * @return mixed[]
     */
    public function getComplimentaryItemReasonIds(): array
    {
        return $this->checkOrderDetailsService->orderItems->pluck('complimentary_item_reason_id')
            ->unique()
            ->filter()
            ->toArray();
    }

    public function checkPriceOverrideForCartDetails(float $cartSubtotal): void
    {
        $cartSubtotal = $this->checkOrderDetailsService->getCartSubtotalByDiscountApplicableType($cartSubtotal);

        $orderPriceOverrideService = resolve(OrderPriceOverrideService::class);
        $orderPriceOverrideService->checkForApplicability($this->checkOrderDetailsService, $cartSubtotal);
    }

    public function checkItemWisePromotionDetails(Product $product, array $orderItem): void
    {
        if ($this->checkOrderDetailsService->hasComplimentaryItem($orderItem)) {
            /** @var ComplimentaryItemReason $complimentaryItemReason */
            $complimentaryItemReason = $this->complimentaryItemReasons->firstWhere(
                'id',
                '===',
                (int) $orderItem['complimentary_item_reason_id']
            );

            $complimentaryItemService = resolve(OrderComplimentaryItemService::class);
            $complimentaryItemService->checkForApplicability(
                $this->checkOrderDetailsService,
                $complimentaryItemReason,
                $orderItem,
            );

            return;
        }

        if ($this->checkOrderDetailsService->hasPriceOverride($orderItem)) {
            $orderItemPriceOverrideService = resolve(OrderItemPriceOverrideService::class);
            $orderItemPriceOverrideService->checkForApplicability($this->checkOrderDetailsService, $orderItem);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function getCartDiscountAmountFor(float $subtotal): array
    {
        $cartDiscount = [
            'total_discount' => 0,
            'price_override_discount' => 0,
        ];

        if ($this->checkOrderDetailsService->hasPriceOverrideForCart()) {
            $subtotalForDiscount = $this->checkOrderDetailsService->getCartSubtotalByDiscountApplicableType($subtotal);

            $priceOverrideDiscount = $this->getDiscountAmount(
                $subtotalForDiscount,
                (float) $this->checkOrderDetailsService->orderData->cart_price_override_amount
            );

            $cartDiscount['price_override_discount'] = $priceOverrideDiscount;
            $cartDiscount['total_discount'] += $priceOverrideDiscount;
            $subtotal -= $priceOverrideDiscount;
        }

        return $cartDiscount;
    }

    public function getItemCartDiscountAmount(float $cartSubtotal, float $itemSubtotal): float
    {
        $cartDiscount = $this->getCartDiscountAmountFor($cartSubtotal);

        if ($cartSubtotal > 0) {
            return CommonFunctions::numberFormat($itemSubtotal * $cartDiscount['total_discount'] / $cartSubtotal);
        }

        return 0.00;
    }

    public function getTotalItemDiscountAmount(): float|int
    {
        $totalItemDiscount = 0;
        foreach ($this->checkOrderDetailsService->orderItems as $orderItem) {
            $itemDiscounts = $this->getItemDiscountAmountFor($orderItem);
            $totalItemDiscount += $itemDiscounts['total_discount'];
        }

        return $totalItemDiscount;
    }

    /**
     * @return array<string, mixed>
     */
    public function getItemDiscountAmountFor(array $orderItem): array
    {
        $discounts = [
            'complimentary_item_discount' => 0.00,
            'price_override_discount' => 0.00,
            'total_discount' => 0.00,
        ];

        if ($this->checkOrderDetailsService->hasComplimentaryItem($orderItem)) {
            if (! array_key_exists('complimentary_item_discount', $orderItem)) {
                abort(412, 'Complimentary item discount amount not specified.');
            }

            $complimentaryItemService = resolve(ComplimentaryItemService::class);
            $itemSubtotal = $this->checkOrderDetailsService->getItemSubtotal($orderItem);
            $discountAmount = $complimentaryItemService->getItemDiscountAmount($itemSubtotal);
            $cartComplimentaryDiscountAmount = (float) $orderItem['complimentary_item_discount'];
            $discounts['complimentary_item_discount'] = $cartComplimentaryDiscountAmount;
            $discounts['total_discount'] += $discountAmount;

            if (! CommonFunctions::compareFloatNumbers($cartComplimentaryDiscountAmount, $discountAmount)) {
                abort(
                    412,
                    'Provided complimentary item discount does not match with calculated amount.\nExpected: ' . CommonFunctions::numberFormat(
                        $discountAmount
                    ) . '\\n' .
                        'Received: ' . CommonFunctions::numberFormat($discounts['complimentary_item_discount'])
                );
            }

            return $discounts;
        }

        if ($this->checkOrderDetailsService->hasPriceOverride($orderItem)) {
            $itemPrice = $orderItem['price'] ?? $orderItem['open_price'];

            $orderItemPriceOverrideService = resolve(OrderItemPriceOverrideService::class);
            $allowedPriceOverrideDiscountAmount = $orderItemPriceOverrideService->getItemDiscountAmount($orderItem);

            $discounts['price_override_discount'] = $allowedPriceOverrideDiscountAmount;
            $discounts['total_discount'] += $allowedPriceOverrideDiscountAmount;
            $orderItem['price'] = $itemPrice;
        }

        return $discounts;
    }

    public function getItemDiscountAmount(array $orderItem): float
    {
        $itemPrice = $orderItem['price'] ?? $orderItem['open_price'];

        $discountAmount = CommonFunctions::numberFormat(
            ($itemPrice - (float) $orderItem['price_override_amount']) * $orderItem['quantity']
        );

        if ($discountAmount < 0) {
            return 0.00;
        }

        return $discountAmount;
    }

    public function applyDreamPriceAndItemPromotionOn(array $orderItem): float
    {
        return $this->checkOrderDetailsService->getItemSubtotal($orderItem);
    }

    private function getDiscountAmount(float $cartSubtotal, float $priceOverrideAmount): float
    {
        $discountAmount = CommonFunctions::numberFormat($cartSubtotal - $priceOverrideAmount);

        if ($discountAmount < 0) {
            return 0.00;
        }

        return $discountAmount;
    }
}

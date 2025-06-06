<?php

declare(strict_types=1);

namespace App\Domains\Order\Services;

use App\CommonFunctions;
use App\Domains\City\CityQueries;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Country\CountryQueries;
use App\Domains\Inventory\InventoryQueries;
use App\Domains\Inventory\Services\OrderInventoryService;
use App\Domains\Member\MemberQueries;
use App\Domains\Order\Enums\OrderChannels;
use App\Domains\Order\Enums\OrderStatus;
use App\Domains\Order\OrderQueries;
use App\Domains\OrderAddress\Enums\OrderAddressesType;
use App\Domains\OrderAddress\OrderAddressQueries;
use App\Domains\OrderChannelReference\OrderChannelReferenceQueries;
use App\Domains\OrderDiscount\OrderDiscountQueries;
use App\Domains\OrderItem\OrderItemQueries;
use App\Domains\OrderItemDiscount\OrderItemDiscountQueries;
use App\Domains\OrderPayment\OrderPaymentQueries;
use App\Domains\PosMismatch\PosMismatchQueries;
use App\Domains\Product\Enums\ProductTypes;
use App\Domains\Product\Services\ProductService;
use App\Domains\Sequence\Enums\SequenceTypes;
use App\Domains\Sequence\SequenceQueries;
use App\Domains\State\StateQueries;
use App\Domains\Voucher\VoucherQueries;
use App\Domains\VoucherTransaction\Enums\VoucherTransactionActionTypes;
use App\Domains\VoucherTransaction\VoucherTransactionQueries;
use App\Models\Location;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Voucher;
use Carbon\Carbon;
use Illuminate\Support\Str;

class SaveOrderEcommerceDetailsService
{
    public function saveDetails(
        CheckOrderEcommerceDetailsService $checkOrderEcommerceDetailsService,
        int $locationId,
        ?int $memberId,
    ): ?Order {
        if (! $checkOrderEcommerceDetailsService->hasOrderItems()) {
            return null;
        }

        $sequenceQueries = resolve(SequenceQueries::class);

        $sequenceNumber = $sequenceQueries->addNew($locationId, SequenceTypes::OR->value)->number;
        $locationCode = Str::of($checkOrderEcommerceDetailsService->location->code)->substr(0, 2)->upper()->value();
        $receiptNumber = SequenceTypes::OR->name . $locationCode . $sequenceNumber;

        $orderQueries = resolve(OrderQueries::class);
        $orderChannelReferenceQueries = resolve(OrderChannelReferenceQueries::class);

        $location = $checkOrderEcommerceDetailsService->location;
        $digitalInvoiceNumber = $this->getSequenceNumber($location);

        $happenedAt = $checkOrderEcommerceDetailsService->orderECommerceData->happened_at ?: Carbon::now()->format(
            'Y-m-d H:i:s'
        );

        $channelId = $checkOrderEcommerceDetailsService->orderECommerceData->channel ? OrderChannels::getValueByCaseName(
            $checkOrderEcommerceDetailsService->orderECommerceData->channel
        ) : null;
        $channelId ??= OrderChannels::E_COMMERCE->value;

        $order = $orderQueries->addNewForEcommerce(
            $checkOrderEcommerceDetailsService->orderECommerceData,
            $locationId,
            $digitalInvoiceNumber,
            $receiptNumber,
            $happenedAt,
            $channelId,
            $memberId,
            $checkOrderEcommerceDetailsService->saleChannel->id,
        );

        if ($checkOrderEcommerceDetailsService->orderECommerceData->external_order_id) {
            $orderChannelReferenceQueries->addNew(
                $order->id,
                $checkOrderEcommerceDetailsService->saleChannel->id,
                $checkOrderEcommerceDetailsService->orderECommerceData->external_order_id
            );
        }

        $orderAddressQueries = resolve(OrderAddressQueries::class);
        if ([] !== $checkOrderEcommerceDetailsService->orderECommerceData->shipping_address) {
            $orderAddressQueries->addNewAddress(
                (array) $checkOrderEcommerceDetailsService->orderECommerceData->shipping_address,
                $this->getAddressIds(
                    (array) $checkOrderEcommerceDetailsService->orderECommerceData->shipping_address
                ),
                $order->getKey(),
                OrderAddressesType::SHIPPING_ADDRESS->value,
            );
        }

        if ([] !== $checkOrderEcommerceDetailsService->orderECommerceData->billing_address) {
            $orderAddressQueries->addNewAddress(
                (array) $checkOrderEcommerceDetailsService->orderECommerceData->billing_address,
                $this->getAddressIds(
                    (array) $checkOrderEcommerceDetailsService->orderECommerceData->billing_address
                ),
                $order->getKey(),
                OrderAddressesType::BILLING_ADDRESS->value,
            );
        }

        $cartSubtotal = $checkOrderEcommerceDetailsService->getCartSubtotal();
        $cartSubtotal -= $checkOrderEcommerceDetailsService->orderEcommerceDiscountService->getTotalItemDiscountAmount();
        $cartDiscount = $checkOrderEcommerceDetailsService->orderEcommerceDiscountService->getOrderDiscountAmountFor(
            $cartSubtotal
        );

        $cartDiscountAmount = $cartDiscount;
        $cartDiscountAmountCalculated = $cartDiscount['total_discount'];
        $cartDiscountAmount = $cartDiscountAmountCalculated;
        $cartSubtotalAfterDiscount = $cartSubtotal - $cartDiscountAmount;

        $totalTax = (float) $checkOrderEcommerceDetailsService->orderECommerceData->total_tax_amount;

        if (null === $checkOrderEcommerceDetailsService->orderECommerceData->total_tax_amount) {
            $totalTax = $cartSubtotalAfterDiscount >= 0 ? CommonFunctions::numberFormat(
                $cartSubtotalAfterDiscount * $checkOrderEcommerceDetailsService->location->sales_tax_percentage / 100
            ) : 0.0;
        }

        $productService = resolve(ProductService::class);
        $vendorCommissionPercentages = $productService->getVendorCommissionPercentages(
            $checkOrderEcommerceDetailsService->products
        );

        foreach ($checkOrderEcommerceDetailsService->orderItems as $orderItem) {
            $orderItem['vendor_commission_percentage'] = $vendorCommissionPercentages[$orderItem['id']];

            $itemDiscountAmount = array_key_exists(
                'item_discount_amount',
                $orderItem
            ) ? (float) $orderItem['item_discount_amount'] : 0.0;

            $itemDiscounts = $checkOrderEcommerceDetailsService->orderEcommerceDiscountService->getItemDiscountAmountFor(
                $orderItem
            );
            $itemTotalDiscount = $itemDiscounts['total_discount'] + $itemDiscountAmount;

            $itemSubTotal = $checkOrderEcommerceDetailsService->getItemSubtotal($orderItem);
            $itemSubTotalAfterDiscount = $itemSubTotal - $itemTotalDiscount;

            $itemCartDiscount = $checkOrderEcommerceDetailsService->orderEcommerceDiscountService->getItemOrderDiscountAmount(
                $cartSubtotal,
                $itemSubTotalAfterDiscount
            );

            $itemSubTotalAfterDiscount -= $itemCartDiscount;
            $itemTax = $cartSubtotalAfterDiscount > 0 ? CommonFunctions::numberFormat(
                $itemSubTotalAfterDiscount * $totalTax / $cartSubtotalAfterDiscount
            ) : 0.0;

            $orderItemQueries = resolve(OrderItemQueries::class);
            $orderItemData = $orderItemQueries->addNewForEcommerce(
                $order,
                $orderItem,
                (int) $orderItem['id'],
                $itemTax,
                $itemCartDiscount,
                $itemTotalDiscount,
            );

            $this->saveItemDiscounts(
                $checkOrderEcommerceDetailsService,
                $orderItem,
                $orderItemData->id,
                $itemDiscounts
            );

            if ($checkOrderEcommerceDetailsService->products->firstWhere(
                'id',
                (int) $orderItem['id']
            )->is_non_inventory) {
                continue;
            }

            if ($checkOrderEcommerceDetailsService->saleChannel->getInventoryDeductOrderStatus() === OrderStatus::PLACED) {
                $this->updateInventory(
                    $orderItemData,
                    $order,
                    $orderItem,
                    checkOrderEcommerceDetailsService: $checkOrderEcommerceDetailsService
                );
            }
        }

        $this->saveVoucherDiscount($checkOrderEcommerceDetailsService, $order->id);

        $roundOffAmount = $checkOrderEcommerceDetailsService->orderECommerceData->order_round_off_amount;

        $orderQueries->updateTotals($order, $roundOffAmount);

        $this->saveCartWideDiscount(
            $checkOrderEcommerceDetailsService,
            $cartDiscount['cart_wide_discount'],
            $order->id
        );

        $useEcommerceLoyaltyPointsService = resolve(UseEcommerceLoyaltyPointsService::class);
        $useEcommerceLoyaltyPointsService->saveCartWideLoyaltyPointsDiscount(
            $checkOrderEcommerceDetailsService,
            $order
        );

        $this->savePayments($checkOrderEcommerceDetailsService, $order, $locationId);

        $checkOrderEcommerceDetailsService->generateVoucherECommerceService->saveVouchers($order);

        if ($order->getMemberId() !== null) {
            $this->updateMemberSpentTillNowAndLastPurchaseData(
                $order->getTotalAmountPaid(),
                $order->getMemberId(),
                $checkOrderEcommerceDetailsService->companyId
            );

            $this->updateMemberSalesQuantity(
                $checkOrderEcommerceDetailsService->orderItems->sum('quantity'),
                $order->getMemberId(),
            );
        }

        $order = $orderQueries->loadRelationsForApi($order);
        $checkOrderEcommerceDetailsService->generateEcommerceLoyaltyPointsService->saveGenerateLoyaltyPoints(
            $checkOrderEcommerceDetailsService,
            $order,
            $memberId
        );

        $this->saveOrderMismatches($order, $checkOrderEcommerceDetailsService);

        return $orderQueries->loadRelationsForApi($order);
    }

    public function saveOrderMismatches(
        Order $order,
        CheckOrderEcommerceDetailsService $checkOrderEcommerceDetailsService
    ): void {
        $posMismatchQueries = resolve(PosMismatchQueries::class);
        foreach ($checkOrderEcommerceDetailsService->orderMismatches as $orderMismatch) {
            $posMismatchQueries->addNew($order, $orderMismatch);
        }
    }

    public function saveVoucherDiscount(
        CheckOrderEcommerceDetailsService $checkOrderEcommerceDetailsService,
        int $orderId
    ): void {
        if (! $checkOrderEcommerceDetailsService->orderECommerceData->voucher_number) {
            return;
        }

        if ((float) $checkOrderEcommerceDetailsService->orderECommerceData->voucher_discount_amount <= 0.000) {
            return;
        }

        /** @var Voucher $voucher */
        $voucher = $checkOrderEcommerceDetailsService->orderEcommerceDiscountService->voucher;

        $happenedAtFormat = $checkOrderEcommerceDetailsService->getHappenedAtFormat();

        $voucherQueries = resolve(VoucherQueries::class);
        $voucherQueries->markAsUsed($voucher, $happenedAtFormat->format('Y-m-d H:i:s'));

        $voucherTransactionQueries = resolve(VoucherTransactionQueries::class);
        $voucherTransactionQueries->addNew(
            $voucher->id,
            VoucherTransactionActionTypes::USED->value,
            $happenedAtFormat->format('Y-m-d H:i:s'),
            null,
            $checkOrderEcommerceDetailsService->location->getKey(),
            $orderId
        );

        $discountTypeClass = ModelMapping::VOUCHER->name;

        $orderDiscountQueries = resolve(OrderDiscountQueries::class);
        $orderDiscountQueries->addNew(
            $orderId,
            $voucher->id,
            $discountTypeClass,
            (float) $checkOrderEcommerceDetailsService->orderECommerceData->voucher_discount_amount,
        );
    }

    private function getAddressIds(array $addresses): array
    {
        $countryId = null;
        $stateId = null;
        $cityId = null;

        $countryQueries = resolve(CountryQueries::class);
        $stateQueries = resolve(StateQueries::class);
        $cityQueries = resolve(CityQueries::class);

        if ([] !== $addresses) {
            $countryId = $addresses['country_id'] ?? null;
            $stateId = $addresses['state_id'] ?? null;
            $cityId = $addresses['city_id'] ?? null;

            if (null !== $countryId && ! $countryQueries->existsById((int) $countryId)) {
                $countryId = null;
            }

            if (null !== $stateId && ! $stateQueries->existsById((int) $stateId)) {
                $stateId = null;
            }

            if (null !== $cityId && ! $cityQueries->existsById((int) $cityId)) {
                $cityId = null;
            }

            if (null === $countryId && ! empty($addresses['country_name'])) {
                $countryId = $countryQueries->checkNameExists($addresses['country_name']);
            }

            if (null === $countryId && ! empty($addresses['country_code'])) {
                $countryId = $countryQueries->checkCodeExists($addresses['country_code']);
            }

            if (null === $stateId && ! empty($addresses['state_name'])) {
                $stateId = $stateQueries->checkNameExists($addresses['state_name']);
            }

            if (null === $cityId && ! empty($addresses['city_name'])) {
                $cityId = $cityQueries->checkNameExists($addresses['city_name']);
            }
        }

        return [
            'country_id' => $countryId,
            'state_id' => $stateId,
            'city_id' => $cityId,
            'country_name' => null === $countryId ? $addresses['country_name'] ?? null : null,
            'state_name' => null === $stateId ? $addresses['state_name'] ?? null : null,
            'city_name' => null === $cityId ? $addresses['city_name'] ?? null : null,
        ];
    }

    public function savePayments(
        CheckOrderEcommerceDetailsService $checkOrderEcommerceDetailsService,
        Order $order,
        int $locationId,
    ): void {
        $orderECommerceData = $checkOrderEcommerceDetailsService->orderECommerceData;

        if (null === $orderECommerceData->payment_type_id && null === $orderECommerceData->payment_amount) {
            return;
        }

        $orderPaymentQueries = resolve(OrderPaymentQueries::class);

        $payment = [
            'type_id' => $orderECommerceData->payment_type_id,
            'amount' => $orderECommerceData->payment_amount,
            'notes' => $orderECommerceData->payment_notes,
        ];

        $orderPaymentQueries->addNewForEcommerce($order, $payment, $locationId);
    }

    public function updateMemberSpentTillNowAndLastPurchaseData(
        float $totalAmountPaid,
        int $memberId,
        int $companyId
    ): void {
        $memberQueries = resolve(MemberQueries::class);

        $memberQueries->updateSpentTillNow($totalAmountPaid, $memberId);
        $memberQueries->updateLastPurchaseDate($companyId, $memberId);
    }

    public function updateMemberSalesQuantity(float $totalSaleQty, int $memberId): void
    {
        $memberQueries = resolve(MemberQueries::class);
        $memberQueries->updateSalesQuantity($totalSaleQty, $memberId);
    }

    public function updateInventory(
        OrderItem $orderItem,
        Order $order,
        array $item,
        CheckOrderEcommerceDetailsService $checkOrderEcommerceDetailsService
    ): void {
        $orderInventoryService = resolve(OrderInventoryService::class);
        $product = $checkOrderEcommerceDetailsService->products->firstWhere('id', $item['id']);

        if ($product->type_id === ProductTypes::ASSEMBLY_PRODUCT->value) {
            return;
        }

        $inventoryQueries = resolve(InventoryQueries::class);
        $inventory = $inventoryQueries->fetchOrCreate(
            $checkOrderEcommerceDetailsService->location->getKey(),
            (int) $item['id']
        );

        $productBoxUnits = $orderItem->product_box_units > 0 ? $orderItem->getBoxProductUnits() : 1;
        $itemQuantity = CommonFunctions::numberFormat($item['quantity'] * $productBoxUnits);

        if (! $product->has_batch) {
            $orderInventoryService->updateInventoryUnits(
                $inventory,
                $product,
                $checkOrderEcommerceDetailsService->location->getKey(),
                $orderItem,
                $checkOrderEcommerceDetailsService->saleChannel,
                (float) ('-' . $itemQuantity),
                $order->getHappenedAt() ?? Carbon::now()->format('Y-m-d H:i:s'),
                null
            );
        }

        if ($inventory->stock < 0 || ($inventory->stock - $itemQuantity < 0)) {
            // Add Notification For Negative Inventory
        }

        $inventoryQueries->decreaseStock($inventory, $itemQuantity);
    }

    public function getSequenceNumber(Location $location): string
    {
        $sequenceQueries = resolve(SequenceQueries::class);
        $number = $sequenceQueries->addNew($location->id, SequenceTypes::OS->value)->number;

        return $location->code . '-' . SequenceTypes::OS->name . '-' . $number;
    }

    public function saveItemDiscounts(
        CheckOrderEcommerceDetailsService $checkOrderEcommerceDetailsService,
        array $cartItem,
        int $orderItemId,
        array $itemDiscounts
    ): void {
        if (0.00 === $itemDiscounts['total_discount']) {
            return;
        }

        if (
            $checkOrderEcommerceDetailsService->hasDreamPrice($cartItem)
            && 0.00 !== $itemDiscounts['dream_price_discount']
        ) {
            $orderItemDiscountQueries = resolve(OrderItemDiscountQueries::class);
            $orderItemDiscountQueries->addNew(
                $orderItemId,
                (int) $cartItem['dream_price_id'],
                ModelMapping::DREAM_PRICE->name,
                $itemDiscounts['dream_price_discount']
            );
        }

        if (! array_key_exists('promotion_id', $cartItem)) {
            return;
        }

        if (! $cartItem['promotion_id']) {
            return;
        }

        if (0.00 === $itemDiscounts['item_wise_discount']) {
            return;
        }

        $orderItemDiscountQueries = resolve(OrderItemDiscountQueries::class);
        $orderItemDiscountQueries->addNew(
            $orderItemId,
            (int) $cartItem['promotion_id'],
            ModelMapping::PROMOTION->name,
            $itemDiscounts['item_wise_discount']
        );
    }

    public function saveCartWideDiscount(
        CheckOrderEcommerceDetailsService $checkOrderEcommerceDetailsService,
        float $totalCartDiscount,
        int $orderId
    ): void {
        if ($checkOrderEcommerceDetailsService->orderECommerceData->cart_promotion_id && 0.00 !== $totalCartDiscount) {
            $discountTypeClass = ModelMapping::PROMOTION->name;

            $orderDiscountQueries = resolve(OrderDiscountQueries::class);
            $orderDiscountQueries->addNew(
                $orderId,
                $checkOrderEcommerceDetailsService->orderECommerceData->cart_promotion_id,
                $discountTypeClass,
                $totalCartDiscount,
                null,
            );
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Domains\Order\Services;

use App\CommonFunctions;
use App\Domains\Inventory\InventoryQueries;
use App\Domains\Inventory\Services\OrderInventoryService;
use App\Domains\Member\MemberQueries;
use App\Domains\Order\OrderQueries;
use App\Domains\OrderItem\OrderItemQueries;
use App\Domains\OrderItemAssemblyChildProduct\OrderItemAssemblyChildProductQueries;
use App\Domains\OrderPayment\OrderPaymentQueries;
use App\Domains\Product\Enums\ProductTypes;
use App\Domains\Product\Services\ProductService;
use App\Domains\ReservedStock\Services\OrderReservedStockService;
use App\Domains\Sequence\Enums\SequenceTypes;
use App\Domains\Sequence\SequenceQueries;
use App\Models\BoxProduct;
use App\Models\Location;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderReturn;
use App\Models\Product;
use App\Models\StoreManager;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SaveOrderDetailsService
{
    public function saveDetails(
        StoreManager $storeManager,
        CheckOrderDetailsService $checkOrderDetailsService,
        int $locationId,
        ?int $memberId,
        ?OrderReturn $orderReturn = null
    ): ?Order {
        if (! $checkOrderDetailsService->hasOrderItems()) {
            return null;
        }

        $sequenceQueries = resolve(SequenceQueries::class);
        $orderReservedStockService = resolve(OrderReservedStockService::class);

        $sequenceNumber = $sequenceQueries->addNew($locationId, SequenceTypes::OR->value)->number;
        $locationCode = Str::of($checkOrderDetailsService->location->code)->substr(0, 2)->upper()->value();
        $receiptNumber = SequenceTypes::OR->name . $locationCode . $sequenceNumber;

        $location = $checkOrderDetailsService->location;
        $digitalInvoiceNumber = $this->getSequenceNumber($location);

        $orderQueries = resolve(OrderQueries::class);

        $order = $orderQueries->addNew(
            $checkOrderDetailsService->orderData,
            $storeManager->getKey(),
            $locationId,
            $digitalInvoiceNumber,
            $receiptNumber,
            $memberId,
            $orderReturn?->getKey()
        );

        $cartSubtotal = $checkOrderDetailsService->getCartSubtotal();
        $cartSubtotal -= $checkOrderDetailsService->orderDiscountService->getTotalItemDiscountAmount();

        $cartDiscount = $checkOrderDetailsService->orderDiscountService->getCartDiscountAmountFor($cartSubtotal);

        $isCartDiscountAmountSpecified = null !== $checkOrderDetailsService->orderData->cart_discount_amount;
        $cartDiscountAmountSpecified = (float) $checkOrderDetailsService->orderData->cart_discount_amount;
        $cartDiscountAmountCalculated = $cartDiscount['total_discount'];

        $cartDiscountAmount = $isCartDiscountAmountSpecified
            ? $cartDiscountAmountSpecified
            : $cartDiscountAmountCalculated;

        $cartSubtotalAfterDiscount = $cartSubtotal - $cartDiscountAmount;

        $totalTax = (float) $checkOrderDetailsService->orderData->total_tax_amount;

        if (null === $checkOrderDetailsService->orderData->total_tax_amount) {
            $totalTax = $checkOrderDetailsService->orderTaxService->getTotalTaxAmountFor($cartSubtotalAfterDiscount);
        }

        $productService = resolve(ProductService::class);
        $vendorCommissionPercentages = $productService->getVendorCommissionPercentages(
            $checkOrderDetailsService->products
        );

        foreach ($checkOrderDetailsService->orderItems as $orderItem) {
            $orderItem['vendor_commission_percentage'] = $vendorCommissionPercentages[$orderItem['id']];

            $itemDiscounts = $checkOrderDetailsService->orderDiscountService->getItemDiscountAmountFor($orderItem);
            $itemTotalDiscount = $itemDiscounts['total_discount'];

            $itemSubTotal = $checkOrderDetailsService->getItemSubtotal($orderItem);
            $itemSubTotalAfterDiscount = $itemSubTotal - $itemTotalDiscount;

            $itemCartDiscount = $checkOrderDetailsService->orderDiscountService->getItemCartDiscountAmount(
                $cartSubtotal,
                $itemSubTotalAfterDiscount
            );

            $itemSubTotalAfterDiscount -= $itemCartDiscount;

            $itemTax = $checkOrderDetailsService->orderTaxService->getItemTaxAmountFor(
                $itemSubTotalAfterDiscount,
                $totalTax,
                $cartSubtotalAfterDiscount
            );

            $boxProduct = null;

            $product = $checkOrderDetailsService->products->firstWhere('id', $orderItem['id']);

            if ($product instanceof Product) {
                /** @var BoxProduct $boxProduct */
                $boxProductId = $orderItem['box_product_id'] ?? $orderItem['product_bundle_id'] ?? null;
                $boxProduct = $boxProductId ? $product->boxes->firstWhere('id', $boxProductId) : null;
            }

            $orderItemQueries = resolve(OrderItemQueries::class);
            $orderItemData = $orderItemQueries->addNew(
                $order,
                $orderItem,
                $itemSubTotal,
                $itemTax,
                $itemCartDiscount,
                (float) $itemTotalDiscount,
                null,
                $boxProduct
            );

            $this->saveOrderItemAssemblyChildProduct($checkOrderDetailsService, $orderItemData->id, $orderItem);

            if ($this->isNonInventoryAssemblyProduct($checkOrderDetailsService, $orderItem)) {
                continue;
            }

            if ($checkOrderDetailsService->isLayawaySale()) {
                $orderReservedStockService->updateReservedStock($orderItemData, $orderItem, $checkOrderDetailsService);

                continue;
            }

            $this->updateInventory($orderItemData, $orderItem, $storeManager, $checkOrderDetailsService);
        }

        $roundOffAmount = $checkOrderDetailsService->orderData->order_round_off_amount;

        $this->updateLayawayDetails($order, $checkOrderDetailsService, $roundOffAmount);
        $this->updateCreditDetails($order, $checkOrderDetailsService, $roundOffAmount);

        $orderQueries->updateTotals($order, $roundOffAmount);

        $this->savePayments($checkOrderDetailsService, $order, $storeManager->getKey(), $locationId, $orderReturn);

        if ($order->getMemberId() !== null) {
            $this->updateMemberSpentTillNowAndLastPurchaseData(
                $order->getTotalAmountPaid(),
                $order->getMemberId(),
                $checkOrderDetailsService->companyId
            );

            $this->updateMemberSalesQuantity(
                $checkOrderDetailsService->orderItems->sum('quantity'),
                $order->getMemberId(),
            );
        }

        return $orderQueries->loadRelations($order);
    }

    public function isNonInventoryAssemblyProduct(
        CheckOrderDetailsService $checkOrderDetailsService,
        array $cartItem
    ): bool {
        /** @var Product $product */
        $product = $checkOrderDetailsService->products->firstWhere('id', $cartItem['id']);

        if ($product->type_id === ProductTypes::ASSEMBLY_PRODUCT->value && true === $product->is_non_inventory) {
            return false;
        }

        return $product->is_non_inventory;
    }

    public function updateInventory(
        OrderItem $orderItem,
        array $item,
        StoreManager $storeManager,
        CheckOrderDetailsService $checkOrderDetailsService
    ): void {
        $orderInventoryService = resolve(OrderInventoryService::class);
        $product = $checkOrderDetailsService->products->firstWhere('id', $item['id']);

        if ($product->type_id === ProductTypes::ASSEMBLY_PRODUCT->value) {
            $this->updateAssemblyProductInventory(
                $orderItem,
                $item,
                $storeManager,
                $checkOrderDetailsService,
                $product
            );

            return;
        }

        $inventoryQueries = resolve(InventoryQueries::class);
        $inventory = $inventoryQueries->fetchOrCreate(
            $checkOrderDetailsService->location->getKey(),
            (int) $item['id']
        );

        $productBoxUnits = $orderItem->product_box_units > 0 ? $orderItem->getBoxProductUnits() : 1;
        $itemQuantity = CommonFunctions::numberFormat($item['quantity'] * $productBoxUnits);

        $orderInventoryService = resolve(OrderInventoryService::class);
        if (! $product->has_batch) {
            $orderInventoryService->updateInventoryUnits(
                $inventory,
                $product,
                $checkOrderDetailsService->location->getKey(),
                $orderItem,
                $storeManager,
                (float) ('-' . $itemQuantity),
                Carbon::now()->format('Y-m-d H:i:s'),
                null
            );
        }

        if ($product->has_batch && $checkOrderDetailsService->hasBatchDetails($item)) {
            foreach ($item['batch_details'] as $batchDetail) {
                $batch = $checkOrderDetailsService->batches->where('product_id', $product->id)
                        ->firstWhere('number', $batchDetail['batch_number']);
                $batchId = $batch?->id;

                $batchQuantity = CommonFunctions::numberFormat($productBoxUnits * $batchDetail['quantity']);

                $orderInventoryService->updateInventoryUnits(
                    $inventory,
                    $product,
                    $checkOrderDetailsService->location->getKey(),
                    $orderItem,
                    $storeManager,
                    (float) ('-' . $batchQuantity),
                    Carbon::now()->format('Y-m-d H:i:s'),
                    $batchId
                );
            }
        }

        if ($inventory->stock < 0 || ($inventory->stock - $itemQuantity < 0)) {
            // Add Notification For Negative Inventory
        }

        $inventoryQueries->decreaseStock($inventory, $itemQuantity);
    }

    public function updateAssemblyProductInventory(
        OrderItem $orderItem,
        array $item,
        StoreManager $storeManager,
        CheckOrderDetailsService $checkOrderDetailsService,
        Product $assemblyProduct
    ): void {
        /** @var Collection $assemblyChildProducts */
        $assemblyChildProducts = $assemblyProduct->assemblyChildProducts;
        foreach ($assemblyChildProducts as $assemblyChildProduct) {
            /** @var Product $product */
            $product = $assemblyChildProduct->product;

            $inventoryQueries = resolve(InventoryQueries::class);
            $inventory = $inventoryQueries->fetchOrCreate(
                $checkOrderDetailsService->location->getKey(),
                $product->id
            );

            $itemQuantity = CommonFunctions::numberFormat($assemblyChildProduct->units * $item['quantity']);

            $orderInventoryService = resolve(OrderInventoryService::class);

            $orderInventoryService->updateInventoryUnits(
                $inventory,
                $product,
                $checkOrderDetailsService->location->getKey(),
                $orderItem,
                $storeManager,
                (float) ('-' . $itemQuantity),
                Carbon::now()->format('Y-m-d H:i:s'),
                null
            );

            if ($inventory->stock < 0 || ($inventory->stock - $itemQuantity < 0)) {
                // Add Notification For Negative Inventory
            }

            $inventoryQueries->decreaseStock($inventory, $itemQuantity);
        }
    }

    public function saveOrderItemAssemblyChildProduct(
        CheckOrderDetailsService $checkOrderDetailsService,
        int $orderItemId,
        array $cartItem,
    ): void {
        $product = $checkOrderDetailsService->products->firstWhere('id', $cartItem['id']);

        if ($product->type_id !== ProductTypes::ASSEMBLY_PRODUCT->value) {
            return;
        }

        foreach ($product->assemblyChildProducts as $assemblyChildProduct) {
            $saleItemAssemblyChildProductQueries = resolve(OrderItemAssemblyChildProductQueries::class);
            $saleItemAssemblyChildProductQueries->addNew([
                'order_item_id' => $orderItemId,
                'child_product_id' => $assemblyChildProduct->child_product_id,
                'units' => $assemblyChildProduct->units,
            ]);
        }
    }

    public function savePayments(
        CheckOrderDetailsService $checkOrderDetailsService,
        Order $order,
        int $storeManagerId,
        int $locationId,
        ?OrderReturn $orderReturn,
    ): void {
        $orderData = $checkOrderDetailsService->orderData;

        if (! $orderData->payments) {
            return;
        }

        $orderPaymentQueries = resolve(OrderPaymentQueries::class);

        foreach ($orderData->payments as $payment) {
            $orderPaymentQueries->addNew($order, $payment, $storeManagerId, $locationId);
        }
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

    public function updateLayawayDetails(
        Order $order,
        CheckOrderDetailsService $checkOrderDetailsService,
        ?float $roundOffAmount
    ): void {
        if ($checkOrderDetailsService->isLayawaySale()) {
            $payments = collect($checkOrderDetailsService->orderData->payments);

            $isLayawayPendingAmountSpecified = null !== $checkOrderDetailsService->orderData->layaway_pending_amount;
            $specifiedLayawayPendingAmount = (float) $checkOrderDetailsService->orderData->layaway_pending_amount;
            $calculatedLayawayPendingAmount = $order->getOrderItems()->sum('total_price_paid') - $payments->sum(
                'amount'
            ) + $roundOffAmount;

            $layawayPendingAmount = $isLayawayPendingAmountSpecified
                ? $specifiedLayawayPendingAmount
                : $calculatedLayawayPendingAmount;

            $isCompleteLayawayOrder = $layawayPendingAmount <= 0;

            $this->updateLayawayPaymentsToTheOrderItems(
                $order->getOrderItems(),
                $payments->sum('amount'),
                $isCompleteLayawayOrder
            );

            $orderQueries = resolve(OrderQueries::class);
            $orderQueries->updateLayawayPendingAmountAndStatus($order, $layawayPendingAmount);
        }
    }

    public function updateLayawayPaymentsToTheOrderItems(
        Collection $orderItems,
        float $totalPaidAmount,
        bool $isCompleteLayawayOrder
    ): void {
        $orderItemQueries = resolve(OrderItemQueries::class);
        $totalAmount = $orderItems->sum('total_price_paid');

        foreach ($orderItems as $orderItem) {
            $totalPricePaid = CommonFunctions::numberFormat(
                $totalPaidAmount * $orderItem->total_price_paid / $totalAmount
            );

            if ($isCompleteLayawayOrder) {
                $totalPricePaid = CommonFunctions::numberFormat(
                    $orderItem->getPricePaidPerUnit() * $orderItem->getQuantity()
                );
            }

            $orderItemQueries->updateTotalPricePaid($orderItem, $totalPricePaid);
        }
    }

    public function updateCreditDetails(
        Order $order,
        CheckOrderDetailsService $checkOrderDetailsService,
        ?float $roundOffAmount
    ): void {
        if ($checkOrderDetailsService->isCreditOrder()) {
            $payments = collect($checkOrderDetailsService->orderData->payments);

            $isCreditPendingAmountSpecified = null !== $checkOrderDetailsService->orderData->credit_pending_amount;
            $specifiedCreditPendingAmount = (float) $checkOrderDetailsService->orderData->credit_pending_amount;
            $calculatedCreditPendingAmount = $order->getOrderItems()->sum('total_price_paid') - $payments->sum(
                'amount'
            ) + $roundOffAmount;

            $creditPendingAmount = $isCreditPendingAmountSpecified
                ? $specifiedCreditPendingAmount
                : $calculatedCreditPendingAmount;

            $isCompleteCreditOrder = $creditPendingAmount <= 0;

            $this->updateCreditPaymentsToTheOrderItems(
                $order->getOrderItems(),
                $payments->sum('amount'),
                $isCompleteCreditOrder
            );

            $orderQueries = resolve(OrderQueries::class);
            $orderQueries->updateCreditPendingAmountAndTypeId($order, $creditPendingAmount);
        }
    }

    public function updateCreditPaymentsToTheOrderItems(
        Collection $orderItems,
        float $totalPaidAmount,
        bool $isCompleteCreditOrder
    ): void {
        $orderItemQueries = resolve(OrderItemQueries::class);
        $totalAmount = $orderItems->sum('total_price_paid');

        foreach ($orderItems as $orderItem) {
            $totalPricePaid = CommonFunctions::numberFormat(
                $totalPaidAmount * $orderItem->total_price_paid / $totalAmount
            );

            if ($isCompleteCreditOrder) {
                $totalPricePaid = CommonFunctions::numberFormat(
                    $orderItem->getPricePaidPerUnit() * $orderItem->getQuantity()
                );
            }

            $orderItemQueries->updateTotalPricePaid($orderItem, $totalPricePaid);
        }
    }

    public function getSequenceNumber(Location $location): string
    {
        $sequenceQueries = resolve(SequenceQueries::class);
        $number = $sequenceQueries->addNew($location->id, SequenceTypes::OS->value)->number;

        return $location->code.'-'.SequenceTypes::OS->name.'-'.$number;
    }
}

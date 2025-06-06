<?php

declare(strict_types=1);

namespace App\Domains\OrderReturn\Services;

use App\CommonFunctions;
use App\Domains\Company\RoundOffConfiguration;
use App\Domains\Inventory\Services\OrderReturnInventoryService;
use App\Domains\Order\OrderQueries;
use App\Domains\OrderCreditNote\OrderCreditNoteQueries;
use App\Domains\OrderItemUnit\OrderItemUnitQueries;
use App\Domains\OrderReturn\OrderReturnQueries;
use App\Domains\OrderReturnItem\OrderReturnItemQueries;
use App\Domains\Product\Enums\ProductTypes;
use App\Domains\Sequence\Enums\SequenceTypes;
use App\Domains\Sequence\SequenceQueries;
use App\Models\Location;
use App\Models\Member;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderReturn;
use App\Models\OrderReturnItem;
use App\Models\SaleReturnReason;
use App\Models\StoreManager;
use Carbon\Carbon;
use Illuminate\Support\Str;

class SaveOrderReturnDetailsService
{
    public function saveOrderReturnDetails(
        StoreManager $storeManager,
        CheckOrderReturnDetailsService $checkOrderReturnDetailsService,
        ?int $memberId
    ): ?OrderReturn {
        $orderReturnService = $checkOrderReturnDetailsService->orderReturnService;

        $orderReturnService->checkRoundOffValue();

        $sequenceQueries = resolve(SequenceQueries::class);
        $location = $checkOrderReturnDetailsService->location;
        $digitalInvoiceNumber = $this->getSequenceNumber($location);

        $sequenceNumber = $sequenceQueries->addNew(
            $checkOrderReturnDetailsService->locationId,
            SequenceTypes::ORR->value,
        )->number;
        $storeCode = Str::of($checkOrderReturnDetailsService->location->code)->substr(0, 2)->upper()->value();
        $receiptNumber = SequenceTypes::ORR->name . $storeCode . $sequenceNumber;

        $orderReturnQueries = resolve(OrderReturnQueries::class);
        $orderReturn = $orderReturnQueries->addNew(
            $storeManager,
            $orderReturnService->returnedOrderItems->first()->order_id,
            $checkOrderReturnDetailsService->locationId,
            $digitalInvoiceNumber,
            $receiptNumber,
            $memberId,
        );

        $orderReturnItemQueries = resolve(OrderReturnItemQueries::class);

        foreach ($orderReturnService->orderReturnItems as $orderReturnItem) {
            $returnedOrderItem = $orderReturnService->returnedOrderItems->firstWhere(
                'id',
                $orderReturnItem['order_item_id']
            );

            $totalDiscountAmount = 0;
            $itemDiscountAmount = 0;
            $itemCartDiscount = 0;
            $totalTaxAmount = 0;

            if ($returnedOrderItem->cart_discount_amount) {
                $itemCartDiscount = $this->getReturnItemAmountFor(
                    (float) $returnedOrderItem->cart_discount_amount,
                    (float) $returnedOrderItem->quantity,
                    (float) $orderReturnItem['return_quantity']
                );
            }

            if ($returnedOrderItem->item_discount_amount) {
                $itemDiscountAmount = $this->getReturnItemAmountFor(
                    (float) $returnedOrderItem->item_discount_amount,
                    (float) $returnedOrderItem->quantity,
                    (float) $orderReturnItem['return_quantity']
                );
            }

            if ($returnedOrderItem->total_discount_amount) {
                $totalDiscountAmount = $this->getReturnItemAmountFor(
                    (float) $returnedOrderItem->total_discount_amount,
                    (float) $returnedOrderItem->quantity,
                    (float) $orderReturnItem['return_quantity']
                );
            }

            if ($returnedOrderItem->item_tax_amount) {
                $totalTaxAmount = $this->getReturnItemAmountFor(
                    (float) $returnedOrderItem->item_tax_amount,
                    (float) $returnedOrderItem->quantity,
                    (float) $orderReturnItem['return_quantity']
                );
            }

            $totalPricePaid = $orderReturnItem['price_paid_per_unit'] * $orderReturnItem['return_quantity'];

            $orderReturnReason = $orderReturnService->orderReturnReasons->firstWhere(
                'id',
                $orderReturnItem['order_return_reason_id']
            );

            $orderReturnItemData = $orderReturnItemQueries->addNew(
                $orderReturnReason->id,
                $orderReturn->id,
                $returnedOrderItem->id,
                $returnedOrderItem->product_id,
                (float) $orderReturnItem['return_quantity'],
                $totalPricePaid,
                $totalTaxAmount,
                $itemCartDiscount,
                $itemDiscountAmount,
                $totalDiscountAmount
            );

            if (! $returnedOrderItem->product->is_non_inventory) {
                $this->updateInventory(
                    $checkOrderReturnDetailsService,
                    $returnedOrderItem,
                    $orderReturnItemData,
                    $storeManager,
                    $orderReturnReason,
                    $orderReturnItem,
                );
            }
        }

        $orderReturnRoundOff = $checkOrderReturnDetailsService->orderReturnData->order_return_round_off_amount;

        if (null === $orderReturnRoundOff) {
            $orderReturnRoundOff = $this->calculateOrderReturnRoundOff($orderReturn);
        }

        $orderReturnQueries->updateTotals($orderReturn, $orderReturnRoundOff);

        if ($checkOrderReturnDetailsService->member instanceof Member) {
            $orderCreditNoteQueries = resolve(OrderCreditNoteQueries::class);
            $orderCreditNoteQueries->addNew(
                $storeManager->id,
                $checkOrderReturnDetailsService->locationId,
                $orderReturn->id,
                $orderReturn->total_price_paid,
                $checkOrderReturnDetailsService->member->getKey(),
                $checkOrderReturnDetailsService->location->credit_note_expiration_days,
            );
        }

        return $orderReturnQueries->loadRelations($orderReturn);
    }

    public function calculateOrderReturnRoundOff(OrderReturn $orderReturn): float
    {
        $orderReturnAmount = $orderReturn->orderReturnItems->sum('total_price_paid');

        return RoundOffConfiguration::roundOffCalculationFor((string) $orderReturnAmount);
    }

    public function getReturnItemAmountFor(float $totalAmount, float $itemQuantity, float $returnQuantity): float
    {
        $totalAmount = CommonFunctions::numberFormat($totalAmount / $itemQuantity);

        return CommonFunctions::numberFormat($totalAmount * $returnQuantity);
    }

    public function updateInventory(
        CheckOrderReturnDetailsService $checkOrderReturnDetailsService,
        OrderItem $orderItem,
        OrderReturnItem $orderReturnItem,
        StoreManager $storeManager,
        SaleReturnReason $orderReturnReason,
        array $returnItemDetails
    ): void {
        if (
            $orderItem->product &&
            $orderItem->product->type_id === ProductTypes::ASSEMBLY_PRODUCT->value
        ) {
            return;
        }

        $orderItemUnits = $orderItem->getOrderItemUnits();

        $orderItemUnitQueries = resolve(OrderItemUnitQueries::class);
        $orderReturnInventoryService = resolve(OrderReturnInventoryService::class);
        $quantitiesToBeHandled = abs((float) $returnItemDetails['return_quantity']);
        foreach ($orderItemUnits as $orderItemUnit) {
            if ($quantitiesToBeHandled <= 0) {
                return;
            }

            $availableQuantitiesInThisRecord = $orderItemUnit['quantity'] - $orderItemUnit['return_quantity'];

            $quantitiesToReturnFromThisRecord = $availableQuantitiesInThisRecord;

            if ($availableQuantitiesInThisRecord >= $quantitiesToBeHandled) {
                $quantitiesToReturnFromThisRecord = $quantitiesToBeHandled;
                $quantitiesToBeHandled = 0;
            }

            $quantitiesToBeHandled -= $quantitiesToReturnFromThisRecord;

            $orderItemUnitQueries->incrementReturnedQuantity($orderItemUnit, (float) $quantitiesToReturnFromThisRecord);

            $locationId = $checkOrderReturnDetailsService->location->id;

            if (
                ! $orderReturnReason->put_back_in_inventory
                && $orderReturnReason->location_id
            ) {
                $locationId = $orderReturnReason->location_id;
            }

            $orderReturnInventoryService->addInventory(
                $orderReturnItem,
                $storeManager,
                (float) $quantitiesToReturnFromThisRecord,
                $locationId,
                $orderItem->product_id,
                $orderItemUnit->purchase_amount_id,
                $orderItemUnit->batch_id,
                Carbon::now()->format('Y-m-d H:i:s')
            );
        }
    }

    public function getItemAmountPaid(OrderItem $orderItem): float
    {
        return CommonFunctions::numberFormat($orderItem->quantity * $orderItem->price_paid_per_unit);
    }

    public function getTotalAmountPaid(Order $order): float
    {
        $orderQueries = resolve(OrderQueries::class);
        $order = $orderQueries->loadOrderItems($order);
        $totalAmountPaid = $order->getOrderItems()->sum(fn ($orderItem): float => $this->getItemAmountPaid($orderItem));

        return CommonFunctions::numberFormat($totalAmountPaid);
    }

    public function getSequenceNumber(Location $location): string
    {
        $sequenceQueries = resolve(SequenceQueries::class);
        $number = $sequenceQueries->addNew($location->id, SequenceTypes::ORT->value)->number;

        return $location->code . '-' . SequenceTypes::ORT->name . '-' . $number;
    }
}

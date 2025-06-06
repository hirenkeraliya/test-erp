<?php

declare(strict_types=1);

namespace App\Domains\OrderItem;

use App\CommonFunctions;
use App\Domains\Attribute\AttributeQueries;
use App\Domains\Color\ColorQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\MasterProduct\MasterProductQueries;
use App\Domains\Order\OrderQueries;
use App\Domains\OrderItemUnit\OrderItemUnitQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\ProductVariantValue\ProductVariantValueQueries;
use App\Domains\Size\SizeQueries;
use App\Models\BoxProduct;
use App\Models\Order;
use App\Models\OrderItem;
use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OrderItemQueries
{
    public function addNew(
        Order $order,
        array $item,
        float $itemSubTotal,
        float $itemTax,
        float $itemCartDiscount,
        float $itemDiscountAmount,
        ?int $exchangeReturnItemId = null,
        ?BoxProduct $boxProduct = null
    ): OrderItem {
        $totalPricePaid = array_key_exists('total_price_paid', $item)
            ? (float) $item['total_price_paid']
            : 0;

        $pricePaidPerUnit = $totalPricePaid / (float) $item['quantity'];
        $totalDiscountAmount = $itemCartDiscount + $itemDiscountAmount;

        $orderItem = OrderItem::create([
            'order_id' => $order->getKey(),
            'product_id' => $item['id'],
            'quantity' => $item['quantity'],
            'exchange_item_id' => $exchangeReturnItemId,
            'complimentary_item_reason_id' => $item['complimentary_item_reason_id'] ?? null,
            'original_product_price_per_unit' => $item['price'] ?? $item['open_price'],
            'promotion_id' => $item['promotion_id'] ?? null,
            'item_discount_amount' => CommonFunctions::numberFormat($itemDiscountAmount),
            'cart_discount_amount' => CommonFunctions::numberFormat($itemCartDiscount),
            'total_discount_amount' => CommonFunctions::numberFormat($totalDiscountAmount),
            'price_paid_per_unit' => CommonFunctions::numberFormat($pricePaidPerUnit),
            'total_price_paid' => CommonFunctions::numberFormat($totalPricePaid),
            'item_tax_amount' => $itemTax,
            'is_exchange' => $exchangeReturnItemId && array_key_exists('is_exchange', $item) && $item['is_exchange'],
            'box_product_id' => $boxProduct instanceof BoxProduct ? $boxProduct->id : null,
            'product_box_package_type_id' => $boxProduct instanceof BoxProduct ? $boxProduct->package_type_id : null,
            'product_box_units' => $boxProduct instanceof BoxProduct ? $boxProduct->units : null,
            'vendor_commission_percentage' => $item['vendor_commission_percentage'],
        ]);

        if (array_key_exists('promoter_ids', $item)) {
            $orderItem->promoters()->attach($item['promoter_ids']);
        }

        return $orderItem;
    }

    public function addNewForEcommerce(
        Order $order,
        array $item,
        ?int $productId,
        float $itemTax,
        float $itemCartDiscount,
        float $itemDiscountAmount,
        ?int $exchangeReturnItemId = null,
        ?BoxProduct $boxProduct = null
    ): OrderItem {
        $itemSubTotal = $item['quantity'] * $item['price'];
        $totalDiscountAmount = $itemCartDiscount + $itemDiscountAmount;

        $totalPricePaid = array_key_exists('total_amount', $item)
            ? (float) $item['total_amount']
            : ($itemSubTotal - $totalDiscountAmount);
        $pricePaidPerUnit = $totalPricePaid / (float) $item['quantity'];

        $orderItem = OrderItem::create([
            'order_id' => $order->getKey(),
            'product_id' => $productId,
            'quantity' => $item['quantity'],
            'exchange_item_id' => $exchangeReturnItemId,
            'complimentary_item_reason_id' => $item['complimentary_item_reason_id'] ?? null,
            'original_product_price_per_unit' => $item['price'] ?? $item['open_price'],
            'promotion_id' => $item['promotion_id'] ?? null,
            'item_discount_amount' => CommonFunctions::numberFormat($itemDiscountAmount),
            'cart_discount_amount' => CommonFunctions::numberFormat($itemCartDiscount),
            'total_discount_amount' => CommonFunctions::numberFormat($totalDiscountAmount),
            'price_paid_per_unit' => CommonFunctions::numberFormat($pricePaidPerUnit),
            'total_price_paid' => CommonFunctions::numberFormat($totalPricePaid),
            'item_tax_amount' => $itemTax,
            'is_exchange' => $exchangeReturnItemId && array_key_exists('is_exchange', $item) && $item['is_exchange'],
            'box_product_id' => $boxProduct instanceof BoxProduct ? $boxProduct->id : null,
            'product_box_package_type_id' => $boxProduct instanceof BoxProduct ? $boxProduct->package_type_id : null,
            'product_box_units' => $boxProduct instanceof BoxProduct ? $boxProduct->units : null,
            'vendor_commission_percentage' => $item['vendor_commission_percentage'],
        ]);

        if (array_key_exists('promoter_ids', $item)) {
            $orderItem->promoters()->attach($item['promoter_ids']);
        }

        return $orderItem;
    }

    public static function getColumnNamesForOrderUpdate(): string
    {
        return 'id,order_id,cart_discount_amount,item_discount_amount,total_discount_amount,item_tax_amount,total_price_paid';
    }

    public static function getBasicColumnNames(): string
    {
        return 'id,order_id,product_id,exchange_item_id,quantity,complimentary_item_reason_id,original_product_price_per_unit,cart_discount_amount,item_discount_amount,total_discount_amount,item_tax_amount,price_paid_per_unit,total_price_paid,is_exchange,box_product_id,product_box_units,product_box_package_type_id';
    }

    public function updateProductId(int $companyId, int $oldProductId, int $newProductId): void
    {
        $orderQueries = resolve(OrderQueries::class);

        $orderItems = OrderItem::query()
            ->select('id', 'order_id', 'product_id')
            ->whereHas('order', $orderQueries->filterByCompanyId($companyId))
            ->where('product_id', $oldProductId)
            ->get();

        foreach ($orderItems as $orderItem) {
            $orderItem->product_id = $newProductId;
            $orderItem->save();
        }
    }

    public function getOfflineOrderWithRelation(): Closure
    {
        $orderQueries = resolve(OrderQueries::class);

        return fn ($query) => $query->select('id', 'order_id')
            ->with(['order:' . $orderQueries->getOfflineOrderId()]);
    }

    public function updateTotalPricePaid(OrderItem $orderItem, float $totalPricePaid): void
    {
        $orderItem->total_price_paid = $totalPricePaid;
        $orderItem->save();
    }

    public function updateLayawayAmountOf(Order $order, float $totalPaymentAmount, bool $isCompleteLayawayOrder): void
    {
        $order->fresh();

        $totalAmount = $order->getTotalAmountPaid() + $order->getLayawayPendingAmount();

        foreach ($order->getOrderItems() as $orderItem) {
            if ($isCompleteLayawayOrder) {
                $orderItem->total_price_paid = $orderItem->getQuantity() * $orderItem->getPricePaidPerUnit();
                $orderItem->save();

                continue;
            }

            $itemSubtotal = $orderItem->getOriginalPricePerUnit() * $orderItem->getQuantity();
            $itemSubtotal -= $orderItem->getTotalDiscountAmount();
            $itemSubtotal += $orderItem->getTotalTaxAmount();

            $orderItem->total_price_paid += $totalPaymentAmount * $itemSubtotal / $totalAmount;
            $orderItem->save();
        }
    }

    public function updateCreditAmountOf(Order $order, float $totalPaymentAmount, bool $isCompleteCreditOrder): void
    {
        $order->fresh();

        $totalAmount = $order->getTotalAmountPaid() + $order->getCreditPendingAmount();

        foreach ($order->getOrderItems() as $orderItem) {
            if ($isCompleteCreditOrder) {
                $orderItem->total_price_paid = $orderItem->getQuantity() * $orderItem->getPricePaidPerUnit();
                $orderItem->save();

                continue;
            }

            $itemSubtotal = $orderItem->getOriginalPricePerUnit() * $orderItem->getQuantity();
            $itemSubtotal -= $orderItem->getTotalDiscountAmount();
            $itemSubtotal += $orderItem->getTotalTaxAmount();

            $orderItem->total_price_paid += $totalPaymentAmount * $itemSubtotal / $totalAmount;
            $orderItem->save();
        }
    }

    public function getSumOfPriceAndQuantity(): Closure
    {
        return fn ($query) => $query->select('id', 'order_id', 'total_price_paid', 'quantity')
            ->selectRaw('SUM(total_price_paid) as total_orders_amount')
            ->selectRaw('SUM(quantity) as total_units_sold')
            ->selectRaw('COUNT(DISTINCT order_id) as total_orders')
            ->where('is_exchange', false);
    }

    public function getOrderItemsForTheReport(array $filterData, int $companyId): Collection
    {
        $locationQueries = resolve(LocationQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $orderQueries = resolve(OrderQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);

        $relations = [
            'order:' . implode(',', $orderQueries->getBasicColumns()),
            'product:' . $productQueries->getBasicColumnNames(),
        ];

        if (config('app.product_variant')) {
            $relations = array_merge($relations, [
                'product.masterProduct:' . $masterProductQueries->getBasicColumnNames(),
                'product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                'product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
            ]);
        } else {
            $relations = array_merge($relations, [
                'product.color:' . $colorQueries->getBasicColumnNames(),
                'product.size:' . $sizeQueries->getBasicColumnNames(),
            ]);
        }

        return OrderItem::query()
            ->with($relations)
            ->whereHas('order', function ($query) use ($filterData, $locationQueries, $companyId): void {
                $query->whereHas(
                    'location',
                    $locationQueries->filterByCompanyAndTypeId($companyId, LocationTypes::STORE->value)
                )
                    ->when($filterData['store_manager_id'], function ($query) use ($filterData): void {
                        $query->where('store_manager_id', $filterData['store_manager_id']);
                    })
                    ->when($filterData['location_id'], function ($query) use ($filterData): void {
                        $query->where('location_id', $filterData['location_id']);
                    })
                    ->where('created_at', '>=', CommonFunctions::addStartTime($filterData['date_range'][0]))
                    ->where('created_at', '<=', CommonFunctions::addEndTime($filterData['date_range'][1]));
            })
            ->when($filterData['product_id'], function ($query) use ($filterData): void {
                $query->where('product_id', $filterData['product_id']);
            })
            ->when(
                array_key_exists('product_collection_id', $filterData) && null !== $filterData['product_collection_id'],
                function ($query) use ($filterData): void {
                    $query->whereIn('product_id', function ($query) use ($filterData): void {
                        $query->select('product_id')
                            ->from('product_collection_products')
                            ->where('product_collection_id', (int) $filterData['product_collection_id']);
                    });
                }
            )
            ->when($filterData['article_number'], function ($query) use ($filterData): void {
                $query->whereHas('product', function ($query) use ($filterData): void {
                    if (config('app.product_variant')) {
                        $query->whereHas('masterProduct', function ($query) use ($filterData): void {
                            $query->where('article_number', $filterData['article_number']);
                        });
                    } else {
                        $query->where('article_number', $filterData['article_number']);
                    }
                });
            })
            ->get();
    }

    public function getByIdsWithRelations(array $ids): Collection
    {
        $productQueries = new ProductQueries();
        $orderQueries = resolve(OrderQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $orderItemUnitQueries = resolve(OrderItemUnitQueries::class);

        return OrderItem::query()
            ->select(explode(',', static::getBasicColumnNames()))
            ->with(
                'orderItemUnits:' . $orderItemUnitQueries->getBasicColumnNames(),
                'order:' . implode(',', $orderQueries->getBasicColumns()),
                'product:' . $productQueries->getBasicColumnNames(),
                'product.color:' . $colorQueries->getBasicColumnNames(),
                'product.size:' . $sizeQueries->getBasicColumnNames(),
            )
            ->whereIntegerInRaw('id', $ids)
            ->get();
    }

    public function getByOrderIdForWebHook(int $orderId): Collection
    {
        return OrderItem::query()
            ->select('id', 'order_id', 'product_id', 'quantity')
            ->where('order_id', $orderId)
            ->get();
    }

    public function getBasicColumnNamesForOrderReturn(): string
    {
        return 'id,original_product_price_per_unit';
    }

    public function getOrderPickingListItemsBy(int $orderPickingListId): Collection
    {
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);

        if (config('app.product_variant')) {
            return OrderItem::query()
                ->select('id', 'order_id', 'product_id', 'quantity')
                ->selectRaw('sum(quantity) as total_quantity')
                ->whereHas('pickingListItems', function ($query) use ($orderPickingListId): void {
                    $query->where('order_picking_list_id', $orderPickingListId);
                })
                ->with([
                    'product:' . $productQueries->getBasicColumnNamesForPurchaseOrderInvoice(),
                    'product.masterProduct:' . $masterProductQueries->getBasicColumnNames(),
                    'product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                    'product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                ])
                ->groupBy('product_id')
                ->get();
        }

        return OrderItem::query()
            ->select('id', 'order_id', 'product_id', 'quantity')
            ->selectRaw('sum(quantity) as total_quantity')
            ->whereHas('pickingListItems', function ($query) use ($orderPickingListId): void {
                $query->where('order_picking_list_id', $orderPickingListId);
            })
            ->with([
                'product:' . $productQueries->getBasicColumnNamesForPurchaseOrderInvoice(),
                'product.color:' . $colorQueries->getBasicColumnNames(),
                'product.size:' . $sizeQueries->getBasicColumnNames(),
            ])
            ->groupBy('product_id')
            ->get();
    }

    public function getOrderItemWithOrder(): Closure
    {
        $orderQueries = resolve(OrderQueries::class);

        return fn ($query) => $query->select('id', 'order_id')
            ->with(['order:' . $orderQueries->getColumnNamesForMarketPlace()]);
    }

    public function getSelectIdColumn(): Closure
    {
        return fn ($query) => $query->select('id');
    }

    public function getOrderItemCount(): Closure
    {
        return fn ($query) => $query->select(DB::raw('COUNT(*)'));
    }
}

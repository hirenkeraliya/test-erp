<?php

declare(strict_types=1);

namespace App\Domains\Order;

use App\CommonFunctions;
use App\Domains\Attribute\AttributeQueries;
use App\Domains\BoxProduct\BoxProductQueries;
use App\Domains\Brand\BrandQueries;
use App\Domains\City\CityQueries;
use App\Domains\Color\ColorQueries;
use App\Domains\Company\CompanyQueries;
use App\Domains\ComplimentaryItemReason\ComplimentaryItemReasonQueries;
use App\Domains\Country\CountryQueries;
use App\Domains\Currency\CurrencyQueries;
use App\Domains\Employee\EmployeeQueries;
use App\Domains\Inventory\Services\EcommerceOrderInventoryService;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\MasterProduct\MasterProductQueries;
use App\Domains\Media\MediaQueries;
use App\Domains\Member\MemberQueries;
use App\Domains\MemberAddress\MemberAddressQueries;
use App\Domains\Order\DataObjects\CancelOrderData;
use App\Domains\Order\DataObjects\OrderData;
use App\Domains\Order\DataObjects\OrderECommerceData;
use App\Domains\Order\DataObjects\OrderECommerceStatusData;
use App\Domains\Order\DataObjects\OrderTrackingDetailsData;
use App\Domains\Order\Enums\OrderChannels;
use App\Domains\Order\Enums\OrderStatus;
use App\Domains\Order\Enums\OrderTypes;
use App\Domains\OrderAddress\OrderAddressQueries;
use App\Domains\OrderChannelReference\OrderChannelReferenceQueries;
use App\Domains\OrderItem\OrderItemQueries;
use App\Domains\OrderItemUnit\OrderItemUnitQueries;
use App\Domains\OrderPayment\OrderPaymentQueries;
use App\Domains\OrderPickingListItem\OrderPickingListItemQueries;
use App\Domains\OrderReturn\OrderReturnQueries;
use App\Domains\OrderReturnItem\OrderReturnItemQueries;
use App\Domains\PackageType\PackageTypeQueries;
use App\Domains\PaymentType\PaymentTypeQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\ProductVariantValue\ProductVariantValueQueries;
use App\Domains\Promoter\PromoterQueries;
use App\Domains\SaleChannel\SaleChannelQueries;
use App\Domains\Size\SizeQueries;
use App\Domains\StoreManager\StoreManagerQueries;
use App\Models\Order;
use App\Models\OrderChannelReference;
use App\Models\SaleChannel;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OrderQueries
{
    public function addNew(
        OrderData $orderData,
        int $storeManagerId,
        int $locationId,
        string $digitalInvoiceNumber,
        string $receiptNumber,
        ?int $memberId = null,
        ?int $orderReturnId = null,
    ): Order {
        return Order::create([
            'store_manager_id' => $storeManagerId,
            'location_id' => $locationId,
            'member_id' => $memberId,
            'order_return_id' => $orderReturnId,
            'receipt_number' => $receiptNumber,
            'type_id' => $orderData->order_type,
            'channel_id' => $orderData->channel_type,
            'notes' => $orderData->notes,
            'bill_reference_number' => $orderData->bill_reference_number,
            'layaway_pending_amount' => $orderData->layaway_pending_amount,
            'credit_pending_amount' => $orderData->credit_pending_amount,
            'digital_invoice_number' => $digitalInvoiceNumber,
        ]);
    }

    public function addNewForEcommerce(
        OrderECommerceData $orderECommerceData,
        int $locationId,
        string $digitalInvoiceNumber,
        string $receiptNumber,
        string $happenedAt,
        int $channelId,
        ?int $memberId = null,
        ?int $saleChannelId = null,
    ): Order {
        return Order::create([
            'location_id' => $locationId,
            'member_id' => $memberId,
            'receipt_number' => $receiptNumber,
            'type_id' => OrderTypes::REGULAR_ORDER,
            'channel_id' => $channelId,
            'notes' => $orderECommerceData->notes,
            'status' => OrderStatus::PLACED,
            'sale_channel_id' => $saleChannelId,
            'digital_invoice_number' => $digitalInvoiceNumber,
            'delivery_charges' => (float) $orderECommerceData->delivery_charges,
            'happened_at' => $happenedAt,
        ]);
    }

    public function updateTotals(Order $order, ?float $roundOffAmount): void
    {
        $orderItemQueries = resolve(OrderItemQueries::class);
        $orderItemTotalPricePaid = $order->getOrderItems()->sum('total_price_paid');

        $order->load('orderItems:' . $orderItemQueries->getColumnNamesForOrderUpdate());
        $order->update([
            'total_tax_amount' => $order->getOrderItems()->sum('item_tax_amount'),
            'item_discount_amount' => $order->getOrderItems()->sum('item_discount_amount'),
            'cart_discount_amount' => $order->getOrderItems()->sum('cart_discount_amount'),
            'total_discount_amount' => $order->getOrderItems()->sum('total_discount_amount'),
            'total_amount_paid' => $this->checkOrderTypeValueIsPendingCreditOrPendingLayaway(
                $order->getTypeId()->value
            ) ?
                $orderItemTotalPricePaid :
                $orderItemTotalPricePaid + $roundOffAmount + $order->getDeliveryCharges(),
            'total_amount_before_round_off' => $orderItemTotalPricePaid,
            'round_off' => $roundOffAmount,
        ]);
    }

    public function getPaginatedOrderListForMemberApi(array $filterData, int $memberId): LengthAwarePaginator
    {
        $orderItemQueries = resolve(OrderItemQueries::class);

        return $this->commonOrderRecordsByMemberIdQuery($memberId)
            ->withCount([
                'orderItems' => $orderItemQueries->getOrderItemCount(),
            ])
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            })
            ->paginate($filterData['per_page']);
    }

    public function getOrderDetailsById(int $orderId, int $memberId): Order
    {
        $orderPaymentQueries = resolve(OrderPaymentQueries::class);
        $paymentTypeQueries = resolve(PaymentTypeQueries::class);
        $memberQueries = resolve(MemberQueries::class);
        $orderItemQueries = resolve(OrderItemQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $orderChannelReferenceQueries = resolve(OrderChannelReferenceQueries::class);

        /* @phpstan-ignore-next-line */
        return $this->commonOrderRecordsByMemberIdQuery($memberId)
            ->with([
                'orderItems:' . $orderItemQueries->getBasicColumnNames(),
                'orderItems.product:' . $productQueries->getBasicColumnNames(),
                'orderItems.product.color:' . $colorQueries->getBasicColumnNamesForRegularSalesApi(),
                'orderItems.product.size:' . $sizeQueries->getBasicColumnNamesForRegularSalesApi(),
                'member:' . $memberQueries->getBasicColumnNamesForPosSale(),
                'payments:' . $orderPaymentQueries->getBasicColumnNames(),
                'payments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
                'orderChannelReference:' . $orderChannelReferenceQueries->getBasicNames(),
            ])
            ->findOrFail($orderId);
    }

    private function commonOrderRecordsByMemberIdQuery(int $memberId): Builder
    {
        return Order::query()
            ->select(
                'id',
                'receipt_number',
                'total_tax_amount',
                'cart_discount_amount',
                'item_discount_amount',
                'total_discount_amount',
                'total_amount_paid',
                'total_amount_before_round_off',
                'happened_at',
                'notes',
                'bill_reference_number',
                'round_off',
                'layaway_pending_amount',
                'credit_pending_amount',
                'credit_completed_at',
                'layaway_completed_at',
                'delivery_charges',
                'member_id',
                'status'
            )
            ->where('member_id', $memberId);
    }

    private function checkOrderTypeValueIsPendingCreditOrPendingLayaway(int $orderTypeId): bool
    {
        return $orderTypeId === OrderTypes::PENDING_LAYAWAY_ORDER->value || $orderTypeId === OrderTypes::PENDING_CREDIT_ORDER->value;
    }

    private function getCompleteOrderWithRelationsQuery(
        array $filterData,
        int $storeManagerId,
        int $locationId,
        int $companyId,
        bool $isOnlyB2B,
    ): Builder {
        $storeManagerQueries = resolve(StoreManagerQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $orderReturnQueries = resolve(OrderReturnQueries::class);
        $companyQueries = resolve(CompanyQueries::class);
        $countryQueries = resolve(CountryQueries::class);
        $currencyQueries = resolve(CurrencyQueries::class);
        $orderPaymentQueries = resolve(OrderPaymentQueries::class);
        $paymentTypeQueries = resolve(PaymentTypeQueries::class);
        $orderChannelReferenceQueries = resolve(OrderChannelReferenceQueries::class);

        return Order::query()
            ->select(...$this->getBasicColumns())
            ->when($isOnlyB2B, function ($query): void {
                $query->where('channel_id', OrderChannels::B2B_ORDERS->value);
            }, function ($query): void {
                $query->whereNot('channel_id', OrderChannels::B2B_ORDERS->value);
            })
            ->when(0 !== $storeManagerId, function ($query) use ($storeManagerQueries, $storeManagerId): void {
                $query->whereHas('storeManager', $storeManagerQueries->filterById($storeManagerId));
            })
            ->when(0 !== $locationId, function ($query) use ($locationQueries, $locationId, $companyId): void {
                $query->whereHas('location', $locationQueries->filterByStoreAndCompanyId($locationId, $companyId));
            })
            ->withSum('orderItems', 'quantity')
            ->with(
                'member',
                'orderChannelReference:' . $orderChannelReferenceQueries->getBasicNames(),
                'storeManager:' . $storeManagerQueries->getEmployeeIdColumnNames(),
                'location:' . $locationQueries->getNameColumnName(),
                'location.company:' . $companyQueries->getBasicColumnNames(),
                'location.company.defaultCountry:' . $countryQueries->getColumnId(),
                'location.company.defaultCountry.currency:' . $currencyQueries->getBasicColumnNames(),
                'storeManager.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'checkHasOrderReturn:' . $orderReturnQueries->getIdAndOriginalOrderId(),
                'payments:' . $orderPaymentQueries->getBasicColumnNames(),
                'payments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
            )
            ->when(null !== $filterData['e_invoice_submitted'], function ($query) use ($filterData): void {
                $query->where('digital_invoice_submitted', (bool) $filterData['e_invoice_submitted']);
            })
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query
                        ->whereAny(
                            ['receipt_number', 'bill_reference_number'],
                            'LIKE',
                            '%' . $filterData['search_text'] . '%'
                        )
                        ->orWhereHas('orderChannelReference', function ($query) use ($filterData): void {
                            $query->where('external_order_id', 'LIKE', '%' . $filterData['search_text'] . '%');
                        });
                });
            })
            ->when($filterData['member_id'], function ($query) use ($filterData): void {
                $query->where('member_id', (int) $filterData['member_id']);
            })
            ->when($filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('happened_at', '>=', $filterData['date_range'][0])
                    ->where('happened_at', '<=', $filterData['date_range'][1]);
            })
            ->when($filterData['type_id'], function ($query) use ($filterData): void {
                $query->where('type_id', (int) $filterData['type_id']);
            })
            ->when($filterData['channel_id'], function ($query) use ($filterData): void {
                $query->where('channel_id', (int) $filterData['channel_id']);
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            });
    }

    public function getPaginatedCompleteOrderWithRelations(
        array $filterData,
        int $storeManagerId,
        int $locationId,
        int $companyId,
        bool $isOnlyB2B,
    ): LengthAwarePaginator {
        return $this->getCompleteOrderWithRelationsQuery(
            $filterData,
            $storeManagerId,
            $locationId,
            $companyId,
            $isOnlyB2B,
        )->paginate($filterData['per_page']);
    }

    public function getCompleteOrderWithRelationsForExport(
        array $filterData,
        int $storeManagerId,
        int $locationId,
        int $companyId,
        bool $isOnlyB2B,
    ): Collection {
        return $this->getCompleteOrderWithRelationsQuery(
            $filterData,
            $storeManagerId,
            $locationId,
            $companyId,
            $isOnlyB2B,
        )->get();
    }

    public function getFilteredTotalsForReport(
        array $filterData,
        int $storeManagerId,
        int $locationId,
        int $companyId,
        bool $isOnlyB2B,
    ): Collection {
        $storeManagerQueries = resolve(StoreManagerQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $orderItemQueries = resolve(OrderItemQueries::class);

        return Order::query()
            ->select('id')
            ->with([
                'orderItems' => $orderItemQueries->getSumOfPriceAndQuantity(),
            ])
            ->when($isOnlyB2B, function ($query): void {
                $query->where('channel_id', OrderChannels::B2B_ORDERS->value);
            }, function ($query): void {
                $query->whereNot('channel_id', OrderChannels::B2B_ORDERS->value);
            })
            ->when(0 !== $storeManagerId, function ($query) use ($storeManagerQueries, $storeManagerId): void {
                $query->whereHas('storeManager', $storeManagerQueries->filterById($storeManagerId));
            })
            ->when(0 !== $locationId, function ($query) use ($locationQueries, $locationId, $companyId): void {
                $query->whereHas('location', $locationQueries->filterByStoreAndCompanyId($locationId, $companyId));
            })
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query
                        ->whereAny(
                            ['receipt_number', 'bill_reference_number'],
                            'LIKE',
                            '%' . $filterData['search_text'] . '%'
                        );
                });
            })
            ->when(null !== $filterData['e_invoice_submitted'], function ($query) use ($filterData): void {
                $query->where('digital_invoice_submitted', (bool) $filterData['e_invoice_submitted']);
            })
            ->when($filterData['member_id'], function ($query) use ($filterData): void {
                $query->where('member_id', (int) $filterData['member_id']);
            })
            ->when($filterData['type_id'], function ($query) use ($filterData): void {
                $query->where('type_id', (int) $filterData['type_id']);
            })
            ->when($filterData['channel_id'], function ($query) use ($filterData): void {
                $query->where('channel_id', (int) $filterData['channel_id']);
            })
            ->when($filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('created_at', '>=', $filterData['date_range'][0])
                    ->where('created_at', '<=', $filterData['date_range'][1]);
            })
            ->get();
    }

    public function getOrderItemsBy(string $orderId, int $storeManagerId, int $locationId, int $companyId): Order
    {
        $orderItemQueries = resolve(OrderItemQueries::class);
        $orderReturnItemQueries = resolve(OrderReturnItemQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $orderPaymentQueries = resolve(OrderPaymentQueries::class);
        $paymentTypeQueries = resolve(PaymentTypeQueries::class);
        $promoterQueries = resolve(PromoterQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $storeManagerQueries = resolve(StoreManagerQueries::class);
        $complimentaryItemReasonQueries = resolve(ComplimentaryItemReasonQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $memberQueries = resolve(MemberQueries::class);
        $boxProductQueries = resolve(BoxProductQueries::class);
        $packageTypeQueries = resolve(PackageTypeQueries::class);
        $companyQueries = resolve(CompanyQueries::class);
        $countryQueries = resolve(CountryQueries::class);
        $currencyQueries = resolve(CurrencyQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);

        if (config('app.product_variant')) {
            return Order::query()
                ->select(...$this->getBasicColumns())
                ->when($storeManagerId > 0, function ($query) use ($storeManagerQueries, $storeManagerId): void {
                    $query->whereHas('storeManager', $storeManagerQueries->filterById($storeManagerId));
                })
                ->whereHas('location', $locationQueries->filterByStoreAndCompanyId($locationId, $companyId))
                ->with(
                    'member:' . $memberQueries->getBasicColumnNames(),
                    'orderItems:' . $orderItemQueries->getBasicColumnNames(),
                    'orderItems.boxProduct:' . $boxProductQueries->getBasicColumnNames(),
                    'orderItems.boxProduct.packageType:' . $packageTypeQueries->getBasicColumnNames(),
                    'orderItems.orderReturnItems:' . $orderReturnItemQueries->getColumnNames(),
                    'orderItems.complimentaryItemReason:' . $complimentaryItemReasonQueries->getBasicColumnNames(),
                    'orderItems.product:' . $productQueries->getBasicColumnNames(),
                    'orderItems.product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                    'orderItems.product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                    'orderItems.promoters:' . $promoterQueries->getBasicColumnNames(),
                    'orderItems.promoters.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                    'payments:' . $orderPaymentQueries->getBasicColumnNames(),
                    'payments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
                    'location:' . $locationQueries->getNameColumnName(),
                    'location.company:' . $companyQueries->getBasicColumnNames(),
                    'location.company.defaultCountry:' . $countryQueries->getColumnId(),
                    'location.company.defaultCountry.currency:' . $currencyQueries->getBasicColumnNames(),
                )
                ->findOrFail($orderId);
        }

        return Order::query()
            ->select(...$this->getBasicColumns())
            ->when($storeManagerId > 0, function ($query) use ($storeManagerQueries, $storeManagerId): void {
                $query->whereHas('storeManager', $storeManagerQueries->filterById($storeManagerId));
            })
            ->whereHas('location', $locationQueries->filterByStoreAndCompanyId($locationId, $companyId))
            ->with(
                'member:' . $memberQueries->getBasicColumnNames(),
                'orderItems:' . $orderItemQueries->getBasicColumnNames(),
                'orderItems.boxProduct:' . $boxProductQueries->getBasicColumnNames(),
                'orderItems.boxProduct.packageType:' . $packageTypeQueries->getBasicColumnNames(),
                'orderItems.orderReturnItems:' . $orderReturnItemQueries->getColumnNames(),
                'orderItems.complimentaryItemReason:' . $complimentaryItemReasonQueries->getBasicColumnNames(),
                'orderItems.product:' . $productQueries->getBasicColumnNames(),
                'orderItems.product.color:' . $colorQueries->getBasicColumnNames(),
                'orderItems.product.size:' . $sizeQueries->getBasicColumnNames(),
                'orderItems.promoters:' . $promoterQueries->getBasicColumnNames(),
                'orderItems.promoters.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'payments:' . $orderPaymentQueries->getBasicColumnNames(),
                'payments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
                'location:' . $locationQueries->getNameColumnName(),
                'location.company:' . $companyQueries->getBasicColumnNames(),
                'location.company.defaultCountry:' . $countryQueries->getColumnId(),
                'location.company.defaultCountry.currency:' . $currencyQueries->getBasicColumnNames(),
            )
            ->findOrFail($orderId);
    }

    public function getOrderItemsForEcommerce(string $orderId, int $locationId, int $companyId): Order
    {
        $orderItemQueries = resolve(OrderItemQueries::class);
        $orderReturnItemQueries = resolve(OrderReturnItemQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $orderPaymentQueries = resolve(OrderPaymentQueries::class);
        $paymentTypeQueries = resolve(PaymentTypeQueries::class);
        $promoterQueries = resolve(PromoterQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $complimentaryItemReasonQueries = resolve(ComplimentaryItemReasonQueries::class);
        $memberQueries = resolve(MemberQueries::class);
        $boxProductQueries = resolve(BoxProductQueries::class);
        $packageTypeQueries = resolve(PackageTypeQueries::class);
        $companyQueries = resolve(CompanyQueries::class);
        $countryQueries = resolve(CountryQueries::class);
        $currencyQueries = resolve(CurrencyQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);

        if (config('app.product_variant')) {
            return Order::query()
                ->select(...$this->getBasicColumns())
                ->whereHas('location', $locationQueries->filterByStoreIdAndCompanyId($locationId, $companyId))
                ->with(
                    'member:' . $memberQueries->getBasicColumnNames(),
                    'orderItems:' . $orderItemQueries->getBasicColumnNames(),
                    'orderItems.boxProduct:' . $boxProductQueries->getBasicColumnNames(),
                    'orderItems.boxProduct.packageType:' . $packageTypeQueries->getBasicColumnNames(),
                    'orderItems.orderReturnItems:' . $orderReturnItemQueries->getColumnNames(),
                    'orderItems.complimentaryItemReason:' . $complimentaryItemReasonQueries->getBasicColumnNames(),
                    'orderItems.product:' . $productQueries->getBasicColumnNames(),
                    'orderItems.product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                    'orderItems.product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                    'orderItems.promoters:' . $promoterQueries->getBasicColumnNames(),
                    'orderItems.promoters.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                    'payments:' . $orderPaymentQueries->getBasicColumnNames(),
                    'payments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
                    'location:' . $locationQueries->getNameColumnName(),
                    'location.company:' . $companyQueries->getBasicColumnNames(),
                    'location.company.defaultCountry:' . $countryQueries->getColumnId(),
                    'location.company.defaultCountry.currency:' . $currencyQueries->getBasicColumnNames(),
                )
                ->findOrFail($orderId);
        }

        return Order::query()
            ->select(...$this->getBasicColumns())
            ->whereHas('location', $locationQueries->filterByStoreIdAndCompanyId($locationId, $companyId))
            ->with(
                'member:' . $memberQueries->getBasicColumnNames(),
                'orderItems:' . $orderItemQueries->getBasicColumnNames(),
                'orderItems.boxProduct:' . $boxProductQueries->getBasicColumnNames(),
                'orderItems.boxProduct.packageType:' . $packageTypeQueries->getBasicColumnNames(),
                'orderItems.orderReturnItems:' . $orderReturnItemQueries->getColumnNames(),
                'orderItems.complimentaryItemReason:' . $complimentaryItemReasonQueries->getBasicColumnNames(),
                'orderItems.product:' . $productQueries->getBasicColumnNames(),
                'orderItems.product.color:' . $colorQueries->getBasicColumnNames(),
                'orderItems.product.size:' . $sizeQueries->getBasicColumnNames(),
                'orderItems.promoters:' . $promoterQueries->getBasicColumnNames(),
                'orderItems.promoters.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'payments:' . $orderPaymentQueries->getBasicColumnNames(),
                'payments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
                'location:' . $locationQueries->getNameColumnName(),
                'location.company:' . $companyQueries->getBasicColumnNames(),
                'location.company.defaultCountry:' . $countryQueries->getColumnId(),
                'location.company.defaultCountry.currency:' . $currencyQueries->getBasicColumnNames(),
            )
            ->findOrFail($orderId);
    }

    public function getOrderWithStoreAndItemsForCompleteLayaway(
        int $orderId,
        int $storeManagerId,
        int $locationId,
        int $companyId,
    ): Order {
        $orderItemQueries = resolve(OrderItemQueries::class);
        $storeManagerQueries = resolve(StoreManagerQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $brandQueries = resolve(BrandQueries::class);

        return Order::query()
            ->select(...$this->getBasicColumns())
            ->whereHas('storeManager', $storeManagerQueries->filterById($storeManagerId))
            ->whereHas('location', $locationQueries->filterByStoreAndCompanyId($locationId, $companyId))
            ->with(
                'orderItems:' . $orderItemQueries->getBasicColumnNames(),
                'orderItems.product:' . $productQueries->getBasicColumnNames(),
                'orderItems.product.brand:' . $brandQueries->getIdAndNameColumnNames(),
            )
            ->findOrFail($orderId);
    }

    public function getOrderWithStoreAndItemsForCompleteCredit(
        int $orderId,
        int $storeManagerId,
        int $locationId,
        int $companyId,
    ): Order {
        $orderItemQueries = resolve(OrderItemQueries::class);
        $storeManagerQueries = resolve(StoreManagerQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $brandQueries = resolve(BrandQueries::class);

        return Order::query()
            ->select(...$this->getBasicColumns())
            ->whereHas('storeManager', $storeManagerQueries->filterById($storeManagerId))
            ->whereHas('location', $locationQueries->filterByStoreAndCompanyId($locationId, $companyId))
            ->with(
                'orderItems:' . $orderItemQueries->getBasicColumnNames(),
                'orderItems.product:' . $productQueries->getBasicColumnNames(),
                'orderItems.product.brand:' . $brandQueries->getIdAndNameColumnNames(),
            )
            ->findOrFail($orderId);
    }

    public function getColumnNames(): string
    {
        return 'id,receipt_number';
    }

    public function getColumnsForAddressUpdate(): string
    {
        return 'id,sale_channel_id,status';
    }

    public function getReceiptNumberColumn(): Closure
    {
        return fn ($query) => $query->select('id', 'receipt_number');
    }

    public function getColumnNamesForMarketPlace(): string
    {
        return 'id,receipt_number,type_id,channel_id,member_id,channel_id,bill_reference_number,status,happened_at';
    }

    public function getBasicColumns(): array
    {
        return [
            'id',
            'receipt_number',
            'member_id',
            'store_manager_id',
            'location_id',
            'total_tax_amount',
            'cart_discount_amount',
            'item_discount_amount',
            'total_discount_amount',
            'layaway_pending_amount',
            'credit_pending_amount',
            'total_amount_paid',
            'delivery_charges',
            'created_at',
            'round_off',
            'type_id',
            'channel_id',
            'bill_reference_number',
            'notes',
            'status',
            'total_amount_before_round_off',
            'pickup_location_id',
            'tracking_number',
            'courier_name',
            'digital_invoice_submitted',
            'tracking_url',
            'sale_channel_id',
            'shipment_order_number',
            'digital_invoice_number',
            'happened_at',
        ];
    }

    public function getBasicColumnNames(): string
    {
        return 'id,receipt_number,member_id,store_manager_id,location_id,total_tax_amount,cart_discount_amount,item_discount_amount,total_discount_amount,layaway_pending_amount,credit_pending_amount,total_amount_paid,delivery_charges,created_at,round_off,type_id,channel_id,bill_reference_number,notes,status,total_amount_before_round_off,pickup_location_id,tracking_number,courier_name,digital_invoice_submitted,tracking_url,sale_channel_id,shipment_order_number,digital_invoice_number';
    }

    public function loadRelations(Order $order): Order
    {
        $productQueries = resolve(ProductQueries::class);
        $memberQueries = resolve(MemberQueries::class);
        $paymentTypeQueries = resolve(PaymentTypeQueries::class);
        $orderPaymentQueries = resolve(OrderPaymentQueries::class);
        $orderItemQueries = resolve(OrderItemQueries::class);
        $promoterQueries = resolve(PromoterQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $orderItemUnitQueries = resolve(OrderItemUnitQueries::class);
        $brandQueries = resolve(BrandQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $companyQueries = resolve(CompanyQueries::class);
        $mediaQueries = resolve(MediaQueries::class);
        $storeManagerQueries = resolve(StoreManagerQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $boxProductQueries = resolve(BoxProductQueries::class);
        $packageTypeQueries = resolve(PackageTypeQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);
        $cityQueries = resolve(CityQueries::class);
        $saleChannelQueries = resolve(SaleChannelQueries::class);

        $order->refresh();

        if (config('app.product_variant')) {
            return $order->load(
                'member:' . $memberQueries->getBasicColumnNames(),
                'orderItems:' . $orderItemQueries->getBasicColumnNames(),
                'orderItems.boxProduct:' . $boxProductQueries->getBasicColumnNames(),
                'orderItems.boxProduct.packageType:' . $packageTypeQueries->getBasicColumnNames(),
                'orderItems.orderItemUnits:' . $orderItemUnitQueries->getBasicColumnNames(),
                'orderItems.product:' . $productQueries->getBasicColumnNames(),
                'orderItems.product.brand:' . $brandQueries->getBasicColumnNames(),
                'orderItems.product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                'orderItems.product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                'orderItems.promoters:' . $promoterQueries->getBasicColumnNames(),
                'orderItems.promoters.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'payments:' . $orderPaymentQueries->getBasicColumnNames(),
                'payments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
                'storeManager:' . $storeManagerQueries->getEmployeeIdColumnNames(),
                'location:' . $locationQueries->getBasicColumnNamesForAdminSaleReports(),
                'location.city:' . $cityQueries->getBasicColumnNames(),
                'location.company:' . $companyQueries->getBasicColumnNamesForAdminSaleReports(),
                'location.company.media:' . $mediaQueries->getBasicColumnNames(),
                'storeManager.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'saleChannel:' . $saleChannelQueries->getBasicColumnsInString(),
            );
        }

        return $order->load(
            'member:' . $memberQueries->getBasicColumnNames(),
            'orderItems:' . $orderItemQueries->getBasicColumnNames(),
            'orderItems.boxProduct:' . $boxProductQueries->getBasicColumnNames(),
            'orderItems.boxProduct.packageType:' . $packageTypeQueries->getBasicColumnNames(),
            'orderItems.orderItemUnits:' . $orderItemUnitQueries->getBasicColumnNames(),
            'orderItems.product:' . $productQueries->getBasicColumnNames(),
            'orderItems.product.brand:' . $brandQueries->getBasicColumnNames(),
            'orderItems.product.color:' . $colorQueries->getBasicColumnNames(),
            'orderItems.product.size:' . $sizeQueries->getBasicColumnNames(),
            'orderItems.promoters:' . $promoterQueries->getBasicColumnNames(),
            'orderItems.promoters.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
            'payments:' . $orderPaymentQueries->getBasicColumnNames(),
            'payments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
            'storeManager:' . $storeManagerQueries->getEmployeeIdColumnNames(),
            'location:' . $locationQueries->getBasicColumnNamesForAdminSaleReports(),
            'location.city:' . $cityQueries->getBasicColumnNames(),
            'location.company:' . $companyQueries->getBasicColumnNamesForAdminSaleReports(),
            'location.company.media:' . $mediaQueries->getBasicColumnNames(),
            'storeManager.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
            'saleChannel:' . $saleChannelQueries->getBasicColumnsInString(),
        );
    }

    public function loadRelationsForApi(Order $order): Order
    {
        $productQueries = resolve(ProductQueries::class);
        $memberQueries = resolve(MemberQueries::class);
        $paymentTypeQueries = resolve(PaymentTypeQueries::class);
        $orderPaymentQueries = resolve(OrderPaymentQueries::class);
        $orderItemQueries = resolve(OrderItemQueries::class);
        $promoterQueries = resolve(PromoterQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $orderItemUnitQueries = resolve(OrderItemUnitQueries::class);
        $brandQueries = resolve(BrandQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $companyQueries = resolve(CompanyQueries::class);
        $mediaQueries = resolve(MediaQueries::class);
        $storeManagerQueries = resolve(StoreManagerQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $boxProductQueries = resolve(BoxProductQueries::class);
        $packageTypeQueries = resolve(PackageTypeQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);
        $cityQueries = resolve(CityQueries::class);
        $saleChannelQueries = resolve(SaleChannelQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);

        $order->refresh();

        return $order->load(
            'member:' . $memberQueries->getBasicColumnNames(),
            'orderItems:' . $orderItemQueries->getBasicColumnNames(),
            'orderItems.boxProduct:' . $boxProductQueries->getBasicColumnNames(),
            'orderItems.boxProduct.packageType:' . $packageTypeQueries->getBasicColumnNames(),
            'orderItems.orderItemUnits:' . $orderItemUnitQueries->getBasicColumnNames(),
            'orderItems.product:' . $productQueries->getBasicColumnNames(),
            'orderItems.product.brand:' . $brandQueries->getBasicColumnNames(),
            'orderItems.product.color:' . $colorQueries->getBasicColumnNames(),
            'orderItems.product.size:' . $sizeQueries->getBasicColumnNames(),
            'orderItems.product.masterProduct:' . $masterProductQueries->getBasicColumnNames(),
            'orderItems.product.masterProduct.brand:' . $brandQueries->getBasicColumnNames(),
            'orderItems.product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
            'orderItems.product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
            'orderItems.promoters:' . $promoterQueries->getBasicColumnNames(),
            'orderItems.promoters.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
            'payments:' . $orderPaymentQueries->getBasicColumnNames(),
            'payments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
            'storeManager:' . $storeManagerQueries->getEmployeeIdColumnNames(),
            'location:' . $locationQueries->getBasicColumnNamesForAdminSaleReports(),
            'location.city:' . $cityQueries->getBasicColumnNames(),
            'location.company:' . $companyQueries->getBasicColumnNamesForAdminSaleReports(),
            'location.company.media:' . $mediaQueries->getBasicColumnNames(),
            'storeManager.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
            'saleChannel:' . $saleChannelQueries->getBasicColumnsInString(),
        );
    }

    public function cancelOrder(CancelOrderData $cancelOrderData): Order
    {
        $order = Order::select('id', 'cancel_order_reason_id', 'type_id')
            ->findOrFail($cancelOrderData->orderId);

        $order->update([
            'cancel_order_reason_id' => $cancelOrderData->cancelOrderReasonId,
            'type_id' => OrderTypes::CANCEL_ORDER->value,
        ]);

        $order->save();

        return $order;
    }

    public function loadOrderItemsAndOrderItemsUnits(Order $order): Order
    {
        $orderItemQueries = new OrderItemQueries();
        $orderItemUnitQueries = new OrderItemUnitQueries();

        return $order->load(
            'orderItems:' . $orderItemQueries->getBasicColumnNames(),
            'orderItems.orderItemUnits:' . $orderItemUnitQueries->getBasicColumnNames(),
        );
    }

    public function filterByCompanyId(int $companyId): Closure
    {
        $locationQueries = resolve(LocationQueries::class);

        return fn ($query) => $query->select('id', 'location_id')
            ->whereHas('location', $locationQueries->filterByCompanyAndTypeId($companyId, LocationTypes::STORE->value));
    }

    public function filterByLocationId(int $locationId): Closure
    {
        return fn ($query) => $query->select('id', 'location_id')
            ->where('location_id', $locationId);
    }

    public function updateLayawayPendingAmountAndStatus(Order $order, float $layawayPendingAmount): void
    {
        $order->update([
            'layaway_pending_amount' => $layawayPendingAmount,
            'type_id' => $layawayPendingAmount > 0 ? OrderTypes::PENDING_LAYAWAY_ORDER : OrderTypes::COMPLETE_LAYAWAY_ORDER,
        ]);
    }

    public function updateLayawayAmountOf(Order $order, Collection $payments): Order
    {
        $order = $this->loadOrderItems($order);

        $order->layaway_pending_amount -= $payments->sum('amount');
        $order->total_amount_paid += $payments->sum('amount');
        $order->total_amount_before_round_off += $payments->sum('amount');

        if ($order->layaway_pending_amount <= 0) {
            $order->total_amount_before_round_off = $order->getOrderItems()->sum('total_price_paid');
            $order->layaway_pending_amount = null;
            $order->type_id = OrderTypes::COMPLETE_LAYAWAY_ORDER;
            $order->layaway_completed_at = now()->format('Y-m-d H:i:s');
        }

        $order->save();

        return $order;
    }

    public function updateCreditPendingAmountAndTypeId(Order $order, float $creditPendingAmount): void
    {
        $order->update([
            'credit_pending_amount' => $creditPendingAmount,
            'type_id' => $creditPendingAmount > 0 ? OrderTypes::PENDING_CREDIT_ORDER : OrderTypes::COMPLETE_CREDIT_ORDER,
        ]);
    }

    public function updateCreditAmountOf(Order $order, Collection $payments): Order
    {
        $order = $this->loadOrderItems($order);

        $order->credit_pending_amount -= $payments->sum('amount');
        $order->total_amount_paid += $payments->sum('amount');
        $order->total_amount_before_round_off += $payments->sum('amount');

        if ($order->credit_pending_amount <= 0) {
            $order->total_amount_before_round_off = $order->getOrderItems()->sum('total_price_paid');
            $order->credit_pending_amount = null;
            $order->type_id = OrderTypes::COMPLETE_CREDIT_ORDER;
            $order->credit_completed_at = now()->format('Y-m-d H:i:s');
        }

        $order->save();

        return $order;
    }

    public function getLayawayOrderItemsByForPrint(int $orderId): Order
    {
        $orderItemQueries = resolve(OrderItemQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $orderPaymentQueries = resolve(OrderPaymentQueries::class);
        $paymentTypeQueries = resolve(PaymentTypeQueries::class);
        $promoterQueries = resolve(PromoterQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $memberQueries = resolve(MemberQueries::class);
        $memberAddressQueries = resolve(MemberAddressQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);

        if (config('app.product_variant')) {
            return Order::query()
                ->select(...$this->getBasicColumns())
                ->with(
                    'member:' . $memberQueries->getColumnNamesForOrderReport(),
                    'member.primaryMemberAddress:' . $memberAddressQueries->getBasicColumnNames(),
                    'orderItems:' . $orderItemQueries->getBasicColumnNames(),
                    'orderItems.product:' . $productQueries->getBasicColumnNames(),
                    'orderItems.product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                    'orderItems.product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                    'orderItems.promoters:' . $promoterQueries->getBasicColumnNames(),
                    'orderItems.promoters.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                    'payments:' . $orderPaymentQueries->getBasicColumnNames(),
                    'payments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
                )
                ->findOrFail($orderId);
        }

        return Order::query()
            ->select(...$this->getBasicColumns())
            ->with(
                'member:' . $memberQueries->getColumnNamesForOrderReport(),
                'member.primaryMemberAddress:' . $memberAddressQueries->getBasicColumnNames(),
                'orderItems:' . $orderItemQueries->getBasicColumnNames(),
                'orderItems.product:' . $productQueries->getBasicColumnNames(),
                'orderItems.product.color:' . $colorQueries->getBasicColumnNames(),
                'orderItems.product.size:' . $sizeQueries->getBasicColumnNames(),
                'orderItems.promoters:' . $promoterQueries->getBasicColumnNames(),
                'orderItems.promoters.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'payments:' . $orderPaymentQueries->getBasicColumnNames(),
                'payments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
            )
            ->findOrFail($orderId);
    }

    public function getOrderDetailsForReceipt(int $orderId): Order
    {
        $productQueries = resolve(ProductQueries::class);
        $memberQueries = resolve(MemberQueries::class);
        $paymentTypeQueries = resolve(PaymentTypeQueries::class);
        $orderPaymentQueries = resolve(OrderPaymentQueries::class);
        $orderItemQueries = resolve(OrderItemQueries::class);
        $promoterQueries = resolve(PromoterQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $orderItemUnitQueries = resolve(OrderItemUnitQueries::class);
        $brandQueries = resolve(BrandQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $companyQueries = resolve(CompanyQueries::class);
        $mediaQueries = resolve(MediaQueries::class);
        $storeManagerQueries = resolve(StoreManagerQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $boxProductQueries = resolve(BoxProductQueries::class);
        $packageTypeQueries = resolve(PackageTypeQueries::class);
        $memberAddressQueries = resolve(MemberAddressQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);
        $cityQueries = resolve(CityQueries::class);

        if (config('app.product_variant')) {
            return Order::select(...$this->getBasicColumns())->with(
                'member:' . $memberQueries->getBasicColumnNamesForOrderReport(),
                'member.primaryMemberAddress:' . $memberAddressQueries->getBasicColumnNames(),
                'orderItems:' . $orderItemQueries->getBasicColumnNames(),
                'orderItems.boxProduct:' . $boxProductQueries->getBasicColumnNames(),
                'orderItems.boxProduct.packageType:' . $packageTypeQueries->getBasicColumnNames(),
                'orderItems.orderItemUnits:' . $orderItemUnitQueries->getBasicColumnNames(),
                'orderItems.product:' . $productQueries->getBasicColumnNames(),
                'orderItems.product.brand:' . $brandQueries->getBasicColumnNames(),
                'orderItems.product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                'orderItems.product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                'orderItems.promoters:' . $promoterQueries->getBasicColumnNames(),
                'orderItems.promoters.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'payments:' . $orderPaymentQueries->getBasicColumnNames(),
                'payments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
                'storeManager:' . $storeManagerQueries->getEmployeeIdColumnNames(),
                'location:' . $locationQueries->getBasicColumnNamesForAdminSaleReports(),
                'location.city:' . $cityQueries->getBasicColumnNames(),
                'location.company:' . $companyQueries->getBasicColumnNamesForOrderReports(),
                'location.company.media:' . $mediaQueries->getBasicColumnNames(),
                'storeManager.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
            )->findOrFail($orderId);
        }

        return Order::select(...$this->getBasicColumns())->with(
            'member:' . $memberQueries->getBasicColumnNamesForOrderReport(),
            'member.primaryMemberAddress:' . $memberAddressQueries->getBasicColumnNames(),
            'orderItems:' . $orderItemQueries->getBasicColumnNames(),
            'orderItems.boxProduct:' . $boxProductQueries->getBasicColumnNames(),
            'orderItems.boxProduct.packageType:' . $packageTypeQueries->getBasicColumnNames(),
            'orderItems.orderItemUnits:' . $orderItemUnitQueries->getBasicColumnNames(),
            'orderItems.product:' . $productQueries->getBasicColumnNames(),
            'orderItems.product.brand:' . $brandQueries->getBasicColumnNames(),
            'orderItems.product.color:' . $colorQueries->getBasicColumnNames(),
            'orderItems.product.size:' . $sizeQueries->getBasicColumnNames(),
            'orderItems.promoters:' . $promoterQueries->getBasicColumnNames(),
            'orderItems.promoters.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
            'payments:' . $orderPaymentQueries->getBasicColumnNames(),
            'payments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
            'storeManager:' . $storeManagerQueries->getEmployeeIdColumnNames(),
            'location:' . $locationQueries->getBasicColumnNamesForAdminSaleReports(),
            'location.city:' . $cityQueries->getBasicColumnNames(),
            'location.company:' . $companyQueries->getBasicColumnNamesForOrderReports(),
            'location.company.media:' . $mediaQueries->getBasicColumnNames(),
            'storeManager.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
        )->findOrFail($orderId);
    }

    public function getOrderDetailsForReport(array $filterData, int $companyId): Collection
    {
        $memberQueries = resolve(MemberQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $orderItemQueries = resolve(OrderItemQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);

        if (config('app.product_variant')) {
            return Order::select(...$this->getBasicColumns())
                ->with([
                    'location' => $locationQueries->filterByCompanyAndTypeId($companyId, LocationTypes::STORE->value),
                    'member:' . $memberQueries->getBasicColumnNames(),
                    'orderItems' => function ($query) use (
                        $filterData,
                        $orderItemQueries,
                        $productQueries,
                        $productVariantValueQueries,
                        $attributeQueries,
                        $masterProductQueries
                    ): void {
                        $columns = explode(',', $orderItemQueries->getBasicColumnNames());
                        $query->select(...$columns)
                            ->with([
                                'product:' . $productQueries->getBasicColumnNames(),
                                'product.masterProduct:' . $masterProductQueries->getBasicColumnsForInventory(),
                                'product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                                'product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                            ])
                            ->when(null !== $filterData['product_id'], function ($query) use ($filterData): void {
                                $query->where('product_id', $filterData['product_id']);
                            })
                            ->when(null !== $filterData['article_number'], function ($query) use ($filterData): void {
                                $query->whereHas('product.masterProduct', function ($query) use ($filterData): void {
                                    $query->where('article_number', $filterData['article_number']);
                                });
                            })
                            ->when(
                                array_key_exists(
                                    'product_collection_id',
                                    $filterData
                                ) && null !== $filterData['product_collection_id'],
                                function ($query) use ($filterData): void {
                                    $query->whereIn('product_id', function ($query) use ($filterData): void {
                                        $query->select('product_id')
                                            ->from('product_collection_products')
                                            ->where(
                                                'product_collection_id',
                                                (int) $filterData['product_collection_id']
                                            );
                                    });
                                }
                            );
                    },
                ])
                ->when(null !== $filterData['store_manager_id'], function ($query) use ($filterData): void {
                    $query->where('store_manager_id', $filterData['store_manager_id']);
                })
                ->when(null !== $filterData['product_id'], function ($query) use ($filterData): void {
                    $query->whereHas('orderItems', function ($query) use ($filterData): void {
                        $query->where('product_id', $filterData['product_id']);
                    });
                })
                ->when(
                    array_key_exists(
                        'product_collection_id',
                        $filterData
                    ) && null !== $filterData['product_collection_id'],
                    function ($query) use ($filterData): void {
                        $query->whereHas('orderItems', function ($query) use ($filterData): void {
                            $query->whereIn('product_id', function ($query) use ($filterData): void {
                                $query->select('product_id')
                                    ->from('product_collection_products')
                                    ->where('product_collection_id', (int) $filterData['product_collection_id']);
                            });
                        });
                    }
                )
                ->when(null !== $filterData['article_number'], function ($query) use ($filterData): void {
                    $query->whereHas('orderItems', function ($query) use ($filterData): void {
                        $query->whereHas('product.masterProduct', function ($query) use ($filterData): void {
                            $query->where('article_number', $filterData['article_number']);
                        });
                    });
                })
                ->when(null !== $filterData['location_id'], function ($query) use ($filterData): void {
                    $query->where('location_id', $filterData['location_id']);
                })
                ->where('created_at', '>=', CommonFunctions::addStartTime($filterData['date_range'][0]))
                ->where('created_at', '<=', CommonFunctions::addEndTime($filterData['date_range'][1]))
                ->get();
        }

        return Order::select(...$this->getBasicColumns())
            ->with([
                'location' => $locationQueries->filterByCompanyAndTypeId($companyId, LocationTypes::STORE->value),
                'member:' . $memberQueries->getBasicColumnNames(),
                'orderItems' => function ($query) use (
                    $filterData,
                    $orderItemQueries,
                    $productQueries,
                    $colorQueries,
                    $sizeQueries
                ): void {
                    $columns = explode(',', $orderItemQueries->getBasicColumnNames());
                    $query->select(...$columns)
                        ->with([
                            'product:' . $productQueries->getBasicColumnNames(),
                            'product.color:' . $colorQueries->getBasicColumnNames(),
                            'product.size:' . $sizeQueries->getBasicColumnNames(),
                        ])
                        ->when(null !== $filterData['product_id'], function ($query) use ($filterData): void {
                            $query->where('product_id', $filterData['product_id']);
                        })
                        ->when(null !== $filterData['article_number'], function ($query) use ($filterData): void {
                            $query->whereHas('product', function ($query) use ($filterData): void {
                                $query->where('article_number', $filterData['article_number']);
                            });
                        })
                        ->when(
                            array_key_exists(
                                'product_collection_id',
                                $filterData
                            ) && null !== $filterData['product_collection_id'],
                            function ($query) use ($filterData): void {
                                $query->whereIn('product_id', function ($query) use ($filterData): void {
                                    $query->select('product_id')
                                        ->from('product_collection_products')
                                        ->where('product_collection_id', (int) $filterData['product_collection_id']);
                                });
                            }
                        );
                },
            ])
            ->when(null !== $filterData['store_manager_id'], function ($query) use ($filterData): void {
                $query->where('store_manager_id', $filterData['store_manager_id']);
            })
            ->when(null !== $filterData['product_id'], function ($query) use ($filterData): void {
                $query->whereHas('orderItems', function ($query) use ($filterData): void {
                    $query->where('product_id', $filterData['product_id']);
                });
            })
            ->when(
                array_key_exists('product_collection_id', $filterData) && null !== $filterData['product_collection_id'],
                function ($query) use ($filterData): void {
                    $query->whereHas('orderItems', function ($query) use ($filterData): void {
                        $query->whereIn('product_id', function ($query) use ($filterData): void {
                            $query->select('product_id')
                                ->from('product_collection_products')
                                ->where('product_collection_id', (int) $filterData['product_collection_id']);
                        });
                    });
                }
            )
            ->when(null !== $filterData['article_number'], function ($query) use ($filterData): void {
                $query->whereHas('orderItems', function ($query) use ($filterData): void {
                    $query->whereHas('product', function ($query) use ($filterData): void {
                        $query->where('article_number', $filterData['article_number']);
                    });
                });
            })
            ->when(null !== $filterData['location_id'], function ($query) use ($filterData): void {
                $query->where('location_id', $filterData['location_id']);
            })
            ->where('created_at', '>=', CommonFunctions::addStartTime($filterData['date_range'][0]))
            ->where('created_at', '<=', CommonFunctions::addEndTime($filterData['date_range'][1]))
            ->get();
    }

    public function getByLocationWithPaymentsFilterByDates(
        int $locationId,
        ?string $lastStoreDayCloseClosedAtDate,
    ): Collection {
        $orderItemQueries = resolve(OrderItemQueries::class);

        return Order::query()
            ->select(...$this->getBasicColumns())
            ->with(['orderItems:' . $orderItemQueries->getBasicColumnNames()])
            ->where('location_id', $locationId)
            ->whereNull('store_day_close_id')
            ->when($lastStoreDayCloseClosedAtDate, function ($query) use ($lastStoreDayCloseClosedAtDate): void {
                $query->where('created_at', '>=', $lastStoreDayCloseClosedAtDate)
                    ->where('created_at', '<=', now()->format('Y-m-d H:i:s'));
            })
            ->get();
    }

    public function updateLocationDayCloseId(array $orderIds, int $storeDayCloseId): void
    {
        foreach ($orderIds as $orderId) {
            $order = Order::select('id', 'store_day_close_id')
                ->find($orderId);

            if (! $order instanceof Order) {
                return;
            }

            $order->update([
                'store_day_close_id' => $storeDayCloseId,
            ]);
        }
    }

    public function getOfflineOrderId(): string
    {
        return 'id,receipt_number,happened_at';
    }

    public function loadOrderItems(Order $order): Order
    {
        $orderItemQueries = new OrderItemQueries();

        return $order->load('orderItems:' . $orderItemQueries->getBasicColumnNames());
    }

    public function getById(int $orderId): Order
    {
        return Order::select($this->getBasicColumns())
            ->findOrFail($orderId);
    }

    public function updateStatus(Order $order, OrderECommerceStatusData $orderECommerceStatusData): void
    {
        $order->status = OrderStatus::getCaseWithName($orderECommerceStatusData->status);
        $order->save();
    }

    public function getByIdWithItemsAndStore(
        ?string $shipmateOrderNumber = null,
        ?int $orderId = null,
        ?int $externalOrderId = null,
        ?string $trackingNumber = null,
        ?int $saleChannelId = null,
    ): ?Order {
        $orderItemQueries = resolve(OrderItemQueries::class);
        $orderChannelReferenceQueries = resolve(OrderChannelReferenceQueries::class);

        return Order::select('id', 'location_id', 'status', 'type_id')
            ->with(['orderItems:' . $orderItemQueries->getBasicColumnNames()])
            ->when(
                $shipmateOrderNumber,
                fn ($query, $shipmateOrderNumber) => $query->where('shipment_order_number', $shipmateOrderNumber)
            )
            ->when($orderId, fn ($query, $orderId) => $query->orWhere('id', $orderId))
            ->when(
                $trackingNumber,
                fn ($query, $trackingNumber) => $query->orWhere('tracking_number', $trackingNumber)
            )
            ->when(
                null !== $externalOrderId && null !== $saleChannelId,
                fn ($query) => $query->orWhereHas(
                    'orderChannelReference',
                    $orderChannelReferenceQueries->filterByExternalOrderIdAndSalesChannelId(
                        (int) $externalOrderId,
                        (int) $saleChannelId
                    )
                )
            )
            ->first();
    }

    public function getByIds(array $orderIds): Collection
    {
        return Order::select('id', 'status')
            ->whereIntegerInRaw('id', $orderIds)
            ->get();
    }

    public function statusUpdate(Order $order, OrderStatus $status): void
    {
        $order->status = $status;
        $order->save();
    }

    public function getCountByIdsAndStatus(array $orderIds, OrderStatus $status): int
    {
        return Order::select('id')
            ->whereIntegerInRaw('id', $orderIds)
            ->where('status', $status)
            ->count();
    }

    public function getBasicColumnsForMarketPlaceOrder(): string
    {
        return 'id,receipt_number,member_id,type_id,channel_id,bill_reference_number,status,created_at';
    }

    public function updateTrackingDetails(OrderTrackingDetailsData $orderTrackingDetailsData, int $orderId): bool
    {
        $order = $this->getById($orderId);

        $order->tracking_number = $orderTrackingDetailsData->tracking_number;
        $order->courier_name = $orderTrackingDetailsData->courier_name;
        $order->tracking_url = $orderTrackingDetailsData->tracking_url;
        $order->shipment_order_number = $orderTrackingDetailsData->shipment_order_number;
        $order->save();

        return true;
    }

    public function digitalInvoiceUpdate(int $orderId): void
    {
        $order = Order::select('id', 'digital_invoice_submitted')
            ->where('digital_invoice_submitted', false)
            ->findOrFail($orderId);

        $order->update([
            'digital_invoice_submitted' => true,
        ]);
    }

    public function accepted(int $orderId): void
    {
        $order = $this->getById($orderId);
        $order->status = OrderStatus::ACCEPTED;
        $order->save();
    }

    public function cancelled(int $orderId): void
    {
        $order = $this->getById($orderId);
        $order->status = OrderStatus::CANCELLED;
        $order->save();

        if (OrderChannels::B2B_ORDERS !== $order->channel_id) {
            $this->deductInventoryLoyaltyPointAndVoucher($order);
        }
    }

    public function readyForPickup(int $orderId): void
    {
        $order = $this->getById($orderId);
        $order->status = OrderStatus::READY_FOR_PICKUP;
        $order->save();
    }

    public function getByIdsWithLoadRelationsForShipment(array $orderIds): Collection
    {
        $memberQueries = resolve(MemberQueries::class);
        $companyQueries = resolve(CompanyQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $cityQueries = resolve(CityQueries::class);
        $orderAddressQueries = resolve(OrderAddressQueries::class);

        return
            Order::select(...$this->getBasicColumns())
            ->whereIntegerInRaw('id', $orderIds)
            ->with([
                'billingAddress:' . $orderAddressQueries->getBasicColumnsInString(),
                'billingAddress.city:' . $cityQueries->getBasicColumnNames(),
                'billingAddress.country:id,name,iso2',
                'shippingAddress:' . $orderAddressQueries->getBasicColumnsInString(),
                'shippingAddress.city:' . $cityQueries->getBasicColumnNames(),
                'shippingAddress.country:id,name,iso2',
                'member:' . $memberQueries->getBasicColumnNames(),
                'location:' . $locationQueries->getColumnsForShipment(),
                'location.company:' . $companyQueries->getBasicColumnNamesForAdminSaleReports(),
                'location.country:id,name,iso2',
                'location.city:' . $cityQueries->getBasicColumnNames(),
            ])
            ->get();
    }

    public function updateMember(int $oldMemberId, int $newMemberId): void
    {
        $orders = Order::query()
            ->select('id', 'member_id')
            ->where('member_id', $oldMemberId)
            ->get();

        foreach ($orders as $order) {
            $order->member_id = $newMemberId;
            $order->save();
        }
    }

    public function getBasicColumnForDigitalInvoice(): Closure
    {
        return fn ($query) => $query->select('id', 'receipt_number', 'digital_invoice_number');
    }

    public function markAsAccepted(int $orderPickingListId): void
    {
        $orders = $this->getOrdersByOrderPickingListId($orderPickingListId);

        foreach ($orders as $order) {
            $order->status = OrderStatus::ACCEPTED->value;
            $order->save();
        }
    }

    public function markAsReadyForPickup(int $orderPickingListId): void
    {
        $orders = $this->getOrdersByOrderPickingListId($orderPickingListId);

        foreach ($orders as $order) {
            $order->status = OrderStatus::READY_FOR_PICKUP->value;
            $order->save();
        }
    }

    public function getOrderForOrderIntegration(int $orderId): Order
    {
        $orderItemQueries = resolve(OrderItemQueries::class);
        $memberQueries = resolve(MemberQueries::class);
        $cityQueries = resolve(CityQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $orderAddressQueries = resolve(OrderAddressQueries::class);
        $locationQueries = resolve(LocationQueries::class);

        return Order::query()
            ->select(...$this->getBasicColumns())
            ->with(
                'member:' . $memberQueries->getBasicColumnNames(),
                'orderItems:' . $orderItemQueries->getBasicColumnNames(),
                'orderItems.product:' . $productQueries->getBasicColumnNames(),
                'shippingAddress:' . $orderAddressQueries->getBasicColumnsInString(),
                'shippingAddress.city:' . $cityQueries->getBasicColumnNames(),
                'shippingAddress.country:id,name,iso2',
                'shippingAddress.state:id,name',
                'location:' . $locationQueries->getColumnsForShipment(),
                'location.country:id,name,iso2',
                'location.city:' . $cityQueries->getBasicColumnNames(),
                'location.state:id,name',
            )
            ->findOrFail($orderId);
    }

    public function getByReceiptNumber(string $receiptNumber): Order
    {
        return Order::select('id', 'status')
            ->where('receipt_number', $receiptNumber)
            ->firstOrFail();
    }

    public function getChannelReferenceByOrderId(int $orderId): ?OrderChannelReference
    {
        return OrderChannelReference::query()
            ->select('id', 'external_order_id', 'order_id')
            ->where('order_id', $orderId)
            ->first();
    }

    public function getPaginatedOrders(array $filterData, bool $isOnlyEcommerce): LengthAwarePaginator
    {
        return $this->commonOrderQuery((int) $filterData['sale_channel_id'])
            ->when($isOnlyEcommerce, function ($query): void {
                $query->where('channel_id', OrderChannels::E_COMMERCE->value);
            }, function ($query): void {
                $query->whereNot('channel_id', OrderChannels::E_COMMERCE->value);
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            })
            ->paginate($filterData['per_page']);
    }

    public function getOrderByIdAndSaleChannelIdWithRelation(int $orderId, int $saleChannelId): Order
    {
        $orderItemQueries = resolve(OrderItemQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $companyQueries = resolve(CompanyQueries::class);
        $countryQueries = resolve(CountryQueries::class);
        $currencyQueries = resolve(CurrencyQueries::class);
        $orderPaymentQueries = resolve(OrderPaymentQueries::class);
        $paymentTypeQueries = resolve(PaymentTypeQueries::class);

        return Order::query()
            ->select(
                'id',
                'receipt_number',
                'total_tax_amount',
                'cart_discount_amount',
                'item_discount_amount',
                'total_discount_amount',
                'total_amount_paid',
                'delivery_charges',
                'status',
                'notes',
                'happened_at',
                'location_id'
            )
            ->with(
                'orderItems:' . $orderItemQueries->getBasicColumnNames(),
                'orderItems.product:' . $productQueries->getColumnNameAndId(),
                'location:' . $locationQueries->getNameColumnName(),
                'location.company:' . $companyQueries->getBasicColumnNames(),
                'location.company.defaultCountry:' . $countryQueries->getColumnId(),
                'location.company.defaultCountry.currency:' . $currencyQueries->getBasicColumnNames(),
                'payments:' . $orderPaymentQueries->getBasicColumnNames(),
                'payments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
            )
            ->where('sale_channel_id', $saleChannelId)
            ->findOrFail($orderId);
    }

    private function getOrdersByOrderPickingListId(int $orderPickingListId): Collection
    {
        $orderPickingListItemQueries = resolve(OrderPickingListItemQueries::class);

        return Order::query()
            ->select('id', 'status')
            ->where('status', OrderStatus::PACKING->value)
            ->whereHas(
                'orderPickingListItems',
                $orderPickingListItemQueries->filterByOrderPickingListId($orderPickingListId)
            )
            ->get();
    }

    private function deductInventoryLoyaltyPointAndVoucher(Order $order): void
    {
        $order = $this->loadRelations($order);

        /** @var OrderStatus $orderStatus */
        $orderStatus = $order->status;

        /** @var SaleChannel|null $saleChannel */
        $saleChannel = $order->saleChannel;

        if (! $saleChannel instanceof SaleChannel) {
            return;
        }

        $ecommerceOrderInventoryService = resolve(EcommerceOrderInventoryService::class);

        /** @var array $saleChannelSelectedStatuses */
        $saleChannelSelectedStatuses = $saleChannel->saleChannelInventoryRollbackOrderStatus->pluck(
            'order_status'
        )->toArray();

        if ($saleChannel->getInventoryDeductOrderStatus()->name === $orderStatus->name) {
            $ecommerceOrderInventoryService->deductInventory($order, $saleChannel);
        }

        if (
            in_array($orderStatus->value, $saleChannelSelectedStatuses)
        ) {
            $ecommerceOrderInventoryService->rollBackInventory($order, $saleChannel);

            $ecommerceOrderInventoryService->checkAndRevertLoyaltyPoints($order);
            $ecommerceOrderInventoryService->revertUsedLoyaltyPoints($order);

            $ecommerceOrderInventoryService->checkAndRevertVouchersGenerated($order->id, $order->location_id);
            $ecommerceOrderInventoryService->checkAndRevertUsedVoucher($order->id, $order->location_id);
        }
    }

    public function getOrdersUnitsSold(int $productId, int $locationId, string $date): Collection
    {
        return Order::select(
            'orders.id',
            DB::raw('SUM(CASE WHEN order_items.total_price_paid = 0 THEN quantity ELSE 0 END) as foc_units_sold'),
            DB::raw('SUM(CASE WHEN order_items.total_price_paid != 0 THEN quantity ELSE 0 END) as units_sold'),
            DB::raw('SUM(order_items.total_price_paid) as total_price_paid')
        )
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->whereDate('orders.happened_at', '=', $date)
            ->where('orders.location_id', $locationId)
            ->where('products.id', $productId)
            ->get();
    }

    private function commonOrderQuery(int $saleChannelId): Builder
    {
        $orderItemQueries = resolve(OrderItemQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $companyQueries = resolve(CompanyQueries::class);
        $countryQueries = resolve(CountryQueries::class);
        $currencyQueries = resolve(CurrencyQueries::class);
        $orderPaymentQueries = resolve(OrderPaymentQueries::class);
        $paymentTypeQueries = resolve(PaymentTypeQueries::class);

        return Order::query()
            ->select(
                'id',
                'receipt_number',
                'total_tax_amount',
                'cart_discount_amount',
                'item_discount_amount',
                'total_discount_amount',
                'total_amount_paid',
                'delivery_charges',
                'status',
                'notes',
                'happened_at',
                'location_id'
            )->with(
                'orderItems:' . $orderItemQueries->getBasicColumnNames(),
                'orderItems.product:' . $productQueries->getColumnNameAndId(),
                'location:' . $locationQueries->getNameColumnName(),
                'location.company:' . $companyQueries->getBasicColumnNames(),
                'location.company.defaultCountry:' . $countryQueries->getColumnId(),
                'location.company.defaultCountry.currency:' . $currencyQueries->getBasicColumnNames(),
                'payments:' . $orderPaymentQueries->getBasicColumnNames(),
                'payments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
            )->where('sale_channel_id', $saleChannelId);
    }
}

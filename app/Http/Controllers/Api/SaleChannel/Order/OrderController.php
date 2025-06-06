<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\SaleChannel\Order;

use App\Domains\Common\Jobs\AutomationJob;
use App\Domains\Inventory\Services\EcommerceOrderInventoryService;
use App\Domains\Order\DataObjects\OrderECommerceData;
use App\Domains\Order\DataObjects\OrderECommerceStatusData;
use App\Domains\Order\DataObjects\OrdersDataForApi;
use App\Domains\Order\DataObjects\OrderTrackingDetailsData;
use App\Domains\Order\Enums\OrderStatus;
use App\Domains\Order\OrderQueries;
use App\Domains\Order\Resources\OrderECommerceResource;
use App\Domains\Order\Resources\PaginatedOrderResource;
use App\Domains\Order\Services\CheckOrderEcommerceDetailsService;
use App\Domains\Order\Services\SaveOrderEcommerceDetailsService;
use App\Domains\OrderAddress\DataObjects\EcommerceOrderAddressData;
use App\Domains\OrderAddress\OrderAddressQueries;
use App\Domains\OrderChannelReference\OrderChannelReferenceQueries;
use App\Domains\SaleChannel\Enums\SaleChannelTypes;
use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\Order;
use App\Models\SaleChannel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class OrderController extends Controller
{
    public function __construct(
        protected OrderQueries $orderQueries
    ) {
    }

    public function saveOrderDetails(OrderECommerceData $orderECommerceData, Request $request): array
    {
        $saleChannel = $request->user();

        if (! $saleChannel instanceof SaleChannel) {
            abort(401, 'You are not authenticated.');
        }

        $checkOrderEcommerceDetailsService = resolve(CheckOrderEcommerceDetailsService::class);
        $orderECommerceData = $checkOrderEcommerceDetailsService->checkValidMember($saleChannel, $orderECommerceData);

        [$orderItems, $products] = $checkOrderEcommerceDetailsService->orderItemMapping(
            $saleChannel,
            $orderECommerceData
        );

        $checkOrderEcommerceDetailsService->setDetails($orderECommerceData, $products, $orderItems, $saleChannel);

        $checkOrderEcommerceDetailsService->checkRequestDetails();

        $member = $checkOrderEcommerceDetailsService->member;

        DB::beginTransaction();
        try {
            $saveOrderEcommerceDetailsService = resolve(SaveOrderEcommerceDetailsService::class);

            $order = $saveOrderEcommerceDetailsService->saveDetails(
                $checkOrderEcommerceDetailsService,
                $saleChannel->getDefaultLocationId(),
                $member?->id,
            );

            DB::commit();

            if ($order) {
                /** @var Member $member */
                $member = $order->member;

                AutomationJob::dispatch($member);
            }

            return [
                'order' => $order instanceof Order ? new OrderECommerceResource($order) : null,
            ];
        } catch (Throwable $throwable) {
            Log::error('Placing Order Error', [
                'Orders' => $throwable->getMessage(),
                'Full error' => [$throwable],
            ]);

            DB::rollBack();

            abort(412, 'An error occurred. Please try again.');
        }
    }

    public function updateStatus(OrderECommerceStatusData $orderECommerceStatusData, Request $request): array
    {
        $saleChannel = $request->user();

        if (! $saleChannel instanceof SaleChannel) {
            abort(401, 'You are not authenticated.');
        }

        $order = $this->orderQueries->getByIdWithItemsAndStore(
            $orderECommerceStatusData->shipment_order_number,
            $orderECommerceStatusData->order_id,
            $orderECommerceStatusData->external_order_id,
            $orderECommerceStatusData->tracking_number,
            $saleChannel->id
        );
        if (! $order instanceof Order) {
            abort(412, 'Order not found.');
        }

        if ($order->getStatus() instanceof OrderStatus && $order->getStatus()->name === $orderECommerceStatusData->status) {
            abort(412, 'This status for this order has already been set.');
        }

        if ($order->getStatus() instanceof OrderStatus && ! OrderStatus::isValidTransition(
            $order->getStatus(),
            $orderECommerceStatusData->status
        )) {
            abort(412, 'Invalid status transition.');
        }

        /** @var array $saleChannelSelectedStatuses */
        $saleChannelSelectedStatuses = $saleChannel->saleChannelInventoryRollbackOrderStatus->pluck(
            'order_status'
        )->toArray();

        /** @var array $orderStatuses */
        $orderStatuses = [
            OrderStatus::CANCELLED->value,
            OrderStatus::DECLINED->value,
            OrderStatus::RETURNED->value,
            OrderStatus::UNDELIVERED->value,
        ];

        $order = $this->orderQueries->loadRelations($order);

        if ($saleChannel->getInventoryDeductOrderStatus()->name === $orderECommerceStatusData->status) {
            $ecommerceOrderInventoryService = resolve(EcommerceOrderInventoryService::class);
            $ecommerceOrderInventoryService->deductInventory($order, $saleChannel);
        }

        if (
            in_array(
                OrderStatus::getValueByCaseName($orderECommerceStatusData->status),
                $saleChannelSelectedStatuses
            ) &&
            in_array(OrderStatus::getValueByCaseName($orderECommerceStatusData->status), $orderStatuses)
        ) {
            $ecommerceOrderInventoryService = resolve(EcommerceOrderInventoryService::class);
            $ecommerceOrderInventoryService->rollBackInventory($order, $saleChannel);

            $ecommerceOrderInventoryService->checkAndRevertLoyaltyPoints($order);
            $ecommerceOrderInventoryService->revertUsedLoyaltyPoints($order);

            $ecommerceOrderInventoryService->checkAndRevertVouchersGenerated($order->id, $order->location_id);
            $ecommerceOrderInventoryService->checkAndRevertUsedVoucher($order->id, $order->location_id);
        }

        $this->orderQueries->updateStatus($order, $orderECommerceStatusData);

        return [
            'message' => 'order status update successfully.',
        ];
    }

    public function updateOrderTrackingDetails(
        OrderTrackingDetailsData $orderTrackingDetailsData,
        Request $request,
        int $orderId
    ): array {
        $saleChannel = $request->user();

        if (! $saleChannel instanceof SaleChannel) {
            abort(401, 'You are not authenticated.');
        }

        $order = $this->orderQueries->updateTrackingDetails($orderTrackingDetailsData, $orderId);

        $message = 'Order tracking details update ' . ($order ? 'successful.' : 'unsuccessful.');

        return [
            'message' => $message,
        ];
    }

    public function getStatuses(Request $request): array
    {
        $saleChannel = $request->user();

        if (! $saleChannel instanceof SaleChannel) {
            abort(401, 'You are not authenticated.');
        }

        return [
            'statuses' => OrderStatus::getList(),
        ];
    }

    public function updateOrderAddress(EcommerceOrderAddressData $ecommerceOrderAddressData, Request $request): void
    {
        $saleChannel = $request->user();

        if (! $saleChannel instanceof SaleChannel) {
            abort(401, 'You are not authenticated.');
        }

        $orderChannelReferenceQueries = resolve(OrderChannelReferenceQueries::class);
        $orderRecord = $orderChannelReferenceQueries->getRecordByExternalOrderId(
            $ecommerceOrderAddressData->order_id,
            $saleChannel->id
        );

        if (! $orderRecord) {
            abort(404, 'Order not found.');
        }

        $orderAddressQueries = resolve(OrderAddressQueries::class);
        $orderAddressQueries->updateOrderAddress($ecommerceOrderAddressData, $orderRecord->order_id);
    }

    public function getOrderIds(string $externalOrderId): array
    {
        $orderChannelReferenceQueries = resolve(OrderChannelReferenceQueries::class);

        return [
            'order_ids' => $orderChannelReferenceQueries->getOrderIdsByExternalOrderId($externalOrderId),
        ];
    }

    public function getPaginatedOrders(Request $request, OrdersDataForApi $ordersDataForApi): array
    {
        $saleChannel = $request->user();

        if (! $saleChannel instanceof SaleChannel) {
            abort(401, 'You are not authenticated.');
        }

        $filterData = [
            'page' => $ordersDataForApi->page,
            'per_page' => $ordersDataForApi->per_page,
            'sort_by' => $ordersDataForApi->sort_by,
            'sort_direction' => $ordersDataForApi->sort_direction,
            'sale_channel_id' => $saleChannel->id,
        ];

        $isOnlyEcommerce = $saleChannel->type_id->value === SaleChannelTypes::ECOMMERCE->value;
        $orders = $this->orderQueries->getPaginatedOrders($filterData, $isOnlyEcommerce);

        return [
            'orders' => PaginatedOrderResource::collection($orders),
            'total_records' => $orders->total(),
            'last_page' => $orders->lastPage(),
            'current_page' => $orders->currentPage(),
            'per_page' => $orders->perPage(),
        ];
    }

    public function getOrderDetailsById(Request $request, int $orderId): array
    {
        $saleChannel = $request->user();

        if (! $saleChannel instanceof SaleChannel) {
            abort(401, 'You are not authenticated.');
        }

        $order = $this->orderQueries->getOrderByIdAndSaleChannelIdWithRelation($orderId, $saleChannel->id);

        return [
            'order' => new PaginatedOrderResource($order),
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Courier;

use App\Domains\Courier\CourierQueries;
use App\Domains\Courier\Enums\CourierTypes;
use App\Domains\OnlineOrderTransaction\OnlineOrderTransactionQueries;
use App\Domains\Order\Enums\OrderStatus;
use App\Domains\Order\OrderQueries;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NinjaVanCourierController extends Controller
{
    public function updateStatus(Request $request): void
    {
        Log::channel('online_order_status_update')->info('online order status update', [$request]);

        $courierQueries = resolve(CourierQueries::class);
        $courier = $courierQueries->getByTypeId(CourierTypes::NINJA_VAN->value);

        if (! $courier) {
            return;
        }

        $data = $request->getContent();

        $orderRefNo = $request->shipper_order_ref_no;

        /** @var string $status */
        $status = $request->status;

        /** @var string $hmacHeader */
        $hmacHeader = $request->header('X-Hmac-Signature');

        $calculatedHmac = base64_encode(hash_hmac('sha256', $data, $courier->client_secret, true));

        if (hash_equals($calculatedHmac, $hmacHeader)) {
            $this->updateOrderStatus($orderRefNo, $status);
        }
    }

    private function updateOrderStatus(string $orderRefNo, string $status, ?array $response = []): void
    {
        $orderQueries = resolve(OrderQueries::class);
        $order = $orderQueries->getByReceiptNumber($orderRefNo);

        /** @var OrderStatus $orderStatus */
        $orderStatus = $order->status;

        $oldStatus = $orderStatus;
        $newStatus = $orderStatus;

        if ('Pending Pickup' === $status) {
            $newStatus = OrderStatus::READY_FOR_PICKUP;
        }

        if ('Picked Up' === $status) {
            $newStatus = OrderStatus::OUT_FOR_DELIVERY;
        }

        if ('Delivered' === $status) {
            $newStatus = OrderStatus::DELIVERED;
        }

        if ('Cancelled' === $status) {
            $newStatus = OrderStatus::CANCELLED;
        }

        if ('Returned to Sender' === $status) {
            $newStatus = OrderStatus::RETURNED;
        }

        if ($oldStatus === $newStatus) {
            return;
        }

        $orderQueries->statusUpdate($order, $newStatus);

        $this->addOnlineOrderTransaction($order->id, $oldStatus->value, $newStatus->value, $response);
    }

    private function addOnlineOrderTransaction(
        int $orderId,
        int $oldStatus,
        int $newStatus,
        ?array $response = []
    ): void {
        $onlineOrderTransactionQueries = resolve(OnlineOrderTransactionQueries::class);
        $onlineOrderTransactionQueries->addNew($orderId, $oldStatus, $newStatus, $response);
    }
}

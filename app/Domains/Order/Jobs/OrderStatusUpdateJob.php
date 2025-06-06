<?php

declare(strict_types=1);

namespace App\Domains\Order\Jobs;

use App\Domains\Common\Enums\WebhookUrls;
use App\Domains\Courier\CourierQueries;
use App\Domains\Courier\Enums\CourierTypes;
use App\Domains\Order\Enums\OrderChannels;
use App\Domains\Order\Enums\OrderStatus;
use App\Domains\Order\OrderQueries;
use App\Domains\OrderIntegration\Jobs\OrderIntegrationJob;
use App\Domains\OrderIntegration\OrderIntegrationQueries;
use App\Domains\SaleChannel\Enums\SaleChannelTypes;
use App\Domains\SaleChannel\SaleChannelQueries;
use App\Models\Courier;
use App\Models\Order;
use App\Models\OrderChannelReference;
use App\Models\SaleChannel;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class OrderStatusUpdateJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public function __construct(
        protected int $orderId,
    ) {
    }

    public function handle(): void
    {
        $orderQueries = resolve(OrderQueries::class);
        $order = $orderQueries->getById($this->orderId);

        $courierQueries = resolve(CourierQueries::class);
        $courier = $courierQueries->getByTypeId(CourierTypes::NINJA_VAN->value);

        if (
            in_array($order->channel_id, [
                OrderChannels::E_COMMERCE,
                OrderChannels::TIKTOK,
                OrderChannels::SHOPEE,
                OrderChannels::E_COMMERCE_WEBSITE,
                OrderChannels::MOBILE_APPS_ANDROID,
                OrderChannels::MOBILE_APPS_IOS,
            ])
            && OrderStatus::READY_FOR_PICKUP === $order->status
            && $courier
        ) {
            $orderIntegrationQueries = resolve(OrderIntegrationQueries::class);
            $orderIntegrationId = $orderIntegrationQueries->addNew($order->id, $courier->id);
            OrderIntegrationJob::dispatch($orderIntegrationId, $order->id);
        }

        if (! $order->sale_channel_id) {
            return;
        }

        if (! $this->isWebhookCall($order)) {
            return;
        }

        $saleChannelQueries = resolve(SaleChannelQueries::class);
        $saleChannel = $saleChannelQueries->getByIdAndStatus($order->sale_channel_id);

        if ($saleChannel->getType()->value === SaleChannelTypes::WEBSPERT_ECOMMERCE->value) {
            return;
        }

        /** @var OrderChannelReference $orderChannelReference */
        $orderChannelReference = $order->orderChannelReference;

        Log::channel('e_commerce')->info('e-commerce webhook order status started', [
            'start time of the webhook call for the order status' => Carbon::now()->format('Y-m-d H:i:s'),
            'order id: ' . $order->getKey(),
        ]);

        try {
            $webhookUrl = $this->getWebhookUrls($order, $saleChannel);

            if (! $webhookUrl) {
                return;
            }

            $postData = $this->getPostData($order, $orderChannelReference, $courier);

            Http::withHeaders([
                'Authorization' => 'Bearer ' . $saleChannel->secret,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->timeout(config('services.http_time_out'))->post($webhookUrl, $postData);
        } catch (Throwable $throwable) {
            Log::channel('e_commerce')->error('e-commerce webhook order status failed', [
                'Error message' => $throwable->getMessage(),
                'Error code' => $throwable->getCode(),
                'File' => $throwable->getFile(),
                'Line' => $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
        }

        Log::channel('e_commerce')->info('e-commerce webhook order status ended', [
            'end time of the webhook call for the order status' => Carbon::now()->format('Y-m-d H:i:s'),
            'order id: ' . $order->getKey(),
        ]);
    }

    public function isWebhookCall(Order $order): bool
    {
        return OrderStatus::ACCEPTED === $order->status ||
            OrderStatus::PACKING === $order->status ||
            OrderStatus::READY_FOR_PICKUP === $order->status ||
            OrderStatus::OUT_FOR_DELIVERY === $order->status ||
            OrderStatus::DELIVERED === $order->status ||
            OrderStatus::CANCELLED === $order->status ||
            OrderStatus::DECLINED === $order->status ||
            OrderStatus::RETURNED === $order->status ||
            OrderStatus::REFUNDED === $order->status ||
            OrderStatus::UNDELIVERED === $order->status;
    }

    public function getWebhookUrls(Order $order, SaleChannel $saleChannel): ?string
    {
        $saleChannelWebhookUrl = null;

        if (OrderStatus::ACCEPTED === $order->status) {
            $saleChannelWebhookUrl = $saleChannel->saleChannelWebhookUrls->firstWhere(
                'webhook_url_type_id',
                WebhookUrls::ORDER_STATUS_ACCEPTED->value
            );
        }

        if (OrderStatus::READY_FOR_PICKUP === $order->status) {
            $saleChannelWebhookUrl = $saleChannel->saleChannelWebhookUrls->firstWhere(
                'webhook_url_type_id',
                WebhookUrls::ORDER_STATUS_READY_FOR_PICKUP->value
            );
        }

        if (OrderStatus::DELIVERED === $order->status) {
            $saleChannelWebhookUrl = $saleChannel->saleChannelWebhookUrls->firstWhere(
                'webhook_url_type_id',
                WebhookUrls::ORDER_STATUS_DELIVERED->value
            );
        }

        if (OrderStatus::CANCELLED === $order->status) {
            $saleChannelWebhookUrl = $saleChannel->saleChannelWebhookUrls->firstWhere(
                'webhook_url_type_id',
                WebhookUrls::ORDER_STATUS_CANCELLED->value
            );
        }

        if (OrderStatus::DECLINED === $order->status) {
            $saleChannelWebhookUrl = $saleChannel->saleChannelWebhookUrls->firstWhere(
                'webhook_url_type_id',
                WebhookUrls::ORDER_STATUS_DECLINED->value
            );
        }

        if (null === $saleChannelWebhookUrl) {
            $saleChannelWebhookUrl = $saleChannel->saleChannelWebhookUrls->firstWhere(
                'webhook_url_type_id',
                WebhookUrls::ORDER_STATUS_UPDATE->value
            );
        }

        return $saleChannelWebhookUrl?->url;
    }

    public function getPostData(
        Order $order,
        OrderChannelReference $orderChannelReference,
        ?Courier $courier = null
    ): array {
        return [
            'order_id' => $orderChannelReference->external_order_id,
            'status' => $order->status,
            'carrier_title' => $courier instanceof Courier ? $courier->name : null,
            'track_number' => $order->tracking_number,
        ];
    }
}

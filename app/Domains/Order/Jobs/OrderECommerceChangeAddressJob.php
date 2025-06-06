<?php

declare(strict_types=1);

namespace App\Domains\Order\Jobs;

use App\Domains\Common\Enums\WebhookUrls;
use App\Domains\Order\Enums\OrderStatus;
use App\Domains\Order\OrderQueries;
use App\Domains\OrderAddress\OrderAddressQueries;
use App\Domains\SaleChannel\Enums\SaleChannelTypes;
use App\Domains\SaleChannel\SaleChannelQueries;
use App\Models\City;
use App\Models\Order;
use App\Models\OrderAddress;
use App\Models\SaleChannel;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class OrderECommerceChangeAddressJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public function __construct(
        protected int $orderAddressId,
    ) {
    }

    public function handle(): void
    {
        $orderAddressQueries = resolve(OrderAddressQueries::class);
        $orderAddress = $orderAddressQueries->getRelationRecordsById($this->orderAddressId);

        /** @var Order $order */
        $order = $orderAddress->order;
        $orderQueries = resolve(OrderQueries::class);
        $orderChannelReference = $orderQueries->getChannelReferenceByOrderId($order->getKey());

        if (! $orderChannelReference) {
            Log::channel('e_commerce')->info('e-commerce order address change skipped', [
                'order address id' => $orderAddress->getKey(),
                'reason' => 'Order channel reference not found',
            ]);

            return;
        }

        $externalOrderId = $orderChannelReference->external_order_id;

        if (! $order->sale_channel_id) {
            return;
        }

        if (! $this->shouldCallECommerce($order)) {
            return;
        }

        $saleChannelQueries = resolve(SaleChannelQueries::class);
        $saleChannel = $saleChannelQueries->getByIdAndStatus($order->sale_channel_id);

        if ($saleChannel->getType()->value === SaleChannelTypes::WEBSPERT_ECOMMERCE->value) {
            return;
        }

        Log::channel('e_commerce')->info('e-commerce webhook order address change started', [
            'start time of the webhook call for the order address change' => Carbon::now()->format('Y-m-d H:i:s'),
            'order Address id: ' . $orderAddress->getKey(),
        ]);

        try {
            $webhookUrl = $this->getWebhookUrls($saleChannel);

            if (! $webhookUrl) {
                return;
            }

            $postData = $this->prepareData($orderAddress, (int) $externalOrderId);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $saleChannel->secret,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->timeout(config('services.http_time_out'))->post($webhookUrl, $postData);

            if ($response->successful()) {
                $responseData = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

                Log::channel('e_commerce')->info('Response: Order Address Update in E-Commerce', [
                    'response' => $responseData,
                ]);
            } else {
                Log::channel('e_commerce')->info('Response: Error on Order Address Update in E-Commerce');
            }
        } catch (Throwable $throwable) {
            Log::channel('e_commerce')->error('e-commerce webhook order address update failed', [
                'Error message' => $throwable->getMessage(),
                'Error code' => $throwable->getCode(),
                'File' => $throwable->getFile(),
                'Line' => $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
        }

        Log::channel('e_commerce')->info('e-commerce webhook order address change ended', [
            'end time of the webhook call for the order address change' => Carbon::now()->format('Y-m-d H:i:s'),
            'order address id: ' . $orderAddress->getKey(),
        ]);
    }

    private function prepareData(OrderAddress $orderAddress, int $externalOrderId): array
    {
        /** @var ?City $city */
        $city = $orderAddress->city;

        $cityName = $orderAddress->city_name;
        if (null !== $city) {
            $cityName = $city->name;
        }

        return [
            'order_id' => $externalOrderId,
            'first_name' => $orderAddress->first_name,
            'last_name' => $orderAddress->last_name,
            'phone' => $orderAddress->phone,
            'address_line_1' => $orderAddress->address_line_1,
            'address_line_2' => $orderAddress->address_line_2,
            'type_id' => $orderAddress->type_id->value,
            'city' => $cityName,
            'area_code' => $orderAddress->area_code,
        ];
    }

    private function getWebhookUrls(SaleChannel $saleChannel): ?string
    {
        $saleChannelWebhookUrl = $saleChannel->saleChannelWebhookUrls->firstWhere(
            'webhook_url_type_id',
            WebhookUrls::ORDER_ADDRESS_UPDATE->value
        );

        return $saleChannelWebhookUrl?->url;
    }

    private function shouldCallECommerce(Order $order): bool
    {
        return OrderStatus::ACCEPTED === $order->status ||
            OrderStatus::PLACED === $order->status;
    }
}

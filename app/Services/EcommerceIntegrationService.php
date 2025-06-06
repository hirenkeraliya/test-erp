<?php

declare(strict_types=1);

namespace App\Services;

use App\Domains\Order\Services\OrderShipmentService;
use App\Domains\SaleChannel\SaleChannelQueries;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\ProductChannelReference;
use App\Models\SaleChannel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class EcommerceIntegrationService
{
    public function updateExternalProductStockForEcommerce(
        Inventory $inventory,
        ProductChannelReference $productChannelReference,
        string $saleChannelWebhookUrl,
    ): void {
        /** @var Carbon $updatedAt */
        $updatedAt = $inventory->updated_at;

        Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->timeout(config('services.http_time_out'))->post($saleChannelWebhookUrl, [
            'id' => $productChannelReference->external_product_id,
            'stock' => $inventory->stock,
            'updated_at' => $updatedAt->format('Y-m-d H:i:s'),
        ]);
    }

    public function updateProductStockForEcommerce(
        array $inventoryData,
        string $saleChannelWebhookUrl,
        SaleChannel $saleChannel
    ): void {
        Http::withHeaders([
            'Authorization' => 'Bearer ' . $saleChannel->secret,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->timeout(config('services.http_time_out'))->post($saleChannelWebhookUrl, $inventoryData);
    }

    public function createShipment(Order $order): void
    {
        $saleChannelQueries = resolve(SaleChannelQueries::class);
        $orderShipmentService = resolve(OrderShipmentService::class);

        /** @var int $saleChannelId */
        $saleChannelId = $order->sale_channel_id;
        $saleChannel = $saleChannelQueries->getByIdAndStatus($saleChannelId);

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->timeout(config('services.http_time_out'))->post(
                $saleChannel->url. '/create-shipment',
                [$orderShipmentService->prepareShipmentData($order)]
            );

            if ($response->successful()) {
                return;
            }
        } catch (Throwable $throwable) {
            Log::channel('sale_channel_shipment')->error('Card creation in lite card failed', [
                'Error message' => $throwable->getMessage(),
                'Error code' => $throwable->getCode(),
                'File' => $throwable->getFile(),
                'Line' => $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
        }
    }
}

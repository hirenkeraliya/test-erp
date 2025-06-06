<?php

declare(strict_types=1);

namespace App\Domains\OrderChannelReference;

use App\Models\OrderChannelReference;
use Closure;
use Illuminate\Support\Collection;

class OrderChannelReferenceQueries
{
    public function addNew(int $orderId, int $saleChannelId, string|int $externalOrderId): void
    {
        OrderChannelReference::create([
            'order_id' => $orderId,
            'sale_channel_id' => $saleChannelId,
            'external_order_id' => $externalOrderId,
        ]);
    }

    public function getBasicNames(): string
    {
        return 'id,order_id,sale_channel_id,external_order_id';
    }

    public function filterByExternalOrderIdAndSalesChannelId(int $externalOrderId, int $saleChannelId): Closure
    {
        return function ($query) use ($externalOrderId, $saleChannelId): void {
            $query->where('external_order_id', $externalOrderId)
                    ->where('sale_channel_id', $saleChannelId);
        };
    }

    public function getRecordByExternalOrderId(int $externalOrderId, int $saleChannelId): ?OrderChannelReference
    {
        return OrderChannelReference::select('order_id')
            ->where('external_order_id', $externalOrderId)
            ->where('sale_channel_id', $saleChannelId)
            ->first();
    }

    public function getOrderIdsByExternalOrderId(string $externalOrderId): Collection
    {
        return OrderChannelReference::query()->select('order_id')->where('external_order_id', $externalOrderId)->pluck(
            'order_id'
        );
    }

    public function getExternalOrderIdByOrderId(int $orderId): ?OrderChannelReference
    {
        return OrderChannelReference::query()
            ->select('order_id', 'external_order_id')
            ->where('order_id', $orderId)
            ->first();
    }

    public function getExternalOrderIdByOrderIdAndSaleChannelId(
        int $orderId,
        int $saleChannelId
    ): ?OrderChannelReference {
        return OrderChannelReference::query()
            ->select('order_id', 'external_order_id')
            ->where('order_id', $orderId)
            ->where('sale_channel_id', $saleChannelId)
            ->first();
    }
}

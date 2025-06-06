<?php

declare(strict_types=1);

namespace App\Domains\ShippingZoneChannelReference;

use App\Models\ShippingZoneChannelReference;

class ShippingZoneChannelReferenceQueries
{
    public function addNew(array $record): void
    {
        ShippingZoneChannelReference::create($record);
    }

    public function getByShippingZoneIdAndSaleChannelId(
        int $shippingZoneId,
        int $saleChannelId
    ): ?ShippingZoneChannelReference {
        return ShippingZoneChannelReference::select('id', 'shipping_zone_id', 'external_shipping_zone_id')
            ->where('shipping_zone_id', $shippingZoneId)
            ->where('sale_channel_id', $saleChannelId)
            ->first();
    }

    public function getByShippingZoneIds(array $shippingZoneIds, int $saleChannelId): array
    {
        return ShippingZoneChannelReference::select('external_shipping_zone_id')
            ->where('sale_channel_id', $saleChannelId)
            ->whereIn('shipping_zone_id', $shippingZoneIds)
            ->pluck('external_shipping_zone_id')
            ->toArray();
    }
}

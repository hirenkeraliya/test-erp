<?php

declare(strict_types=1);

namespace App\Domains\AttributeChannelReference;

use App\Models\AttributeChannelReference;
use Illuminate\Support\Collection;

class AttributeChannelReferenceQueries
{
    public function addNew(array $attributeExternalRecords): AttributeChannelReference
    {
        return AttributeChannelReference::create($attributeExternalRecords);
    }

    public function getByAttributeIdAndSaleChannelId(int $attributeId, int $saleChannelId): ?AttributeChannelReference
    {
        return AttributeChannelReference::select('id', 'sale_channel_id', 'attribute_id', 'external_attribute_id')
            ->where('attribute_id', $attributeId)
            ->where('sale_channel_id', $saleChannelId)
            ->first();
    }

    public function getByAttributeIdAndSaleChannelIds(array $attributeIds, int $saleChannelId): Collection
    {
        return AttributeChannelReference::select('id', 'sale_channel_id', 'attribute_id', 'external_attribute_id')
            ->whereIntegerInRaw('attribute_id', $attributeIds)
            ->where('sale_channel_id', $saleChannelId)
            ->get();
    }

    public function getBasicColumnNames(): string
    {
        return 'id,sale_channel_id,attribute_id,external_attribute_id';
    }

    public function deleteById(int $id): void
    {
        AttributeChannelReference::where('id', $id)->delete();
    }
}

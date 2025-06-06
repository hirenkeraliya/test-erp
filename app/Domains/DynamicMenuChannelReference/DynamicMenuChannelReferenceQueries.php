<?php

declare(strict_types=1);

namespace App\Domains\DynamicMenuChannelReference;

use App\Models\DynamicMenuChannelReference;

class DynamicMenuChannelReferenceQueries
{
    public function getByMenuIdAndSaleChannelId(int $menuId, int $saleChannelId): ?DynamicMenuChannelReference
    {
        return DynamicMenuChannelReference::select('id', 'sale_channel_id', 'dynamic_menu_id', 'external_menu_id')
            ->where('dynamic_menu_id', $menuId)
            ->where('sale_channel_id', $saleChannelId)
            ->first();
    }

    public function addNew(array $menuExternalIdRecords): void
    {
        DynamicMenuChannelReference::create($menuExternalIdRecords);
    }
}

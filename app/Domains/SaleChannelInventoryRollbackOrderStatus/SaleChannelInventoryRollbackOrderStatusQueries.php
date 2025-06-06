<?php

declare(strict_types=1);

namespace App\Domains\SaleChannelInventoryRollbackOrderStatus;

use App\Models\SaleChannel;
use App\Models\SaleChannelInventoryRollbackOrderStatus;

class SaleChannelInventoryRollbackOrderStatusQueries
{
    public function addNew(array $orderStatusData): SaleChannelInventoryRollbackOrderStatus
    {
        return SaleChannelInventoryRollbackOrderStatus::create($orderStatusData);
    }

    public function deleteInventoryRollbackOrder(SaleChannel $saleChannel): void
    {
        $saleChannel->saleChannelInventoryRollbackOrderStatus()->delete();
    }

    public function getBasicColumns(): string
    {
        return 'id,sale_channel_id,order_status';
    }
}

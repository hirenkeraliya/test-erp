<?php

declare(strict_types=1);

namespace App\Domains\ExternalPurchaseOrderPartialReceiveItemBatch;

use App\Models\ExternalPurchaseOrderPartialReceiveItemBatch;

class ExternalPurchaseOrderPartialReceiveItemBatchQueries
{
    public function addNew(
        array $externalPurchaseOrderPartialReceiveItemBatchData
    ): ExternalPurchaseOrderPartialReceiveItemBatch {
        return ExternalPurchaseOrderPartialReceiveItemBatch::create($externalPurchaseOrderPartialReceiveItemBatchData);
    }

    public function getBasicColumnNames(): string
    {
        return 'id,external_purchase_order_partial_receive_item_id,batch_number,expiry_date,quantity,notes';
    }

    public function deleteByExternalPurchaseOrderPartialReceiveItem(
        int $externalPurchaseOrderPartialReceiveItemId
    ): void {
        ExternalPurchaseOrderPartialReceiveItemBatch::where(
            'external_purchase_order_partial_receive_item_id',
            $externalPurchaseOrderPartialReceiveItemId
        )->delete();
    }
}

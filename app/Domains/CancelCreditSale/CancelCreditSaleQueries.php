<?php

declare(strict_types=1);

namespace App\Domains\CancelCreditSale;

use App\Domains\Sale\DataObjects\CancelCreditSaleData;
use App\Models\CancelCreditSale;

class CancelCreditSaleQueries
{
    public function addNew(CancelCreditSaleData $cancelCreditSaleData, int $saleId): CancelCreditSale
    {
        $saleData = $cancelCreditSaleData->all();
        unset($saleData['passcode']);
        unset($saleData['happened_at']);
        unset($saleData['store_manager_authorization_code']);
        $saleData['sale_id'] = $saleId;

        return CancelCreditSale::create($saleData);
    }

    public function getBasicColumnNames(): string
    {
        return 'id,sale_id,store_manager_id,reason';
    }
}

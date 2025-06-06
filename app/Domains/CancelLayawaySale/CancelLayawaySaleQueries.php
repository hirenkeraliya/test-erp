<?php

declare(strict_types=1);

namespace App\Domains\CancelLayawaySale;

use App\Domains\Sale\DataObjects\CancelLayawaySaleData;
use App\Models\CancelLayawaySale;

class CancelLayawaySaleQueries
{
    public function addNew(CancelLayawaySaleData $cancelLayawaySaleData, int $saleId): CancelLayawaySale
    {
        $saleData = $cancelLayawaySaleData->all();
        unset($saleData['passcode']);
        unset($saleData['happened_at']);
        unset($saleData['store_manager_authorization_code']);
        $saleData['sale_id'] = $saleId;

        return CancelLayawaySale::create($saleData);
    }

    public function getBasicColumnNames(): string
    {
        return 'id,sale_id,store_manager_id,reason';
    }

    public function getSaleIdColumn(): string
    {
        return 'id,sale_id';
    }
}

<?php

declare(strict_types=1);

namespace App\Domains\VoidSale;

use App\Domains\VoidSale\DataObjects\PosVoidSaleData;
use App\Domains\VoidSaleReason\VoidSaleReasonQueries;
use App\Models\VoidSale;
use Closure;

class VoidSaleQueries
{
    public function addNew(PosVoidSaleData $posVoidSaleData, int $saleId, int $companyId): VoidSale
    {
        $lastVoidSaleNumber = $this->getLastVoidSaleNumber($companyId);

        $voidSaleData = $posVoidSaleData->all();
        unset($voidSaleData['passcode']);
        unset($voidSaleData['store_manager_authorization_code']);
        $voidSaleData['sale_id'] = $saleId;
        $voidSaleData['void_sale_number'] = $lastVoidSaleNumber + 1;

        return VoidSale::create($voidSaleData);
    }

    public function getLastVoidSaleNumber(int $companyId): int
    {
        $voidSaleReasonQueries = resolve(VoidSaleReasonQueries::class);

        $voidSale = VoidSale::select('void_sale_number')
            ->whereHas('voidSaleReason', $voidSaleReasonQueries->filterByCompany($companyId))
            ->latest()
            ->first();

        return $voidSale && $voidSale->void_sale_number ? (int) $voidSale->void_sale_number : 0;
    }

    public function getBasicColumnNames(): string
    {
        return 'id,sale_id,void_sale_reason_id,voided_by_store_manager_id';
    }

    public function getColumnsForListPage(): string
    {
        return 'id,sale_id,void_sale_reason_id,voided_by_store_manager_id,void_sale_number';
    }

    public function getSelectIdColumn(): Closure
    {
        return fn ($query) => $query->select('id');
    }

    public function getSelectIdAndVoleSaleNumberColumns(): Closure
    {
        return fn ($query) => $query->select('id', 'void_sale_number', 'created_at');
    }

    public function getSelectIdAndSaleIdColumns(): Closure
    {
        return fn ($query) => $query->select('id', 'void_sale_number', 'sale_id');
    }
}

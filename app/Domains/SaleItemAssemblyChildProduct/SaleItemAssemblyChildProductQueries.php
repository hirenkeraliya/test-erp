<?php

declare(strict_types=1);

namespace App\Domains\SaleItemAssemblyChildProduct;

use App\Models\SaleItemAssemblyChildProduct;

class SaleItemAssemblyChildProductQueries
{
    public function addNew(array $productBoxRecord): void
    {
        SaleItemAssemblyChildProduct::create($productBoxRecord);
    }

    public function getBasicColumnNames(): string
    {
        return 'id,sale_item_id,child_product_id,units';
    }
}

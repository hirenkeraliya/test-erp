<?php

declare(strict_types=1);

namespace App\Domains\OrderItemAssemblyChildProduct;

use App\Models\OrderItemAssemblyChildProduct;

class OrderItemAssemblyChildProductQueries
{
    public function addNew(array $productAssemblyRecord): void
    {
        OrderItemAssemblyChildProduct::create($productAssemblyRecord);
    }
}

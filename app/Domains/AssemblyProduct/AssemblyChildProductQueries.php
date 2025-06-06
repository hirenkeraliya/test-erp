<?php

declare(strict_types=1);

namespace App\Domains\AssemblyProduct;

use App\Models\AssemblyChildProduct;
use App\Models\Product;

class AssemblyChildProductQueries
{
    public function addNew(array $productBoxRecord): void
    {
        AssemblyChildProduct::create($productBoxRecord);
    }

    public function deleteAssemblyChildProduct(Product $product): void
    {
        $product->assemblyChildProducts()->delete();
    }

    public function getBasicColumnNames(): string
    {
        return 'id,product_id,child_product_id,units';
    }
}

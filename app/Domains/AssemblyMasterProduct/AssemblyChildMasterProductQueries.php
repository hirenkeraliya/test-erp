<?php

declare(strict_types=1);

namespace App\Domains\AssemblyMasterProduct;

use App\Models\AssemblyChildMasterProduct;
use App\Models\MasterProduct;

class AssemblyChildMasterProductQueries
{
    public function addNew(array $assemblyChildProductRecord): void
    {
        AssemblyChildMasterProduct::create($assemblyChildProductRecord);
    }

    public function deleteAssemblyChildMasterProducts(MasterProduct $masterProduct): void
    {
        $masterProduct->assemblyChildMasterProducts()->delete();
    }

    public function getBasicColumnNames(): string
    {
        return 'id,master_product_id,child_master_product_id,units';
    }
}

<?php

declare(strict_types=1);

use App\Domains\AssemblyMasterProduct\AssemblyChildMasterProductQueries;
use App\Models\AssemblyChildMasterProduct;
use App\Models\MasterProduct;

beforeEach(function (): void {
    $this->assemblyChildMasterProductQueries = new AssemblyChildMasterProductQueries();
});

test('assembly child master product can be added', function (): void {
    $masterProduct = MasterProduct::factory()->create()->id;
    $masterProductA = MasterProduct::factory()->create()->id;

    $assemblyChildMasterProductRecord = AssemblyChildMasterProduct::factory()->make([
        'master_product_id' => $masterProduct,
        'child_master_product_id' => $masterProductA,
        'units' => 20,
    ]);

    $this->assemblyChildMasterProductQueries->addNew($assemblyChildMasterProductRecord->toArray());

    $this->assertDatabaseHas('assembly_child_master_products', [
        'master_product_id' => $assemblyChildMasterProductRecord->master_product_id,
        'child_master_product_id' => $assemblyChildMasterProductRecord->child_master_product_id,
        'units' => $assemblyChildMasterProductRecord->units,
    ]);
});

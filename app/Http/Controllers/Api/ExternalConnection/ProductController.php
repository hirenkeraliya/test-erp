<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\ExternalConnection;

use App\Domains\Inventory\InventoryQueries;
use App\Domains\Product\ProductQueries;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function getProductsByUpc(Request $request): array
    {
        $productQueries = resolve(ProductQueries::class);

        $products = $productQueries->getProductsByUpcForInterCompany($request->upc, (int) $request->company_id);

        return [
            'products' => $products,
        ];
    }

    public function getProductsStockByUpc(Request $request): array
    {
        $upcs = (array) $request->upcs;
        $locationId = (int) $request->location_id;

        $products = [];
        if (count($upcs) <= 0) {
            return [
                'products' => $products,
            ];
        }

        $inventoryQueries = resolve(InventoryQueries::class);

        $inventories = $inventoryQueries->getInventoriesWithProductByProductUpcs($locationId, $upcs);

        $productsWithoutInventories = array_diff($upcs, $inventories->pluck('product.upc')->toArray());

        foreach ($productsWithoutInventories as $productWithoutInventory) {
            $products[] = [
                'upc' => $productWithoutInventory,
                'external_stock' => 0,
                'external_reserved_stock' => 0,
            ];
        }

        foreach ($inventories as $inventory) {
            $products[] = [
                'upc' => $inventory->product->upc,
                'external_stock' => $inventory->stock,
                'external_reserved_stock' => $inventory->reserved_stock,
            ];
        }

        return [
            'products' => $products,
        ];
    }
}

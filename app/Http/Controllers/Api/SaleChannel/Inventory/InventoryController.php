<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\SaleChannel\Inventory;

use App\Domains\Inventory\InventoryQueries;
use App\Domains\Inventory\Resources\EcommerceProductsByStoreListResource;
use App\Http\Controllers\Controller;
use App\Models\SaleChannel;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function __construct(
        protected InventoryQueries $inventoryQueries
    ) {
    }

    public function getStoresByProducts(Request $request): array
    {
        $saleChannel = $request->user();

        if (! $saleChannel instanceof SaleChannel) {
            abort(401, 'You are not authenticated.');
        }

        $validatedData = $request->validate([
            'cart_details' => ['required', 'array'],
            'cart_details.*.product_id' => ['required', 'integer'],
            'cart_details.*.quantity' => ['required', 'integer'],
        ]);

        $inventories = $this->inventoryQueries->getStoresHavingInventoriesByProductIds(
            $saleChannel->getCompanyId(),
            $validatedData['cart_details']
        );

        return [
            'stores' => EcommerceProductsByStoreListResource::collection($inventories),
            'locations' => EcommerceProductsByStoreListResource::collection($inventories),
        ];
    }
}

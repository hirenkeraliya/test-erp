<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Pos;

use App\CommonFunctions;
use App\Domains\Inventory\InventoryQueries;
use App\Domains\Inventory\Resources\ProductInventoriesResource;
use App\Domains\Location\LocationQueries;
use App\Domains\Product\DataObjects\ProductListDataForPos;
use App\Domains\Product\DataObjects\ProductStockForAllStoreDataForPos;
use App\Domains\Product\Jobs\PosProductsZipJob;
use App\Domains\Product\ProductQueries;
use App\Domains\Product\Resources\PosProductListResource;
use App\Domains\Storage\Enums\StorageTypes;
use App\Http\Controllers\Controller;
use App\Models\Cashier;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    /**
     * @return array<string, AnonymousResourceCollection>|array<string, int>
     */
    public function getList(Request $request, ProductListDataForPos $productListDataForPos): array
    {
        $filteredData = [
            'per_page' => $productListDataForPos->per_page,
            'page' => $productListDataForPos->page,
            'search_text' => $productListDataForPos->search_text,
            'after_updated_at' => $productListDataForPos->after_updated_at,
        ];

        /** @var Cashier $cashier */
        $cashier = $request->user();

        if (! $cashier->getCounterUpdateId()) {
            abort(412, 'The counter has not been opened yet.');
        }

        /** @var int $counterUpdateId */
        $counterUpdateId = $cashier->counter_update_id;

        $locationQueries = resolve(LocationQueries::class);
        $location = $locationQueries->getLocationByCountersCounterUpdateId($counterUpdateId);

        $companyId = CommonFunctions::getCashierCompanyId($cashier);

        $productQueries = resolve(ProductQueries::class);
        $lengthAwarePaginator = $productQueries->getList($filteredData, $companyId, $location->id);

        return [
            'products' => PosProductListResource::collection($lengthAwarePaginator),
            'total_records' => $lengthAwarePaginator->total(),
            'last_page' => $lengthAwarePaginator->lastPage(),
            'current_page' => $lengthAwarePaginator->currentPage(),
        ];
    }

    public function getProductStockForAllStores(
        Request $request,
        ProductStockForAllStoreDataForPos $productStockForAllStoreDataForPos
    ): array {
        /** @var Cashier $cashier */
        $cashier = $request->user();

        if (! $cashier->getCounterUpdateId()) {
            abort(412, 'The counter has not been opened yet.');
        }

        $filterData = [
            'product_id' => $productStockForAllStoreDataForPos->product_id,
            'after_updated_at' => $productStockForAllStoreDataForPos->after_updated_at,
        ];

        $companyId = CommonFunctions::getCashierCompanyId($cashier);
        $inventoryQueries = resolve(InventoryQueries::class);
        $productStocks = $inventoryQueries->getInventoryByProductIdWithLocation($filterData, $companyId);

        return [
            'product_stocks' => ProductInventoriesResource::collection($productStocks),
        ];
    }

    public function getProductsZip(Request $request): array
    {
        /** @var Cashier $cashier */
        $cashier = $request->user();

        $companyId = CommonFunctions::getCashierCompanyId($cashier);

        $defaultFileSystem = config('filesystems.default');

        $zipModuleFilePath = $defaultFileSystem === StorageTypes::PUBLIC->value ? 'pos_modules' : 'public/pos_modules';

        $directoryName = $zipModuleFilePath . '/products/' . $companyId;

        $files = Storage::files($directoryName);

        if ([] === $files) {
            PosProductsZipJob::dispatch()->onQueue(config('horizon.default_queue_name'));

            return [
                'message' => 'There doesn`t appear to be a ZIP file at the moment. Please wait a few seconds while the ZIP file is being created.',
            ];
        }

        rsort($files);

        return [
            'product_url' => $defaultFileSystem === StorageTypes::OCI->value ? Storage::temporaryUrl(
                $files[0],
                now()->addMinutes(5)
            ) : url(Storage::url($files[0])),
        ];
    }
}

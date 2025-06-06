<?php

declare(strict_types=1);

namespace App\Domains\PurchaseOrder\Services;

use App\Domains\Batch\BatchQueries;
use App\Domains\ExternalCompany\ExternalCompanyQueries;
use App\Domains\ExternalConnection\ExternalConnectionQueries;
use App\Domains\ExternalLocation\ExternalLocationQueries;
use App\Domains\Inventory\InventoryQueries;
use App\Domains\Inventory\Services\PurchaseOrderInventoryService;
use App\Domains\Location\LocationQueries;
use App\Domains\PackageType\PackageTypeQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\PurchaseOrder\Enums\DashboardPurchaseOrderStatuses;
use App\Domains\PurchaseOrder\Enums\DashboardPurchaseRequestStatuses;
use App\Domains\PurchaseOrder\Enums\OrderTypes;
use App\Domains\PurchaseOrder\Enums\Statuses;
use App\Domains\PurchaseOrder\Exports\PurchaseOrderExport;
use App\Domains\PurchaseOrder\PurchaseOrderQueries;
use App\Domains\PurchaseOrder\Resource\PurchaseOrderListResource;
use App\Domains\PurchaseOrderFulfillment\Enums\FulfillmentStatuses;
use App\Domains\PurchaseOrderFulfillment\PurchaseOrderFulfillmentQueries;
use App\Domains\PurchaseOrderFulfillment\Services\PurchaseOrderFulfillmentService;
use App\Domains\PurchaseOrderFulfillmentItem\PurchaseOrderFulfillmentItemQueries;
use App\Domains\PurchaseOrderFulfillmentItemBatch\PurchaseOrderFulfillmentItemBatchQueries;
use App\Domains\PurchaseOrderFulfillmentTransaction\PurchaseOrderFulfillmentTransactionQueries;
use App\Domains\PurchaseOrderItem\Export\PurchaseOrderItemExport;
use App\Domains\PurchaseOrderItem\PurchaseOrderItemQueries;
use App\Domains\PurchaseOrderItem\Resource\PurchaseOrderItemsResource;
use App\Domains\PurchaseOrderTransaction\PurchaseOrderTransactionQueries;
use App\Domains\Sequence\Enums\SequenceTypes;
use App\Domains\Sequence\SequenceQueries;
use App\Domains\UnitOfMeasureDerivative\UnitOfMeasureDerivativeQueries;
use App\Exceptions\RedirectWithErrorException;
use App\Models\Admin;
use App\Models\Batch;
use App\Models\ExternalCompany;
use App\Models\ExternalConnection;
use App\Models\ExternalLocation;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderFulfillment;
use App\Models\PurchaseOrderFulfillmentItem;
use App\Models\PurchaseOrderItem;
use App\Models\StoreManager;
use App\Models\WarehouseManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PurchaseOrderService
{
    /**
     * @return mixed[]
     */
    public function getStoresAndWarehouses(int $companyId): array
    {
        $locationQueries = resolve(LocationQueries::class);

        $stores = $locationQueries->getStoreWithBasicColumns($companyId);
        $warehouses = $locationQueries->getWithBasicColumnsOfWarehouse($companyId);

        return [$stores, $warehouses];
    }

    public function getProducts(array $productIds, int $companyId): Collection
    {
        $productQueries = resolve(ProductQueries::class);

        return $productQueries->getBatchProductsByIds($productIds, $companyId);
    }

    public function getExternalProducts(int $externalCompanyId, array $upcs): Collection
    {
        $externalCompanyQueries = resolve(ExternalCompanyQueries::class);
        $externalCompany = $externalCompanyQueries->getByIdWithExternalConnection($externalCompanyId);
        if (! $externalCompany) {
            return collect([]);
        }

        /** @var ExternalConnection $externalConnection */
        $externalConnection = $externalCompany->externalConnection;

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->timeout(config('services.http_time_out'))->post(
            $externalConnection->url . '/api/external-connection/get-products-by-upc',
            [
                'token' => $externalConnection->token,
                'upc' => $upcs,
                'company_id' => $externalCompany->external_company_id,
            ]
        );

        if ($response->successful()) {
            $data = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);
            /** @var array $products */
            $products = $data['products'];

            return collect($products);
        }

        Log::error('External Connection', [
            'url' => $externalConnection->url . '/api/external-connection/get-products-by-upc',
            'token' => $externalConnection->token,
            'upc' => $upcs,
            'company_id' => $externalCompany->external_company_id,
            'response' => $response,
        ]);

        return collect([]);
    }

    public function getExternalProductStocks(array $upcs, int $externalCompanyId, int $externalLocationId): Collection
    {
        $externalCompanyQueries = resolve(ExternalCompanyQueries::class);
        $externalCompany = $externalCompanyQueries->getByIdWithExternalConnection($externalCompanyId);
        if (! $externalCompany) {
            return collect([]);
        }

        /** @var ExternalConnection $externalConnection */
        $externalConnection = $externalCompany->externalConnection;

        /** @var Collection $externalLocations */
        $externalLocations = $externalCompany->externalLocations;

        $externalLocation = $externalLocations->where('id', $externalLocationId)->first();
        if (! $externalLocation) {
            return collect([]);
        }

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->timeout(config('services.http_time_out'))->post(
            $externalConnection->url . '/api/external-connection/get-products-stock-by-upc',
            [
                'token' => $externalConnection->token,
                'upcs' => $upcs,
                'company_id' => $externalCompany->external_company_id,
                'location_id' => $externalLocation->external_location_id,
            ]
        );

        if ($response->successful()) {
            $data = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);
            /** @var array $products */
            $products = $data['products'];

            return collect($products);
        }

        Log::error('External Connection', [
            'url' => $externalConnection->url . '/api/external-connection/get-products-by-upc',
            'token' => $externalConnection->token,
            'upc' => $upcs,
            'company_id' => $externalCompany->external_company_id,
            'response' => $response,
        ]);

        return collect([]);
    }

    public function getExternalProductBatch(array $batchNumbers, int $externalCompanyId, string $upc): Collection
    {
        $externalCompanyQueries = resolve(ExternalCompanyQueries::class);
        $externalCompany = $externalCompanyQueries->getByIdWithExternalConnection($externalCompanyId);
        if (! $externalCompany) {
            return collect([]);
        }

        /** @var ExternalConnection $externalConnection */
        $externalConnection = $externalCompany->externalConnection;

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->timeout(config('services.http_time_out'))->post(
            $externalConnection->url . '/api/external-connection/get-product-batch-numbers',
            [
                'token' => $externalConnection->token,
                'batch_details' => $batchNumbers,
                'upc' => $upc,
            ]
        );

        if ($response->successful()) {
            $data = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

            /** @var Collection $batches */
            /* @phpstan-ignore-next-line */
            return collect($data['batches']);
        }

        Log::error('External Connection', [
            'url' => $externalConnection->url . '/api/external-connection/get-product-batch-numbers',
            'token' => $externalConnection->token,
            'upc' => $upc,
            'batch_numbers' => $batchNumbers,
            'response' => $response,
        ]);

        return collect([]);
    }

    public function getExternalBatchInventoryUnits(array $batchNumbers, array $externalDetails, string $upc): Collection
    {
        $externalCompanyQueries = resolve(ExternalCompanyQueries::class);
        $externalCompany = $externalCompanyQueries->getByIdWithExternalConnection(
            $externalDetails['external_company_id']
        );
        if (! $externalCompany) {
            return collect([]);
        }

        $externalLocationQueries = resolve(ExternalLocationQueries::class);
        $externalLocation = $externalLocationQueries->getByIdWithExternalCompanyAndExternalConnection(
            $externalDetails['external_location_id']
        );

        if (! $externalLocation) {
            return collect([]);
        }

        /** @var ExternalConnection $externalConnection */
        $externalConnection = $externalCompany->externalConnection;

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->timeout(config('services.http_time_out'))->post(
            $externalConnection->url . '/api/external-connection/get-inventory-units',
            [
                'token' => $externalConnection->token,
                'batch_details' => $batchNumbers,
                'external_location_id' => $externalLocation->external_location_id,
                'upc' => $upc,
            ]
        );

        if ($response->successful()) {
            $data = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

            /* @phpstan-ignore-next-line */
            return collect($data['batch_inventory_units']);
        }

        Log::error('External Connection', [
            'url' => $externalConnection->url . '/api/external-connection/get-inventory-units',
            'token' => $externalConnection->token,
            'batch_details' => $batchNumbers,
            'external_details' => $externalDetails,
            'upc' => $upc,
            'response' => $response,
        ]);

        return collect([]);
    }

    public function getExternalConnectionByExternalCompanyId(int $externalCompanyId): ExternalConnection
    {
        $externalConnectionQueries = resolve(ExternalConnectionQueries::class);

        return $externalConnectionQueries->getByExternalCompanyId($externalCompanyId);
    }

    public function savePurchaseOrder(array $purchaseOrderData, int $companyId, Collection $products): void
    {
        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);
        $purchaseOrderItemQueries = resolve(PurchaseOrderItemQueries::class);
        $sequenceQueries = resolve(SequenceQueries::class);
        $transferType = $this->prepareTransferType($purchaseOrderData['order_type']);

        $sequence = $sequenceQueries->addNew($purchaseOrderData['location_id'], $transferType);

        $purchaseOrderData['order_number'] = $sequence->getCompleteNumber();

        $purchaseOrderItems = $purchaseOrderData['transfer_items'];
        unset($purchaseOrderData['transfer_items']);
        $purchaseOrderData['company_id'] = $companyId;
        $purchaseOrderData['created_by_company_id'] = $companyId;
        $purchaseOrder = $purchaseOrderQueries->addNew($purchaseOrderData);

        foreach ($purchaseOrderItems as $purchaseOrderItem) {
            /** @var Product $product */
            $product = $products->firstWhere('id', $purchaseOrderItem['product_id']);

            $purchaseOrderItemQueries->addNew([
                'purchase_order_id' => $purchaseOrder->id,
                'product_id' => $purchaseOrderItem['product_id'],
                'quantity' => $purchaseOrderItem['quantity'],
                'purchase_cost' => (float) $product->purchase_cost,
                'unit_of_measure_derivative_id' => $purchaseOrderItem['unit_of_measure_derivative_id'] ?? null,
            ]);
        }

        if ($purchaseOrderData['order_type'] === OrderTypes::TRANSFER_REQUEST->value) {
            $purchaseOrderInventoryService = resolve(PurchaseOrderInventoryService::class);
            $purchaseOrderInventoryService->addInventoryReservedStockForPurchaseOrder($purchaseOrder);
        }
    }

    public function getStocks(PurchaseOrder $purchaseOrder): Collection
    {
        $inventoryQueries = resolve(InventoryQueries::class);

        $purchaseOrderItems = collect($purchaseOrder->getItems());

        return $inventoryQueries->getInventoriesByProductIds(
            $purchaseOrder->location_id,
            $purchaseOrderItems->pluck('product_id')->toArray()
        );
    }

    public function postExternalPurchaseOrder(
        PurchaseOrder $purchaseOrder,
        int $companyId,
        Admin|WarehouseManager|StoreManager|null $user
    ): array {
        /** @var ExternalCompany $externalCompany */
        $externalCompany = $purchaseOrder->externalCompany;

        /** @var ExternalConnection $externalConnection */
        $externalConnection = $externalCompany->externalConnection;

        /** @var ExternalLocation $externalLocation */
        $externalLocation = $purchaseOrder->externalLocation;

        $purchaseOrderItems = $purchaseOrder->items;

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->timeout(config('services.http_time_out'))->post(
            $externalConnection->url . '/api/external-connection/purchase-orders',
            [
                'token' => $externalConnection->token,
                'external_purchase_order_id' => $purchaseOrder->id,
                'external_company_id' => $companyId,
                'external_location_id' => $purchaseOrder->location_id,
                'location_id' => $externalLocation->external_location_id,
                'company_id' => $externalCompany->external_company_id,
                'reference_number' => $purchaseOrder->reference_number,
                'remarks' => $purchaseOrder->remarks,
                'attention' => $purchaseOrder->attention,
                'require_date' => $purchaseOrder->require_date,
                'order_type' => $purchaseOrder->order_type,
                'status' => Statuses::OPENED->value,
                'external_order_number' => $purchaseOrder->order_number,
                'external_username' => $this->getExternalUserName($user),
                'items' => $purchaseOrderItems->map(function (PurchaseOrderItem $purchaseOrderItem): array {
                    /** @var Product $product */
                    $product = $purchaseOrderItem->product;

                    return [
                        'external_purchase_order_item_id' => $purchaseOrderItem->id,
                        'id' => $purchaseOrderItem->external_purchase_order_item_id,
                        'upc' => $product->upc,
                        'quantity' => $purchaseOrderItem->quantity,
                        'rejected_quantity' => $purchaseOrderItem->rejected_quantity,
                        'transferred_quantity' => $purchaseOrderItem->transferred_quantity,
                        'price_per_unit' => $purchaseOrderItem->price_per_unit,
                        'unit_of_measure_derivative' => $purchaseOrderItem->derivative?->name,
                        'remarks' => $purchaseOrderItem->remarks,
                    ];
                })->toArray(),
            ]
        );

        if (! $response->successful()) {
            Log::error('External Connection', [
                'url' => $externalConnection->url . '/api/external-connection/purchase-orders',
                'token' => $externalConnection->token,
                'external_purchase_order_id' => $purchaseOrder->id,
                'external_company_id' => $companyId,
                'external_location_id' => $purchaseOrder->location_id,
                'location_id' => $externalLocation->external_location_id,
                'company_id' => $externalCompany->external_company_id,
                'reference_number' => $purchaseOrder->reference_number,
                'remarks' => $purchaseOrder->remarks,
                'attention' => $purchaseOrder->attention,
                'require_date' => $purchaseOrder->require_date,
                'order_type' => $purchaseOrder->order_type,
                'status' => Statuses::OPENED->value,
                'external_order_number' => $purchaseOrder->order_number,
                'external_username' => $this->getExternalUserName($user),
                'items' => $purchaseOrderItems->map(function (PurchaseOrderItem $purchaseOrderItem): array {
                    /** @var Product $product */
                    $product = $purchaseOrderItem->product;

                    return [
                        'external_purchase_order_item_id' => $purchaseOrderItem->id,
                        'upc' => $product->upc,
                        'quantity' => $purchaseOrderItem->quantity,
                        'rejected_quantity' => $purchaseOrderItem->rejected_quantity,
                        'transferred_quantity' => $purchaseOrderItem->transferred_quantity,
                        'price_per_unit' => $purchaseOrderItem->price_per_unit,
                        'unit_of_measure_derivative' => $purchaseOrderItem->derivative?->name,
                        'remarks' => $purchaseOrderItem->remarks,
                    ];
                })->toArray(),
                'response' => $response,
            ]);

            abort(417, 'An error occurred. Please try again.');
        }

        return json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);
    }

    public function saveExternalPurchaseOrder(array $purchaseOrderData, Collection $products): array
    {
        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);
        $purchaseOrderItemQueries = resolve(PurchaseOrderItemQueries::class);

        $sequenceQueries = resolve(SequenceQueries::class);
        $transferType = $this->prepareTransferType($purchaseOrderData['order_type']);

        $sequence = $sequenceQueries->addNew($purchaseOrderData['location_id'], $transferType);

        $purchaseOrderData['order_number'] = $sequence->getCompleteNumber();

        $purchaseOrderItems = $purchaseOrderData['items'];
        $externalUsername = $purchaseOrderData['external_username'];
        unset($purchaseOrderData['items'], $purchaseOrderData['external_username']);

        $purchaseOrder = $purchaseOrderQueries->addNew($purchaseOrderData);

        $purchaseOrderTransactionQueries = resolve(PurchaseOrderTransactionQueries::class);

        $purchaseOrderTransactionQueries->addNew(
            $purchaseOrder->id,
            null,
            $purchaseOrder->status,
            null,
            $externalUsername
        );

        $response = [];
        $response['purchase_order_id'] = $purchaseOrderData['external_purchase_order_id'];
        $response['external_purchase_order_id'] = $purchaseOrder->id;
        $response['external_order_number'] = $purchaseOrder->order_number;

        foreach ($purchaseOrderItems as $purchaseOrderItem) {
            $product = $products->firstWhere('upc', $purchaseOrderItem['upc']);
            $purchaseOrderItem = $purchaseOrderItemQueries->addNew([
                'purchase_order_id' => $purchaseOrder->id,
                'parent_purchase_order_item_id' => $purchaseOrderItem['parent_purchase_order_item_id'] ?? null,
                'external_purchase_order_item_id' => $purchaseOrderItem['external_purchase_order_item_id'],
                'product_id' => $product->id,
                'purchase_cost' => (float) $product->purchase_cost,
                'quantity' => $purchaseOrderItem['quantity'],
                'rejected_quantity' => $purchaseOrderItem['rejected_quantity'],
                'transferred_quantity' => $purchaseOrderItem['transferred_quantity'],
                'price_per_unit' => $purchaseOrderItem['price_per_unit'],
                'unit_of_measure_derivative_id' => $purchaseOrderItem['unit_of_measure_derivative_id'],
                'remarks' => $purchaseOrderItem['remarks'],
            ]);

            $response['items'][] = [
                'purchase_order_item_id' => $purchaseOrderItem['external_purchase_order_item_id'],
                'external_purchase_order_item_id' => $purchaseOrderItem->id,
            ];
        }

        $purchaseOrderInventoryService = resolve(PurchaseOrderInventoryService::class);
        $purchaseOrderInventoryService->updateTheReservedStockFromPurchaseRequestToSalesOrder($purchaseOrder);

        if ($purchaseOrderData['order_type'] === OrderTypes::PURCHASE_REQUEST->value) {
            $purchaseOrderInventoryService->addInventoryReservedStockForPurchaseOrder($purchaseOrder);
        }

        return $response;
    }

    public function prepareExternalPurchaseOrder(array $purchaseOrderData, string $token): array
    {
        $externalConnectionQueries = resolve(ExternalConnectionQueries::class);
        $externalConnection = $externalConnectionQueries->getByToken($token);

        $externalCompanyQueries = resolve(ExternalCompanyQueries::class);
        $externalCompany = $externalCompanyQueries->getByExternalCompanyId(
            $purchaseOrderData['external_company_id'],
            $externalConnection->id
        );
        $purchaseOrderData['external_company_id'] = $externalCompany->id;

        $externalLocationQueries = resolve(ExternalLocationQueries::class);
        $externalLocation = $externalLocationQueries->getByExternalLocationId(
            $purchaseOrderData['external_location_id'],
            $externalCompany->id
        );

        $purchaseOrderData['external_location_id'] = $externalLocation->id;

        /** @var array $items */
        $items = $purchaseOrderData['items'];
        $productQueries = resolve(ProductQueries::class);
        $products = $productQueries->getProductsByUpcAndCompanyId(
            collect($items)->pluck('upc')->toArray(),
            $purchaseOrderData['company_id']
        );

        foreach ($items as $itemId => $item) {
            $purchaseOrderData['items'][$itemId]['unit_of_measure_derivative_id'] = null;
            if (! array_key_exists('unit_of_measure_derivative', $item)) {
                continue;
            }

            if (! $item['unit_of_measure_derivative']) {
                continue;
            }

            $unitOfMeasureDerivativeQueries = resolve(UnitOfMeasureDerivativeQueries::class);
            $unitOfMeasureDerivative = $unitOfMeasureDerivativeQueries->getByName(
                $item['unit_of_measure_derivative']
            );

            if (! $unitOfMeasureDerivative) {
                abort(417, 'The Unit of Measure Derivative does not match across records.');
            }

            $purchaseOrderData['items'][$itemId]['unit_of_measure_derivative_id'] = $unitOfMeasureDerivative->id;
        }

        return [$products, $purchaseOrderData];
    }

    public function rejectExternalPurchaseOrder(
        PurchaseOrder $purchaseOrder,
        Admin|WarehouseManager|StoreManager|null $user
    ): void {
        /** @var ExternalCompany $externalCompany */
        $externalCompany = $purchaseOrder->externalCompany;

        /** @var ExternalConnection $externalConnection */
        $externalConnection = $externalCompany->externalConnection;

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->timeout(config('services.http_time_out'))->post(
            $externalConnection->url . '/api/external-connection/purchase-orders/reject',
            [
                'token' => $externalConnection->token,
                'purchase_order_id' => $purchaseOrder->external_purchase_order_id,
                'company_id' => $externalCompany->external_company_id,
                'external_username' => $this->getExternalUserName($user),
            ]
        );

        if ($response->successful()) {
            return;
        }

        Log::error('External Connection', [
            'url' => $externalConnection->url . '/api/external-connection/purchase-orders/reject',
            'token' => $externalConnection->token,
            'purchase_order_id' => $purchaseOrder->external_purchase_order_id,
            'company_id' => $externalCompany->external_company_id,
            'external_username' => $this->getExternalUserName($user),
            'response' => $response,
        ]);

        abort(417, 'An error occurred. Please try again.');
    }

    public function saveSalesOrder(PurchaseOrder $purchaseOrder): PurchaseOrder
    {
        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);
        $purchaseOrderItemQueries = resolve(PurchaseOrderItemQueries::class);

        $sequenceQueries = resolve(SequenceQueries::class);
        $transferType = $this->prepareTransferType(OrderTypes::SALES_ORDER->value);

        $sequence = $sequenceQueries->addNew($purchaseOrder->location_id, $transferType);

        $purchaseOrderData = [
            'parent_purchase_order_id' => $purchaseOrder->id,
            'external_company_id' => $purchaseOrder->external_company_id,
            'company_id' => $purchaseOrder->company_id,
            'external_location_id' => $purchaseOrder->external_location_id,
            'location_id' => $purchaseOrder->location_id,
            'reference_number' => $purchaseOrder->reference_number,
            'remarks' => $purchaseOrder->remarks,
            'attention' => $purchaseOrder->attention,
            'require_date' => $purchaseOrder->require_date,
            'order_type' => $this->getOrderType($purchaseOrder->order_type),
            'order_number' => $sequence->getCompleteNumber(),
            'status' => Statuses::APPROVED->value,
        ];

        $salesOrder = $purchaseOrderQueries->addNew($purchaseOrderData);

        foreach ($purchaseOrder->items as $purchaseOrderItem) {
            $purchaseOrderItemQueries->addNew([
                'purchase_order_id' => $salesOrder->id,
                'parent_purchase_order_item_id' => $purchaseOrderItem->id,
                'product_id' => $purchaseOrderItem->product_id,
                'purchase_cost' => (float) $purchaseOrderItem->product?->purchase_cost,
                'quantity' => $purchaseOrderItem->quantity,
                'rejected_quantity' => $purchaseOrderItem->rejected_quantity,
                'transferred_quantity' => $purchaseOrderItem->transferred_quantity,
                'price_per_unit' => $purchaseOrderItem->price_per_unit,
                'remarks' => $purchaseOrderItem->remarks,
                'unit_of_measure_derivative_id' => $purchaseOrderItem->unit_of_measure_derivative_id,
            ]);
        }

        return $salesOrder;
    }

    public function closeExternalPurchaseOrder(
        PurchaseOrder $purchaseOrder,
        Admin|WarehouseManager|StoreManager|null $user
    ): void {
        /** @var ExternalCompany $externalCompany */
        $externalCompany = $purchaseOrder->externalCompany;

        /** @var ExternalConnection $externalConnection */
        $externalConnection = $externalCompany->externalConnection;

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->timeout(config('services.http_time_out'))->post(
            $externalConnection->url . '/api/external-connection/purchase-orders/closed',
            [
                'token' => $externalConnection->token,
                'purchase_order_id' => $purchaseOrder->external_purchase_order_id,
                'company_id' => $externalCompany->external_company_id,
                'external_username' => $this->getExternalUserName($user),
            ]
        );

        if ($response->successful()) {
            return;
        }

        Log::error('External Connection', [
            'url' => $externalConnection->url . '/api/external-connection/purchase-orders/closed',
            'token' => $externalConnection->token,
            'purchase_order_id' => $purchaseOrder->external_purchase_order_id,
            'company_id' => $externalCompany->external_company_id,
            'external_username' => $this->getExternalUserName($user),
            'response' => $response,
        ]);

        abort(417, 'An error occurred. Please try again.');
    }

    public function postExternalSalesOrder(
        PurchaseOrder $purchaseOrder,
        PurchaseOrder $purchaseRequest,
        Admin|WarehouseManager|StoreManager|null $user
    ): void {
        $companyId = $purchaseOrder->company_id;
        $parentPurchaseOrderId = $purchaseRequest->external_purchase_order_id ?? null;

        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);
        $purchaseOrder = $purchaseOrderQueries->getByIdAndCompanyIdWithRelation(
            $purchaseOrder->id,
            $purchaseOrder->company_id
        );

        /** @var ExternalCompany $externalCompany */
        $externalCompany = $purchaseOrder->externalCompany;

        /** @var ExternalConnection $externalConnection */
        $externalConnection = $externalCompany->externalConnection;

        /** @var ExternalLocation $externalLocation */
        $externalLocation = $purchaseOrder->externalLocation;

        $purchaseOrderItems = $purchaseOrder->items;
        $purchaseRequestItems = $purchaseRequest->items;

        $postSaleOrderData = [
            'token' => $externalConnection->token,
            'external_purchase_order_id' => $purchaseOrder->id,
            'external_company_id' => $companyId,
            'created_by_company_id' => $externalCompany->external_company_id,
            'parent_purchase_order_id' => $parentPurchaseOrderId,
            'external_location_id' => $purchaseOrder->location_id,
            'location_id' => $externalLocation->external_location_id,
            'company_id' => $externalCompany->external_company_id,
            'reference_number' => $purchaseOrder->reference_number,
            'external_order_number' => $purchaseOrder->order_number,
            'remarks' => $purchaseOrder->remarks,
            'attention' => $purchaseOrder->attention,
            'require_date' => $purchaseOrder->require_date,
            'order_type' => $this->getOrderType($purchaseOrder->order_type),
            'status' => Statuses::APPROVED->value,
            'external_username' => $this->getExternalUserName($user),
            'items' => $purchaseOrderItems->map(function (PurchaseOrderItem $purchaseOrderItem) use (
                $purchaseRequestItems
            ): array {
                /** @var Product $product */
                $product = $purchaseOrderItem->product;

                return [
                    'external_purchase_order_item_id' => $purchaseOrderItem->id,
                    'parent_purchase_order_item_id' => $purchaseRequestItems->firstWhere(
                        'id',
                        $purchaseOrderItem->parent_purchase_order_item_id
                    )?->external_purchase_order_item_id,
                    'upc' => $product->upc,
                    'quantity' => $purchaseOrderItem->quantity,
                    'rejected_quantity' => $purchaseOrderItem->rejected_quantity,
                    'transferred_quantity' => $purchaseOrderItem->transferred_quantity,
                    'price_per_unit' => $purchaseOrderItem->price_per_unit,
                    'unit_of_measure_derivative' => $purchaseOrderItem->derivative?->name,
                    'remarks' => $purchaseOrderItem->remarks,
                ];
            })->toArray(),
        ];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->timeout(config('services.http_time_out'))->post(
            $externalConnection->url . '/api/external-connection/purchase-orders',
            $postSaleOrderData
        );

        if ($response->successful()) {
            $data = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

            $purchaseOrderQueries->updateExternalPurchaseOrderId(
                $data['purchase_order_id'],
                $data['external_purchase_order_id'],
                $data['external_order_number']
            );

            $purchaseOrderItemQueries = resolve(PurchaseOrderItemQueries::class);

            foreach ($data['items'] as $item) {
                $purchaseOrderItemQueries->updateExternalPurchaseOrderItemId(
                    $item['purchase_order_item_id'],
                    $item['external_purchase_order_item_id']
                );
            }

            return;
        }

        $postSaleOrderData['url'] = $externalConnection->url . '/api/external-connection/purchase-orders';
        $postSaleOrderData['response'] = $response;

        Log::error('External Connection', $postSaleOrderData);

        abort(417, 'An error occurred. Please try again.');
    }

    public function postExternalFulfillment(
        PurchaseOrderFulfillment $purchaseOrderFulfillment,
        Admin|WarehouseManager|StoreManager|null $user
    ): void {
        /** @var PurchaseOrder $purchaseOrder */
        $purchaseOrder = $purchaseOrderFulfillment->purchaseOrder;

        $purchaseOrderItems = $purchaseOrder->items;

        /** @var ExternalCompany $externalCompany */
        $externalCompany = $purchaseOrder->externalCompany;

        /** @var ExternalConnection $externalConnection */
        $externalConnection = $externalCompany->externalConnection;

        $purchaseOrderFulfillmentItems = $purchaseOrderFulfillment->items;

        $postData = [
            'token' => $externalConnection->token,
            'purchase_order_id' => $purchaseOrder->external_purchase_order_id,
            'company_id' => $externalCompany->external_company_id,
            'external_purchase_order_fulfillment_id' => $purchaseOrderFulfillment->id,
            'happened_at' => $purchaseOrderFulfillment->happened_at,
            'delivery_order_number' => $purchaseOrderFulfillment->delivery_order_number,
            'notes' => $purchaseOrderFulfillment->notes,
            'status' => FulfillmentStatuses::OPEN->value,
            'external_username' => $this->getExternalUserName($user),
            'items' => $purchaseOrderFulfillmentItems->map(
                function (PurchaseOrderFulfillmentItem $purchaseOrderFulfillmentItem) use ($purchaseOrderItems): array {
                    /** @var Product $product */
                    $product = $purchaseOrderFulfillmentItem->product;
                    $purchaseOrderItem = $purchaseOrderItems->firstWhere(
                        'id',
                        $purchaseOrderFulfillmentItem->purchase_order_item_id
                    );

                    $itemBatches = $purchaseOrderFulfillmentItem->itemBatches;

                    $packageType = $purchaseOrderFulfillmentItem->packageType;

                    return [
                        'external_purchase_order_fulfillment_item_id' => $purchaseOrderFulfillmentItem->id,
                        'purchase_order_item_id' => $purchaseOrderItem?->external_purchase_order_item_id,
                        'upc' => $product->upc,
                        'transfer_quantity' => $purchaseOrderFulfillmentItem->transfer_quantity,
                        'received_quantity' => $purchaseOrderFulfillmentItem->received_quantity,
                        'package_type' => $packageType?->name,
                        'package_quantity' => $purchaseOrderFulfillmentItem->package_quantity,
                        'package_total_quantity' => $purchaseOrderFulfillmentItem->package_total_quantity,
                        'remarks' => $purchaseOrderFulfillmentItem->remarks,
                        'batch_details' => $product->has_batch ? $this->getBatchDetails($itemBatches) : [],
                    ];
                }
            )->toArray(),
        ];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->timeout(config('services.http_time_out'))->post(
            $externalConnection->url . '/api/external-connection/purchase-order-fulfillment',
            $postData
        );

        if ($response->successful()) {
            $data = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

            $purchaseOrderFulfillmentQueries = resolve(PurchaseOrderFulfillmentQueries::class);
            $purchaseOrderFulfillmentQueries->updateExternalId(
                $data['purchase_order_fulfillment_id'],
                $data['external_purchase_order_fulfillment_id']
            );

            $purchaseOrderFulfillmentItemQueries = resolve(PurchaseOrderFulfillmentItemQueries::class);
            foreach ($data['items'] as $item) {
                $purchaseOrderFulfillmentItemQueries->updateExternalId(
                    $item['purchase_order_fulfillment_item_id'],
                    $item['external_purchase_order_fulfillment_item_id']
                );
            }

            return;
        }

        $postData['url'] = $externalConnection->url . '/api/external-connection/purchase-order-fulfillment';
        $postData['response'] = $response;

        Log::error('External Connection', $postData);

        abort(417, 'An error occurred. Please try again.');
    }

    public function getBatchDetails(Collection $itemBatches): array
    {
        return $itemBatches->map(function ($itemBatch): array {
            /** @var ?Batch $batch */
            $batch = $itemBatch->batch;

            return [
                'batch_id' => $batch instanceof Batch ? $batch->id : 'N/A',
                'batch_number' => $batch instanceof Batch ? $batch->number : 'N/A',
                'expiry_date' => $batch instanceof Batch ? $batch->expiry_date : 'N/A',
                'quantity' => $itemBatch->quantity,
                'received_quantity' => $itemBatch->received_quantity,
                'is_discrepancy' => $itemBatch->is_discrepancy,
            ];
        })->toArray();
    }

    public function prepareExternalFulfillment(array $purchaseOrderFulfillData): Collection
    {
        /** @var array $items */
        $items = $purchaseOrderFulfillData['items'];
        $productQueries = resolve(ProductQueries::class);

        return $productQueries->getActiveProductsByUpc(
            collect($items)->pluck('upc')->toArray(),
            $purchaseOrderFulfillData['company_id']
        );
    }

    public function checkProductNotExist(Collection $products, array $purchaseOrderFulfillData): void
    {
        /** @var array $items */
        $items = $purchaseOrderFulfillData['items'];
        if ($products->count() === collect($items)->pluck('upc')->unique()->count()) {
            return;
        }

        abort(412, 'Some of products are not available in our records.');
    }

    public function saveExternalFulfillment(
        array $purchaseOrderFulfillData,
        Collection $products,
        PurchaseOrder $purchaseOrder
    ): array {
        $purchaseOrderFulfillmentQueries = resolve(PurchaseOrderFulfillmentQueries::class);
        $purchaseOrderFulfillmentItemQueries = resolve(PurchaseOrderFulfillmentItemQueries::class);
        $packageTypeQueries = resolve(PackageTypeQueries::class);
        $purchaseOrderItemQueries = resolve(PurchaseOrderItemQueries::class);

        $purchaseOrderFulfillmentItems = $purchaseOrderFulfillData['items'];
        unset($purchaseOrderFulfillData['items']);

        $purchaseOrderFulfillment = $purchaseOrderFulfillmentQueries->addNew([
            'purchase_order_id' => $purchaseOrderFulfillData['purchase_order_id'],
            'external_purchase_order_fulfillment_id' => $purchaseOrderFulfillData['external_purchase_order_fulfillment_id'],
            'happened_at' => $purchaseOrderFulfillData['happened_at'],
            'notes' => $purchaseOrderFulfillData['notes'],
            'delivery_order_number' => $purchaseOrderFulfillData['delivery_order_number'],
            'status' => $purchaseOrderFulfillData['status'],
        ]);

        $purchaseOrderFulfillmentTransactionQueries = resolve(PurchaseOrderFulfillmentTransactionQueries::class);
        $purchaseOrderFulfillmentTransactionQueries->addNew(
            $purchaseOrderFulfillment->id,
            FulfillmentStatuses::OPEN->value,
            $purchaseOrderFulfillment->status,
            null,
            $purchaseOrderFulfillData['external_username']
        );

        $response = [];
        $response['purchase_order_fulfillment_id'] = $purchaseOrderFulfillData['external_purchase_order_fulfillment_id'];
        $response['external_purchase_order_fulfillment_id'] = $purchaseOrderFulfillment->id;
        $purchaseOrderItems = $purchaseOrder->getItems();
        foreach ($purchaseOrderFulfillmentItems as $purchaseOrderFulfillmentItemData) {
            $product = $products->firstWhere('upc', $purchaseOrderFulfillmentItemData['upc']);

            $packageTypeId = null;
            if ($purchaseOrderFulfillmentItemData['package_type']) {
                $packageType = $packageTypeQueries->fetchOrCreate(
                    $purchaseOrderFulfillmentItemData['package_type'],
                    (int) $purchaseOrderFulfillData['company_id']
                );
                $packageType = $packageType->id;
            }

            $purchaseOrderFulfillmentItem = $purchaseOrderFulfillmentItemQueries->addNew([
                'purchase_order_fulfillment_id' => $purchaseOrderFulfillment->id,
                'purchase_order_item_id' => $purchaseOrderFulfillmentItemData['purchase_order_item_id'],
                'external_purchase_order_fulfillment_item_id' => $purchaseOrderFulfillmentItemData['external_purchase_order_fulfillment_item_id'],
                'product_id' => $product->id,
                'transfer_quantity' => $purchaseOrderFulfillmentItemData['transfer_quantity'],
                'received_quantity' => $purchaseOrderFulfillmentItemData['received_quantity'],
                'package_type_id' => $packageTypeId,
                'package_quantity' => $purchaseOrderFulfillmentItemData['package_quantity'],
                'package_total_quantity' => $purchaseOrderFulfillmentItemData['package_total_quantity'],
                'remarks' => $purchaseOrderFulfillmentItemData['remarks'],
            ]);

            $purchaseOrderItem = $purchaseOrderItems->firstWhere(
                'id',
                $purchaseOrderFulfillmentItemData['purchase_order_item_id']
            );
            $purchaseOrderItemQueries->updateTransferredQuantity(
                $purchaseOrderItem,
                (float) $purchaseOrderFulfillmentItemData['transfer_quantity']
            );

            $this->updateBatches(
                $purchaseOrderFulfillmentItem,
                $purchaseOrderFulfillmentItemData['batch_details'],
                (int) $purchaseOrderFulfillData['company_id'],
            );

            $response['items'][] = [
                'purchase_order_fulfillment_item_id' => $purchaseOrderFulfillmentItemData['external_purchase_order_fulfillment_item_id'],
                'external_purchase_order_fulfillment_item_id' => $purchaseOrderFulfillmentItem->id,
            ];
        }

        return $response;
    }

    public function updateBatches(
        PurchaseOrderFulfillmentItem $purchaseOrderFulfillmentItem,
        array $batchDetails,
        int $companyId,
    ): void {
        $purchaseOrderFulfillmentItemBatchQueries = resolve(PurchaseOrderFulfillmentItemBatchQueries::class);
        $batchQueries = resolve(BatchQueries::class);

        foreach ($batchDetails as $batchDetail) {
            $batchId = $batchQueries->addNewAndGetId(
                [
                    'batch_number' => $batchDetail['batch_number'],
                    'batch_expiry_date' => $batchDetail['expiry_date'],
                    'batch_external_id' => null,
                    'batch_notes' => null,
                ],
                $companyId,
                $purchaseOrderFulfillmentItem->product_id,
            );

            $purchaseOrderFulfillmentItemBatchQueries->addOrUpdateWithQuantity([
                'purchase_order_fulfillment_item_id' => $purchaseOrderFulfillmentItem->id,
                'batch_id' => $batchId,
                'quantity' => $batchDetail['quantity'],
                'received_quantity' => $batchDetail['received_quantity'],
                'is_discrepancy' => $batchDetail['is_discrepancy'],
            ]);
        }
    }

    public function postExternalFulfillmentDiscrepancy(
        PurchaseOrderFulfillment $purchaseOrderFulfillment,
        Admin|WarehouseManager|StoreManager|null $user
    ): void {
        /** @var PurchaseOrder $purchaseOrder */
        $purchaseOrder = $purchaseOrderFulfillment->purchaseOrder;

        $purchaseOrderItems = $purchaseOrder->items;

        /** @var ExternalCompany $externalCompany */
        $externalCompany = $purchaseOrder->externalCompany;

        /** @var ExternalConnection $externalConnection */
        $externalConnection = $externalCompany->externalConnection;

        $purchaseOrderFulfillmentItems = $purchaseOrderFulfillment->items;

        $postData = [
            'token' => $externalConnection->token,
            'purchase_order_id' => $purchaseOrder->external_purchase_order_id,
            'purchase_order_fulfillment_id' => $purchaseOrderFulfillment->external_purchase_order_fulfillment_id,
            'company_id' => $externalCompany->external_company_id,
            'status' => FulfillmentStatuses::DISCREPANCY->value,
            'external_username' => $this->getExternalUserName($user),
            'items' => $purchaseOrderFulfillmentItems->map(
                function (PurchaseOrderFulfillmentItem $purchaseOrderFulfillmentItem) use (
                    $purchaseOrderItems
                ): array {
                    /** @var Product $product */
                    $product = $purchaseOrderFulfillmentItem->product;

                    /** @var PurchaseOrderItem $purchaseOrderItem */
                    $purchaseOrderItem = $purchaseOrderItems->firstWhere(
                        'id',
                        $purchaseOrderFulfillmentItem->purchase_order_item_id
                    );

                    $itemBatches = $purchaseOrderFulfillmentItem->itemBatches;

                    $packageType = $purchaseOrderFulfillmentItem->packageType;

                    return [
                        'external_purchase_order_fulfillment_item_id' => $purchaseOrderFulfillmentItem->id,
                        'purchase_order_fulfillment_item_id' => $purchaseOrderFulfillmentItem->external_purchase_order_fulfillment_item_id,
                        'purchase_order_item_id' => $purchaseOrderItem->external_purchase_order_item_id,
                        'external_purchase_order_item_id' => $purchaseOrderItem->id,
                        'unit_of_measure_derivative' => $purchaseOrderItem->derivative?->name,
                        'upc' => $product->upc,
                        'transfer_quantity' => $purchaseOrderFulfillmentItem->transfer_quantity,
                        'received_quantity' => $purchaseOrderFulfillmentItem->received_quantity,
                        'package_type' => $packageType?->name,
                        'package_quantity' => $purchaseOrderFulfillmentItem->package_quantity,
                        'package_total_quantity' => $purchaseOrderFulfillmentItem->package_total_quantity,
                        'is_extra_item' => $purchaseOrderFulfillmentItem->is_extra_item,
                        'discrepancy_type' => $purchaseOrderFulfillmentItem->discrepancy_type,
                        'remarks' => $purchaseOrderFulfillmentItem->remarks,
                        'discrepancy_proof' => $purchaseOrderFulfillmentItem->getDiskBasedFirstMediaUrl(
                            'discrepancy_proof'
                        ),
                        'batch_details' => $product->has_batch ? $this->getBatchDetails($itemBatches) : [],
                    ];
                }
            )->toArray(),
        ];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->timeout(config('services.http_time_out'))->post(
            $externalConnection->url . '/api/external-connection/purchase-order-fulfillment/discrepancy',
            $postData
        );

        if ($response->successful()) {
            $data = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

            $purchaseOrderFulfillmentItemQueries = resolve(PurchaseOrderFulfillmentItemQueries::class);
            foreach ($data['items'] as $item) {
                $purchaseOrderFulfillmentItemQueries->updateExternalId(
                    $item['purchase_order_fulfillment_item_id'],
                    $item['external_purchase_order_fulfillment_item_id']
                );
            }

            $purchaseOrderItemQueries = resolve(PurchaseOrderItemQueries::class);
            foreach ($data['purchase_order_items'] as $purchaseOrderItem) {
                $purchaseOrderItemQueries->updateExternalPurchaseOrderItemId(
                    $purchaseOrderItem['purchase_order_item_id'],
                    $purchaseOrderItem['external_purchase_order_item_id']
                );
            }

            return;
        }

        $postData['url'] = $externalConnection->url . '/api/external-connection/purchase-order-fulfillment/discrepancy';
        $postData['response'] = $response;

        Log::error('External Connection', $postData);
        abort(417, 'An error occurred. Please try again.');
    }

    public function updateExternalFulfillmentDiscrepancy(
        array $purchaseOrderFulfillData,
        PurchaseOrderFulfillment $purchaseOrderFulfillment,
        Collection $products
    ): array {
        $response = [
            'items' => [],
            'purchase_order_items' => [],
        ];

        $purchaseOrderFulfillmentItemQueries = resolve(PurchaseOrderFulfillmentItemQueries::class);
        $packageTypeQueries = resolve(PackageTypeQueries::class);
        $purchaseOrderItemQueries = resolve(PurchaseOrderItemQueries::class);

        $purchaseOrderFulfillmentDataItems = $purchaseOrderFulfillData['items'];

        /** @var PurchaseOrder $purchaseOrder */
        $purchaseOrder = $purchaseOrderFulfillment->purchaseOrder;

        $purchaseOrderItems = $purchaseOrder->getItems();

        foreach ($purchaseOrderFulfillmentDataItems as $purchaseOrderFulfillmentDataItem) {
            if ($purchaseOrderFulfillmentDataItem['purchase_order_fulfillment_item_id']) {
                /** @var PurchaseOrderFulfillmentItem $purchaseOrderFulfillmentItem */
                $purchaseOrderFulfillmentItem = $purchaseOrderFulfillment->items->firstWhere(
                    'id',
                    $purchaseOrderFulfillmentDataItem['purchase_order_fulfillment_item_id']
                );

                $purchaseOrderFulfillmentItemQueries->update(
                    $purchaseOrderFulfillmentItem,
                    [
                        'received_quantity' => $purchaseOrderFulfillmentDataItem['received_quantity'],
                        'is_extra_item' => $purchaseOrderFulfillmentDataItem['is_extra_item'],
                        'discrepancy_type' => $purchaseOrderFulfillmentDataItem['discrepancy_type'],
                        'remarks' => $purchaseOrderFulfillmentDataItem['remarks'],
                    ]
                );

                if ($purchaseOrderFulfillmentDataItem['discrepancy_proof']) {
                    $purchaseOrderFulfillmentItemQueries->addDiscrepancyProof(
                        $purchaseOrderFulfillmentItem,
                        $purchaseOrderFulfillmentDataItem['discrepancy_proof']
                    );
                }

                if ($purchaseOrderFulfillmentDataItem['discrepancy_type']) {
                    $this->updateBatches(
                        $purchaseOrderFulfillmentItem,
                        $purchaseOrderFulfillmentDataItem['batch_details'],
                        (int) $purchaseOrderFulfillData['company_id'],
                    );
                }

                continue;
            }

            $product = $products->firstWhere('upc', $purchaseOrderFulfillmentDataItem['upc']);

            $packageTypeId = null;
            if ($purchaseOrderFulfillmentDataItem['package_type']) {
                $packageType = $packageTypeQueries->fetchOrCreate(
                    $purchaseOrderFulfillmentDataItem['package_type'],
                    (int) $purchaseOrderFulfillData['company_id']
                );
                $packageTypeId = $packageType->id;
            }

            $purchaseOrderItem = $purchaseOrderItems->firstWhere(
                'id',
                $purchaseOrderFulfillmentDataItem['purchase_order_item_id']
            );

            if (! $purchaseOrderItem) {
                $unitOfMeasureDerivativeId = null;
                if (array_key_exists(
                    'unit_of_measure_derivative',
                    $purchaseOrderFulfillmentDataItem
                ) && null !== $purchaseOrderFulfillmentDataItem['unit_of_measure_derivative']) {
                    $unitOfMeasureDerivativeQueries = resolve(UnitOfMeasureDerivativeQueries::class);
                    $unitOfMeasureDerivative = $unitOfMeasureDerivativeQueries->getByName(
                        $purchaseOrderFulfillmentDataItem['unit_of_measure_derivative']
                    );
                    $unitOfMeasureDerivativeId = $unitOfMeasureDerivative?->id;
                }

                $purchaseOrderItem = $purchaseOrderItemQueries->addNew([
                    'purchase_order_id' => $purchaseOrder->id,
                    'external_purchase_order_item_id' => $purchaseOrderFulfillmentDataItem['external_purchase_order_item_id'],
                    'product_id' => $product->id,
                    'purchase_cost' => (float) $product->purchase_cost,
                    'quantity' => $purchaseOrderFulfillmentDataItem['received_quantity'],
                    'transferred_quantity' => 0,
                    'unit_of_measure_derivative_id' => $unitOfMeasureDerivativeId,
                    'remarks' => 'Extra item receive',
                ]);

                $purchaseOrderFulfillmentDataItem['purchase_order_item_id'] = $purchaseOrderItem->id;

                $response['purchase_order_items'][] = [
                    'purchase_order_item_id' => $purchaseOrderFulfillmentDataItem['external_purchase_order_item_id'],
                    'external_purchase_order_item_id' => $purchaseOrderItem->id,
                ];
            }

            $purchaseOrderFulfillmentItem = $purchaseOrderFulfillmentItemQueries->addNew([
                'purchase_order_fulfillment_id' => $purchaseOrderFulfillment->id,
                'purchase_order_item_id' => $purchaseOrderFulfillmentDataItem['purchase_order_item_id'],
                'external_purchase_order_fulfillment_item_id' => $purchaseOrderFulfillmentDataItem['external_purchase_order_fulfillment_item_id'],
                'product_id' => $product->id,
                'transfer_quantity' => $purchaseOrderFulfillmentDataItem['transfer_quantity'],
                'received_quantity' => $purchaseOrderFulfillmentDataItem['received_quantity'],
                'package_type_id' => $packageTypeId,
                'package_quantity' => $purchaseOrderFulfillmentDataItem['package_quantity'],
                'package_total_quantity' => $purchaseOrderFulfillmentDataItem['package_total_quantity'],
                'is_extra_item' => $purchaseOrderFulfillmentDataItem['is_extra_item'],
                'discrepancy_type' => $purchaseOrderFulfillmentDataItem['discrepancy_type'],
                'remarks' => $purchaseOrderFulfillmentDataItem['remarks'],
            ]);

            $purchaseOrderItemQueries->updateTransferredQuantity(
                $purchaseOrderItem,
                (float) $purchaseOrderFulfillmentDataItem['transfer_quantity']
            );

            if ($purchaseOrderFulfillmentDataItem['discrepancy_proof']) {
                $purchaseOrderFulfillmentItemQueries->addDiscrepancyProof(
                    $purchaseOrderFulfillmentItem,
                    $purchaseOrderFulfillmentDataItem['discrepancy_proof']
                );
            }

            $this->updateBatches(
                $purchaseOrderFulfillmentItem,
                $purchaseOrderFulfillmentDataItem['batch_details'],
                (int) $purchaseOrderFulfillData['company_id'],
            );

            $response['items'][] = [
                'purchase_order_fulfillment_item_id' => $purchaseOrderFulfillmentDataItem['external_purchase_order_fulfillment_item_id'],
                'external_purchase_order_fulfillment_item_id' => $purchaseOrderFulfillmentItem->id,
            ];
        }

        return $response;
    }

    public function closedExternalFulfillment(
        PurchaseOrderFulfillment $purchaseOrderFulfillment,
        Admin|WarehouseManager|StoreManager|null $user
    ): void {
        /** @var PurchaseOrder $purchaseOrder */
        $purchaseOrder = $purchaseOrderFulfillment->purchaseOrder;

        /** @var ExternalCompany $externalCompany */
        $externalCompany = $purchaseOrder->externalCompany;

        /** @var ExternalConnection $externalConnection */
        $externalConnection = $externalCompany->externalConnection;

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->timeout(config('services.http_time_out'))->post(
            $externalConnection->url . '/api/external-connection/purchase-order-fulfillment/closed',
            [
                'token' => $externalConnection->token,
                'purchase_order_fulfillment_id' => $purchaseOrderFulfillment->external_purchase_order_fulfillment_id,
                'company_id' => $externalCompany->external_company_id,
                'external_username' => $this->getExternalUserName($user),
            ]
        );

        if (! $response->successful()) {
            Log::error('External Connection', [
                'url' => $externalConnection->url . '/api/external-connection/purchase-order-fulfillment/closed',
                'token' => $externalConnection->token,
                'purchase_order_fulfillment_id' => $purchaseOrderFulfillment->external_purchase_order_fulfillment_id,
                'company_id' => $externalCompany->external_company_id,
                'external_username' => $this->getExternalUserName($user),
                'response' => $response,
            ]);

            abort(417, 'An error occurred. Please try again.');
        }
    }

    public function closedDiscrepancyExternalFulfillment(
        PurchaseOrderFulfillment $purchaseOrderFulfillment,
        Admin|WarehouseManager|StoreManager|null $user
    ): void {
        /** @var PurchaseOrder $purchaseOrder */
        $purchaseOrder = $purchaseOrderFulfillment->purchaseOrder;

        /** @var ExternalCompany $externalCompany */
        $externalCompany = $purchaseOrder->externalCompany;

        /** @var ExternalConnection $externalConnection */
        $externalConnection = $externalCompany->externalConnection;
        $purchaseOrderFulfillmentItems = $purchaseOrderFulfillment->items;

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->timeout(config('services.http_time_out'))->post(
            $externalConnection->url . '/api/external-connection/purchase-order-fulfillment/closed-discrepancy',
            [
                'token' => $externalConnection->token,
                'purchase_order_fulfillment_id' => $purchaseOrderFulfillment->external_purchase_order_fulfillment_id,
                'company_id' => $externalCompany->external_company_id,
                'external_username' => $this->getExternalUserName($user),
                'items' => $purchaseOrderFulfillmentItems->map(
                    function (PurchaseOrderFulfillmentItem $purchaseOrderFulfillmentItem): array {
                        $itemBatches = $purchaseOrderFulfillmentItem->itemBatches;

                        /** @var Product $product */
                        $product = $purchaseOrderFulfillmentItem->product;

                        return [
                            'id' => $purchaseOrderFulfillmentItem->external_purchase_order_fulfillment_item_id,
                            'received_quantity' => $purchaseOrderFulfillmentItem->received_quantity,
                            'batch_details' => $product->has_batch ? $this->getBatchDetails($itemBatches) : [],
                            'remarks' => $purchaseOrderFulfillmentItem->remarks,
                        ];
                    }
                )->toArray(),
            ]
        );

        if (! $response->successful()) {
            Log::error('External Connection', [
                'url' => $externalConnection->url . '/api/external-connection/purchase-order-fulfillment/closed-discrepancy',
                'token' => $externalConnection->token,
                'purchase_order_fulfillment_id' => $purchaseOrderFulfillment->external_purchase_order_fulfillment_id,
                'company_id' => $externalCompany->external_company_id,
                'external_username' => $this->getExternalUserName($user),
                'items' => $purchaseOrderFulfillmentItems->map(
                    function (PurchaseOrderFulfillmentItem $purchaseOrderFulfillmentItem): array {
                        $itemBatches = $purchaseOrderFulfillmentItem->itemBatches;

                        /** @var Product $product */
                        $product = $purchaseOrderFulfillmentItem->product;

                        return [
                            'id' => $purchaseOrderFulfillmentItem->external_purchase_order_fulfillment_item_id,
                            'received_quantity' => $purchaseOrderFulfillmentItem->received_quantity,
                            'batch_details' => $product->has_batch ? $this->getBatchDetails($itemBatches) : [],
                            'remarks' => $purchaseOrderFulfillmentItem->remarks,
                        ];
                    }
                )->toArray(),
                'response' => $response,
            ]);

            abort(417, 'An error occurred. Please try again.');
        }
    }

    public function closePurchaseOrder(PurchaseOrder $purchaseOrder): void
    {
        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);
        $purchaseOrder = $purchaseOrderQueries->loadRelations($purchaseOrder);

        $purchaseOrderItems = $purchaseOrder->items->filter(
            fn ($item): bool => $item->quantity > ($item->rejected_quantity + $item->transferred_quantity)
        );

        if ($purchaseOrderItems->count() > 0) {
            return;
        }

        $purchaseOrderFulfillments = $purchaseOrder->getFulfillments();
        $withoutClosedFulfillment = $purchaseOrderFulfillments
            ->where('status', '!==', FulfillmentStatuses::CLOSED->value)
            ->where('status', '!==', FulfillmentStatuses::CANCELLED->value);

        if ($withoutClosedFulfillment->isNotEmpty()) {
            return;
        }

        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);
        $purchaseOrderQueries->updateStatus($purchaseOrder, Statuses::CLOSED->value);
    }

    public function prepareTransferType(int $orderType): int
    {
        if (OrderTypes::PURCHASE_REQUEST->value === $orderType) {
            return SequenceTypes::PR->value;
        }

        if (OrderTypes::TRANSFER_REQUEST->value === $orderType) {
            return SequenceTypes::TR->value;
        }

        if (OrderTypes::SALES_ORDER->value === $orderType) {
            return SequenceTypes::SO->value;
        }

        return SequenceTypes::PO->value;
    }

    public function getOrderType(int $orderType): int
    {
        if (OrderTypes::PURCHASE_REQUEST->value === $orderType) {
            return OrderTypes::SALES_ORDER->value;
        }

        if (OrderTypes::PURCHASE_ORDER->value === $orderType) {
            return OrderTypes::SALES_ORDER->value;
        }

        return OrderTypes::PURCHASE_ORDER->value;
    }

    public function receivedExternalFulfillment(
        PurchaseOrderFulfillment $purchaseOrderFulfillment,
        Admin|WarehouseManager|StoreManager|null $user
    ): void {
        /** @var PurchaseOrder $purchaseOrder */
        $purchaseOrder = $purchaseOrderFulfillment->purchaseOrder;

        /** @var ExternalCompany $externalCompany */
        $externalCompany = $purchaseOrder->externalCompany;

        /** @var ExternalConnection $externalConnection */
        $externalConnection = $externalCompany->externalConnection;

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->timeout(config('services.http_time_out'))->post(
            $externalConnection->url . '/api/external-connection/purchase-order-fulfillment/mark-as-received',
            [
                'token' => $externalConnection->token,
                'purchase_order_fulfillment_id' => $purchaseOrderFulfillment->external_purchase_order_fulfillment_id,
                'company_id' => $externalCompany->external_company_id,
                'external_username' => $this->getExternalUserName($user),
            ]
        );

        if (! $response->successful()) {
            Log::error('External Connection', [
                'url' => $externalConnection->url . '/api/external-connection/purchase-order-fulfillment/mark-as-received',
                'token' => $externalConnection->token,
                'purchase_order_fulfillment_id' => $purchaseOrderFulfillment->external_purchase_order_fulfillment_id,
                'company_id' => $externalCompany->external_company_id,
                'external_username' => $this->getExternalUserName($user),
                'response' => $response,
            ]);

            abort(417, 'An error occurred. Please try again.');
        }
    }

    public function cancelExternalFulfillment(
        PurchaseOrderFulfillment $purchaseOrderFulfillment,
        Admin|WarehouseManager|StoreManager|null $user
    ): void {
        /** @var PurchaseOrder $purchaseOrder */
        $purchaseOrder = $purchaseOrderFulfillment->purchaseOrder;

        /** @var ExternalCompany $externalCompany */
        $externalCompany = $purchaseOrder->externalCompany;

        /** @var ExternalConnection $externalConnection */
        $externalConnection = $externalCompany->externalConnection;

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->timeout(config('services.http_time_out'))->post(
            $externalConnection->url . '/api/external-connection/purchase-order-fulfillment/mark-as-canceled',
            [
                'token' => $externalConnection->token,
                'purchase_order_fulfillment_id' => $purchaseOrderFulfillment->external_purchase_order_fulfillment_id,
                'company_id' => $externalCompany->external_company_id,
                'external_username' => $this->getExternalUserName($user),
            ]
        );

        if (! $response->successful()) {
            Log::error('External Connection', [
                'url' => $externalConnection->url . '/api/external-connection/purchase-order-fulfillment/mark-as-canceled',
                'token' => $externalConnection->token,
                'purchase_order_fulfillment_id' => $purchaseOrderFulfillment->external_purchase_order_fulfillment_id,
                'company_id' => $externalCompany->external_company_id,
                'external_username' => $this->getExternalUserName($user),
                'response' => $response,
            ]);

            abort(417, 'An error occurred. Please try again.');
        }
    }

    public function shiftExternalFulfillment(
        PurchaseOrderFulfillment $purchaseOrderFulfillment,
        Admin|WarehouseManager|StoreManager|null $user
    ): void {
        /** @var PurchaseOrder $purchaseOrder */
        $purchaseOrder = $purchaseOrderFulfillment->purchaseOrder;

        /** @var ExternalCompany $externalCompany */
        $externalCompany = $purchaseOrder->externalCompany;

        /** @var ExternalConnection $externalConnection */
        $externalConnection = $externalCompany->externalConnection;

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->timeout(config('services.http_time_out'))->post(
            $externalConnection->url . '/api/external-connection/purchase-order-fulfillment/mark-as-shift',
            [
                'token' => $externalConnection->token,
                'purchase_order_fulfillment_id' => $purchaseOrderFulfillment->external_purchase_order_fulfillment_id,
                'company_id' => $externalCompany->external_company_id,
                'external_username' => $this->getExternalUserName($user),
            ]
        );

        if (! $response->successful()) {
            Log::error('External Connection', [
                'url' => $externalConnection->url . '/api/external-connection/purchase-order-fulfillment/mark-as-shift',
                'token' => $externalConnection->token,
                'purchase_order_fulfillment_id' => $purchaseOrderFulfillment->external_purchase_order_fulfillment_id,
                'company_id' => $externalCompany->external_company_id,
                'external_username' => $this->getExternalUserName($user),
                'response' => $response,
            ]);

            abort(417, 'An error occurred. Please try again.');
        }
    }

    public function postAutoApproveExternalSalesOrder(int $purchaseOrderId, int $companyId): void
    {
        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);
        $purchaseOrder = $purchaseOrderQueries->getByIdAndCompanyIdWithRelation($purchaseOrderId, $companyId);

        if ($purchaseOrder->order_type !== OrderTypes::TRANSFER_REQUEST->value) {
            return;
        }

        /** @var ExternalCompany $externalCompany */
        $externalCompany = $purchaseOrder->externalCompany;

        /** @var ExternalConnection $externalConnection */
        $externalConnection = $externalCompany->externalConnection;

        Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->timeout(config('services.http_time_out'))->post(
            $externalConnection->url . '/api/external-connection/purchase-orders/auto-approve',
            [
                'token' => $externalConnection->token,
                'purchase_order_id' => $purchaseOrder->external_purchase_order_id,
                'company_id' => $externalCompany->external_company_id,
            ]
        );
    }

    public function purchaseOrderMarkAsPartialFulfillment(
        PurchaseOrder $purchaseOrder,
        Admin|WarehouseManager|StoreManager|null $user
    ): void {
        if ($purchaseOrder->status === Statuses::APPROVED->value) {
            $purchaseOrderTransactionQueries = resolve(PurchaseOrderTransactionQueries::class);
            $purchaseOrderTransactionQueries->addNew(
                $purchaseOrder->id,
                $purchaseOrder->status,
                Statuses::PARTIAL_FULFILLMENT->value,
                $user
            );

            $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);
            $purchaseOrderQueries->updateStatus($purchaseOrder, Statuses::PARTIAL_FULFILLMENT->value);
        }
    }

    public function checkMarkAsRejected(PurchaseOrder $purchaseOrder): void
    {
        if (
            $purchaseOrder->status === Statuses::OPENED->value
            && null === $purchaseOrder->created_by_company_id
        ) {
            return;
        }

        if (
            $purchaseOrder->status === Statuses::APPROVED->value
            && $purchaseOrder->order_type === OrderTypes::PURCHASE_ORDER->value
        ) {
            $this->checkExternalPurchaseOrderForReject($purchaseOrder);

            return;
        }

        throw new RedirectWithErrorException(
            'admin.purchase_orders.index',
            'Regrettably, we cannot proceed with the rejection of the purchase order at this time, as it does not currently have an open or approved status.'
        );
    }

    public function checkExternalPurchaseOrderForReject(PurchaseOrder $purchaseOrder): void
    {
        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);
        $purchaseOrder = $purchaseOrderQueries->getByIdAndCompanyIdWithRelation(
            $purchaseOrder->id,
            $purchaseOrder->company_id
        );

        /** @var ExternalCompany $externalCompany */
        $externalCompany = $purchaseOrder->externalCompany;

        /** @var ExternalConnection $externalConnection */
        $externalConnection = $externalCompany->externalConnection;

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->timeout(config('services.http_time_out'))->post(
            $externalConnection->url . '/api/external-connection/check-purchase-order-cancel',
            [
                'token' => $externalConnection->token,
                'purchase_order_id' => $purchaseOrder->external_purchase_order_id,
                'company_id' => $externalCompany->external_company_id,
            ]
        );

        if ($response->successful()) {
            $data = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);
            if ($data['is_purchase_order_cancel']) {
                return;
            }
        }

        throw new RedirectWithErrorException(
            'admin.purchase_orders.index',
            'Regrettably, we cannot proceed with the rejection of the purchase order at this time, as it does not currently have an open or approved status.'
        );
    }

    public function purchaseOrderMarkAsRejected(
        PurchaseOrder $purchaseOrder,
        Admin|WarehouseManager|StoreManager|null $user
    ): void {
        $purchaseOrderTransactionQueries = resolve(PurchaseOrderTransactionQueries::class);
        $purchaseOrderTransactionQueries->addNew(
            $purchaseOrder->id,
            $purchaseOrder->status,
            Statuses::REJECTED->value,
            $user
        );

        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);
        $purchaseOrderQueries->updateStatus($purchaseOrder, Statuses::REJECTED->value);

        if ($purchaseOrder->order_type === OrderTypes::PURCHASE_REQUEST->value) {
            $purchaseOrderInventoryService = resolve(PurchaseOrderInventoryService::class);

            $purchaseOrderItems = $purchaseOrder->items;

            foreach ($purchaseOrderItems as $purchaseOrderItem) {
                $purchaseOrderInventoryService->revertReservedStockForPurchaseOrderItem($purchaseOrderItem);
            }
        }

        $this->rejectExternalPurchaseOrder($purchaseOrder, $user);
    }

    public function checkMarkAsCanceled(PurchaseOrder $purchaseOrder): void
    {
        if ($purchaseOrder->status === Statuses::DRAFT->value) {
            return;
        }

        if (
            $purchaseOrder->status === Statuses::APPROVED->value
            && $purchaseOrder->order_type === OrderTypes::SALES_ORDER->value
        ) {
            $this->checkExternalPurchaseOrderForCancel($purchaseOrder);

            return;
        }

        throw new RedirectWithErrorException(
            'admin.purchase_orders.index',
            'Cancellation of the purchase order is not possible at this moment, as it is not in a draft status.'
        );
    }

    public function checkExternalPurchaseOrderForCancel(PurchaseOrder $purchaseOrder): void
    {
        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);
        $purchaseOrder = $purchaseOrderQueries->getByIdAndCompanyIdWithRelation(
            $purchaseOrder->id,
            $purchaseOrder->company_id
        );

        /** @var ExternalCompany $externalCompany */
        $externalCompany = $purchaseOrder->externalCompany;

        /** @var ExternalConnection $externalConnection */
        $externalConnection = $externalCompany->externalConnection;

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->timeout(config('services.http_time_out'))->post(
            $externalConnection->url . '/api/external-connection/check-purchase-order-cancel',
            [
                'token' => $externalConnection->token,
                'purchase_order_id' => $purchaseOrder->external_purchase_order_id,
                'company_id' => $externalCompany->external_company_id,
            ]
        );

        if ($response->successful()) {
            $data = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);
            if ($data['is_purchase_order_cancel']) {
                return;
            }
        }

        throw new RedirectWithErrorException(
            'admin.purchase_orders.index',
            'Regrettably, we cannot proceed with the cancel of the purchase order at this time, as it does not currently have an open or approved status.'
        );
    }

    public function purchaseOrderMarkAsCanceled(
        PurchaseOrder $purchaseOrder,
        Admin|WarehouseManager|StoreManager|null $user
    ): void {
        $this->cancelExternalPurchaseOrder($purchaseOrder, $user);

        $purchaseOrderTransactionQueries = resolve(PurchaseOrderTransactionQueries::class);
        $purchaseOrderTransactionQueries->addNew(
            $purchaseOrder->id,
            $purchaseOrder->status,
            Statuses::CANCELLED->value,
            $user
        );

        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);
        $purchaseOrderQueries->updateStatus($purchaseOrder, Statuses::CANCELLED->value);

        $purchaseOrderInventoryService = resolve(PurchaseOrderInventoryService::class);
        $purchaseOrderInventoryService->revertReservedStockForPurchaseOrderRecord($purchaseOrder);
    }

    public function cancelExternalPurchaseOrder(
        PurchaseOrder $purchaseOrder,
        Admin|WarehouseManager|StoreManager|null $user
    ): void {
        if ($purchaseOrder->status !== Statuses::APPROVED->value) {
            return;
        }

        if ($purchaseOrder->order_type !== OrderTypes::SALES_ORDER->value) {
            return;
        }

        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);
        $purchaseOrder = $purchaseOrderQueries->getByIdAndCompanyIdWithRelation(
            $purchaseOrder->id,
            $purchaseOrder->company_id
        );

        $purchaseOrderInventoryService = resolve(PurchaseOrderInventoryService::class);
        $purchaseOrderInventoryService->revertReservedStockForPurchaseOrder($purchaseOrder);

        /** @var ExternalCompany $externalCompany */
        $externalCompany = $purchaseOrder->externalCompany;

        /** @var ExternalConnection $externalConnection */
        $externalConnection = $externalCompany->externalConnection;

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->timeout(config('services.http_time_out'))->post(
            $externalConnection->url . '/api/external-connection/purchase-orders/cancel',
            [
                'token' => $externalConnection->token,
                'purchase_order_id' => $purchaseOrder->external_purchase_order_id,
                'company_id' => $externalCompany->external_company_id,
                'external_username' => $this->getExternalUserName($user),
            ]
        );

        if ($response->successful()) {
            return;
        }

        Log::error('External Connection', [
            'url' => $externalConnection->url . '/api/external-connection/purchase-orders/cancel',
            'token' => $externalConnection->token,
            'purchase_order_id' => $purchaseOrder->external_purchase_order_id,
            'company_id' => $externalCompany->external_company_id,
            'external_username' => $this->getExternalUserName($user),
            'response' => $response,
        ]);

        abort(417, 'An error occurred. Please try again.');
    }

    public function fetchStatusesCount(array $filterData, int $companyId): array
    {
        $transferRequestCounts = [];
        $purchaseRequestCounts = [];
        $salesOrderCounts = [];
        $purchaseOrderCounts = [];

        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);

        $filterData['order_type'] = OrderTypes::TRANSFER_REQUEST->value;
        $transferRequestStatusCounts = $purchaseOrderQueries->allRequestStatusCount($filterData, $companyId);

        $filterData['order_type'] = OrderTypes::PURCHASE_REQUEST->value;
        $purchaseRequestStatusCounts = $purchaseOrderQueries->allRequestStatusCount($filterData, $companyId);

        $filterData['order_type'] = OrderTypes::SALES_ORDER->value;
        $salesOrderStatusCounts = $purchaseOrderQueries->allRequestStatusCount($filterData, $companyId);

        $filterData['order_type'] = OrderTypes::PURCHASE_ORDER->value;
        $purchaseOrderStatusCounts = $purchaseOrderQueries->allRequestStatusCount($filterData, $companyId);
        foreach (DashboardPurchaseRequestStatuses::getList() as $status) {
            $statusCount = $transferRequestStatusCounts->firstWhere('status', $status['id']);
            $statusName = Statuses::getFormattedCaseName($status['id']);
            $transferRequestCounts[$statusName] = [
                'count' => (int) $statusCount?->count,
                'id' => $status['id'],
            ];
        }

        foreach (DashboardPurchaseRequestStatuses::getList() as $status) {
            $statusCount = $purchaseRequestStatusCounts->firstWhere('status', $status['id']);
            $statusName = Statuses::getFormattedCaseName($status['id']);
            $purchaseRequestCounts[$statusName] = [
                'count' => (int) $statusCount?->count,
                'id' => $status['id'],
            ];
        }

        foreach (DashboardPurchaseOrderStatuses::getList() as $status) {
            $statusCount = $salesOrderStatusCounts->firstWhere('status', $status['id']);
            $statusName = Statuses::getFormattedCaseName($status['id']);
            $salesOrderCounts[$statusName] = [
                'count' => (int) $statusCount?->count,
                'id' => $status['id'],
            ];
        }

        foreach (DashboardPurchaseOrderStatuses::getList() as $status) {
            $statusCount = $purchaseOrderStatusCounts->firstWhere('status', $status['id']);
            $statusName = Statuses::getFormattedCaseName($status['id']);
            $purchaseOrderCounts[$statusName] = [
                'count' => (int) $statusCount?->count,
                'id' => $status['id'],
            ];
        }

        $purchaseOrderFulfillmentService = resolve(PurchaseOrderFulfillmentService::class);
        $deliveryOrdersStatusCounts = $purchaseOrderFulfillmentService->getDeliveryOrdersStatusCount(
            $filterData,
            $companyId
        );

        return [
            $transferRequestCounts,
            $purchaseRequestCounts,
            $salesOrderCounts,
            $purchaseOrderCounts,
            $deliveryOrdersStatusCounts,
        ];
    }

    public function getLocationStock(array $productIds, int $locationId, int $externalLocationId): array
    {
        if (count($productIds) <= 0) {
            return [];
        }

        $inventoryQueries = resolve(InventoryQueries::class);
        $inventories = $inventoryQueries->getInventoriesWithProductByProductIds($locationId, $productIds);

        $productsWithoutInventories = [];

        if ($inventories->pluck('product_id')->isNotEmpty()) {
            $productsWithoutInventories = array_diff(
                $productIds,
                $inventories->pluck('product_id')->filter()->toArray()
            );
        }

        foreach ($productsWithoutInventories as $productWithoutInventory) {
            $inventories->push($inventoryQueries->fetchOrCreate($locationId, (int) $productWithoutInventory));
        }

        $productUpcs = $inventories->pluck('product.upc')->toArray();
        $externalProducts = $this->getExternalLocationStocks($externalLocationId, $productUpcs);

        return $inventories->transform(function (Inventory $inventory) use ($externalProducts): array {
            $product = $inventory->product;
            if (! $product) {
                $productQueries = resolve(ProductQueries::class);
                $product = $productQueries->getByIdWithUpc($inventory->product_id);
            }

            $externalProduct = null;
            if ($product) {
                $externalProduct = $externalProducts->firstWhere('upc', $product->upc);
            }

            return [
                'product_id' => $inventory['product_id'],
                'stock' => $inventory['stock'],
                'reserved_stock' => $inventory['reserved_stock'],
                'external_stock' => $externalProduct ? $externalProduct['external_stock'] : 0,
                'external_reserved_stock' => $externalProduct ? $externalProduct['external_reserved_stock'] : 0,
            ];
        })->toArray();
    }

    public function getExternalStocks(PurchaseOrder $purchaseOrder): Collection
    {
        $upcs = $purchaseOrder->items->pluck('product.upc')->toArray();

        return $this->getExternalLocationStocks($purchaseOrder->external_location_id, $upcs);
    }

    public function getExternalLocationStocks(int $externalLocationId, array $upcs): Collection
    {
        if ($externalLocationId <= 0) {
            return collect([]);
        }

        $externalLocationQueries = resolve(ExternalLocationQueries::class);
        $externalLocation = $externalLocationQueries->getByIdWithExternalCompanyAndExternalConnection(
            $externalLocationId
        );

        if (! $externalLocation) {
            return collect([]);
        }

        /** @var ExternalCompany $externalCompany */
        $externalCompany = $externalLocation->externalCompany;

        /** @var ExternalConnection $externalConnection */
        $externalConnection = $externalCompany->externalConnection;
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->timeout(config('services.http_time_out'))->post(
            $externalConnection->url . '/api/external-connection/get-products-stock-by-upc',
            [
                'token' => $externalConnection->token,
                'upcs' => $upcs,
                'location_id' => $externalLocation->external_location_id,
            ]
        );

        if ($response->successful()) {
            $data = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);
            /** @var array $products */
            $products = $data['products'];

            return collect($products);
        }

        Log::error('External Connection', [
            'url' => $externalConnection->url . '/api/external-connection/get-products-stock-by-upc',
            'token' => $externalConnection->token,
            'upcs' => $upcs,
            'location_id' => $externalLocation->external_location_id,
            'response' => $response,
        ]);

        return collect([]);
    }

    public function getExternalUserName(Admin|StoreManager|WarehouseManager|null $user): ?string
    {
        if (null === $user) {
            return null;
        }

        if (! $user->employee) {
            return null;
        }

        if (! $user->employee->last_name) {
            return $user->employee->first_name;
        }

        return $user->employee->first_name . ' ' . $user->employee->last_name;
    }

    public function openPurchaseOrderAndSyncExternalData(
        Admin|StoreManager|WarehouseManager|null $user,
        PurchaseOrder $purchaseOrder,
        int $companyId
    ): int {
        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);
        $purchaseOrderItemQueries = resolve(PurchaseOrderItemQueries::class);
        $purchaseOrderTransactionQueries = resolve(PurchaseOrderTransactionQueries::class);

        $purchaseOrderTransactionQueries->addNew(
            $purchaseOrder->getKey(),
            $purchaseOrder->status,
            Statuses::OPENED->value,
            $user
        );

        $purchaseOrderQueries->updateStatus($purchaseOrder, Statuses::OPENED->value);

        $data = $this->postExternalPurchaseOrder($purchaseOrder, $companyId, $user);

        $purchaseOrderQueries->updateExternalPurchaseOrderId(
            $data['purchase_order_id'],
            $data['external_purchase_order_id'],
            $data['external_order_number'],
        );

        foreach ($data['items'] as $item) {
            $purchaseOrderItemQueries->updateExternalPurchaseOrderItemId(
                $item['purchase_order_item_id'],
                $item['external_purchase_order_item_id']
            );
        }

        return $data['purchase_order_id'];
    }

    public function fetchPurchaseOrders(array $filterData, int $companyId): array
    {
        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);
        $lengthAwarePaginator = $purchaseOrderQueries->listQuery($filterData, $companyId);

        [$transferRequestCounts, $purchaseRequestCounts, $salesOrderCounts, $purchaseOrderCounts, $deliveryOrdersStatusCounts] = $this->fetchStatusesCount(
            $filterData,
            $companyId
        );

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => PurchaseOrderListResource::collection($lengthAwarePaginator->getCollection()),
            'transferRequestStatusCounts' => $transferRequestCounts,
            'purchaseRequestStatusCounts' => $purchaseRequestCounts,
            'salesOrderStatusCounts' => $salesOrderCounts,
            'purchaseOrderStatusCounts' => $purchaseOrderCounts,
            'deliveryOrdersStatusCounts' => $deliveryOrdersStatusCounts,
        ];
    }

    public function fetchPurchaseOrderItemByPurchaseOrderId(int $purchaseOrderId, int $companyId): array
    {
        $purchaseOrderItemQueries = resolve(PurchaseOrderItemQueries::class);
        $purchaseOrderItems = $purchaseOrderItemQueries->getByPurchaseOrderId($purchaseOrderId, $companyId);

        return [
            'purchase_order_items' => PurchaseOrderItemsResource::collection($purchaseOrderItems),
            'totals' => [
                'requested' => $purchaseOrderItems->sum('quantity'),
                'rejected' => $purchaseOrderItems->sum('rejected_quantity'),
                'transferred' => $purchaseOrderItems->sum('transferred_quantity'),
            ],
        ];
    }

    public function exportPurchaseOrderItems(int $purchaseOrderId, int $companyId, string $fileName): BinaryFileResponse
    {
        $purchaseOrderItemQueries = resolve(PurchaseOrderItemQueries::class);
        $purchaseOrderItems = $purchaseOrderItemQueries->getByPurchaseOrderId($purchaseOrderId, $companyId);

        return Excel::download(new PurchaseOrderItemExport($purchaseOrderItems), $fileName);
    }

    public function exportPurchaseOrders(array $filterData, int $companyId, string $fileName): BinaryFileResponse
    {
        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);
        $purchaseOrders = $purchaseOrderQueries->exportPurchaseOrder($filterData, $companyId);

        return Excel::download(new PurchaseOrderExport($purchaseOrders), $fileName);
    }

    public function checkPurchaseOrderApprove(PurchaseOrder $purchaseOrder): void
    {
        if (($purchaseOrder->status !== Statuses::OPENED->value) || (null !== $purchaseOrder->created_by_company_id)) {
            abort(417, 'At this moment, we are unable to approve the purchase order, as it is not in an open status.');
        }
    }

    public function purchaseOrderApprove(
        PurchaseOrder $purchaseOrder,
        Admin|WarehouseManager|StoreManager|null $user
    ): void {
        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);

        $this->addOrderTransaction($purchaseOrder, Statuses::CLOSED->value, $user);

        $this->closeExternalPurchaseOrder($purchaseOrder, $user);
        $salesOrder = $this->saveSalesOrder($purchaseOrder);

        $this->addOrderTransaction($salesOrder, Statuses::OPENED->value, $user);

        $purchaseOrderQueries->updateStatus($purchaseOrder, Statuses::CLOSED->value);

        $this->postExternalSalesOrder($salesOrder, $purchaseOrder, $user);

        $purchaseOrderInventoryService = resolve(PurchaseOrderInventoryService::class);
        $purchaseOrderInventoryService->updateTheReservedStockFromPurchaseRequestToSalesOrder($salesOrder);
    }

    public function addOrderTransaction(
        PurchaseOrder $purchaseOrder,
        int $newStatus,
        Admin|WarehouseManager|StoreManager|null $user = null
    ): void {
        $purchaseOrderTransactionQueries = resolve(PurchaseOrderTransactionQueries::class);
        $purchaseOrderTransactionQueries->addNew($purchaseOrder->id, $newStatus, $purchaseOrder->status, $user);
    }

    public function markAsFulfillmentCompletedPurchaseOrder(
        PurchaseOrder $purchaseOrder,
        Admin|WarehouseManager|StoreManager|null $user,
        ?string $externalUsername = null
    ): void {
        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);
        $purchaseOrder = $purchaseOrderQueries->loadRelations($purchaseOrder);

        $purchaseOrderItems = $purchaseOrder->items->filter(
            fn ($item): bool => $item->quantity > ($item->rejected_quantity + $item->transferred_quantity)
        );

        if ($purchaseOrderItems->count() > 0) {
            return;
        }

        $purchaseOrderTransactionQueries = resolve(PurchaseOrderTransactionQueries::class);
        $purchaseOrderTransactionQueries->addNew(
            $purchaseOrder->id,
            $purchaseOrder->status,
            Statuses::FULFILLMENT_COMPLETED->value,
            $user,
            $externalUsername
        );

        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);
        $purchaseOrderQueries->updateStatus($purchaseOrder, Statuses::FULFILLMENT_COMPLETED->value);
    }

    public function getExternalProductBatchesAndInventoryUnits(
        array $batchNumbers,
        array $inventoryDetails,
        string $productUpc
    ): array {
        $externalBatches = $this->getExternalProductBatch(
            $batchNumbers,
            $inventoryDetails['external_company_id'],
            $productUpc
        );

        $externalProduct = $this->getExternalProducts(
            $inventoryDetails['external_company_id'],
            [$productUpc]
        )->first();

        $externalBatchInventoryUnits = $this->getExternalBatchInventoryUnits(
            $batchNumbers,
            $inventoryDetails,
            $productUpc
        );

        return [$externalBatches, $externalProduct, $externalBatchInventoryUnits];
    }

    public function update(
        Collection $products,
        array $purchaseOrderData,
        int $companyId,
        int $purchaseOrderId
    ): void {
        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);
        $purchaseOrder = $purchaseOrderQueries->getByIdWithRelationItems($purchaseOrderId);

        $purchaseOrderInventoryService = resolve(PurchaseOrderInventoryService::class);
        $purchaseOrderInventoryService->revertReservedStockForPurchaseOrderRecord($purchaseOrder);

        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);
        $purchaseOrderQueries->update($purchaseOrderData, $companyId, $purchaseOrderId, $products);

        $purchaseOrderInventoryService = resolve(PurchaseOrderInventoryService::class);
        $purchaseOrderInventoryService->addInventoryReservedStockForPurchaseOrder($purchaseOrder);
    }
}

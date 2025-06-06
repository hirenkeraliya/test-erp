<?php

declare(strict_types=1);

use App\Http\Controllers\Api\ExternalConnection\BatchController;
use App\Http\Controllers\Api\ExternalConnection\CompanyController;
use App\Http\Controllers\Api\ExternalConnection\ExternalCompanyController;
use App\Http\Controllers\Api\ExternalConnection\ExternalConnectionController;
use App\Http\Controllers\Api\ExternalConnection\ExternalLoginController;
use App\Http\Controllers\Api\ExternalConnection\InventoryReportController;
use App\Http\Controllers\Api\ExternalConnection\InventoryUnitController;
use App\Http\Controllers\Api\ExternalConnection\LocationController;
use App\Http\Controllers\Api\ExternalConnection\ProductController;
use App\Http\Controllers\Api\ExternalConnection\PurchaseOrderController;
use App\Http\Controllers\Api\ExternalConnection\PurchaseOrderFulfillmentController;
use App\Http\Controllers\Api\ExternalConnection\PurchaseOrderInvoiceController;
use App\Http\Controllers\Api\ExternalConnection\StoreController;
use App\Http\Controllers\Api\ExternalConnection\WarehouseController;
use Illuminate\Support\Facades\Route;

Route::prefix('external-connection')->name('external_connection.')->group(function (): void {
    Route::controller(ExternalConnectionController::class)->group(function (): void {
        Route::post('set-notification', 'setNotification');
        Route::post('reject', 'reject');
        Route::get('approve', 'approve');
        Route::get('verify', 'verify');
        Route::post('sync-data', 'syncData');
        Route::post('send-external-product-data', 'sendExternalProductData');
    });
    Route::controller(ExternalLoginController::class)->group(function (): void {
        Route::get('warehouse-manager-verify-external-token', 'verifyExternalToken');
        Route::get('admin-verify-external-token', 'adminVerifyExternalToken');
    });
    Route::controller(InventoryReportController::class)->group(function (): void {
        Route::post('fetch-inventories', 'fetchInventories');
        Route::post('get-stores-warehouses-and-regions', 'getStoresWarehousesAndRegions');
        Route::post('get-stores-and-regions', 'getStoresAndRegions');
        Route::post('get-warehouses-and-regions', 'getWarehousesAndRegions');
        Route::get('export-inventories/{fileName}', 'exportInventories');
        Route::post('get-filtered-inventory-products', 'getFilteredInventoryProducts');
        Route::post('get-filtered-inventory-categories', 'getFilteredInventoryCategories');
        Route::post('get-filtered-inventory-brands', 'getFilteredInventoryBrands');
        Route::post('get-filtered-inventory-sizes', 'getFilteredInventorySizes');
        Route::post('get-filtered-inventory-colors', 'getFilteredInventoryColors');
        Route::post('get-filtered-inventory-departments', 'getFilteredInventoryDepartments');
        Route::post('get-filtered-inventory-article-numbers', 'getFilteredInventoryArticleNumbers');
        Route::post('get-filtered-inventory-tags', 'getFilteredInventoryTags');
        Route::post('get-filtered-inventory-styles', 'getFilteredInventoryStyles');
        Route::post('get-filtered-inventory-attributes', 'getFilteredInventoryAttributes');
    });
    Route::middleware(['verify_external_connection_token'])->group(function (): void {
        Route::controller(ExternalCompanyController::class)->group(function (): void {
            Route::post('external-company-archive', 'externalCompanyArchive');
            Route::post('external-company-restore', 'externalCompanyRestore');
        });
        Route::controller(CompanyController::class)->group(function (): void {
            Route::post('get-companies', 'getCompanies');
        });
        Route::controller(StoreController::class)->group(function (): void {
            Route::get('get-stores', 'getStores');
        });
        Route::controller(WarehouseController::class)->group(function (): void {
            Route::get('get-warehouses', 'getWarehouses');
        });
        Route::controller(LocationController::class)->group(function (): void {
            Route::post('get-locations', 'getLocations');
        });
        Route::controller(ProductController::class)->group(function (): void {
            Route::post('get-products-by-upc', 'getProductsByUpc');
            Route::post('get-products-stock-by-upc', 'getProductsStockByUpc');
        });
        Route::controller(BatchController::class)->group(function (): void {
            Route::post('get-product-batch-numbers', 'getProductBatchNumbers');
        });
        Route::controller(InventoryUnitController::class)->group(function (): void {
            Route::post('get-inventory-units', 'getBatchInventoryUnits');
        });
        Route::controller(PurchaseOrderController::class)->group(function (): void {
            Route::post('purchase-orders', 'store');
            Route::post('purchase-orders/reject', 'reject');
            Route::post('purchase-orders/cancel', 'cancel');
            Route::post('purchase-orders/closed', 'closed');
            Route::post('purchase-orders/auto-approve', 'autoApprove');
            Route::post('check-purchase-order-cancel', 'checkPurchaseOrderCancel');
        });
        Route::controller(PurchaseOrderFulfillmentController::class)->group(function (): void {
            Route::post('get-delivery-order-status', 'getDeliveryOrderStatus');
            Route::post('purchase-order-fulfillment/discrepancy', 'discrepancy');
            Route::post('purchase-order-fulfillment/closed', 'closed');
            Route::post('purchase-order-fulfillment/mark-as-received', 'markAsReceived');
            Route::post('purchase-order-fulfillment/mark-as-canceled', 'markAsCanceled');
            Route::post('purchase-order-fulfillment/mark-as-shift', 'markAsShift');
            Route::post('purchase-order-fulfillment/closed-discrepancy', 'closedDiscrepancy');
            Route::post('purchase-order-fulfillment', 'store');
        });
        Route::controller(PurchaseOrderInvoiceController::class)->group(function (): void {
            Route::post('purchase-order-invoices/paid', 'paid');
            Route::post('purchase-order-invoices/mark-as-received', 'markAsReceived');
            Route::post('purchase-order-invoices/sent', 'sent');
        });
    });
});

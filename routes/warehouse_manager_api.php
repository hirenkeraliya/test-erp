<?php

declare(strict_types=1);

use App\Http\Controllers\Api\WarehouseManager\ConfigurationController;
use App\Http\Controllers\Api\WarehouseManager\DashboardController;
use App\Http\Controllers\Api\WarehouseManager\GoodsReceivedNoteController;
use App\Http\Controllers\Api\WarehouseManager\NotificationController;
use App\Http\Controllers\Api\WarehouseManager\PackageTypeController;
use App\Http\Controllers\Api\WarehouseManager\ProductController;
use App\Http\Controllers\Api\WarehouseManager\PurchaseOrderController;
use App\Http\Controllers\Api\WarehouseManager\PurchaseOrderFulfillmentController;
use App\Http\Controllers\Api\WarehouseManager\StockTransferController;
use App\Http\Controllers\Api\WarehouseManager\TokenController;
use App\Http\Controllers\Api\WarehouseManager\VendorController;
use App\Http\Controllers\Api\WarehouseManager\WarehouseController;
use App\Http\Controllers\Api\WarehouseManager\WarehouseManagerController;
use Illuminate\Support\Facades\Route;

Route::prefix('warehouse-manager')->name('warehouse_manager.')->group(function (): void {
    Route::post('get-token', [TokenController::class, 'issueToken'])->name('get_token');

    Route::middleware(['auth:sanctum'])->group(function (): void {
        Route::controller(TokenController::class)->group(function (): void {
            Route::get('logout', 'logout');
            Route::post('set-fcm-token', 'setFcmToken');
        });

        Route::controller(ConfigurationController::class)->group(function (): void {
            Route::get('get-configuration', 'getConfiguration');
        });

        Route::controller(WarehouseController::class)->group(function (): void {
            Route::get('get-warehouses', 'getWarehouses');
            Route::get('get-warehouse-stock/{productId}', 'getWarehouseStock');
            Route::get('get-store-stock/{productId}', 'getStoreStock');
        });
        Route::controller(ProductController::class)->group(function (): void {
            Route::get('get-products', 'getProducts');
            Route::get('get-product-details/{productId}/{storeId}', 'getProductDetails');
        });
        Route::controller(WarehouseManagerController::class)->group(function (): void {
            Route::post('profile-update', 'updateProfile');
            Route::get('email-verification', 'emailVerification');
            Route::get('profile-details', 'getProfileDetails')->name('profile_details');
        });

        Route::controller(DashboardController::class)->group(function (): void {
            Route::get('get-dashboard-data', 'getDashboardData');
            Route::get('get-transfer-statuses-data', 'getTransferStatusesData');
        });

        Route::controller(GoodsReceivedNoteController::class)->group(function (): void {
            Route::get('get-goods-received-notes', 'getGoodsReceivedNotes');
            Route::get('get-goods-received-note-products', 'getGoodsReceivedNoteProducts');
            Route::post('goods-received-notes/create', 'store');
        });

        Route::controller(StockTransferController::class)->group(function (): void {
            Route::get('get-paginated-stock-transfers', 'getPaginatedStockTransfers');
            Route::get('get-stock-transfer-items', 'getStockTransferItemsByStockTransferId');
            Route::get('get-transfer-types', 'getTransferTypes');
            Route::get('get-status-list', 'getStatusList');
        });

        Route::controller(PurchaseOrderController::class)->group(function (): void {
            Route::get('get-paginated-purchase-orders', 'getPaginatedPurchaseOrders');
            Route::get('get-purchase-order-items', 'getItemsByPurchaseOrderId');
            Route::get('get-statuses', 'getStatuses');
            Route::get('get-order-types', 'getOrderTypes');
        });

        Route::controller(VendorController::class)->group(function (): void {
            Route::get('vendors', 'getVendors');
        });

        Route::controller(PackageTypeController::class)->group(function (): void {
            Route::get('package-types', 'getList');
        });

        Route::controller(PurchaseOrderFulfillmentController::class)->group(function (): void {
            Route::post('add-shipping-details', 'addShippingDetails');
            Route::get('get-paginated-purchase-order-delivery-orders', 'getPaginatedDeliveryOrders');
        });

        Route::controller(NotificationController::class)->group(function (): void {
            Route::get('get-unread-notifications', 'getUnReadNotificationList');
            Route::get('get-archived-notifications', 'getArchivedNotificationList');
            Route::post('mark-as-read-notifications', 'markAsRead');
            Route::post('mark-as-unread-notifications', 'markAsUnRead');
        });
    });
});

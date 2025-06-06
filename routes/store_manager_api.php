<?php

declare(strict_types=1);

use App\Http\Controllers\Api\StoreManager\CashbackController;
use App\Http\Controllers\Api\StoreManager\CashierController;
use App\Http\Controllers\Api\StoreManager\ClosedCounterReportController;
use App\Http\Controllers\Api\StoreManager\ConfigurationController;
use App\Http\Controllers\Api\StoreManager\CounterController;
use App\Http\Controllers\Api\StoreManager\DashboardController;
use App\Http\Controllers\Api\StoreManager\DayCloseController;
use App\Http\Controllers\Api\StoreManager\DreamPriceController;
use App\Http\Controllers\Api\StoreManager\EmployeeController;
use App\Http\Controllers\Api\StoreManager\GoodsReceivedNoteController;
use App\Http\Controllers\Api\StoreManager\LoyaltyCampaignController;
use App\Http\Controllers\Api\StoreManager\MemberController;
use App\Http\Controllers\Api\StoreManager\NotificationController;
use App\Http\Controllers\Api\StoreManager\PackageTypeController;
use App\Http\Controllers\Api\StoreManager\ProductController;
use App\Http\Controllers\Api\StoreManager\PromoterController;
use App\Http\Controllers\Api\StoreManager\PromotionController;
use App\Http\Controllers\Api\StoreManager\PurchaseOrderController;
use App\Http\Controllers\Api\StoreManager\PurchaseOrderFulfillmentController;
use App\Http\Controllers\Api\StoreManager\SaleController;
use App\Http\Controllers\Api\StoreManager\SalesTargetController;
use App\Http\Controllers\Api\StoreManager\StockTransferController;
use App\Http\Controllers\Api\StoreManager\StoreController;
use App\Http\Controllers\Api\StoreManager\StoreManagerAuthorizationCodeController;
use App\Http\Controllers\Api\StoreManager\StoreManagerController;
use App\Http\Controllers\Api\StoreManager\TokenController;
use App\Http\Controllers\Api\StoreManager\VendorController;
use App\Http\Controllers\Api\StoreManager\VoucherConfigurationController;
use App\Http\Controllers\Api\StoreManager\VoucherController;
use Illuminate\Support\Facades\Route;

Route::prefix('store-manager')->name('store_manager.')->group(function (): void {
    Route::post('get-token', [TokenController::class, 'issueToken'])->name('get_token');

    Route::middleware(['auth:sanctum'])->group(function (): void {
        Route::controller(TokenController::class)->group(function (): void {
            Route::get('logout', 'logout');
            Route::post('set-fcm-token', 'setFcmToken');
        });
        Route::controller(ConfigurationController::class)->group(function (): void {
            Route::get('get-configuration', 'getConfiguration');
        });
        Route::controller(StoreController::class)->group(function (): void {
            Route::get('get-stores', 'getStores');
            Route::get('get-store-stock/{productId}', 'getStoreStock');
        });
        Route::controller(PromoterController::class)->group(function (): void {
            Route::get('get-promoters', 'getLists');
            Route::get('get-top-ten-promoters', 'getTopPromoters');
            Route::post('update-status', 'updateStatus');
        });
        Route::controller(ProductController::class)->group(function (): void {
            Route::get('get-products', 'getProducts');
            Route::post('update-product-prices/{productId}', 'updateProductPrices');
            Route::get('get-product-details/{productId}/{locationId}', 'getProductDetails');
        });
        Route::controller(PromotionController::class)->group(function (): void {
            Route::get('get-promotions', 'getPromotions');
            Route::get('get-promotions/{locationId}', 'getStoreWisePromotion');
            Route::get('get-manual-promotions', 'getManualPromotions');
            Route::get('get-manual-promotions/{locationId}', 'getStoreWiseManualPromotion');
            Route::get('get-manual-promotion-with-promo-code/{promoCode}', 'getPromotionWithPromoCode');
        });
        Route::controller(CashierController::class)->group(function (): void {
            Route::get('get-cashiers', 'getCashiers');
        });
        Route::controller(CounterController::class)->group(function (): void {
            Route::get('get-counters', 'getCounters');
        });
        Route::controller(MemberController::class)->group(function (): void {
            Route::get('get-paginated-members', 'getPaginatedList');
            Route::get('member-static-details', 'memberStaticDetails');
            Route::post('add-member', 'addMember');
            Route::get('get-member-preference/{memberId}', 'getMemberPreference');
        });
        Route::controller(EmployeeController::class)->group(function (): void {
            Route::get('get-paginated-employees', 'getPaginatedList');
            Route::post('store-employee', 'store');
            Route::get('get-employee-details/{id}', 'getEmployeeDetails');
            Route::post('update-employee/{employeeId}', 'update')->name('employee.update');
            Route::get('get-employee-group-list', 'getEmployeeGroupList');
            Route::get('get-job-type-list', 'getJobTypeList');
            Route::get('get-designation-list', 'getDesignationList');
            Route::get('email-verification', 'emailVerification');
        });
        Route::controller(DreamPriceController::class)->group(function (): void {
            Route::get('get-dream-prices', 'getDreamPrices');
        });
        Route::controller(CashbackController::class)->group(function (): void {
            Route::get('get-cashbacks', 'getCashbacks');
            Route::get('get-cashbacks/{locationId}', 'getStoreWiseCashbacks');
        });
        Route::controller(VoucherConfigurationController::class)->group(function (): void {
            Route::get('get-vouchers-configuration', 'getVouchersConfiguration');
        });
        Route::controller(VoucherController::class)->group(function (): void {
            Route::get('get-vouchers/{locationId}', 'getStoreWiseVouchers');
        });
        Route::controller(LoyaltyCampaignController::class)->group(function (): void {
            Route::get('get-loyalty-campaigns', 'getLoyaltyCampaigns');
        });
        Route::controller(StoreManagerController::class)->group(function (): void {
            Route::post('profile-update', 'updateProfile');
            Route::get('profile-details', 'getProfileDetails')->name('profile_details');
        });
        Route::controller(DashboardController::class)->group(function (): void {
            Route::get('get-dashboard-data', 'getDashboardData');
            Route::get('get-transfer-statuses-data', 'getTransferStatusesData');
            Route::get('get-dashboard-all-details', 'getDashboardAllDetails');
            Route::get('get-top-ten-promoter', 'getTopTenPromoter');
        });
        Route::controller(SaleController::class)->group(function (): void {
            Route::get('sales', 'getSales');
            Route::get('sales-details/{saleId}/{saleType}/{locationId}', 'getSaleDetails');
        });
        Route::controller(DayCloseController::class)->group(function (): void {
            Route::get('get-counters-for-day-close', 'getCountersForDayClose');
            Route::post('close-counter/{locationId}/{id}', 'closeCounter');
            Route::get('get-counter-details', 'counterDetails');
            Route::post('day-close', 'dayClose');
        });
        Route::controller(ClosedCounterReportController::class)->group(function (): void {
            Route::get('get-closed-counters', 'getClosedCounters');
            Route::get('get-closed-counter-details', 'getClosedCounterDetails');
        });
        Route::controller(StockTransferController::class)->group(function (): void {
            Route::get('get-paginated-stock-transfers', 'getPaginatedStockTransfers');
            Route::get('get-stock-transfer-items', 'getStockTransferItemsByStockTransferId');
            Route::get('get-status-list', 'getStatusList');
            Route::get('get-transfer-types', 'getTransferTypes');
        });
        Route::controller(GoodsReceivedNoteController::class)->group(function (): void {
            Route::get('get-goods-received-notes', 'getGoodsReceivedNotes');
            Route::get('get-goods-received-note-products', 'getGoodsReceivedNoteProducts');
            Route::post('goods-received-notes/create', 'store');
        });
        Route::controller(PurchaseOrderController::class)->group(function (): void {
            Route::get('get-paginated-purchase-orders', 'getPaginatedPurchaseOrders');
            Route::get('get-purchase-order-items', 'getItemsByPurchaseOrderId');
            Route::get('get-statuses', 'getStatuses');
            Route::get('get-order-types', 'getOrderTypes');
        });
        Route::controller(SalesTargetController::class)->group(function (): void {
            Route::get('get-time-interval-types', 'getTimeIntervalTypes');
            Route::get('get-sales-targets', 'getSalesTargets');
            Route::get('get-sales-target-details/{salesTargetId}/{locationId}', 'getSalesTargetDetails');
            Route::get('get-sales-targets-by-promoter', 'getSalesTargetsByPromoter');
            Route::get(
                'get-sales-target-details-by-promoter/{salesTargetId}/{locationId}',
                'getSalesTargetDetailsByPromoter'
            );
        });
        Route::controller(PackageTypeController::class)->group(function (): void {
            Route::get('package-types', 'getList');
        });
        Route::controller(PurchaseOrderFulfillmentController::class)->group(function (): void {
            Route::post('add-shipping-details', 'addShippingDetails');
            Route::get('get-paginated-purchase-order-delivery-orders', 'getPaginatedDeliveryOrders');
        });
        Route::controller(VendorController::class)->group(function (): void {
            Route::get('vendors', 'getVendors');
        });
        Route::controller(StoreManagerAuthorizationCodeController::class)->group(function (): void {
            Route::get('get-authorization-code', 'getAuthorizationCode');
        });
        Route::controller(NotificationController::class)->group(function (): void {
            Route::get('get-unread-notifications', 'getUnReadNotificationList');
            Route::get('get-archived-notifications', 'getArchivedNotificationList');
            Route::post('mark-as-read-notifications', 'markAsRead');
            Route::post('mark-as-unread-notifications', 'markAsUnRead');
        });
    });
});

<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Promoter\CashbackController;
use App\Http\Controllers\Api\Promoter\ConfigurationController;
use App\Http\Controllers\Api\Promoter\DashboardController;
use App\Http\Controllers\Api\Promoter\MemberController;
use App\Http\Controllers\Api\Promoter\NotificationController;
use App\Http\Controllers\Api\Promoter\PromoterCommissionController;
use App\Http\Controllers\Api\Promoter\PromoterController;
use App\Http\Controllers\Api\Promoter\PromotionController;
use App\Http\Controllers\Api\Promoter\SaleController;
use App\Http\Controllers\Api\Promoter\SalesTargetController;
use App\Http\Controllers\Api\Promoter\TokenController;
use App\Http\Controllers\Api\Promoter\VoucherController;
use Illuminate\Support\Facades\Route;

Route::prefix('promoter')->name('promoter.')->group(function (): void {
    Route::controller(TokenController::class)->group(function (): void {
        Route::post('get-token', 'issueToken')->name('get_token');
    });

    Route::middleware(['auth:sanctum'])->group(function (): void {
        Route::controller(TokenController::class)->group(function (): void {
            Route::get('logout', 'logout');
            Route::post('set-fcm-token', 'setFcmToken');
        });
        Route::controller(ConfigurationController::class)->group(function (): void {
            Route::get('get-configuration', 'getConfiguration');
        });
        Route::controller(PromoterController::class)->name('promoter.')->group(function (): void {
            Route::get('promoter-products', 'getPaginatedProductList');
            Route::get('get-stores', 'getStoreList');
            Route::get('get-product-details/{productId}/{locationId}', 'getProductDetails');
            Route::get('get-store-stock/{productId}', 'getStoreStock');
            Route::post('profile-update', 'updateProfile')->name('update_profile');
            Route::get('profile-details', 'getProfileDetails')->name('profile_details');
            Route::get('email-verification', 'emailVerification');
        });
        Route::controller(PromoterCommissionController::class)->name('promoter_commission.')->group(
            function (): void {
                Route::get('get-promoter-commission-history', 'getPaginatedPromoterCommissionHistory');
                Route::get('get-promoter-commission-by-single-date', 'getCommissionHistoryBySingleDate');
                Route::get('get-promoter-commission-details/{promoterCommissionId}', 'getPromoterCommissionDetails');
            }
        );
        Route::controller(SaleController::class)->name('sales.')->group(function (): void {
            Route::get('sales', 'getPaginatedSaleHistory');
            Route::get('get-sale-by-single-date', 'getSaleHistoryBySingleDate');
            Route::get('get-item-details/{id}/{type}', 'getItemDetails');
        });
        Route::controller(DashboardController::class)->group(function (): void {
            Route::get('get-dashboard-data', 'getDashboardData');
        });
        Route::controller(MemberController::class)->name('members.')->group(function (): void {
            Route::post('save-member/{storeId}', 'store')->name('store');
            Route::get('get-paginated-members', 'getPaginatedList');
            Route::get('member-static-details', 'memberStaticDetails');
            Route::post('add-member', 'addMember');
            Route::get('get-member-preference/{memberId}', 'getMemberPreference');
        });
        Route::controller(SalesTargetController::class)->group(function (): void {
            Route::get('get-time-interval-types', 'getTimeIntervalTypes');
            Route::get('get-sales-targets', 'getSalesTargets');
            Route::get('get-sales-target-details/{salesTargetId}', 'getSalesTargetDetails');
        });
        Route::controller(CashbackController::class)->group(function (): void {
            Route::get('get-cashbacks/{locationId}', 'getStoreWiseCashbacks');
        });
        Route::controller(PromotionController::class)->group(function (): void {
            Route::get('get-promotions/{locationId}', 'getStoreWisePromotion');
            Route::get('get-manual-promotions/{locationId}', 'getStoreWiseManualPromotion');
            Route::get('get-manual-promotion-with-promo-code/{promoCode}', 'getPromotionWithPromoCode');
        });
        Route::controller(VoucherController::class)->group(function (): void {
            Route::get('get-vouchers/{locationId}', 'getStoreWiseVouchers');
        });
        Route::controller(NotificationController::class)->group(function (): void {
            Route::get('get-unread-notifications', 'getUnReadNotificationList');
            Route::get('get-archived-notifications', 'getArchivedNotificationList');
            Route::post('mark-as-read-notifications', 'markAsRead');
            Route::post('mark-as-unread-notifications', 'markAsUnRead');
        });
    });
});

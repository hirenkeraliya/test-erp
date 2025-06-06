<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Member\Auth\LoginController;
use App\Http\Controllers\Api\Member\ConfigurationController;
use App\Http\Controllers\Api\Member\MemberAddressController;
use App\Http\Controllers\Api\Member\MemberController;
use App\Http\Controllers\Api\Member\NotificationController;
use App\Http\Controllers\Api\Member\OrderController;
use App\Http\Controllers\Api\Member\SaleController;
use App\Http\Controllers\Api\Member\StoreController;
use App\Http\Controllers\Api\Member\TokenController;
use App\Http\Controllers\Api\Member\VoucherConfigurationController;
use App\Http\Controllers\Api\Member\VoucherController;
use Illuminate\Support\Facades\Route;

Route::prefix('member')->name('member.')->group(function (): void {
    Route::controller(LoginController::class)->group(function (): void {
        Route::post('send-otp', 'sendOtp');
        Route::post('validate-otp', 'validateOtp');
    });

    Route::controller(MemberController::class)->group(function (): void {
        Route::post('register-member', 'registerMember');
    });
});

Route::middleware('auth:sanctum')->group(function (): void {
    Route::prefix('member')->name('member.')->group(function (): void {
        Route::controller(LoginController::class)->group(function (): void {
            Route::post('logout', 'logout');
            Route::post('get-ecommerce-token', 'getEcommerceToken');
        });

        Route::controller(TokenController::class)->group(function (): void {
            Route::post('set-fcm-token', 'setFcmToken');
        });

        Route::controller(ConfigurationController::class)->group(function (): void {
            Route::get('get-configuration', 'getConfiguration');
        });

        Route::controller(MemberController::class)->group(function (): void {
            Route::get('member/vouchers', 'getPaginatedVoucherList');
            Route::get('get-genders', 'getGenders');
            Route::get('get-titles', 'getTitles');
            Route::get('get-races', 'getRaces');
            Route::get('get-voucher-statuses', 'getVoucherStatuses');
            Route::post('profile-update', 'updateProfile');
            Route::get('transactions-list', 'getPaginatedTransactionList');
            Route::post('upload-profile-photo', 'uploadProfilePhoto');
            Route::get('member-details', 'memberDetails');
            Route::post('delete-member', 'deleteMember');
            Route::get('email-verification', 'emailVerification');
            Route::get('get-loyalty-point-update-details/{loyaltyPointUpdateId}', 'getLoyaltyPointUsedDetails');
        });

        Route::controller(VoucherConfigurationController::class)->group(function (): void {
            Route::get('get-loyalty-point-voucher-configuration', 'getLoyaltyPointVoucherConfiguration');
        });

        Route::controller(SaleController::class)->group(function (): void {
            Route::get('get-paginated-sales', 'getPaginatedSaleList');
            Route::get('get-sale-details/{saleId}', 'getSaleDetails');
            Route::get('get-statuses', 'getStatuses');
        });

        Route::controller(OrderController::class)->group(function (): void {
            Route::get('get-paginated-orders', 'getPaginatedOrderList');
            Route::get('get-order-details/{orderId}', 'getOrderDetails');
        });

        Route::controller(VoucherController::class)->group(function (): void {
            Route::post('generate-member-loyalty-point-voucher', 'generateMemberLoyaltyPointVoucher');
            Route::get('get-voucher-used-details/{voucherId}', 'getVoucherUsedDetails');
        });

        Route::controller(StoreController::class)->group(function (): void {
            Route::get('get-paginated-stores', 'getPaginatedStoreList');
        });

        Route::controller(MemberAddressController::class)->group(function (): void {
            Route::post('save-member-address', 'store');
            Route::post('member-address/{memberAddressId}', 'update');
            Route::post('remove-member-address/{memberAddressId}', 'removeAddress');
        });

        Route::controller(NotificationController::class)->group(function (): void {
            Route::get('get-unread-notifications', 'getUnReadNotificationList');
            Route::get('get-archived-notifications', 'getArchivedNotificationList');
            Route::post('mark-as-read-notifications', 'markAsRead');
            Route::post('mark-as-unread-notifications', 'markAsUnRead');
        });
    });
});

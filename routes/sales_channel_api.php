<?php

declare(strict_types=1);

use App\Http\Controllers\Api\SaleChannel\Auth\LoginController;
use App\Http\Controllers\Api\SaleChannel\Banner\BannerController;
use App\Http\Controllers\Api\SaleChannel\Category\CategoryController;
use App\Http\Controllers\Api\SaleChannel\ColorGroup\ColorGroupController;
use App\Http\Controllers\Api\SaleChannel\ConfigurationController;
use App\Http\Controllers\Api\SaleChannel\Country\CountryController;
use App\Http\Controllers\Api\SaleChannel\DreamPrice\DreamPriceController;
use App\Http\Controllers\Api\SaleChannel\EmailController;
use App\Http\Controllers\Api\SaleChannel\Inventory\InventoryController;
use App\Http\Controllers\Api\SaleChannel\LoyaltyCampaign\LoyaltyCampaignController;
use App\Http\Controllers\Api\SaleChannel\MasterProduct\MasterProductController;
use App\Http\Controllers\Api\SaleChannel\Member\MemberController;
use App\Http\Controllers\Api\SaleChannel\MemberAddress\MemberAddressController;
use App\Http\Controllers\Api\SaleChannel\MemberGroup\MemberGroupController;
use App\Http\Controllers\Api\SaleChannel\MemberProductReview\MemberProductReviewController;
use App\Http\Controllers\Api\SaleChannel\Membership\MembershipController;
use App\Http\Controllers\Api\SaleChannel\Order\OrderController;
use App\Http\Controllers\Api\SaleChannel\Product\ProductController;
use App\Http\Controllers\Api\SaleChannel\ProductCollection\ProductCollectionController;
use App\Http\Controllers\Api\SaleChannel\Promotion\PromotionController;
use App\Http\Controllers\Api\SaleChannel\SmsController;
use App\Http\Controllers\Api\SaleChannel\Store\StoreController;
use App\Http\Controllers\Api\SaleChannel\Voucher\VoucherController;
use App\Http\Controllers\Api\SaleChannel\VoucherConfiguration\VoucherConfigurationController;
use Illuminate\Support\Facades\Route;

Route::prefix('sales-channel')->middleware('auth:sanctum')->name('sales_channel.')->group(function (): void {
    Route::controller(LoginController::class)->group(function (): void {
        Route::post('token-for-member-application', 'tokenForMemberApplication');
    });

    Route::post('send-sms-message', [SmsController::class, 'sendMessage']);
    Route::post('send-email-otp', [EmailController::class, 'sendEmailOtp']);

    Route::controller(PromotionController::class)->group(function (): void {
        Route::get('get-promotions', 'getPromotions');
    });

    Route::controller(ProductController::class)->group(function (): void {
        Route::get('get-paginated-products', 'getPaginatedList');
        Route::get('get-product-stock', 'getProductStock');
        Route::get('get-all-product-article-numbers', 'getArticleNumbers');
        Route::post('save-product-channel-reference', 'saveProductChannelReference');
    });

    Route::controller(MasterProductController::class)->group(function (): void {
        Route::get('get-all-master-product-article-numbers', 'getMasterProductArticleNumbers');
    });

    Route::controller(ProductCollectionController::class)->group(function (): void {
        Route::get('get-paginated-product-collections', 'getPaginatedList');
        Route::get('get-product-collection-product-ids', 'getProductIds');
    });

    Route::controller(ColorGroupController::class)->group(function (): void {
        Route::get('get-color-groups', 'getColorGroupList');
    });

    Route::controller(MemberController::class)->group(function (): void {
        Route::post('register-member', 'registerMember');
        Route::post('first-or-create-member', 'firstOrCreateMember');
        Route::get('member-exists', 'memberExists');
        Route::post('member-is-exists', 'memberIsExists');
        Route::post('update-member/{memberId}', 'update');
        Route::get('get-paginated-members', 'getPaginatedList');
        Route::get('get-member-by-mobile-number', 'getMemberByMobileNumber');
        Route::post('fetch-member-by-mobile-number', 'fetchMemberByMobile');
        Route::post('delete-member/{memberId}', 'deleteMember');
    });

    Route::controller(MemberGroupController::class)->group(function (): void {
        Route::get('get-paginated-member-groups', 'list');
        Route::get('get-paginated-member-ids', 'getMemberIds');
        Route::post('save-member-group', 'store');
        Route::post('update-member-group/{memberGroupId}', 'update');
    });

    Route::controller(CategoryController::class)->group(function (): void {
        Route::get('get-categories', 'getCategoriesList');
    });

    Route::controller(InventoryController::class)->group(function (): void {
        Route::get('get-stores-by-products', 'getStoresByProducts');
    });

    Route::controller(DreamPriceController::class)->group(function (): void {
        Route::get('dream-prices', 'getList');
        Route::get('get-dream-price-product-ids', 'getDreamPriceProductList');
    });

    Route::controller(BannerController::class)->group(function (): void {
        Route::get('banners', 'getList');
        Route::get('action-types', 'getActionTypes');
    });

    Route::controller(ConfigurationController::class)->group(function (): void {
        Route::get('get-configuration', 'getConfiguration');
        Route::post('get-ecommerce-configuration', 'getEcommerceConfiguration');
        Route::post('get-e-commerce-token', 'getEcommerceToken');
    });

    Route::controller(StoreController::class)->group(function (): void {
        Route::get('get-stores', 'getStoreList');
    });

    Route::controller(CountryController::class)->group(function (): void {
        Route::get('get-countries', 'getCountryList');
    });

    Route::controller(MemberAddressController::class)->group(function (): void {
        Route::post('save-member-address', 'store');
        Route::post('member-address/{memberAddressId}', 'update');
        Route::post('remove-member-address/{memberAddressId}', 'removeAddress');
        Route::get('get-member-addresses/{memberId}', 'getList');
    });

    Route::controller(OrderController::class)->group(function (): void {
        Route::post('save-orders', 'saveOrderDetails');
        Route::post('update-status', 'updateStatus');
        Route::get('get-statuses', 'getStatuses');
        Route::post('update-order-tracking-details/{orderId}', 'updateOrderTrackingDetails');
        Route::post('update-order-address', 'updateOrderAddress');
        Route::post('get-order-ids/{externalOrderId}', 'getOrderIds');
        Route::get('get-paginated-orders', 'getPaginatedOrders');
        Route::get('order-details/{orderId}', 'getOrderDetailsById');
    });

    Route::controller(VoucherConfigurationController::class)->group(function (): void {
        Route::get('get-voucher-configurations', 'getVoucherConfigurations');
    });

    Route::controller(LoyaltyCampaignController::class)->group(function (): void {
        Route::get('get-loyalty-campaign-configurations', 'getLoyaltyCampaignConfigurations');
    });

    Route::controller(MembershipController::class)->group(function (): void {
        Route::get('get-memberships', 'getMembership');
    });

    Route::controller(VoucherController::class)->group(function (): void {
        Route::get('get-vouchers', 'getVouchers');
        Route::post('generate-member-loyalty-point-voucher', 'generateMemberLoyaltyPointVoucher');
    });

    Route::controller(MemberProductReviewController::class)->group(function (): void {
        Route::post('customer-product-review', 'customerProductReview');
    });
});

<?php

declare(strict_types=1);

use App\Domains\Cashier\CashierQueries;
use App\Domains\MemberGroup\DataObjects\PaginatedMemberGroupListDataForPos;
use App\Http\Controllers\Api\Pos\AdvertiseVideoController;
use App\Http\Controllers\Api\Pos\AttributeController;
use App\Http\Controllers\Api\Pos\Auth\LoginController;
use App\Http\Controllers\Api\Pos\BookingPaymentController;
use App\Http\Controllers\Api\Pos\BrandController;
use App\Http\Controllers\Api\Pos\CashbackController;
use App\Http\Controllers\Api\Pos\CashierController;
use App\Http\Controllers\Api\Pos\CashierPermissionController;
use App\Http\Controllers\Api\Pos\CashMovementController;
use App\Http\Controllers\Api\Pos\CashMovementReasonController;
use App\Http\Controllers\Api\Pos\CategoryController;
use App\Http\Controllers\Api\Pos\ComplimentaryItemReasonController;
use App\Http\Controllers\Api\Pos\ConfigurationController;
use App\Http\Controllers\Api\Pos\CounterController;
use App\Http\Controllers\Api\Pos\CounterUpdateDeclarationAttemptController;
use App\Http\Controllers\Api\Pos\CounterUpdateEventController;
use App\Http\Controllers\Api\Pos\CreditNoteController;
use App\Http\Controllers\Api\Pos\CreditSaleController;
use App\Http\Controllers\Api\Pos\DenominationController;
use App\Http\Controllers\Api\Pos\DepartmentController;
use App\Http\Controllers\Api\Pos\DirectorController;
use App\Http\Controllers\Api\Pos\DreamPriceController;
use App\Http\Controllers\Api\Pos\EmployeeGroupController;
use App\Http\Controllers\Api\Pos\GiftCardController;
use App\Http\Controllers\Api\Pos\HappyHourDiscountController;
use App\Http\Controllers\Api\Pos\HoldSaleController;
use App\Http\Controllers\Api\Pos\LayawaySaleController;
use App\Http\Controllers\Api\Pos\LoyaltyCampaignController;
use App\Http\Controllers\Api\Pos\MemberAddressController;
use App\Http\Controllers\Api\Pos\MemberController;
use App\Http\Controllers\Api\Pos\MemberGroupController;
use App\Http\Controllers\Api\Pos\MembershipController;
use App\Http\Controllers\Api\Pos\MysteryGiftUsageController;
use App\Http\Controllers\Api\Pos\PaymentTypeController;
use App\Http\Controllers\Api\Pos\ProductCollectionController;
use App\Http\Controllers\Api\Pos\ProductController;
use App\Http\Controllers\Api\Pos\PromoterController;
use App\Http\Controllers\Api\Pos\PromotionController;
use App\Http\Controllers\Api\Pos\SaleController;
use App\Http\Controllers\Api\Pos\SaleReturnController;
use App\Http\Controllers\Api\Pos\SaleReturnReasonController;
use App\Http\Controllers\Api\Pos\SerialNumberController;
use App\Http\Controllers\Api\Pos\StoreController;
use App\Http\Controllers\Api\Pos\StoreManagerController;
use App\Http\Controllers\Api\Pos\StyleController;
use App\Http\Controllers\Api\Pos\UnitOfMeasureController;
use App\Http\Controllers\Api\Pos\VoidSaleController;
use App\Http\Controllers\Api\Pos\VoidSaleReasonController;
use App\Http\Controllers\Api\Pos\VoucherConfigurationController;
use App\Http\Controllers\Api\Pos\VoucherController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('pos')->name('pos.')->group(function (): void {
    Route::middleware('auth:sanctum')->group(function (): void {
        Route::controller(LoginController::class)->group(function (): void {
            Route::post('logout', 'logout');
            Route::get('me', 'me');
        });
        Route::controller(ConfigurationController::class)->group(function (): void {
            Route::get('get-configuration', 'getConfiguration');
        });
        Route::controller(CounterController::class)->group(function (): void {
            Route::get('store-counters/{locationId}', 'getStoreCounters');
            Route::post('open-counter', 'openCounter');
            Route::get('get-currently-open-counter-details', 'getCurrentlyOpenCounterDetails');
            Route::post('close-counter', 'closeCounter');
            Route::get('get-current-counter-closing-details', 'getCurrentCounterClosingDetails');
            Route::get('get-last-thirty-days-closed-counters', 'getPaginatedLastThirtyDaysClosedCounters');
            Route::get('closed-counter/{counterUpdateId}/sales', 'closedCounterSales');
            Route::get('get-counter-open-status', 'getCounterOpenStatus');
        });
        Route::get(
            'cashier-stores',
            fn (Request $request, CashierQueries $cashierQueries): array => (new StoreController())->cashierStores(
                $request,
                $cashierQueries
            )
        );
        Route::get(
            'cashier-permissions-list',
            fn (): array => (new CashierPermissionController())->getCashierPermissionsList()
        );
        Route::get(
            'get-paginate-member-group-list',
            fn (Request $request, PaginatedMemberGroupListDataForPos $paginatedMemberGroupListDataForPos): array => (new MemberGroupController())->getPaginateMemberGroup(
                $request,
                $paginatedMemberGroupListDataForPos
            )
        );
        Route::get(
            'get-all-promoters',
            fn (Request $request): array => (new PromoterController())->getList($request)
        );
        Route::get(
            'void-sale-reasons',
            fn (Request $request): array => (new VoidSaleReasonController())->getList($request)
        );
        Route::get(
            'denominations',
            fn (Request $request): array => (new DenominationController())->getList($request)
        );
        Route::controller(LoyaltyCampaignController::class)->group(function (): void {
            Route::get('loyalty-campaigns', 'getList');
        });
        Route::get('directors', fn (Request $request): array => (new DirectorController())->getList($request));
        Route::get('cashiers', fn (Request $request): array => (new CashierController())->getList($request));
        Route::get(
            'store-manager-list',
            fn (Request $request): array => (new StoreManagerController())->getList($request)
        );
        Route::get(
            'complimentary-item-reasons',
            fn (Request $request): array => (new ComplimentaryItemReasonController())->getList($request)
        );
        Route::get(
            'sale-return-reasons',
            fn (Request $request): array => (new SaleReturnReasonController())->getList($request)
        );
        Route::controller(ProductController::class)->group(function (): void {
            Route::get('products', 'getList');
            Route::get('get-product-stock-for-all-stores', 'getProductStockForAllStores');
            Route::get('get-products-zip', 'getProductsZip');
        });
        Route::controller(EmployeeGroupController::class)->group(function (): void {
            Route::get('get-paginate-employee-group-list', 'getPaginateEmployeeGroup');
        });
        Route::controller(MemberController::class)->name('members.')->group(function (): void {
            Route::get('get-paginated-members', 'getPaginatedList');
            Route::get('get-member-types', 'getTypes');
            Route::get('get-genders', 'getGenders');
            Route::get('get-titles', 'getTitles');
            Route::get('get-races', 'getRaces');
            Route::get('get-statuses', 'getStatuses');
            Route::get('members/{memberId}', 'getMember');
            Route::post('save-member', 'store')->name('store');
            Route::post('members/{memberId}', 'update')->name('update');
            Route::get('get-employee-members', 'getEmployeeMembers');
        });
        Route::get(
            'get-payment-types',
            fn (Request $request): array => (new PaymentTypeController())->getList($request)
        );
        Route::get(
            'get-all-unit-of-measures',
            fn (Request $request): array => (new UnitOfMeasureController())->getList($request)
        );
        Route::controller(SaleController::class)->group(function (): void {
            Route::post('save-sale-details', 'saveDetails');
            Route::get('get-paginated-regular-and-completed-layaway-sales', 'getPaginatedRegularAndCompletedSales');
            Route::get('get-sales-by-promoter/{promoterId}', 'getSalesByPromoter');
            Route::get('get-sale-details/{saleId}', 'getSaleDetails');
            Route::get('get-price-override-types', 'getPriceOverrideTypes');
            Route::get('get-sale-statuses', 'getSaleStatuses');
        });
        Route::controller(SaleReturnController::class)->group(function (): void {
            Route::get('get-filtered-and-paginated-sale-returns', 'getFilteredAndPaginatedSaleReturns');
        });
        Route::get(
            'cash-movement-reasons',
            fn (Request $request): array => (new CashMovementReasonController())->getList($request)
        );
        Route::controller(CashMovementController::class)->group(function (): void {
            Route::get('get-cash-movement-authorizer-types', 'getAuthorizerTypes');
            Route::get('get-cash-movement-details/{cashMovementId}', 'getCashMovementDetails');
            Route::post('cash-movements', 'store');
            Route::get('get-paginated-cash-movements', 'getPaginatedCashMovements');
        });
        Route::controller(VoidSaleController::class)->group(function (): void {
            Route::post('sales/{saleId}/void', 'store');
            Route::get('get-paginated-voided-sales', 'getPaginatedVoidedSales');
        });
        Route::controller(PromotionController::class)->group(function (): void {
            Route::get('promotions', 'getList');
            Route::get('get-paginated-manual-promotion', 'getPaginatedManualPromotion');
            Route::get('get-promotion-with-promo-code/{promoCode}', 'getPromotionWithPromoCode');
        });
        Route::controller(DreamPriceController::class)->group(function (): void {
            Route::get('dream-prices', 'getList');
        });
        Route::controller(BookingPaymentController::class)->group(function (): void {
            Route::get('get-booking-payment-statuses', 'getBookingPaymentStatuses');
            Route::get('get-paginated-booking-payments', 'getPaginatedBookingPayments');
            Route::get('get-booking-payment-details/{bookingPaymentId}', 'getBookingPaymentDetails');
            Route::post('booking-payments', 'store');
            Route::post('booking-payments/{bookingPaymentId}/refund', 'bookingPaymentRefund');
            Route::post('booking-payments/{bookingPaymentId}/top-up', 'bookingPaymentTopUp');
            Route::post('reset-booking-payment-products/{bookingPaymentId}', 'resetBookingPaymentProducts');
        });
        Route::controller(VoucherConfigurationController::class)->group(function (): void {
            Route::get('vouchers', 'getList');
            Route::get('get-birthday-voucher-configuration', 'getBirthdayVoucherConfiguration');
            Route::get('get-loyalty-point-voucher-configuration', 'getLoyaltyPointVoucherConfiguration');
        });
        Route::controller(CashbackController::class)->group(function (): void {
            Route::get('cashback-list', 'getList');
        });
        Route::controller(LayawaySaleController::class)->group(function (): void {
            Route::get('get-pending-layaway-sales', 'getPendingLayawaySales');
            Route::get('get-pending-layaway-sale/{saleId}', 'getPendingLayawaySale');
            Route::post('complete-layaway-sale/{saleId}', 'completeLayawaySale');
            Route::post('cancel-layaway-sale/{saleId}', 'cancelLayawaySale');
        });
        Route::controller(CreditSaleController::class)->group(function (): void {
            Route::get('get-pending-credit-sales', 'getPendingCreditSales');
            Route::get('get-pending-credit-sale/{saleId}', 'getPendingCreditSale');
            Route::post('complete-credit-sale/{saleId}', 'completeCreditSale');
            Route::get('get-total-credit-pending-amount', 'getTotalCreditPendingAmount');
            Route::post('cancel-credit-sale/{saleId}', 'cancelCreditSale');
        });
        Route::controller(VoucherController::class)->group(function (): void {
            Route::get('get-paginated-vouchers', 'getPaginatedList');
            Route::get('get-voucher-statuses', 'getStatuses');
            Route::post('generate-member-birthday-voucher/{memberId}', 'generateMemberBirthdayVoucher');
            Route::get('get-member-active-birthday-voucher/{memberId}', 'getActiveBirthdayVoucher');
            Route::post('generate-member-loyalty-point-voucher', 'generateMemberLoyaltyPointVoucher');
        });
        Route::controller(CreditNoteController::class)->group(function (): void {
            Route::get('get-paginated-active-credit-notes', 'getPaginatedListOfActiveCreditNotes');
            Route::get('get-credit-note-statuses', 'getStatuses');
            Route::get('get-credit-note-details/{creditNoteId}', 'getCreditNoteDetails')->whereNumber('creditNoteId');
            Route::post('credit-notes/{creditNote}/refund', 'refundCreditNote');
        });
        Route::controller(MembershipController::class)->group(function (): void {
            Route::get('get-memberships', 'getList');
        });
        Route::controller(GiftCardController::class)->group(function (): void {
            Route::get('get-gift-cards', 'getPaginatedList');
            Route::get('gift-card-static-details', 'getStaticDetails');
        });
        Route::controller(CounterUpdateEventController::class)->group(function (): void {
            Route::get('get-counter-update-events', 'getList');
            Route::get('counter-update-events-details', 'getStaticDetails');
            Route::post('counter-update-events', 'store');
        });
        Route::controller(CounterUpdateDeclarationAttemptController::class)->group(function (): void {
            Route::get('get-counter-update-declaration-attempts', 'getList');
            Route::post('counter-update-declaration-attempt', 'store');
        });
        Route::controller(AdvertiseVideoController::class)->group(function (): void {
            Route::get('get-advertisements', 'getList');
        });
        Route::controller(HoldSaleController::class)->group(function (): void {
            Route::get('get-hold-sale-types', 'getTypes');
            Route::post('save-hold-sale-details', 'saveDetails');
            Route::post('cancel-hold-sale', 'cancelHoldSale');
            Route::post('complete-hold-sale', 'completeHoldSale');
            Route::post('released-hold-sale', 'releasedHoldSale');
        });
        Route::controller(BrandController::class)->group(function (): void {
            Route::get('get-brands', 'getList');
        });
        Route::controller(CategoryController::class)->group(function (): void {
            Route::get('get-categories', 'getList');
        });
        Route::controller(StyleController::class)->group(function (): void {
            Route::get('get-styles', 'getList');
        });
        Route::controller(AttributeController::class)->group(function (): void {
            Route::get('get-attributes', 'getList');
        });
        Route::controller(MemberAddressController::class)->group(function (): void {
            Route::post('save-member-address', 'store');
            Route::post('member-address/{memberAddressId}', 'update');
            Route::post('remove-member-address/{memberAddressId}', 'removeAddress');
        });
        Route::controller(DepartmentController::class)->group(function (): void {
            Route::get('get-departments', 'getList');
        });
        Route::controller(HappyHourDiscountController::class)->group(function (): void {
            Route::get('get-happy-hour-discounts', 'getPaginateHappyHourDiscountList');
            Route::get('get-happy-hour-product-types', 'getProductTypes');
            Route::post('save-happy-hours', 'store');
        });
        Route::controller(ProductCollectionController::class)->group(function (): void {
            Route::get('get-paginated-product-collections', 'getPaginatedList');
        });
        Route::controller(SerialNumberController::class)->group(function (): void {
            Route::get('get-serial-number-detail/{number}', 'getSerialNumberDetail');
        });
        Route::controller(MysteryGiftUsageController::class)->group(function (): void {
            Route::post('get-coupon-code-details', 'getCouponCodeDetails');
            Route::post('update-coupon-code-details', 'updateCouponCodeDetails');
        });
    });
    Route::controller(LoginController::class)->group(function (): void {
        Route::post('login', 'login');
        Route::get('get-url-from-configuration-key', 'getUrlFromConfigurationKey');
    });
    Route::get('get-current-time', fn (): array => [
        'current_time' => now()->format('Y-m-d H:i:s'),
    ]);
});

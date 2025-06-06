<?php

declare(strict_types=1);

use App\Http\Controllers\Front\ContactController;
use App\Http\Controllers\Front\DigitalInvoiceController;
use App\Http\Controllers\Front\EmailVerification\EmailVerificationController;
use App\Http\Controllers\Front\GenuineProductVerification\GenuineProductVerificationController;
use App\Http\Controllers\Front\GenuineReceiptVerification\GenuineReceiptVerificationController;
use App\Http\Controllers\Front\Member\MemberController;
use App\Http\Controllers\Front\MysteryGift\MysteryGiftController;
use App\Http\Controllers\Front\PdpaController;
use App\Http\Controllers\Front\PrivacyPolicyController;
use App\Http\Controllers\Front\SalesReturnsPolicyController;
use App\Http\Controllers\Front\TermsConditionsController;
use Illuminate\Support\Facades\Route;

Route::name('front.')->group(function (): void {
    Route::view('/login', 'front/login')->name('login');

    Route::controller(MemberController::class)->name('member.')->group(function (): void {
        Route::get('{store}/member-registration', 'index')->name('member_add_view');
        Route::post('{store}/member-registration', 'store')->name('member_add_store');
        Route::get('member-registered/thank-you', 'thankYou')->name('member_thank_you');
    });

    Route::controller(DigitalInvoiceController::class)->name('digital_invoice.')->group(function (): void {
        Route::get('front/e-invoice-details/{storeId}/{counterId}/{type}/{offline_id}', 'index')->name(
            'digital_invoice_add_view'
        );
        Route::post('front/e-invoice-details/{storeId}/{counterId}/{type}/{offline_id}', 'store')->name(
            'digital_invoice_store'
        );
        Route::get('digital-invoice/thank-you/{isSubmitted?}', 'thankYou')->name('digital_invoice_thank_you');
    });
    Route::controller(GenuineProductVerificationController::class)->name('genuine_product_verification.')->group(
        function (): void {
            Route::get('verify-product', 'index')->name('index');
            Route::post('verify-product/add', 'store')->name('store');
            Route::get('verified-product', 'verifiedProduct')->name('verified_product');
            Route::post('verify-product/update', 'update')->name('update');
            Route::post('generate-verify-image', 'generateVerifiedImage')->name('generate_verified_image');
        }
    );

    Route::controller(GenuineReceiptVerificationController::class)->name('genuine_receipt_verification.')->group(
        function (): void {
            Route::get('verify-receipt', 'index')->name('index');
            Route::post('verify-receipt/add', 'store')->name('store');
            Route::get('verified-receipt', 'verifiedReceipt')->name('verified_receipt');
            Route::get('not-genuine-receipt', 'notGenuineReceipt')->name('not_genuine_receipt');
            Route::get('genuine-receipt-member', 'genuineReceiptMember')->name('genuine_receipt_member');
            Route::post('verify-receipt/add-not-genuine-receipt', 'addNotGenuineReceipt')->name(
                'add_not_genuine_receipt'
            );
            Route::post('verify-receipt/add-genuine-receipt-member', 'addGenuineReceiptMember')->name(
                'add_genuine_receipt_member'
            );
            Route::post('generate-verify-receipt', 'generateVerifiedReceipt')->name('generate_verified_receipt');
        }
    );

    Route::controller(MysteryGiftController::class)->name('mystery_gift.')->group(function (): void {
        Route::get('mystery-gifts', 'index')->name('index');
        Route::post('mystery-gifts/verify-receipt', 'verifyReceipt')->name('verify_receipt');
        Route::post('mystery-gifts/register-member', 'registerMember')->name('register_member');
        Route::post('mystery-gifts/get-reward', 'getReward')->name('get_reward');
    });

    Route::controller(EmailVerificationController::class)->name('email_verify.')->group(function (): void {
        Route::get('email/verify/{token}', [EmailVerificationController::class, 'verify'])
            ->name('verify');
    });

    Route::get('pdp-notice', [PdpaController::class, 'index'])->name('pdpa.policy');
    Route::get('contact-us', [ContactController::class, 'index'])->name('contact');
    Route::get('terms', [TermsConditionsController::class, 'index'])->name('terms.conditions');
    Route::get('privacy', [PrivacyPolicyController::class, 'index'])->name('privacy.policy');
    Route::get('return-exchange-policy', [SalesReturnsPolicyController::class, 'index'])->name('sales.returns.policy');
});

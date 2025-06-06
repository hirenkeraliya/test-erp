<?php

declare(strict_types=1);

use App\Domains\Company\DataObjects\CompanyData;
use App\Domains\Company\Enums\BookingPaymentRefundTypes;
use App\Domains\Company\Enums\CommissionTypes;
use App\Domains\Company\Enums\LocationAssignmentTypes;
use App\Http\Controllers\SuperAdmin\CompanyController;
use App\Models\Brand;
use App\Models\Company;
use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

test('company validation works', function (): void {
    Storage::fake('public');
    $uploadedFile = UploadedFile::fake()->image('avatar.jpg');
    $request = [
        'name' => '',
        'code' => '',
        'grn_format' => '',
        'legal_name' => '',
        'website' => '',
        'email' => '',
        'fax' => '',
        'address' => '',
        'employer_identification_number' => '',
        'social_security_number' => '',
        'void_sale_number_prefix' => '',
        'send_sale_email_to_member' => false,
        'new_member_free_loyalty_points' => 0,
        'commission_type_id' => CommissionTypes::BY_PROMOTER->value,
        'allow_price_override_cart_level' => false,
        'allow_negative_inventory' => true,
        'light_logo' => $uploadedFile,
        'dark_logo' => $uploadedFile,
        'email_footer_logo' => $uploadedFile,
        'brand_ids' => null,
        'allow_happy_hour_discount' => true,
        'currency_rate_auto_update' => true,
    ];
    CompanyData::validate($request);
})->throws(ValidationException::class);

test('unique name validation works while adding a company', function (): void {
    Company::factory()->create([
        'name' => 'ABCD',
        'code' => 'ABCD',
    ]);
    CompanyData::validate(companyData());
})->throws(ValidationException::class);

test('logo validation works while adding a company.', function (): void {
    $country = Country::create([
        'iso2' => 'Ab',
        'name' => 'ABCD',
        'status' => true,
        'phone_code' => '1234',
        'iso3' => 'bc',
        'region' => 'south',
        'subregion' => 'south left',
    ]);

    Company::factory()->create([
        'name' => 'ABCDE',
        'code' => 'ABCDE',
        'commission_type_id' => 1,
        'discount_applicable_type' => 1,
        'booking_payment_refund_type' => BookingPaymentRefundTypes::PARTIALLY->value,
        'booking_payment_use_type' => 1,
        'min_promoters_per_item' => 0,
        'order_picking_list_prefix' => 'Order Picking',
        'show_e_invoice_qr_on_receipt' => false,
        'currency_rate_auto_update' => false,
        'auto_include_in_member_group' => false,
    ]);

    CompanyData::validate(companyData($country->id));
    $this->assertTrue(true);
});

test('unique code validation works while adding a company', function (): void {
    Company::factory()->create([
        'name' => 'ABCD',
        'code' => 'ABCD',
    ]);
    CompanyData::validate(companyData());
})->throws(ValidationException::class);

test('unique name and code validation works while updating a company', function (): void {
    $companyA = Company::factory()->create([
        'name' => 'ABCDE',
        'code' => 'ABCDE',
    ]);

    $brand = Brand::factory()->create();

    Storage::fake('public');

    $uploadedFile = UploadedFile::fake()->image('avatar.jpg');

    $request = new Request([
        'name' => 'ABCDE',
        'code' => 'ABCDE',
        'email' => 'ABCD@gmail.com',
        'grn_format' => 'grn/',
        'void_sale_number_prefix' => '1231212123',
        'send_sale_email_to_member' => false,
        'logo' => $uploadedFile,
        'brand_ids' => [$brand->id],
        'new_member_free_loyalty_points' => 0,
        'commission_type_id' => 1,
        'min_promoters_per_item' => 0,
        'is_bill_reference_number_mandatory' => 0,
        'allow_price_override_cart_level' => 1,
        'allow_negative_inventory' => 0,
        'is_employee_booking_payment_allowed' => 0,
        'allow_only_return' => 0,
        'allow_credit_sale' => 0,
        'allow_employee_credit_sale' => 0,
        'yearly_target' => 1,
        'discount_applicable_type' => 1,
        'booking_payment_use_type' => 1,
        'booking_payment_refund_type' => BookingPaymentRefundTypes::PARTIALLY->value,
        'location_assignment_type' => LocationAssignmentTypes::MANUAL_ASSIGNMENT->value,
        'default_location_id' => null,
        'allow_happy_hour_discount' => 1,
        'auto_include_in_collections' => true,
        'creator_can_approve_draft_product' => false,
        'enable_e_invoice' => false,
        'show_e_invoice_qr_on_receipt' => false,
        'country_ids' => [1],
        'default_country_id' => 1,
        'order_picking_list_prefix' => 'Order Picking',
        'loyalty_point_expiration_days' => 10,
        'number_of_receipts' => 1,
        'currency_rate_auto_update' => true,
        'company_setting' => [
            'credit_sale_use_cashback' => true,
            'credit_sale_redeem_loyalty_points' => true,
            'credit_sale_earn_loyalty_points' => true,
            'credit_sale_redeem_vouchers' => true,
            'credit_sale_generate_vouchers' => true,
            'credit_sale_cart_wide_automatic_promotions' => true,
            'credit_sale_cart_wide_manual_promotions' => true,
            'credit_sale_item_wise_automatic_promotions' => true,
            'credit_sale_item_wise_manual_promotions' => true,
            'credit_sale_complimentary_item' => true,
            'credit_sale_manual_cart_discount' => true,
            'credit_sale_manual_item_discount' => true,
            'credit_sale_happy_hour_discount' => true,
            'credit_sale_allow_multi_currency_in_payment' => true,

            'layaway_sale_use_cashback' => true,
            'layaway_sale_redeem_loyalty_points' => true,
            'layaway_sale_earn_loyalty_points' => true,
            'layaway_sale_redeem_vouchers' => true,
            'layaway_sale_generate_vouchers' => true,
            'layaway_sale_cart_wide_automatic_promotions' => true,
            'layaway_sale_cart_wide_manual_promotions' => true,
            'layaway_sale_item_wise_automatic_promotions' => true,
            'layaway_sale_item_wise_manual_promotions' => true,
            'layaway_sale_complimentary_item' => true,
            'layaway_sale_manual_cart_discount' => true,
            'layaway_sale_manual_item_discount' => true,
            'layaway_sale_happy_hour_discount' => true,
            'layaway_sale_allow_multi_currency_in_payment' => true,

            'booking_payment_allow_multi_currency_in_payment' => true,
        ],
        'auto_include_in_member_group' => false,
    ], server: [
        'REQUEST_URI' => 'companies/' . $companyA->id . '/update',
    ]);

    $request->setRouteResolver(
        fn (): Route => (new Route(
            'Post',
            'companies/{companyId}/update',
            [
                'as' => 'super_admin.companies.update_company',
                'uses' => [CompanyController::class, 'update'],
            ]
        ))->bind($request)
    );

    $request->validate(CompanyData::rules($request));
    $this->assertTrue(true);
});

function companyData(?int $countryId = null): array
{
    Storage::fake('public');
    $uploadedFile = UploadedFile::fake()->image('avatar.jpg');
    $brand = Brand::factory()->create();

    return [
        'name' => 'ABCD',
        'code' => 'ABCD',
        'grn_format' => 'GRN/ABCD',
        'legal_name' => 'ABCD',
        'website' => 'https://www.abc.in',
        'email' => 'ABCD@gmail.com',
        'fax' => '124234',
        'employer_identification_number' => '12312341234',
        'social_security_number' => '1231212',
        'void_sale_number_prefix' => '1231212',
        'send_sale_email_to_member' => false,
        'new_member_free_loyalty_points' => 12,
        'commission_type_id' => 1,
        'min_promoters_per_item' => 0,
        'is_bill_reference_number_mandatory' => 0,
        'allow_price_override_cart_level' => 0,
        'allow_negative_inventory' => 1,
        'is_employee_booking_payment_allowed' => 1,
        'allow_only_return' => 1,
        'allow_credit_sale' => 1,
        'allow_employee_credit_sale' => 1,
        'light_logo' => $uploadedFile,
        'dark_logo' => $uploadedFile,
        'email_footer_logo' => $uploadedFile,
        'brand_ids' => [$brand->id],
        'discount_applicable_type' => 1,
        'booking_payment_use_type' => 1,
        'booking_payment_refund_type' => 1,
        'location_assignment_type' => LocationAssignmentTypes::MANUAL_ASSIGNMENT->value,
        'default_location_id' => null,
        'allow_happy_hour_discount' => 1,
        'auto_include_in_collections' => true,
        'creator_can_approve_draft_product' => false,
        'enable_e_invoice' => false,
        'show_e_invoice_qr_on_receipt' => false,
        'country_ids' => [1],
        'default_country_id' => $countryId,
        'order_picking_list_prefix' => 'Order Picking',
        'loyalty_point_expiration_days' => 10,
        'number_of_receipts' => 1,
        'currency_rate_auto_update' => true,
        'company_setting' => [
            'credit_sale_use_cashback' => true,
            'credit_sale_redeem_loyalty_points' => true,
            'credit_sale_earn_loyalty_points' => true,
            'credit_sale_redeem_vouchers' => true,
            'credit_sale_generate_vouchers' => true,
            'credit_sale_cart_wide_automatic_promotions' => true,
            'credit_sale_cart_wide_manual_promotions' => true,
            'credit_sale_item_wise_automatic_promotions' => true,
            'credit_sale_item_wise_manual_promotions' => true,
            'credit_sale_complimentary_item' => true,
            'credit_sale_manual_cart_discount' => true,
            'credit_sale_manual_item_discount' => true,
            'credit_sale_happy_hour_discount' => true,
            'credit_sale_allow_multi_currency_in_payment' => true,

            'layaway_sale_use_cashback' => true,
            'layaway_sale_redeem_loyalty_points' => true,
            'layaway_sale_earn_loyalty_points' => true,
            'layaway_sale_redeem_vouchers' => true,
            'layaway_sale_generate_vouchers' => true,
            'layaway_sale_cart_wide_automatic_promotions' => true,
            'layaway_sale_cart_wide_manual_promotions' => true,
            'layaway_sale_item_wise_automatic_promotions' => true,
            'layaway_sale_item_wise_manual_promotions' => true,
            'layaway_sale_complimentary_item' => true,
            'layaway_sale_manual_cart_discount' => true,
            'layaway_sale_manual_item_discount' => true,
            'layaway_sale_happy_hour_discount' => true,
            'layaway_sale_allow_multi_currency_in_payment' => true,

            'booking_payment_allow_multi_currency_in_payment' => true,
        ],
        'auto_include_in_member_group' => false,
    ];
}

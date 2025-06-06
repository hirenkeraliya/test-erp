<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Pos;

use App\Domains\Cashier\CashierQueries;
use App\Domains\Company\CompanyQueries;
use App\Domains\Company\Enums\BookingPaymentUseTypes;
use App\Domains\Company\Enums\DiscountApplicableTypes;
use App\Domains\Company\RoundOffConfiguration;
use App\Domains\Currency\CurrencyQueries;
use App\Http\Controllers\Controller;
use App\Models\Cashier;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\Currency;
use App\Models\CurrencyRate;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ConfigurationController extends Controller
{
    /**
     * @return array<string, mixed>
     */
    public function getConfiguration(Request $request): array
    {
        $cashierQueries = resolve(CashierQueries::class);

        /** @var Cashier $cashier */
        $cashier = $request->user();

        if (! $cashier->getCounterUpdateId()) {
            abort(412, 'The counter has not been opened yet.');
        }

        $cashier = $cashierQueries->loadDetailsForConfigurationAPI($cashier);

        /** @var CounterUpdate $counterUpdate */
        $counterUpdate = $cashier->getCounterUpdate();

        /** @var Counter $counter */
        $counter = $counterUpdate->getCounter();

        /** @var Location $location */
        $location = $counter->location;

        /** @var Company $company */
        $company = $location->company;

        /** @var CompanySetting $companySetting */
        $companySetting = $company->companySetting;

        /** @var Collection $countries */
        $countries = $company->countries;

        $roundOffConfiguration = resolve(RoundOffConfiguration::class);

        $currencyQueries = resolve(CurrencyQueries::class);
        $currency = $currencyQueries->getByCompanyId($company->id);

        $companyQueries = resolve(CompanyQueries::class);
        $allowEInvoice = $companyQueries->getEnableEInvoiceById($company->id);

        return [
            'round_off_configuration' => $roundOffConfiguration->getList(),
            'sales_tax_percentage' => (float) $location->sales_tax_percentage,
            'sales_return_days_limit' => $location->sales_return_days_limit,
            'receipt_footer' => $location->receipt_footer,
            'disclaimer' => $location->disclaimer,
            'store_registration_number' => $location->registration_number,
            'store_sst_number' => $location->sst_number,
            'credit_note_expiration_days' => $location->credit_note_expiration_days,
            'loyalty_point_expiration_days' => $company->loyalty_point_expiration_days,
            'cash_out_limit_info' => $location->cash_out_limit_info,
            'cash_out_limit_warning' => $location->cash_out_limit_warning,
            'cash_out_limit_restrict' => $location->cash_out_limit_restrict,
            'new_member_free_loyalty_points' => $company->new_member_free_loyalty_points,
            'min_promoters_per_item' => $company->min_promoters_per_item,
            'is_promoter_mandatory' => $company->min_promoters_per_item > 0,
            'is_bill_reference_number_mandatory' => $company->is_bill_reference_number_mandatory,
            'allow_exchange_to_different_store' => (int) $company->allow_exchange_to_different_store,
            'allow_price_override_cart_level' => $company->allow_price_override_cart_level,
            'is_employee_booking_payment_allowed' => $company->is_employee_booking_payment_allowed,
            'allow_negative_payment' => $company->allow_only_return,
            'allow_only_return' => $company->allow_only_return,
            'allow_credit_sale' => $company->allow_credit_sale,
            'allow_negative_inventory' => $company->allow_negative_inventory,
            'allow_employee_credit_sale' => $company->allow_employee_credit_sale,
            'enable_e_invoice' => $company->enable_e_invoice,
            'show_e_invoice_qr_on_receipt' => $company->show_e_invoice_qr_on_receipt,
            'discount_applicable_type' => DiscountApplicableTypes::getFormattedArrayForPos(
                $company->discount_applicable_type
            ),
            'booking_payment_use_type' => BookingPaymentUseTypes::getFormattedArrayForPos(
                $company->booking_payment_use_type
            ),
            'booking_payment_refund_type' => BookingPaymentUseTypes::getFormattedArrayForPos(
                $company->booking_payment_refund_type
            ),
            'auto_birthday_voucher_generation' => $company->auto_birthday_voucher_generation,
            'number_of_receipts' => $company->number_of_receipts,
            'member_registration_link' => route('front.member.member_add_view', $location->uuid),
            'currency_symbol' => $currency->getSymbol(),
            'allow_e_invoice' => $allowEInvoice,
            'default_currency' => [
                'currency_id' => $currency->id,
                'symbol' => $currency->getSymbol(),
                'rate' => $currency->currencyRate?->rate,
            ],
            'currencies' => $this->getCurrencies($countries),
            'light_logo' => $company->getDiskBasedFirstMediaUrl('light_logo'),
            'dark_logo' => $company->getDiskBasedFirstMediaUrl('dark_logo'),

            'credit_sale' => [
                'use_cashback' => $companySetting->credit_sale_use_cashback,
                'redeem_loyalty_points' => $companySetting->credit_sale_redeem_loyalty_points,
                'earn_loyalty_points' => $companySetting->credit_sale_earn_loyalty_points,
                'redeem_vouchers' => $companySetting->credit_sale_redeem_vouchers,
                'generate_vouchers' => $companySetting->credit_sale_generate_vouchers,
                'cart_wide_automatic_promotions' => $companySetting->credit_sale_cart_wide_automatic_promotions,
                'cart_wide_manual_promotions' => $companySetting->credit_sale_cart_wide_manual_promotions,
                'item_wise_automatic_promotions' => $companySetting->credit_sale_item_wise_automatic_promotions,
                'item_wise_manual_promotions' => $companySetting->credit_sale_item_wise_manual_promotions,
                'complimentary_item' => $companySetting->credit_sale_complimentary_item,
                'manual_cart_discount' => $companySetting->credit_sale_manual_cart_discount,
                'manual_item_discount' => $companySetting->credit_sale_manual_item_discount,
                'happy_hour_discount' => $companySetting->credit_sale_happy_hour_discount,
                'allow_multi_currency_in_payment' => $companySetting->credit_sale_allow_multi_currency_in_payment,
            ],
            'layaway_sale' => [
                'use_cashback' => $companySetting->layaway_sale_use_cashback,
                'redeem_loyalty_points' => $companySetting->layaway_sale_redeem_loyalty_points,
                'earn_loyalty_points' => $companySetting->layaway_sale_earn_loyalty_points,
                'redeem_vouchers' => $companySetting->layaway_sale_redeem_vouchers,
                'generate_vouchers' => $companySetting->layaway_sale_generate_vouchers,
                'cart_wide_automatic_promotions' => $companySetting->layaway_sale_cart_wide_automatic_promotions,
                'cart_wide_manual_promotions' => $companySetting->layaway_sale_cart_wide_manual_promotions,
                'item_wise_automatic_promotions' => $companySetting->layaway_sale_item_wise_automatic_promotions,
                'item_wise_manual_promotions' => $companySetting->layaway_sale_item_wise_manual_promotions,
                'complimentary_item' => $companySetting->layaway_sale_complimentary_item,
                'manual_cart_discount' => $companySetting->layaway_sale_manual_cart_discount,
                'manual_item_discount' => $companySetting->layaway_sale_manual_item_discount,
                'happy_hour_discount' => $companySetting->layaway_sale_happy_hour_discount,
                'allow_multi_currency_in_payment' => $companySetting->layaway_sale_allow_multi_currency_in_payment,
            ],
            'booking_payment' => [
                'allow_multi_currency_in_payment' => $companySetting->booking_payment_allow_multi_currency_in_payment,
            ],
            'e_invoice_url' => config('app.url').'/front/e-invoice-details',
        ];
    }

    private function getCurrencies(Collection $countries): array
    {
        $currencyArray = [];
        foreach ($countries as $country) {
            /** @var Currency $currency */
            $currency = $country->currency;
            /** @var ?CurrencyRate $currencyRate */
            $currencyRate = $currency->currencyRate;

            $currencyArray[] = [
                'currency_id' => $currency->id,
                'symbol' => $currency->getSymbol(),
                'rate' => $currencyRate?->rate,
            ];
        }

        return $currencyArray;
    }
}

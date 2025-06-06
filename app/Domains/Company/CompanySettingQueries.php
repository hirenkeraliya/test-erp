<?php

declare(strict_types=1);

namespace App\Domains\Company;

use App\Models\CompanySetting;

class CompanySettingQueries
{
    public function addNew(?array $companySettingData, int $companyId): void
    {
        $companySettingData['company_id'] = $companyId;
        CompanySetting::create($companySettingData);
    }

    public function update(array $companySettingData, int $companyId): void
    {
        CompanySetting::updateOrCreate([
            'company_id' => $companyId,
        ], $companySettingData);
    }

    public function getNameColumnName(): string
    {
        return 'id,company_id,credit_sale_use_cashback,credit_sale_redeem_loyalty_points,credit_sale_earn_loyalty_points,credit_sale_redeem_vouchers,credit_sale_generate_vouchers,credit_sale_cart_wide_automatic_promotions,credit_sale_cart_wide_manual_promotions,credit_sale_item_wise_automatic_promotions,credit_sale_item_wise_manual_promotions,credit_sale_complimentary_item,credit_sale_manual_cart_discount,credit_sale_manual_item_discount,credit_sale_happy_hour_discount,credit_sale_allow_multi_currency_in_payment,layaway_sale_use_cashback,layaway_sale_redeem_loyalty_points,layaway_sale_earn_loyalty_points,layaway_sale_redeem_vouchers,layaway_sale_generate_vouchers,layaway_sale_cart_wide_automatic_promotions,layaway_sale_cart_wide_manual_promotions,layaway_sale_item_wise_automatic_promotions,layaway_sale_item_wise_manual_promotions,layaway_sale_complimentary_item,layaway_sale_manual_cart_discount,layaway_sale_manual_item_discount,layaway_sale_happy_hour_discount,layaway_sale_allow_multi_currency_in_payment,booking_payment_allow_multi_currency_in_payment';
    }
}

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('company_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->unique()->constrained();
            $table->tinyInteger('credit_sale_use_cashback')->default(1)->comment('0 => inactive, 1 => active');
            $table->tinyInteger('credit_sale_redeem_loyalty_points')->default(1)->comment('0 => inactive, 1 => active');
            $table->tinyInteger('credit_sale_earn_loyalty_points')->default(1)->comment('0 => inactive, 1 => active');
            $table->tinyInteger('credit_sale_redeem_vouchers')->default(1)->comment('0 => inactive, 1 => active');
            $table->tinyInteger('credit_sale_generate_vouchers')->default(1)->comment('0 => inactive, 1 => active');
            $table->tinyInteger('credit_sale_cart_wide_automatic_promotions')->default(1)->comment(
                '0 => inactive, 1 => active'
            );
            $table->tinyInteger('credit_sale_cart_wide_manual_promotions')->default(1)->comment(
                '0 => inactive, 1 => active'
            );
            $table->tinyInteger('credit_sale_item_wise_automatic_promotions')->default(1)->comment(
                '0 => inactive, 1 => active'
            );
            $table->tinyInteger('credit_sale_item_wise_manual_promotions')->default(1)->comment(
                '0 => inactive, 1 => active'
            );
            $table->tinyInteger('credit_sale_complimentary_item')->default(1)->comment('0 => inactive, 1 => active');
            $table->tinyInteger('credit_sale_manual_cart_discount')->default(1)->comment('0 => inactive, 1 => active');
            $table->tinyInteger('credit_sale_manual_item_discount')->default(1)->comment('0 => inactive, 1 => active');
            $table->tinyInteger('credit_sale_happy_hour_discount')->default(1)->comment('0 => inactive, 1 => active');
            $table->tinyInteger('credit_sale_allow_multi_currency_in_payment')->default(1)->comment(
                '0 => inactive, 1 => active'
            );

            $table->tinyInteger('layaway_sale_use_cashback')->default(1)->comment('0 => inactive, 1 => active');
            $table->tinyInteger('layaway_sale_redeem_loyalty_points')->default(1)->comment(
                '0 => inactive, 1 => active'
            );
            $table->tinyInteger('layaway_sale_earn_loyalty_points')->default(1)->comment('0 => inactive, 1 => active');
            $table->tinyInteger('layaway_sale_redeem_vouchers')->default(1)->comment('0 => inactive, 1 => active');
            $table->tinyInteger('layaway_sale_generate_vouchers')->default(1)->comment('0 => inactive, 1 => active');
            $table->tinyInteger('layaway_sale_cart_wide_automatic_promotions')->default(1)->comment(
                '0 => inactive, 1 => active'
            );
            $table->tinyInteger('layaway_sale_cart_wide_manual_promotions')->default(1)->comment(
                '0 => inactive, 1 => active'
            );
            $table->tinyInteger('layaway_sale_item_wise_automatic_promotions')->default(1)->comment(
                '0 => inactive, 1 => active'
            );
            $table->tinyInteger('layaway_sale_item_wise_manual_promotions')->default(1)->comment(
                '0 => inactive, 1 => active'
            );
            $table->tinyInteger('layaway_sale_complimentary_item')->default(1)->comment('0 => inactive, 1 => active');
            $table->tinyInteger('layaway_sale_manual_cart_discount')->default(1)->comment('0 => inactive, 1 => active');
            $table->tinyInteger('layaway_sale_manual_item_discount')->default(1)->comment('0 => inactive, 1 => active');
            $table->tinyInteger('layaway_sale_happy_hour_discount')->default(1)->comment('0 => inactive, 1 => active');
            $table->tinyInteger('layaway_sale_allow_multi_currency_in_payment')->default(1)->comment(
                '0 => inactive, 1 => active'
            );

            $table->tinyInteger('booking_payment_allow_multi_currency_in_payment')->default(1)->comment(
                '0 => inactive, 1 => active'
            );
            $table->timestamps();
        });
    }
};

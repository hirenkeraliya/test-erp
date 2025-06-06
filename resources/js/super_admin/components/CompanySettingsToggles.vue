<template>
    <JSwitch
        v-model:is-checked="state.isAdvanceSettingEnable"
        input-label="Advance Settings"
    />

    <div
        v-if="state.isAdvanceSettingEnable"
        class="mb-12 mt-5 grid gap-y-5 gap-x-6 md:grid-cols-3 xl:grid-cols-3"
    >
        <!-- Credit sales -->
        <div class="relative flex flex-col bg-clip-border rounded-xl bg-white text-gray-700 shadow-md">
            <div class="p-4 pr-20 text-left">
                <h4
                    class="block antialiased tracking-normal text-xl font-semibold leading-snug text-gray-900"
                    v-text="'Credit Sales'"
                />
            </div>

            <div class="border-t border-gray-200 p-4">
                <ul>
                    <JSwitch
                        :is-checked="props.companyData.credit_sale_use_cashback"
                        input-label="Use Cashback"
                        validation-field-name="credit_sale_use_cashback"
                        @update:is-checked="updatePermissionToggle('credit_sale_use_cashback', $event)"
                    />
                    <JSwitch
                        :is-checked="props.companyData.credit_sale_redeem_loyalty_points"
                        input-label="Redeem Loyalty Points"
                        validation-field-name="credit_sale_redeem_loyalty_points"
                        @update:is-checked="updatePermissionToggle('credit_sale_redeem_loyalty_points', $event)"
                    />
                    <JSwitch
                        :is-checked="props.companyData.credit_sale_earn_loyalty_points"
                        input-label="Earn Loyalty Points"
                        validation-field-name="credit_sale_earn_loyalty_points"
                        @update:is-checked="updatePermissionToggle('credit_sale_earn_loyalty_points', $event)"
                    />
                    <JSwitch
                        :is-checked="props.companyData.credit_sale_redeem_vouchers"
                        input-label="Redeem Vouchers"
                        validation-field-name="credit_sale_redeem_vouchers"
                        @update:is-checked="updatePermissionToggle('credit_sale_redeem_vouchers', $event)"
                    />
                    <JSwitch
                        :is-checked="props.companyData.credit_sale_generate_vouchers"
                        input-label="Generate Vouchers"
                        validation-field-name="credit_sale_generate_vouchers"
                        @update:is-checked="updatePermissionToggle('credit_sale_generate_vouchers', $event)"
                    />
                    <JSwitch
                        :is-checked="props.companyData.credit_sale_cart_wide_automatic_promotions"
                        input-label="Cart Wide Automatic Promotions"
                        validation-field-name="credit_sale_cart_wide_automatic_promotions"
                        @update:is-checked="updatePermissionToggle('credit_sale_cart_wide_automatic_promotions', $event)"
                    />
                    <JSwitch
                        :is-checked="props.companyData.credit_sale_cart_wide_manual_promotions"
                        input-label="Cart Wide Manual Promotions"
                        validation-field-name="credit_sale_cart_wide_manual_promotions"
                        @update:is-checked="updatePermissionToggle('credit_sale_cart_wide_manual_promotions', $event)"
                    />
                    <JSwitch
                        :is-checked="props.companyData.credit_sale_item_wise_automatic_promotions"
                        input-label="Item Wise Automatic Promotions"
                        validation-field-name="credit_sale_item_wise_automatic_promotions"
                        @update:is-checked="updatePermissionToggle('credit_sale_item_wise_automatic_promotions', $event)"
                    />
                    <JSwitch
                        :is-checked="props.companyData.credit_sale_item_wise_manual_promotions"
                        input-label="Item Wise Manual Promotions"
                        validation-field-name="credit_sale_item_wise_manual_promotions"
                        @update:is-checked="updatePermissionToggle('credit_sale_item_wise_manual_promotions', $event)"
                    />
                    <JSwitch
                        :is-checked="props.companyData.credit_sale_complimentary_item"
                        input-label="Complimentary Item"
                        validation-field-name="credit_sale_complimentary_item"
                        @update:is-checked="updatePermissionToggle('credit_sale_complimentary_item', $event)"
                    />
                    <JSwitch
                        :is-checked="props.companyData.credit_sale_manual_cart_discount"
                        input-label="Manual Cart Discount"
                        validation-field-name="credit_sale_manual_cart_discount"
                        @update:is-checked="updatePermissionToggle('credit_sale_manual_cart_discount', $event)"
                    />
                    <JSwitch
                        :is-checked="props.companyData.credit_sale_manual_item_discount"
                        input-label="Manual Item Discount"
                        validation-field-name="credit_sale_manual_item_discount"
                        @update:is-checked="updatePermissionToggle('credit_sale_manual_item_discount', $event)"
                    />
                    <JSwitch
                        :is-checked="props.companyData.credit_sale_happy_hour_discount"
                        input-label="Happy Hour Discount"
                        validation-field-name="credit_sale_happy_hour_discount"
                        @update:is-checked="updatePermissionToggle('credit_sale_happy_hour_discount', $event)"
                    />
                    <JSwitch
                        :is-checked="props.companyData.credit_sale_allow_multi_currency_in_payment"
                        input-label="Allow Multi Currency In Payment"
                        validation-field-name="credit_sale_allow_multi_currency_in_payment"
                        @update:is-checked="updatePermissionToggle('credit_sale_allow_multi_currency_in_payment', $event)"
                    />
                </ul>
            </div>
        </div>

        <!-- Layaway sales -->
        <div class="relative flex flex-col bg-clip-border rounded-xl bg-white text-gray-700 shadow-md">
            <div class="p-4 pr-20 text-left">
                <h4
                    class="block antialiased tracking-normal text-xl font-semibold leading-snug text-gray-900"
                    v-text="'Layaway Sale'"
                />
            </div>

            <div class="border-t border-gray-200 p-4">
                <ul>
                    <JSwitch
                        :is-checked="props.companyData.layaway_sale_use_cashback"
                        input-label="Use Cashback"
                        validation-field-name="layaway_sale_use_cashback"
                        @update:is-checked="updatePermissionToggle('layaway_sale_use_cashback', $event)"
                    />
                    <JSwitch
                        :is-checked="props.companyData.layaway_sale_redeem_loyalty_points"
                        input-label="Redeem Loyalty Points"
                        validation-field-name="layaway_sale_redeem_loyalty_points"
                        @update:is-checked="updatePermissionToggle('layaway_sale_redeem_loyalty_points', $event)"
                    />
                    <JSwitch
                        :is-checked="props.companyData.layaway_sale_earn_loyalty_points"
                        input-label="Earn Loyalty Points"
                        validation-field-name="layaway_sale_earn_loyalty_points"
                        @update:is-checked="updatePermissionToggle('layaway_sale_earn_loyalty_points', $event)"
                    />
                    <JSwitch
                        :is-checked="props.companyData.layaway_sale_redeem_vouchers"
                        input-label="Redeem Vouchers"
                        validation-field-name="layaway_sale_redeem_vouchers"
                        @update:is-checked="updatePermissionToggle('layaway_sale_redeem_vouchers', $event)"
                    />
                    <JSwitch
                        :is-checked="props.companyData.layaway_sale_generate_vouchers"
                        input-label="Generate Vouchers"
                        validation-field-name="layaway_sale_generate_vouchers"
                        @update:is-checked="updatePermissionToggle('layaway_sale_generate_vouchers', $event)"
                    />
                    <JSwitch
                        :is-checked="props.companyData.layaway_sale_cart_wide_automatic_promotions"
                        input-label="Cart Wide Automatic Promotions"
                        validation-field-name="layaway_sale_cart_wide_automatic_promotions"
                        @update:is-checked="updatePermissionToggle('layaway_sale_cart_wide_automatic_promotions', $event)"
                    />
                    <JSwitch
                        :is-checked="props.companyData.layaway_sale_cart_wide_manual_promotions"
                        input-label="Cart Wide Manual Promotions"
                        validation-field-name="layaway_sale_cart_wide_manual_promotions"
                        @update:is-checked="updatePermissionToggle('layaway_sale_cart_wide_manual_promotions', $event)"
                    />
                    <JSwitch
                        :is-checked="props.companyData.layaway_sale_item_wise_automatic_promotions"
                        input-label="Item Wise Automatic Promotions"
                        validation-field-name="layaway_sale_item_wise_automatic_promotions"
                        @update:is-checked="updatePermissionToggle('layaway_sale_item_wise_automatic_promotions', $event)"
                    />
                    <JSwitch
                        :is-checked="props.companyData.layaway_sale_item_wise_manual_promotions"
                        input-label="Item Wise Manual Promotions"
                        validation-field-name="layaway_sale_item_wise_manual_promotions"
                        @update:is-checked="updatePermissionToggle('layaway_sale_item_wise_manual_promotions', $event)"
                    />
                    <JSwitch
                        :is-checked="props.companyData.layaway_sale_complimentary_item"
                        input-label="Complimentary Item"
                        validation-field-name="layaway_sale_complimentary_item"
                        @update:is-checked="updatePermissionToggle('layaway_sale_complimentary_item', $event)"
                    />
                    <JSwitch
                        :is-checked="props.companyData.layaway_sale_manual_cart_discount"
                        input-label="Manual Cart Discount"
                        validation-field-name="layaway_sale_manual_cart_discount"
                        @update:is-checked="updatePermissionToggle('layaway_sale_manual_cart_discount', $event)"
                    />
                    <JSwitch
                        :is-checked="props.companyData.layaway_sale_manual_item_discount"
                        input-label="Manual Item Discount"
                        validation-field-name="layaway_sale_manual_item_discount"
                        @update:is-checked="updatePermissionToggle('layaway_sale_manual_item_discount', $event)"
                    />
                    <JSwitch
                        :is-checked="props.companyData.layaway_sale_happy_hour_discount"
                        input-label="Happy Hour Discount"
                        validation-field-name="layaway_sale_happy_hour_discount"
                        @update:is-checked="updatePermissionToggle('layaway_sale_happy_hour_discount', $event)"
                    />
                    <JSwitch
                        :is-checked="props.companyData.layaway_sale_allow_multi_currency_in_payment"
                        input-label="Allow Multi Currency In Payment"
                        validation-field-name="layaway_sale_allow_multi_currency_in_payment"
                        @update:is-checked="updatePermissionToggle('layaway_sale_allow_multi_currency_in_payment', $event)"
                    />
                </ul>
            </div>
        </div>

        <!-- Booking Payment -->
        <div class="relative flex flex-col bg-clip-border rounded-xl bg-white text-gray-700 shadow-md">
            <div class="p-4 pr-20 text-left">
                <h4
                    class="block antialiased tracking-normal text-xl font-semibold leading-snug text-gray-900"
                    v-text="'Booking Payment'"
                />
            </div>

            <div class="border-t border-gray-200 p-4">
                <ul>
                    <JSwitch
                        :is-checked="props.companyData.booking_payment_allow_multi_currency_in_payment"
                        input-label="Allow Multi Currency In Payment"
                        validation-field-name="booking_payment_allow_multi_currency_in_payment"
                        @update:is-checked="updatePermissionToggle('booking_payment_allow_multi_currency_in_payment', $event)"
                    />
                </ul>
            </div>
        </div>
    </div>
</template>

<script setup>
import JSwitch from '@commonComponents/JSwitch.vue';
import { reactive } from 'vue';

const props = defineProps({
    companyData: {
        type: [null, Object],
        required: true,
    },
});

const state = reactive({
    isAdvanceSettingEnable: false
});

const emits = defineEmits([
    'selected-toggles',
]);

const updatePermissionToggle = async (toggleName, val) => {
    emits('selected-toggles', {
        name: toggleName,
        val: val ,
    });
};
</script>

<template>
    <div class="intro-y bg-slate-50">
        <div class="font-medium text-lg p-5 border-b">
            Criteria
        </div>

        <div
            v-if="voucherConfigurationForm.restricted_by_type === null && voucherConfigurationForm.discount_type === null"
            class="intro-y text-base sm:text-lg p-5"
        >
            Please provide details in the previous step(s) first.
        </div>

        <div v-else>
            <InfoAlert
                v-if="voucherConfigurationForm.voucher_type === staticDetails.birthday_voucher"
                color="primary"
                class="m-5 mb-0"
            >
                The birthday vouchers are generated on the day of the member's birthday and they can use the voucher on the same day or up to the number of validity days.
            </InfoAlert>

            <InfoAlert
                v-if="voucherConfigurationForm.voucher_type === staticDetails.welcome_member"
                color="primary"
                class="m-5 mb-0"
            >
                The welcome member vouchers are generated when the new members are registered in our system and they can use the voucher on the same day or up to the number of validity days.
            </InfoAlert>

            <InfoAlert
                v-if="voucherConfigurationForm.voucher_type === staticDetails.multiple_voucher"
                color="primary"
                class="m-5 mb-0"
            >
                Vouchers are issued in multiples as per the purchase amount. Ex: Minimum spend amount is {{ currencySymbol }}100 and if a member spends {{ currencySymbol }}200, he/she will get two vouchers.
            </InfoAlert>

            <div
                v-if="voucherConfigurationForm.voucher_type === staticDetails.loyalty_point"
                class="p-5"
            >
                <div class="grid grid-cols-12 gap-0 sm:gap-6">
                    <div class="input-form col-span-12 sm:col-span-12 md:col-span-6 lg:col-span-4 xl:col-span-3">
                        <JMultiSelect
                            :records="memberships"
                            input-label="Memberships"
                            :required="true"
                            validation-field-name="membership_ids"
                            :selected-records="selectedMemberships"
                            @update:selected-records="updateMemberships"
                        />
                    </div>
                </div>
            </div>

            <div
                v-if="voucherConfigurationForm.voucher_type !==
                    staticDetails.birthday_voucher && voucherConfigurationForm.voucher_type !==
                        staticDetails.welcome_member &&
                    voucherConfigurationForm.voucher_type"
                class="p-5 mt-2"
            >
                <div class="font-medium text-base border-b pb-2">
                    Earn configuration
                </div>
                <div
                    v-if="voucherConfigurationForm.voucher_type !== staticDetails.tier_voucher && voucherConfigurationForm.voucher_type !== staticDetails.loyalty_point"
                    class="grid grid-cols-12 gap-0 sm:gap-6"
                >
                    <div class="input-form col-span-12 sm:col-span-12 md:col-span-6 lg:col-span-4 xl:col-span-3">
                        <FormInput
                            type="number"
                            :input-value="voucherConfigurationForm.issue_minimum_spend_amount"
                            input-name="issue-minimum-spend-amount"
                            input-label="Minimum Spend"
                            :input-group-prefix="currencySymbol"
                            :required="true"
                            :title="'Vouchers will be issued/generated when member spends minimum '+currencySymbol+'XX amount during effective dates.'"
                            @update:input-value="updateDetails($event, 'issue_minimum_spend_amount')"
                        />
                    </div>
                </div>

                <div
                    v-if="voucherConfigurationForm.voucher_type === staticDetails.tier_voucher || voucherConfigurationForm.voucher_type === staticDetails.loyalty_point "
                >
                    <OutlinePrimaryButton
                        v-for="(discountType, index) in discountTypes"
                        :key="'discount-type-'+index"
                        :text="discountType.name"
                        class="shadow-md text-sm mr-2 mb-1 xl:mb-0 mt-4"
                        :class="voucherConfigurationForm.discount_type === discountType.id ? 'btn btn-primary text-white hover:text-primary' : ''"
                        @click="updateDetails(discountType.id, 'discount_type')"
                    />

                    <Tiers
                        v-if="voucherConfigurationForm.voucher_type === staticDetails.tier_voucher"
                        :tiers="voucherConfigurationForm.tiers"
                        get-value-input-label="Discount"
                        :get-value-input-group-prefix="staticDetails.flat_discount === voucherConfigurationForm.discount_type ? currencySymbol : null"
                        :get-value-input-group-suffix="staticDetails.percentage_discount === voucherConfigurationForm.discount_type ? '%' : null"
                        @update:tier-value-details="updateTierValueDetails"
                        @add:new-tier-details="addNewTierDetails"
                        @remove:tier-details-of="removeTierDetailsOf"
                    />
                    <LoyaltyPointTiers
                        v-if="voucherConfigurationForm.voucher_type === staticDetails.loyalty_point"
                        :tiers="voucherConfigurationForm.tiers"
                        get-value-input-label="Discount"
                        :get-value-input-group-prefix="staticDetails.flat_discount === voucherConfigurationForm.discount_type ? currencySymbol : null"
                        :get-value-input-group-suffix="staticDetails.percentage_discount === voucherConfigurationForm.discount_type ? '%' : null"
                        @update:tier-value-details="updateTierValueDetails"
                        @add:new-tier-details="addNewTierDetails"
                        @remove:tier-details-of="removeTierDetailsOf"
                    />
                    <InfoAlert
                        v-if="voucherConfigurationForm.voucher_type === staticDetails.tier_voucher"
                        color="primary"
                        class="my-3"
                    >
                        <span v-if="staticDetails.flat_discount === voucherConfigurationForm.discount_type">
                            Voucher for "{{ currencySymbol }}XX" will be issued/generated when member spends minimum "{{ currencySymbol }}XX" and maximum "{{ currencySymbol }}XX" during the effective dates.
                        </span>
                        <span v-else>
                            Voucher for "X%" will be issued/generated when member spend minimum "{{ currencySymbol }}XX" and maximum "{{ currencySymbol }}XX" during the campaign period.
                        </span>
                    </InfoAlert>

                    <InfoAlert
                        v-if="voucherConfigurationForm.voucher_type === staticDetails.loyalty_point"
                        color="primary"
                        class="my-3"
                    >
                        <span v-if="staticDetails.flat_discount === voucherConfigurationForm.discount_type">
                            Voucher for "XX" will be issued/generated when member spends XX" loyalty point during the effective dates.
                        </span>
                        <span v-else>
                            Voucher for "X%" will be issued/generated when member spends "XX" loyalty point during the effective dates.
                        </span>
                    </InfoAlert>
                </div>
            </div>

            <div
                v-if="voucherConfigurationForm.voucher_type"
                class="p-5"
            >
                <div class="font-medium text-base border-b pb-2">
                    Burn configuration
                </div>

                <div class="grid grid-cols-12 gap-0 sm:gap-6">
                    <div class="input-form col-span-12 sm:col-span-12 md:col-span-6 lg:col-span-4 xl:col-span-3">
                        <FormInput
                            type="number"
                            :input-value="voucherConfigurationForm.validity_days"
                            input-name="validity-days"
                            input-label="Validity (Days)"
                            title="Once a voucher is issued/generated, voucher redemption will be allowed within X days only. Set zero (0) for no expiry."
                            :required="true"
                            @update:input-value="updateDetails($event, 'validity_days')"
                        />
                    </div>

                    <div class="input-form col-span-12 sm:col-span-12 md:col-span-6 lg:col-span-4 xl:col-span-3">
                        <FormInput
                            type="number"
                            :input-value="voucherConfigurationForm.use_minimum_spend_amount"
                            input-name="use-minimum-spend-amount"
                            input-label="Minimum Spend"
                            :title="'Members will have to spend '+ currencySymbol +'XX to redeem their vouchers.'"
                            :input-group-prefix="currencySymbol"
                            :required="true"
                            @update:input-value="updateDetails($event, 'use_minimum_spend_amount')"
                        />
                    </div>
                </div>
            </div>

            <div
                v-if="voucherConfigurationForm.voucher_type !== staticDetails.tier_voucher && voucherConfigurationForm.voucher_type !== staticDetails.loyalty_point"
                class="p-5"
            >
                <div class="font-medium text-base border-b pb-2">
                    Discount
                </div>

                <div class="mt-4 mx-0">
                    <OutlinePrimaryButton
                        v-for="(discountType, index) in discountTypes"
                        :key="'discount-type-'+index"
                        :text="discountType.name"
                        class="shadow-md text-sm mr-2 mb-1 xl:mb-0"
                        :class="voucherConfigurationForm.discount_type === discountType.id ? 'btn btn-primary text-white hover:text-primary' : ''"
                        @click="updateDetails(discountType.id, 'discount_type')"
                    />
                </div>

                <div
                    v-if="voucherConfigurationForm.discount_type"
                    class="grid grid-cols-12 gap-0 sm:gap-6"
                >
                    <div class="input-form col-span-12 sm:col-span-12 md:col-span-6 lg:col-span-4 xl:col-span-3">
                        <FormInput
                            :input-value="voucherConfigurationForm.get_value"
                            input-name="get-value"
                            input-label="Discount"
                            :input-group-prefix="staticDetails.flat_discount === voucherConfigurationForm.discount_type ? currencySymbol : null"
                            :input-group-suffix="staticDetails.percentage_discount === voucherConfigurationForm.discount_type ? '%' : null"
                            :required="true"
                            @update:input-value="updateDetails($event, 'get_value')"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import Tiers from '@adminPages/voucher_configurations/Tiers.vue';
import LoyaltyPointTiers from '@adminPages/voucher_configurations/LoyaltyPointTiers.vue';
import FormInput from '@commonComponents/FormInput.vue';
import InfoAlert from '@commonComponents/InfoAlert.vue';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import JMultiSelect from '@commonComponents/JMultiSelect.vue';
const currencySymbol = computed(() => usePage().props.currency_symbol);

defineProps({
    excludeByTypes: {
        type: Array,
        default: () => [],
    },
    voucherConfigurationForm: {
        type: Object,
        required: true,
    },
    staticDetails: {
        type: Object,
        required: true,
    },
    discountTypes: {
        type: Array,
        required: true,
    },
    memberships: {
        type: Array,
        required: true,
    },
    restrictedByTypes: {
        type: Array,
        default: () => [],
    },
    selectedMemberships: {
        type: Array,
        default: () => [],
    },
});

const emits = defineEmits([
    'update:column-details',
    'update:tier-value-details',
    'add:new-tier-details',
    'remove:tier-details-of',
    'update:selected-memberships',
    'clear:columns',
]);

const updateDetails = (data, columnName) => {
    emits('update:column-details', {
        column_name: columnName,
        value: data,
    });
};

const updateTierValueDetails = (details) => {
    emits('update:tier-value-details', {
        key: details.key,
        value: details.value,
        column_name: details.column_name,
    });
};

const addNewTierDetails = () => {
    emits('add:new-tier-details');
};

const removeTierDetailsOf = (index) => {
    emits('remove:tier-details-of', index);
};

const updateMemberships = (memberships) => {
    emits('update:selected-memberships', {
        memberships
    });
};

</script>

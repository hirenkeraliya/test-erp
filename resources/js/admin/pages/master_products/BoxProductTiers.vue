<template>
    <div
        v-for="(tier, index) in boxes"
        :key="index"
        class="grid grid-cols-12 gap-0 sm:gap-6"
    >
        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
            <FormSelectBox
                v-model:selected-record="tier.package_type_id"
                :records="packageTypes"
                input-label="Package Type"
                :validation-field-name="'variants.' + variantIndex + '.boxes.' + index + '.package_type_id'"
                :required="true"
                @update:selected-record="updateTierBoxValueDetails($event, index, 'package_type_id')"
            />
        </div>

        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
            <FormInput
                class="mr-1"
                type="number"
                input-label="Units"
                :input-value="tier.units"
                :required="true"
                :validation-field-name="'variants.' + variantIndex +'.boxes.' + index + '.units'"
                @update:input-value="updateTierBoxValueDetails($event, index, 'units')"
            />
        </div>
        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
            <FormInput
                class="mr-1"
                type="number"
                input-label="Retail Price"
                :input-value="tier.retail_price"
                :input-group-prefix="currencySymbol"
                :validation-field-name="'variants.' + variantIndex +'.boxes.' + index + '.retail_price'"
                @update:input-value="updateTierBoxValueDetails($event, index, 'retail_price')"
            />
        </div>
        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
            <FormInput
                class="mr-1"
                type="number"
                input-label="WholeSale Price"
                :input-value="tier.wholesale_price"
                :input-group-prefix="currencySymbol"
                :validation-field-name="'variants.' + variantIndex + '.boxes.' + index + '.wholesale_price'"
                @update:input-value="updateTierBoxValueDetails($event, index, 'wholesale_price')"
            />
        </div>
        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
            <FormInput
                class="mr-1"
                type="number"
                input-label="Minimum Price"
                :input-value="tier.minimum_price"
                :input-group-prefix="currencySymbol"
                :validation-field-name="'variants.' + variantIndex + '.boxes.' + index + '.minimum_price'"
                @update:input-value="updateTierBoxValueDetails($event, index, 'minimum_price')"
            />
        </div>
        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
            <FormInput
                class="mr-1"
                type="number"
                input-label="Purchase Cost"
                :input-value="tier.purchase_cost"
                :input-group-prefix="currencySymbol"
                :validation-field-name="'variants.' + variantIndex + '.boxes.' + index + '.purchase_cost'"
                @update:input-value="updateTierBoxValueDetails($event, index, 'purchase_cost')"
            />
        </div>
        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-6 xl:col-span-4">
            <FormInput
                class="mr-1"
                type="number"
                input-label="Staff Price (Minimum selling price for employee)"
                :input-value="tier.staff_price"
                :input-group-prefix="currencySymbol"
                :validation-field-name="'variants.' + variantIndex + '.boxes.' + index + '.staff_price'"
                @update:input-value="updateTierBoxValueDetails($event, index, 'staff_price')"
            />
        </div>

        <div class="input-form col-span-12 sm:col-span-2 md:col-span-2 lg:col-span-2 xl:col-span-1">
            <DeleteButton
                type="button"
                class="mt-2 sm:mt-0 md:mt-0 lg:mt-8 xl:mt-8 w-12 h-8"
                @click="removeTierBoxDetailsOf(index)"
            />
        </div>
        <div class="col-span-12 sm:col-span-8 md:col-span-8 lg:col-span-8 xl:col-span-8">
            <BoxProductMembershipLoyaltyPointTiers
                :tiers="tier.box_product_loyalty_points"
                :memberships="memberships"
                :main-index="index"
                :variant-index="variantIndex"
                @update:tier-value-details="updateTierValueDetails($event, index)"
                @add:new-tier-details="addNewTierDetails(index)"
                @remove:tier-details-of="removeTierDetailsOf($event, index)"
            />
        </div>
    </div>

    <div class="grid grid-cols-12 gap-0 sm:gap-6 p-5 pb-0">
        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-6 xl:col-span-6">
            <OutlinePrimaryButton
                text="+ Add New Tier"
                type="button"
                class="border-dashed w-full"
                @click="addNewTierBoxDetails()"
            />
        </div>
    </div>
</template>

<script setup>
import FormInput from '@commonComponents/FormInput.vue';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import DeleteButton from '@commonComponents/DeleteButton.vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import BoxProductMembershipLoyaltyPointTiers from '@adminPages/master_products/BoxProductMembershipLoyaltyPointTiers.vue';
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
const pageProps = computed(() => usePage().props);
const currencySymbol = pageProps.value.currency_symbol;

defineProps({
    boxes: {
        type: Object,
        required: true,
    },
    variantIndex: {
        type: Number,
        default: 0,
    },
    packageTypes: {
        type: Object,
        required: true,
    },
    memberships: {
        type: Object,
        required: true,
    },
});

const emits = defineEmits([
    'update:tier-box-value-details',
    'add:new-tier-box-details',
    'add:new-nested-tier-box-details',
    'remove:tier-box-details-of',
    'remove:nested-tier-box-details-of',
    'update:nested-tier-box-value-details',
]);

const updateTierBoxValueDetails = (event, itemIndex, columnName) => {
    emits('update:tier-box-value-details', {
        key: itemIndex,
        value: event,
        column_name: columnName,
    });
};

const addNewTierBoxDetails = () => {
    emits('add:new-tier-box-details');
};

const removeTierBoxDetailsOf = (index) => {
    emits('remove:tier-box-details-of', index);
};

const addNewTierDetails = (index) => {
    emits('add:new-nested-tier-box-details', index);
};

const updateTierValueDetails = (details, index) => {
    details.main_index = index;
    emits('update:nested-tier-box-value-details', details);
};

const removeTierDetailsOf = (key, mainIndex) => {
    emits('remove:nested-tier-box-details-of', {
        key,
        mainIndex
    });
};

</script>

<template>
    <div
        v-for="(tier, index) in tiers"
        :key="index"
    >
        <div class="block sm:flex items-center">
            <JMonthPicker
                :input-value="tier.months"
                :required="true"
                input-label="Month"
                first-div-class="mb-0"
                @update:input-value="updateTierValueDetails($event, index, 'months')"
            />

            <div class="input-form col-span-12 sm:col-span-8 md:col-span-8 lg:col-span-6 xl:col-span-4 mb-3 ml-3 mr-4">
                <TabPanel
                    :v-if="amountType === saleTargetAmountTypes.amount"
                >
                    <FormInput
                        v-if="amountType === saleTargetAmountTypes.amount"
                        v-model:input-value="tier.amount"
                        type="number"
                        input-name="amount"
                        input-label="Amount"
                        label-class="block text-primary-p3 mt-2"
                        :input-group-prefix="currencySymbol"
                        @update:input-value="updateTierValueDetails($event, index, 'amount')"
                    />
                </TabPanel>

                <TabPanel
                    :v-if="amountType === saleTargetAmountTypes.percentage"
                >
                    <FormInput
                        v-if="amountType === saleTargetAmountTypes.percentage"
                        v-model:input-value="tier.percentage"
                        type="number"
                        input-name="percentage"
                        input-label="Percentage"
                        input-group-suffix="%"
                        label-class="block text-primary-p3 mt-2"
                        title="When you select a timeframe, our system considers historical data, analyzing past net sales within that period, and adds a specified percentage to provide you with a target value."
                        @update:input-value="updateTierValueDetails($event, index, 'percentage')"
                    />
                </TabPanel>
            </div>

            <DeleteButton
                type="button"
                class="mt-2 sm:mt-0 md:mt-0 lg:mt-8 xl:mt-4 w-12 h-8"
                @click="removeTierDetailsOf(index)"
            />
        </div>
    </div>

    <div class="mt-2">
        <OutlinePrimaryButton
            text="+ Add Month"
            type="button"
            class="border-dashed"
            @click="addNewTierDetails()"
        />
    </div>

    <ValidationError :validation-field-name="validationFieldName" />
</template>

<script setup>
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import DeleteButton from '@commonComponents/DeleteButton.vue';
import JMonthPicker from '@commonComponents/JMonthPicker.vue';
import ValidationError from '@commonComponents/ValidationError.vue';
import { TabPanel } from '@commonVendor/tab';
import FormInput from '@commonComponents/FormInput.vue';
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const pageProps = computed(() => usePage().props);
const currencySymbol = pageProps.value.currency_symbol;

defineProps({
    tiers: {
        type: Object,
        required: true,
    },
    amountType: {
        type: Number,
        required: true,
    },
    validationFieldName: {
        type: String,
        default: null,
    },
    saleTargetAmountTypes: {
        type: Object,
        default: null
    },
});

const emits = defineEmits([
    'update:tier-value-details',
    'add:new-tier-details',
    'remove:tier-details-of',
]);

const updateTierValueDetails = (event, itemIndex, columnName) => {
    emits('update:tier-value-details', {
        key: itemIndex,
        value: event,
        column_name: columnName,
    });
};

const addNewTierDetails = () => {
    emits('add:new-tier-details');
};

const removeTierDetailsOf = (index) => {
    emits('remove:tier-details-of', index);
};
</script>

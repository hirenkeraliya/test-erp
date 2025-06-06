<template>
    <div
        v-for="(tier, index) in tiers"
        :key="index"
        class="grid grid-cols-12 gap-0 sm:gap-6 mb-3"
    >
        <div class="input-form col-span-12 sm:col-span-12 md:col-span-6 lg:col-span-4 xl:col-span-3">
            <FormInput
                type="number"
                input-name="buy_value"
                :input-label="buyInputLabel"
                :input-value="tier.buy_value"
                :validation-field-name="'tiers.' + index + '.buy_value'"
                :input-group-prefix="buyValueInputGroupPrefix"
                :title="buyInputTitle"
                @update:input-value="updateTierValueDetails($event, index, 'buy_value')"
            />
        </div>

        <div
            v-if="maxValueInputLabel"
            class="input-form col-span-12 sm:col-span-12 md:col-span-6 lg:col-span-4 xl:col-span-3"
        >
            <FormInput
                type="number"
                input-name="max_value"
                :input-label="maxValueInputLabel"
                :input-value="tier.max_value"
                :validation-field-name="'tiers.' + index + '.max_value'"
                :title="maxValueInputTitle"
                :input-group-prefix="maxValueInputGroupPrefix"
                @update:input-value="updateTierValueDetails($event, index, 'max_value')"
            />
        </div>

        <div
            v-if="getQuantityInputLabel"
            class="input-form col-span-12 sm:col-span-12 md:col-span-6 lg:col-span-4 xl:col-span-3"
        >
            <FormInput
                type="number"
                input-name="get_quantity"
                :input-label="getQuantityInputLabel"
                :input-value="tier.get_quantity"
                :validation-field-name="'tiers.' + index + '.get_quantity'"
                :title="getQuantityInputTitle"
                @update:input-value="updateTierValueDetails($event, index, 'get_quantity')"
            />
        </div>

        <div class="input-form col-span-12 sm:col-span-12 md:col-span-6 lg:col-span-4 xl:col-span-3">
            <FormInput
                type="number"
                input-name="get_value"
                class="mr-1"
                :input-label="getInputLabel"
                :input-value="tier.get_value"
                :validation-field-name="'tiers.' + index + '.get_value'"
                :input-group-prefix="getValueInputGroupPrefix"
                :input-group-suffix="getValueInputGroupSuffix"
                @update:input-value="updateTierValueDetails($event, index, 'get_value')"
            />
        </div>

        <div class="input-form col-span-12 sm:col-span-12 md:col-span-6 lg:col-span-4 xl:col-span-3">
            <DeleteButton
                class="mt-2 sm:mt-0 md:mt-0 lg:mt-8 xl:mt-8 w-12 h-8"
                :disabled="tiers.length <= 1"
                @click="removeTierDetailsOf(index)"
            />
        </div>
    </div>

    <div class="grid grid-cols-1 gap-0 sm:gap-6">
        <OutlinePrimaryButton
            text="+ Add New Tier"
            type="button"
            class="border-dashed w-full"
            @click="addNewTierDetails()"
        />
    </div>
</template>
<script setup>
import FormInput from '@commonComponents/FormInput.vue';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import DeleteButton from '@commonComponents/DeleteButton.vue';

defineProps({
    tiers: {
        type: Object,
        required: true,
    },
    buyInputLabel: {
        type: String,
        default: null,
    },
    buyInputTitle: {
        type: String,
        default: null,
    },
    getQuantityInputLabel: {
        type: String,
        default: null,
    },
    getQuantityInputTitle: {
        type: String,
        default: null,
    },
    getInputLabel: {
        type: String,
        default: null,
    },
    buyValueInputGroupPrefix: {
        type: String,
        default: null,
    },
    getValueInputGroupPrefix: {
        type: String,
        default: null,
    },
    getValueInputGroupSuffix: {
        type: String,
        default: null,
    },
    maxValueInputLabel: {
        type: String,
        default: null,
    },
    maxValueInputTitle: {
        type: String,
        default: null,
    },
    maxValueInputGroupPrefix: {
        type: String,
        default: null,
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

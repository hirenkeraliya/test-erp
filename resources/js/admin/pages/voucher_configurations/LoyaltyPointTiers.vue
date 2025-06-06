<template>
    <div
        v-for="(tier, index) in tiers"
        :key="index"
        class="grid grid-cols-12 gap-0 sm:gap-6 mb-3"
    >
        <div class="input-form col-span-12 sm:col-span-12 md:col-span-6 lg:col-span-4 xl:col-span-3">
            <FormInput
                type="number"
                input-label="Loyalty Point"
                :input-value="tier.minimum_spend_amount"
                :validation-field-name="'tiers.' + index + '.minimum_spend_amount'"
                @update:input-value="updateTierValueDetails($event, index, 'minimum_spend_amount')"
            />
        </div>

        <div class="input-form col-span-12 sm:col-span-12 md:col-span-6 lg:col-span-4 xl:col-span-3">
            <FormInput
                class="mr-1"
                :input-label="getValueInputLabel"
                :input-value="tier.get_value"
                :validation-field-name="'tiers.' + index + '.get_value'"
                :input-group-prefix="getValueInputGroupPrefix"
                :input-group-suffix="getValueInputGroupSuffix"
                @update:input-value="updateTierValueDetails($event, index, 'get_value')"
            />
        </div>

        <div class="input-form col-span-12 sm:col-span-12 md:col-span-6 lg:col-span-4 xl:col-span-3">
            <DeleteButton
                :disabled="tiers.length <= 1"
                type="button"
                class="mt-2 sm:mt-0 md:mt-0 lg:mt-8 xl:mt-8 w-12 h-8"
                @click="removeTierDetailsOf(index)"
            />
        </div>
    </div>

    <div class="grid grid-rows-1 grid-flow-col gap-4 mt-4">
        <OutlinePrimaryButton
            text="+ Add New Tier"
            type="button"
            class="border-dashed"
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
    getValueInputLabel: {
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
    }
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

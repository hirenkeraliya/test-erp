<template>
    <div
        v-for="(tier, index) in tiers"
        :key="index"
        class="grid grid-cols-12 gap-0 sm:gap-6 mt-1"
    >
        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
            <FormSelectBox
                v-model:selected-record="tier.condition_operator_type_id"
                :records="conditionTypes"
                input-label="Condition Type"
                :validation-field-name="'tiers.' + index + '.condition_operator_type_id'"
                :required="true"
                @update:selected-record="updateTierValueDetails($event, index, 'condition_operator_type_id')"
            />
        </div>

        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
            <FormInput
                class="mr-1"
                type="number"
                :input-label="getValueInputLabel"
                :input-value="tier.amount"
                :validation-field-name="'tiers.' + index + '.amount'"
                :required="true"
                @update:input-value="updateTierValueDetails($event, index, 'amount')"
            />
        </div>

        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
            <DeleteButton
                type="button"
                class="mt-2 sm:mt-0 md:mt-0 lg:mt-8 xl:mt-8 w-12 h-8"
                @click="removeTierDetailsOf(index)"
            />
        </div>
    </div>

    <div class="grid grid-cols-12 gap-0 sm:gap-6 py-5">
        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
            <OutlinePrimaryButton
                text="+ Add New Tier"
                type="button"
                class="border-dashed w-full"
                @click="addNewTierDetails()"
            />
        </div>
    </div>
</template>

<script setup>
import FormInput from '@commonComponents/FormInput.vue';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import DeleteButton from '@commonComponents/DeleteButton.vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';

defineProps({
    tiers: {
        type: Object,
        required: true,
    },
    getValueInputLabel: {
        type: String,
        default: null,
    },
    conditionTypes: {
        type: Object,
        required: true,
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

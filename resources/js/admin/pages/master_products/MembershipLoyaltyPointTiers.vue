<template>
    <div
        v-for="(tier, index) in tiers"
        :key="index"
        class="grid grid-cols-12 gap-0 sm:gap-6"
    >
        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
            <FormSelectBox
                v-model:selected-record="tier.membership_id"
                :records="memberships"
                input-label="Membership"
                :validation-field-name="'variants.' + variantIndex +'.tiers.' + index + '.membership_id'"
                @update:selected-record="updateTierValueDetails($event, index, 'membership_id')"
            />
        </div>

        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
            <FormInput
                class="mr-1"
                type="number"
                :input-label="getValueInputLabel"
                :input-value="tier.points"
                :validation-field-name="'variants.' + variantIndex +'.tiers.' + index + '.points'"
                @update:input-value="updateTierValueDetails($event, index, 'points')"
            />
        </div>

        <div class="input-form col-span-12 sm:col-span-12 md:col-span-6 lg:col-span-4 xl:col-span-3">
            <DeleteButton
                type="button"
                class="mt-2 sm:mt-0 md:mt-0 lg:mt-8 xl:mt-8 w-12 h-8"
                @click="removeTierDetailsOf(index)"
            />
        </div>
    </div>

    <div class="grid grid-cols-12 gap-0 sm:gap-6 p-5 pb-0">
        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-6 xl:col-span-6">
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
    variantIndex: {
        type: Number,
        default: 0,
    },
    getValueInputLabel: {
        type: String,
        default: null,
    },
    memberships: {
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

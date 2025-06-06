<template>
    <div
        v-for="(tier, index) in tiers"
        :key="index"
        class="grid grid-cols-12 gap-0 sm:gap-6 mb-3"
    >
        <div class="input-form col-span-12 sm:col-span-12 md:col-span-6 lg:col-span-4 xl:col-span-3">
            <FormSelectBox
                v-model:selected-record="tier.webhook_url_type_id"
                :records="tier.webhook_url_type_id ? webhookUrls : getFilterWebhookUrls()"
                input-label="Webhook URL Type"
                :validation-field-name="'webhookUrls.' + index + '.webhook_url_type_id'"
                :required="true"
                @update:input-value="updateTierValueDetails($event, index, 'webhook_url_type_id')"
            />
        </div>

        <div class="input-form col-span-12 sm:col-span-12 md:col-span-6 lg:col-span-4 xl:col-span-3">
            <FormInput
                type="url"
                input-name="url"
                input-label="Url"
                :input-value="tier.url"
                :validation-field-name="'webhookUrls.' + index + '.url'"
                :required="true"
                @update:input-value="updateTierValueDetails($event, index, 'url')"
            />
        </div>

        <div class="input-form col-span-12 sm:col-span-12 md:col-span-6 lg:col-span-4 xl:col-span-3">
            <DeleteButton
                class="mt-2 sm:mt-0 md:mt-0 lg:mt-8 xl:mt-8 w-12 h-8"
                @click="removeTierDetailsOf(index)"
            />
        </div>
    </div>

    <div class="grid grid-cols-3 gap-0 sm:gap-6">
        <OutlinePrimaryButton
            text="+ Add New Tier"
            type="button"
            class="border-dashed w-full"
            @click="addNewTierDetails()"
        />
    </div>
</template>
<script setup>
import DeleteButton from '@commonComponents/DeleteButton.vue';
import FormInput from '@commonComponents/FormInput.vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';

const props = defineProps({
    tiers: {
        type: Object,
        required: true,
    },
    webhookUrls: {
        type: Object,
        required: true,
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

const getFilterWebhookUrls = () => {
    return props.webhookUrls.filter(webhookUrl => {
        return props.tiers.every(tier => tier.webhook_url_type_id !== webhookUrl.id);
    });
};
</script>

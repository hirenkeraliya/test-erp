<template>
    <PageTitle :title="courier ? 'Edit Courier' : 'Add Courier'" />
    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Courier
        </h2>
    </div>
    <div
        class="grid grid-cols-12 gap-6 mt-5"
    >
        <div class="intro-y col-span-12 lg:col-span-12">
            <div class="intro-y box">
                <div class="flex flex-col sm:flex-row items-center py-2 px-5 sm:p-5 bg-slate-100 border-b border-slate-200/60">
                    <h2 class="font-medium text-base mr-auto">
                        {{ courier ? 'Edit' : 'Add' }} Courier
                    </h2>
                </div>
                <form @submit.prevent="saveCourier();">
                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="courierForm.name"
                                    :required="true"
                                    input-name="name"
                                    input-label="Name"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="courierForm.code"
                                    :required="true"
                                    input-name="code"
                                    input-label="Code"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="courierForm.url"
                                    type="url"
                                    :required="true"
                                    input-name="url"
                                    input-label="Url"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="courierForm.client_id"
                                    :required="true"
                                    input-name="client_id"
                                    input-label="Client Id"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="courierForm.client_secret"
                                    :required="true"
                                    input-name="client_secret"
                                    input-label="Client Secret"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormSelectBox
                                    v-model:selected-record="courierForm.type_id"
                                    :records="courierTypes"
                                    input-label="Courier  Type"
                                    validation-field-name="type_id"
                                    :required="true"
                                />
                            </div>
                        </div>

                        <div class="mt-5">
                            <h1 class="text-lg font-medium mr-auto">
                                Webhook Urls<sup class="text-danger">*</sup>
                            </h1>
                            <Tiers
                                :tiers="courierForm.webhook_urls"
                                :webhook-urls="courierWebhookUrls"
                                @update:tier-value-details="updateTierValueDetails"
                                @add:new-tier-details="addNewTierDetails"
                                @remove:tier-details-of="removeTierDetailsOf"
                            />
                        </div>

                        <div class="mt-5">
                            <Link :href="route('super_admin.courier.index')">
                                <SecondaryButton
                                    type="button"
                                    text="Cancel"
                                    class="w-24 mr-1"
                                />
                            </Link>

                            <PrimaryButton
                                type="submit"
                                :text="courier ? 'Update' : 'Submit'"
                                class="w-24"
                            />
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import FormInput from '@commonComponents/FormInput.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import SecondaryButton from '@commonComponents/SecondaryButton.vue';
import { route } from 'ziggy';
import { onMounted } from 'vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import Tiers from '@superAdminPages/courier/partials/Tiers.vue';

const props = defineProps({
    courier: {
        type: Object,
        default: null,
    },
    courierWebhookUrls: {
        type: Object,
        default: null,
    },
    courierTypes: {
        type: Object,
        default: null,
    },

});

const courierForm = useForm({
    name: null,
    code: null,
    type_id: null,
    url: null,
    client_secret: null,
    client_id: null,
    webhook_urls: [],
});

const saveCourier = () => {
    if (props.courier) {
        courierForm.put(route('super_admin.courier.update', props.courier.data.id));
        return;
    }
    courierForm.post(route('super_admin.courier.store'));
};

onMounted(() => {
    addNewTierDetails();
    if (props.courier) {
        Object.assign(courierForm, props.courier.data);
    }
});

const addNewTierDetails = () => {
    courierForm.webhook_urls.push({ webhook_url_type_id: null, url: null });
};

const updateTierValueDetails = (details) => {
    courierForm.webhook_urls[details.key][details.column_name] = details.value;
};

const removeTierDetailsOf = (key) => {
    courierForm.webhook_urls.splice(key, 1);
};
</script>

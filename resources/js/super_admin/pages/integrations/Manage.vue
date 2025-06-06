<template>
    <PageTitle :title="integrations ? 'Edit Integration' : 'Add Integration'" />
    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Integrations
        </h2>
    </div>
    <div
        v-if="!state.clientToken"
        class="grid grid-cols-12 gap-6 mt-5"
    >
        <div class="intro-y col-span-12 lg:col-span-12">
            <div class="intro-y box">
                <div class="flex flex-col sm:flex-row items-center py-2 px-5 sm:p-5 bg-slate-100 border-b border-slate-200/60">
                    <h2 class="font-medium text-base mr-auto">
                        {{ integrations ? 'Edit' : 'Add' }} Integration
                    </h2>
                </div>
                <form @submit.prevent="saveIntegration();">
                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="integrationForm.name"
                                    :required="true"
                                    input-name="name"
                                    input-label="Name"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="integrationForm.url"
                                    type="url"
                                    :required="true"
                                    input-name="url"
                                    input-label="Url"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="integrationForm.secret"
                                    :required="true"
                                    input-name="secret"
                                    input-label="Secret"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormSelectBox
                                    :selected-record="integrationForm.company_id"
                                    :records="companies"
                                    input-label="Company"
                                    validation-field-name="company_id"
                                    :required="true"
                                    @update:selected-record="setCompanyId($event)"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormSelectBox
                                    v-model:selected-record="integrationForm.connection_type"
                                    :records="state.connectionTypes"
                                    input-label="Connection Type"
                                    validation-field-name="type_id"
                                    :required="true"
                                    @update:selected-record="updateConnectionType"
                                />
                            </div>
                        </div>

                        <div
                            v-if="staticConnectionTypes.retailPlanning === integrationForm.connection_type"
                            class="mt-5"
                        >
                            <h1 class="text-lg font-medium mr-auto">
                                Webhook Urls<sup class="text-danger">*</sup>
                            </h1>
                            <Tiers
                                :tiers="integrationForm.webhook_urls"
                                :webhook-urls="webhookUrls"
                                @update:tier-value-details="updateTierValueDetails"
                                @add:new-tier-details="addNewTierDetails"
                                @remove:tier-details-of="removeTierDetailsOf"
                            />
                        </div>

                        <div class="mt-5">
                            <Link :href="route('super_admin.integrations.index')">
                                <SecondaryButton
                                    type="button"
                                    text="Cancel"
                                    class="w-24 mr-1"
                                />
                            </Link>

                            <PrimaryButton
                                type="submit"
                                :text="integrations ? 'Update' : 'Submit'"
                                class="w-24"
                            />
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div v-else>
        <div class="mt-5">
            <div class="my-3 overflow-auto">
                <p class="text-base">
                    <b>
                        Api Token
                    </b>: {{ state.clientToken }}
                </p>
            </div>

            <Link :href="route('super_admin.integrations.index')">
                <SecondaryButton
                    type="button"
                    text="Done"
                    class="w-24 mr-1"
                />
            </Link>
        </div>
    </div>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import FormInput from '@commonComponents/FormInput.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import SecondaryButton from '@commonComponents/SecondaryButton.vue';
import { route } from 'ziggy';
import { onMounted, reactive } from 'vue';
import axios from 'axios';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import { showErrorNotification } from '@commonServices/notifier';
import Tiers from '@superAdminPages/sales_channel/partials/Tiers.vue';

const props = defineProps({
    integrations: {
        type: Object,
        default: null,
    },
    companies: {
        type: Object,
        default: null,
    },
    connectionTypes: {
        type: Object,
        default: null,
    },
    webhookUrls: {
        type: Object,
        default: null,
    },
    staticConnectionTypes: {
        type: Object,
        required: true,
    },
});

const state = reactive({
    connectionTypes: [],
    clientToken: null,
});

const integrationForm = useForm({
    name: null,
    company_id: null,
    connection_type: null,
    url: null,
    secret: null,
    webhook_urls: [],
});

const saveIntegration = () => {

    if (props.integrations) {
        integrationForm.put(route('super_admin.integrations.update', props.integrations.data.id));
        return;
    }

    axios.post(route('super_admin.integrations.store'), integrationForm)
        .then((response) => {
            state.clientToken = response.data.token;
        })
        .catch((error) => {
            if (error.response.data.message) {
                showErrorNotification(error.response.data.message);
            }
        });
};

const setCompanyId = (companyId) => {
    integrationForm.company_id = companyId;
};

const addNewTierDetails = () => {
    integrationForm.webhook_urls.push({ webhook_url_type_id: null, url: integrationForm.url });
};

const updateTierValueDetails = (details) => {
    integrationForm.webhook_urls[details.key][details.column_name] = details.value;
};

const removeTierDetailsOf = (key) => {
    integrationForm.webhook_urls.splice(key, 1);
};

const updateConnectionType = () => {
    integrationForm.webhook_urls = [];
};

onMounted(() => {
    if (props.integrations) {
        Object.assign(integrationForm, props.integrations.data);
    }

    if (props.connectionTypes && props.connectionTypes.length > 0) {
        state.connectionTypes = props.connectionTypes;
    }
});

</script>

<template>
    <PageTitle :title="salesChannel ? 'Edit Sale Channel' : 'Add Sale Channel'" />
    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Sale Channel
        </h2>
    </div>
    <div
        v-if="state.clientToken.length === 0"
        class="grid grid-cols-12 gap-6 mt-5"
    >
        <div
            class="intro-y col-span-12 lg:col-span-12"
        >
            <div
                class="intro-y box"
            >
                <div
                    class="flex flex-col sm:flex-row items-center py-2 px-5 sm:p-5 bg-slate-100 border-b border-slate-200/60"
                >
                    <h2 class="font-medium text-base mr-auto">
                        {{ salesChannel ? 'Edit' : 'Add' }} Sale Channel
                    </h2>
                </div>
                <form @submit.prevent="saveSaleChannel();">
                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="saleChannelForm.name"
                                    :required="true"
                                    input-name="name"
                                    input-label="Name"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="saleChannelForm.code"
                                    :required="true"
                                    input-name="code"
                                    input-label="Code"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="saleChannelForm.url"
                                    type="url"
                                    :required="true"
                                    input-name="url"
                                    input-label="Url"
                                    @update:input-value="updateUrlToWebhookUrl()"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="saleChannelForm.secret"
                                    :required="true"
                                    input-name="secret"
                                    input-label="Secret"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormSelectBox
                                    :selected-record="saleChannelForm.company_id"
                                    :records="companies"
                                    input-label="Company"
                                    validation-field-name="company_id"
                                    :required="true"
                                    @update:selected-record="setCompanyIdAndFetchLocations($event)"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormSelectBox
                                    v-model:selected-record="saleChannelForm.default_location_id"
                                    :records="state.locations"
                                    input-label="Default Location"
                                    validation-field-name="default_location_id"
                                    :required="true"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormSelectBox
                                    v-model:selected-record="saleChannelForm.type_id"
                                    :records="saleChannelTypes"
                                    input-label="Sale Channel Type"
                                    validation-field-name="type_id"
                                    :required="true"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormSelectBox
                                    v-model:selected-record="saleChannelForm.inventory_deduct_order_status"
                                    :records="orderStatuses"
                                    input-label="Inventory Deduct Order Status"
                                    validation-field-name="inventory_deduct_order_status"
                                    :required="true"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <JMultiSelect
                                    v-model:selected-records="state.inventoryRollbackOrderStatus"
                                    :records="orderStatuses"
                                    input-label="Inventory rollback order statuses "
                                    :required="true"
                                    validation-field-name="inventory_rollback_order_status"
                                />
                            </div>
                        </div>
                    </div>

                    <template v-if="saleChannelForm.type_id === saleChannelTypesEcommerce">
                        <div
                            class="flex flex-col sm:flex-row items-center p-5 mt-5 bg-slate-100 border-b border-slate-200/60"
                        />

                        <div class="grid grid-cols-12 gap-0 sm:gap-2">
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <JSwitch
                                    v-model:is-checked="saleChannelForm.display_variants"
                                    input-label="Display Variant Product"
                                    validation-field-name="display_variants"
                                    title="If enabled, the variant product will be displayed; otherwise, the master product is displayed in the e-commerce product list."
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <JSwitch
                                    v-model:is-checked="saleChannelForm.display_dynamic_menus"
                                    input-label="Display Dynamic Menus"
                                    validation-field-name="display_dynamic_menus"
                                    title="If enabled, menu will be render in eCommerce based on Dynamic menu configured in the Admin panel; otherwise, the categories based menu will be rendered."
                                />
                            </div>
                        </div>
                    </template>

                    <div
                        class="flex flex-col sm:flex-row items-center p-5 mt-5 bg-slate-100 border-b border-slate-200/60"
                    />

                    <div class="grid md:grid-cols-12 gap-0 sm:gap-6">
                        <div class="mt-5 xl:col-span-6 lg:col-span-6 md:col-span-6 ml-5">
                            <h1 class="text-lg font-medium">
                                Webhook Urls<sup class="text-danger">*</sup>
                            </h1>

                            <Tiers
                                :tiers="saleChannelForm.webhook_urls"
                                :webhook-urls="webhookUrls"
                                @update:tier-value-details="updateTierValueDetails"
                                @add:new-tier-details="addNewTierDetails"
                                @remove:tier-details-of="removeTierDetailsOf"
                            />
                        </div>

                        <div class="bg-slate-100 border-b border-slate-200/60" />

                        <div class="mt-5 xl:col-span-5 lg:col-span-5 md:col-span-5">
                            <h1 class="text-lg font-medium">
                                Round Off Configuration<sup class="text-danger">*</sup>
                            </h1>

                            <div class="overflow-x-auto">
                                <table class="table">
                                    <tbody>
                                        <tr
                                            v-for="(item, index) in state.roundOffValues"
                                            :key="index"
                                        >
                                            <td class="whitespace-nowrap">
                                                Decimal Place<br>{{ item.decimal_place }}
                                            </td>

                                            <td class="whitespace-nowrap">
                                                <FormInput
                                                    v-model:input-value="item.value"
                                                    input-label="Value"
                                                    type="text"
                                                    :required="true"
                                                    :input-name="'round_off_value_' + index"
                                                    class="w-50"
                                                />
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5">
                        <Link :href="route('super_admin.sales_channel.index')">
                            <SecondaryButton
                                type="button"
                                text="Cancel"
                                class="w-24 mr-1"
                            />
                        </Link>

                        <PrimaryButton
                            type="submit"
                            :text="salesChannel ? 'Update' : 'Submit'"
                            class="w-24"
                        />
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

            <Link :href="route('super_admin.sales_channel.index')">
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
import JMultiSelect from '@commonComponents/JMultiSelect.vue';
import { useForm } from '@inertiajs/vue3';
import FormInput from '@commonComponents/FormInput.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import SecondaryButton from '@commonComponents/SecondaryButton.vue';
import { route } from 'ziggy';
import { onMounted, reactive, } from 'vue';
import axios from 'axios';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import Tiers from '@superAdminPages/sales_channel/partials/Tiers.vue';
import { showErrorNotification } from '@commonServices/notifier';
import JSwitch from '@commonComponents/JSwitch.vue';

const props = defineProps({
    salesChannel: {
        type: Object,
        default: null,
    },
    webhookUrls: {
        type: Object,
        default: null,
    },
    orderStatuses: {
        type: Object,
        default: null,
    },
    saleChannelTypes: {
        type: Object,
        default: null,
    },
    companies: {
        type: Object,
        default: null,
    },
    locations: {
        type: Object,
        default: null,
    },
    saleChannelTypesEcommerce: {
        type: Number,
        default: null,
    },
    roundOffData: {
        type: Array,
        required: true,
    },
});

const state = reactive({
    locations: [],
    inventoryRollbackOrderStatus: [],
    clientToken: [],
    roundOffValues: [],
});

const saleChannelForm = useForm({
    name: null,
    code: null,
    company_id: null,
    default_location_id: null,
    type_id: null,
    url: null,
    secret: null,
    inventory_deduct_order_status: null,
    inventory_rollback_order_status: [],
    webhook_urls: [],
    display_variants: true,
    display_dynamic_menus: false,
    round_off_configuration: null,
});

const saveSaleChannel = () => {
    saleChannelForm.inventory_rollback_order_status = state.inventoryRollbackOrderStatus.map((status) => {
        return status.id;
    });

    saleChannelForm.round_off_configuration = JSON.stringify(state.roundOffValues);

    if (props.salesChannel) {
        saleChannelForm.put(route('super_admin.sales_channel.update', props.salesChannel.data.id));
        return;
    }

    axios.post(route('super_admin.sales_channel.store'), saleChannelForm)
        .then((response) => {
            state.clientToken = response.data.token;
        })
        .catch((error) => {
            if (error.response.data.message) {
                showErrorNotification(error.response.data.message);
            }
        });
};

onMounted(() => {
    if (props.salesChannel) {
        state.inventoryRollbackOrderStatus = props.salesChannel.data.inventory_rollback_order_status;
        Object.assign(saleChannelForm, props.salesChannel.data);

        if (props.salesChannel.data.round_off_configuration) {
            state.roundOffValues = JSON.parse(props.salesChannel.data.round_off_configuration);
        }
    }

    if (props.locations && props.locations.length > 0) {
        state.locations = props.locations;
    }

    if (!state.roundOffValues.length) {
        state.roundOffValues = props.roundOffData;
    }
});

const setCompanyIdAndFetchLocations = (companyId) => {
    saleChannelForm.company_id = companyId;

    axios.get(route('super_admin.locations.get_by_company', companyId)).then((response) => {
        if (response.data.data.length) {
            state.locations = response.data.data;
        }
    });
};

const addNewTierDetails = () => {
    saleChannelForm.webhook_urls.push({ webhook_url_type_id: null, url: saleChannelForm.url });
};

const updateUrlToWebhookUrl = () => {
    saleChannelForm.webhook_urls.map((webhookUrl) => {
        webhookUrl.url = saleChannelForm.url;
        return webhookUrl;
    });
};

const updateTierValueDetails = (details) => {
    saleChannelForm.webhook_urls[details.key][details.column_name] = details.value;
};

const removeTierDetailsOf = (key) => {
    saleChannelForm.webhook_urls.splice(key, 1);
};
</script>

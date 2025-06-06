<template>
    <PageTitle :title="onlineSalesCharge ? 'Edit Online Sales Charge' : 'Add Online Sales Charge'" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Online Sales charge
        </h2>
    </div>

    <div class="grid grid-cols-12 gap-6 mt-5">
        <div class="intro-y col-span-12 lg:col-span-12">
            <div class="intro-y box">
                <div
                    class="flex flex-col sm:flex-row items-center py-2 px-5 sm:p-5 bg-slate-100 border-b border-slate-200/60"
                >
                    <h2 class="font-medium text-base mr-auto">
                        <span v-if="onlineSalesCharge">Edit Online Sales Charge</span>
                        <span v-else>Add Online Sales Charge</span>
                    </h2>
                    <SecondaryButton
                        type="button"
                        text="Clear"
                        class="w-24"
                        @click="clearFormData"
                    />
                </div>

                <form @submit.prevent="saveOnlineSalesCharge();">
                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="onlineSalesChargeForm.name"
                                    input-name="name"
                                    input-label="Name"
                                    :required="true"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormSelectBox
                                    v-model:selected-record="onlineSalesChargeForm.shipping_zone_id"
                                    :records="shippingZones"
                                    input-label="Zone"
                                    validation-field-name="shipping_zone_id"
                                    :required="true"
                                />
                            </div>

                            <div
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <FormSelectBox
                                    v-model:selected-record="onlineSalesChargeForm.shipping_charge_type_id"
                                    :records="shippingChargeTypes"
                                    input-label="Shipping Charge Types"
                                    validation-field-name="shipping_charge_type_id"
                                    :required="true"
                                    @update:selected-record="getShippingChargeType"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                            <template
                                v-if="onlineSalesChargeForm.shipping_charge_type_id !== staticWeightType"
                            >
                                <div
                                    class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                                >
                                    <FormInput
                                        v-model:input-value="onlineSalesChargeForm.minimum_value"
                                        input-name="minimum_value"
                                        input-label="Minimum Value"
                                        :required="true"
                                    />
                                </div>

                                <div
                                    class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                                >
                                    <FormInput
                                        v-model:input-value="onlineSalesChargeForm.maximum_value"
                                        input-name="maximum_value"
                                        input-label="Maximum Value"
                                        :required="true"
                                    />
                                </div>
                            </template>

                            <div
                                v-if="onlineSalesChargeForm.shipping_charge_type_id !== staticWeightType"
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <FormInput
                                    v-model:input-value="onlineSalesChargeForm.amount"
                                    input-name="amount"
                                    input-label="Amount"
                                    :input-group-prefix="currencySymbol"
                                    :required="true"
                                />
                            </div>
                        </div>

                        <template v-if="onlineSalesChargeForm.shipping_charge_type_id === staticWeightType">
                            <div
                                v-for="(tier, index) in onlineSalesChargeForm.online_sales_charge_tiers"
                                :key="index"
                                class="grid grid-cols-12 gap-0 sm:gap-6"
                            >
                                <div
                                    class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                                >
                                    <FormInput
                                        v-model:input-value="tier.min_weight"
                                        class="mr-1"
                                        type="number"
                                        input-label="Min. Weight(kg)"
                                        :validation-field-name="'weight_tiers.' + index + '.min_weight'"
                                        :required="true"
                                    />
                                </div>

                                <div
                                    class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                                >
                                    <FormInput
                                        v-model:input-value="tier.max_weight"
                                        class="mr-1"
                                        type="number"
                                        input-label="Max. Weight(kg)"
                                        :validation-field-name="'weight_tiers.' + index + '.max_weight'"
                                        :required="true"
                                    />
                                </div>

                                <div
                                    class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                                >
                                    <FormInput
                                        v-model:input-value="tier.amount"
                                        class="mr-1"
                                        type="number"
                                        input-label="Amount"
                                        :input-group-prefix="currencySymbol"
                                        :validation-field-name="'weight_tiers.' + index + '.amount'"
                                        :required="true"
                                    />
                                </div>

                                <div
                                    class="input-form col-span-12 sm:col-span-12 md:col-span-6 lg:col-span-4 xl:col-span-3"
                                >
                                    <DeleteButton
                                        type="button"
                                        class="mt-2 sm:mt-0 md:mt-0 lg:mt-8 xl:mt-8 w-12 h-8"
                                        @click="removeTier(index)"
                                    />
                                </div>
                            </div>

                            <div class="grid grid-cols-12 gap-0 sm:gap-6 pt-5 pb-0">
                                <div
                                    class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-6 xl:col-span-6"
                                >
                                    <OutlinePrimaryButton
                                        text="+ Add New Tier"
                                        type="button"
                                        class="border-dashed w-full"
                                        @click="addNewTier()"
                                    />
                                </div>
                            </div>
                        </template>

                        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                            <JSwitch
                                input-label="Available Online"
                                :is-checked="onlineSalesChargeForm.is_available_in_ecommerce"
                                class="mt-3"
                                @update:is-checked="updateIsAvailableInEcommerce"
                            />

                            <div
                                v-if="onlineSalesChargeForm.is_available_in_ecommerce"
                                class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-6 xl:col-span-6"
                            >
                                <JMultiSelect
                                    v-model:selected-records="onlineSalesChargeForm.sale_channels"
                                    :records="saleChannels"
                                    input-label="Sale Channels"
                                    :required="true"
                                    validation-field-name="sale_channel_ids"
                                    class="w-full"
                                />
                            </div>
                        </div>

                        <div class="mt-5">
                            <Link :href="route('admin.online_sales_charges.index')">
                                <SecondaryButton
                                    type="button"
                                    text="Cancel"
                                    class="w-24 mr-1"
                                />
                            </Link>

                            <PrimaryButton
                                type="submit"
                                :text="onlineSalesCharge ? 'Update' : 'Submit'"
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
import { useForm, usePage } from '@inertiajs/vue3';
import FormInput from '@commonComponents/FormInput.vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import SecondaryButton from '@commonComponents/SecondaryButton.vue';
import { computed, onMounted, watch } from 'vue';
import { route } from 'ziggy';
import { removeLocalStorage, setLocalStorage, saveLocalStorage } from '@commonServices/helper';
import JSwitch from '@commonComponents/JSwitch.vue';
import JMultiSelect from '@commonComponents/JMultiSelect.vue';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import DeleteButton from '@commonComponents/DeleteButton.vue';

const pageProps = computed(() => usePage().props);
const currencySymbol = pageProps.value.currency_symbol;

const props = defineProps({
    onlineSalesCharge: {
        type: Object,
        default: null,
    },
    saleChannels: {
        type: Array,
        required: true,
    },
    shippingChargeTypes: {
        type: Array,
        required: true,
    },
    shippingZones: {
        type: Object,
        required: true,
    },
    staticWeightType: {
        type: Number,
        required: true,
    },
});

const onlineSalesChargeForm = useForm({
    shipping_charge_type_id: null,
    shipping_zone_id: null,
    name: null,
    minimum_value: null,
    maximum_value: null,
    amount: null,
    watchEnabled: true,
    is_available_in_ecommerce: false,
    sale_channels: [],
    sale_channel_ids: [],
    online_sales_charge_tiers: []
});

const addNewTier = () => {
    onlineSalesChargeForm.online_sales_charge_tiers.push({
        'min_weight': 0,
        'max_weight': 0,
        'amount': 0,
    });
};

const removeTier = (key) => {
    onlineSalesChargeForm.online_sales_charge_tiers.splice(key, 1);
};

const saveOnlineSalesCharge = () => {
    onlineSalesChargeForm.watchEnabled = false;
    removeLocalStorage('onlineSalesCharge');

    if (onlineSalesChargeForm.sale_channels) {
        onlineSalesChargeForm.sale_channel_ids = onlineSalesChargeForm.sale_channels.map((saleChannel) => {
            return saleChannel.id;
        });
    }

    if (props.onlineSalesCharge) {
        onlineSalesChargeForm.put(route('admin.online_sales_charges.update', props.onlineSalesCharge.id));
        return;
    }
    onlineSalesChargeForm.post(route('admin.online_sales_charges.store'));
};

const checkSaveLocalStorage = () => {
    if (!props.onlineSalesCharge) {
        saveLocalStorage('onlineSalesCharge', onlineSalesChargeForm);
    }
};

const clearFormData = () => {
    onlineSalesChargeForm.reset();
};

watch(onlineSalesChargeForm, () => {
    if (onlineSalesChargeForm.watchEnabled) {
        checkSaveLocalStorage();
    }
}, { deep: true });

onMounted(() => {
    if (props.onlineSalesCharge) {
        removeLocalStorage('onlineSalesCharge');
        Object.assign(onlineSalesChargeForm, props.onlineSalesCharge);
    } else {
        setLocalStorage('onlineSalesCharge', onlineSalesChargeForm);
    }
});

const updateIsAvailableInEcommerce = (data) => {
    onlineSalesChargeForm.sale_channels = [];
    onlineSalesChargeForm.is_available_in_ecommerce = data;
};

const getShippingChargeType = (value) => {
    if (value !== props.staticWeightType) {
        onlineSalesChargeForm.online_sales_charge_tiers = [];
        return;
    }

    onlineSalesChargeForm.minimum_value = null;
    onlineSalesChargeForm.maximum_value = null;
    onlineSalesChargeForm.amount = null;
};
</script>

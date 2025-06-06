<template>
    <PageTitle :title="paymentType ? 'Edit Payment Type' : 'Add Payment Type'" />
    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Payment Types
        </h2>
    </div>
    <div class="grid grid-cols-12 gap-6 mt-5">
        <div class="intro-y col-span-12 lg:col-span-12">
            <div class="intro-y box">
                <div class="flex flex-col sm:flex-row items-center py-2 px-5 sm:p-5 bg-slate-100 border-b border-slate-200/60">
                    <h2 class="font-medium text-base mr-auto">
                        <span v-if="paymentType">Edit Payment Type</span>
                        <span v-else>Add Payment Type</span>
                    </h2>
                </div>
                <form @submit.prevent="savePaymentType();">
                    <div class="p-5">
                        <div class="grid grid-rows-2 grid-flow-col gap-8">
                            <div class="row row-span-2 ">
                                <div class="input-form col-span-6 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                    <FormInput
                                        v-model:input-value="paymentTypeForm.name"
                                        input-name="name"
                                        input-label="Name"
                                        :required="true"
                                    />
                                </div>

                                <div class="input-form col-span-6 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3 ">
                                    <FormInput
                                        v-model:input-value="paymentTypeForm.payment_terminal_key"
                                        input-name="payment_terminal_key"
                                        input-label="Payment Terminal Key"
                                        title="If this payment type will be done via any of the third party machine then please specify the machine key."
                                    />
                                </div>

                                <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                    <JSwitch
                                        input-label="Available Online"
                                        :is-checked="paymentTypeForm.is_available_in_ecommerce"
                                        class="mt-3"
                                        @update:is-checked="updateIsAvailableInEcommerce"
                                    />

                                    <div
                                        v-if="paymentTypeForm.is_available_in_ecommerce"
                                        class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-6 xl:col-span-6"
                                    >
                                        <JMultiSelect
                                            v-model:selected-records="paymentTypeForm.sale_channels"
                                            :records="saleChannels"
                                            input-label="Sale Channels"
                                            :required="true"
                                            validation-field-name="sale_channel_ids"
                                            class="w-full"
                                        />
                                    </div>
                                    <div
                                        v-if="paymentTypeForm.is_available_in_ecommerce"
                                        class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-6 xl:col-span-6"
                                    >
                                        <FormInput
                                            v-model:input-value="paymentTypeForm.site_key"
                                            input-name="site_key"
                                            input-label="Site Key"
                                        />
                                    </div>
                                    <div
                                        v-if="paymentTypeForm.is_available_in_ecommerce"
                                        class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-6 xl:col-span-6"
                                    >
                                        <FormInput
                                            v-model:input-value="paymentTypeForm.secret_key"
                                            input-name="secret_key"
                                            input-label="Secret Key"
                                        />
                                    </div>
                                    <div
                                        v-if="paymentTypeForm.is_available_in_ecommerce"
                                        class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-6 xl:col-span-6"
                                    >
                                        <FormInput
                                            v-model:input-value="paymentTypeForm.url"
                                            input-name="url"
                                            input-label="Url"
                                        />
                                    </div>
                                </div>
                                <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                    <JSwitch
                                        input-label="Restrict by Zone?"
                                        :is-checked="paymentTypeForm.restrict_by_zone"
                                        class="mt-3"
                                        @update:is-checked="restrictByZone"
                                    /> <br>

                                    <template v-if="paymentTypeForm.restrict_by_zone">
                                        <label
                                            class="form-label text-sm font-medium text-gray-700 p-5 mt-5"
                                        >
                                            Restriction Type
                                        </label>
                                        <div class="form-check-label flex items-center p-3 gap-4 mb-4">
                                            <label
                                                v-for="type in paymentRestrictionTypes"
                                                :key="type.id"
                                                class="flex items-center gap-1"
                                            >
                                                <input
                                                    v-model="paymentTypeForm.restriction_type"
                                                    type="radio"
                                                    :value="type.id"
                                                    class="form-check-input"
                                                    name="restriction_type_id"
                                                >
                                                {{ type.name }}
                                            </label>
                                        </div>

                                        <div
                                            class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-6 xl:col-span-6"
                                        >
                                            <JMultiSelect
                                                v-model:selected-records="paymentTypeForm.shipping_zones"
                                                :records="shippingZones"
                                                input-label="Shipping Zones"
                                                :required="true"
                                                validation-field-name="shipping_zone_ids"
                                                class="w-full"
                                            />
                                        </div>
                                    </template>
                                </div>
                                <div class="input-form col-span-6 sm:col-span-6 md:col-span-12 lg:col-span-6 xl:col-span-6 mt-2">
                                    <label>
                                        Image
                                        <span class="text-danger">*</span>
                                    </label>
                                    <div class="w-full mt-1 mb-3">
                                        <div class="block sm:flex">
                                            <div
                                                v-for="(paymentTypeImage, index) in paymentTypeImages"
                                                :key="index"
                                                class="p-2 rounded mr-4"
                                                :class="paymentTypeForm.image_name === paymentTypeImage.id ? 'bg-gray-100' : ''"
                                            >
                                                <img
                                                    :src="'/images/payment_types/' + paymentTypeImage.id"
                                                    class="img-fluid cursor-pointer w-12 mx-auto"
                                                    @click="selectPaymentTypeImage(paymentTypeImage.id)"
                                                >
                                                <p
                                                    class="text-center cursor-pointer"
                                                    @click="selectPaymentTypeImage(paymentTypeImage.id)"
                                                >
                                                    {{ paymentTypeImage.name }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row row-span-2 mt-4 ml-5">
                                <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                    <JSwitch
                                        v-model:is-checked="paymentTypeForm.is_member_required"
                                        input-label="Only available when a member is attached to the sale?"
                                        :required="true"
                                    />
                                </div>

                                <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                    <JSwitch
                                        v-model:is-checked="paymentTypeForm.is_available_for_refund"
                                        input-label="Should this payment type be available for refund?"
                                        :required="true"
                                    />
                                </div>
                                <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                    <JSwitch
                                        v-model:is-checked="paymentTypeForm.trigger_card_payment_machine"
                                        input-label="Should this payment type trigger card payment in the MBB Payment Machine?"
                                        :required="true"
                                        title="If this payment type is related to card payment and you want to trigger card related actions on the payment machine please enable this feature."
                                        :disabled="disableTriggerAffinKey()"
                                    />
                                </div>
                                <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                    <JSwitch
                                        v-model:is-checked="paymentTypeForm.trigger_qr_code_payment_machine"
                                        input-label="Should this payment type trigger QR code payment in the MBB Payment machine?"
                                        :required="true"
                                        title="If this payment type is related to QR code payment and you want to trigger QR code related actions on the payment machine please enable this feature."
                                        :disabled="disableTriggerAffinKey()"
                                    />
                                </div>

                                <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                    <JSwitch
                                        v-model:is-checked="paymentTypeForm.trigger_card_affin_payment_machine"
                                        input-label="Should this payment type trigger card payment in the Affin Payment Machine?"
                                        :required="true"
                                        :disabled="disableTriggerKey()"
                                    />
                                </div>

                                <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                    <JSwitch
                                        v-model:is-checked="paymentTypeForm.is_card_payment"
                                        input-label="Is Card Payment?"
                                        :required="true"
                                        title="If you want to track card details of the payment. Please enable this feature. When you enable this cashier need to specify card details while sale."
                                    />
                                </div>

                                <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                    <JSwitch
                                        v-model:is-checked="paymentTypeForm.trigger_card_bank_rakyat_terminal"
                                        input-label="Should this payment type trigger card bank rekyat terminal?"
                                        :required="true"
                                    />
                                </div>

                                <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                    <JSwitch
                                        v-model:is-checked="paymentTypeForm.status"
                                        input-label="Status"
                                        :required="true"
                                    />
                                </div>

                                <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                    <JSwitch
                                        v-model:is-checked="paymentTypeForm.is_available_in_pos"
                                        input-label="Is Available In Pos?"
                                        :required="true"
                                    />
                                </div>
                            </div>
                        </div>

                        <div class="mt-5">
                            <Link :href="route('admin.payment_types.index')">
                                <SecondaryButton
                                    type="button"
                                    text="Cancel"
                                    class="w-24 mr-1"
                                />
                            </Link>

                            <PrimaryButton
                                type="submit"
                                :text="paymentType ? 'Update' : 'Submit'"
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
import JSwitch from '@commonComponents/JSwitch.vue';
import { onMounted } from 'vue';
import { route } from 'ziggy';
import JMultiSelect from '@commonComponents/JMultiSelect.vue';

const props = defineProps({
    paymentType: {
        type: Object,
        default: null,
    },
    paymentTypeImages: {
        type: Array,
        required: true,
    },
    saleChannels: {
        type: Array,
        required: true,
    },
    shippingZones: {
        type: Array,
        required: true,
    },
    paymentRestrictionTypes: {
        type: Array,
        required: true,
    },
});

const paymentTypeForm = useForm({
    name: null,
    is_member_required: false,
    is_available_for_refund: false,
    trigger_card_payment_machine: false,
    trigger_qr_code_payment_machine: false,
    trigger_card_affin_payment_machine: false,
    trigger_card_bank_rakyat_terminal: false,
    is_card_payment: false,
    status: true,
    image_name: 'cash.png',
    payment_terminal_key: null,
    site_key: null,
    secret_key: null,
    url: null,
    is_available_in_ecommerce: false,
    restrict_by_zone: false,
    sale_channels: [],
    sale_channel_ids: [],
    shipping_zones: [],
    shipping_zone_ids: [],
    restriction_type: null,
    is_available_in_pos: true,
});

const selectPaymentTypeImage = (imageName) => {
    paymentTypeForm.image_name = imageName;
};

const savePaymentType = () => {
    if (paymentTypeForm.sale_channels) {
        paymentTypeForm.sale_channel_ids = paymentTypeForm.sale_channels.map((saleChannel) => {
            return saleChannel.id;
        });
    }

    if (paymentTypeForm.shipping_zones) {
        paymentTypeForm.shipping_zone_ids = paymentTypeForm.shipping_zones.map((shippingZone) => {
            return shippingZone.id;
        });
    }

    if (props.paymentType) {
        paymentTypeForm.put(route('admin.payment_types.update', props.paymentType.id));
        return;
    }
    paymentTypeForm.post(route('admin.payment_types.store'));
};

const disableTriggerKey = () => {
    if (paymentTypeForm.trigger_card_payment_machine || paymentTypeForm.trigger_qr_code_payment_machine) {
        paymentTypeForm.trigger_card_affin_payment_machine = false;
        return true;
    }
    return false;
};

const disableTriggerAffinKey = () => {
    if (paymentTypeForm.trigger_card_affin_payment_machine) {
        paymentTypeForm.trigger_card_payment_machine = false;
        paymentTypeForm.trigger_qr_code_payment_machine = false;
        return true;
    }
    return false;
};

const updateIsAvailableInEcommerce = (data) => {
    paymentTypeForm.sale_channels = [];
    paymentTypeForm.is_available_in_ecommerce = data;
};

const restrictByZone = (data) => {
    paymentTypeForm.shipping_zones = [];
    paymentTypeForm.restrict_by_zone = data;
    paymentTypeForm.restriction_type = null;
    if (data) {
        paymentTypeForm.restriction_type = props.paymentRestrictionTypes[0]?.id;
    }
};

onMounted(() => {
    if (props.paymentType) {
        Object.assign(paymentTypeForm, props.paymentType);
    }
});
</script>

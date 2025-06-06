<template>
    <PageTitle :title="subPaymentType ? 'Edit Sub Payment Type' : 'Add Sub Payment Type'" />
    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Sub Payment Types
        </h2>
    </div>
    <div class="grid grid-cols-12 gap-6 mt-5">
        <div class="intro-y col-span-12 lg:col-span-12">
            <div class="intro-y box">
                <div class="flex flex-col sm:flex-row items-center py-2 px-5 sm:p-5 bg-slate-100 border-b border-slate-200/60">
                    <h2 class="font-medium text-base mr-auto">
                        <span v-if="subPaymentType">Edit Sub Payment Type</span>
                        <span v-else>Add Sub Payment Type</span>
                    </h2>
                </div>
                <form @submit.prevent="saveSubPaymentType();">
                    <div class="p-5">
                        <div class="grid grid-rows-2 grid-flow-col gap-8">
                            <div class="row row-span-2 ">
                                <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                    <FormInput
                                        v-model:input-value="subPaymentTypeForm.name"
                                        input-name="name"
                                        input-label="Name"
                                        :required="true"
                                    />
                                </div>
                                <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                    <FormInput
                                        v-model:input-value="subPaymentTypeForm.payment_terminal_key"
                                        input-name="payment_terminal_key"
                                        input-label="Payment Terminal Key"
                                        title="If this payment type will be done via any of the third party machine then please specify the machine key."
                                    />
                                </div>
                                <div class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-6 xl:col-span-6 mt-2">
                                    <label>
                                        Image
                                        <span class="text-danger">*</span>
                                    </label>
                                    <div class="w-full mt-1 mb-3">
                                        <div class="block sm:flex">
                                            <div
                                                v-for="(subPaymentTypeImage, index) in subPaymentTypeImages"
                                                :key="index"
                                                class="p-2 rounded mr-4"
                                                :class="subPaymentTypeForm.image_name === subPaymentTypeImage.id ? 'bg-gray-100' : ''"
                                            >
                                                <img
                                                    :src="'/images/payment_types/' + subPaymentTypeImage.id"
                                                    class="img-fluid cursor-pointer w-12 mx-auto"
                                                    @click="selectSubPaymentTypeImages(subPaymentTypeImage.id)"
                                                >
                                                <p
                                                    class="text-center cursor-pointer"
                                                    @click="selectSubPaymentTypeImages(subPaymentTypeImage.id)"
                                                >
                                                    {{ subPaymentTypeImage.name }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row row-span-2 mt-4 ml-5">
                                <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                    <JSwitch
                                        v-model:is-checked="subPaymentTypeForm.is_member_required"
                                        input-label="Only available when a member is attached to the sale?"
                                        :required="true"
                                        class="mb-3 sm:mb-0"
                                    />
                                </div>

                                <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                    <JSwitch
                                        v-model:is-checked="subPaymentTypeForm.is_available_for_refund"
                                        input-label="Should this payment type be available for refund?"
                                        :required="true"
                                        class="mb-3 sm:mb-0"
                                    />
                                </div>

                                <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                    <JSwitch
                                        v-model:is-checked="subPaymentTypeForm.trigger_card_payment_machine"
                                        input-label="Should this payment type trigger card payment in the  MBB Payment machine?"
                                        :required="true"
                                        class="mb-3 sm:mb-0"
                                        title="If this payment type is related to card payment and you want to trigger card related actions on the payment machine please enable this feature."
                                        :disabled="disableTriggerAffinKey()"
                                    />
                                </div>

                                <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                    <JSwitch
                                        v-model:is-checked="subPaymentTypeForm.trigger_qr_code_payment_machine"
                                        input-label="Should this payment type trigger QR code payment in the MBB Payment machine?"
                                        :required="true"
                                        class="mb-3 sm:mb-0"
                                        title="If this payment type is related to QR code payment and you want to trigger QR code related actions on the payment machine please enable this feature."
                                        :disabled="disableTriggerAffinKey()"
                                    />
                                </div>

                                <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                    <JSwitch
                                        v-model:is-checked="subPaymentTypeForm.trigger_card_affin_payment_machine"
                                        input-label="Should this payment type trigger card payment in the Affin Payment Machine?"
                                        :required="true"
                                        :disabled="disableTriggerKey()"
                                    />
                                </div>

                                <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                    <JSwitch
                                        v-model:is-checked="subPaymentTypeForm.is_card_payment"
                                        input-label="Is Card Payment?"
                                        :required="true"
                                        class="mb-3 sm:mb-0"
                                        title="If you want to track card details of the payment. Please enable this feature. When you enable this cashier need to specify card details while sale."
                                    />
                                </div>

                                <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                    <JSwitch
                                        v-model:is-checked="subPaymentTypeForm.trigger_card_bank_rakyat_terminal"
                                        input-label="Should this payment type trigger card bank rekyat terminal?"
                                        :required="true"
                                    />
                                </div>

                                <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                    <JSwitch
                                        v-model:is-checked="subPaymentTypeForm.status"
                                        input-label="Status"
                                        :required="true"
                                    />
                                </div>

                                <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                    <JSwitch
                                        v-model:is-checked="subPaymentTypeForm.is_available_in_pos"
                                        input-label="Is Available In Pos?"
                                        :required="true"
                                    />
                                </div>
                            </div>
                        </div>

                        <div class="mt-5">
                            <Link :href="route('admin.sub_payment_types.index', paymentTypeId)">
                                <SecondaryButton
                                    type="button"
                                    text="Cancel"
                                    class="w-24 mr-1"
                                />
                            </Link>

                            <PrimaryButton
                                type="submit"
                                :text="subPaymentType ? 'Update' : 'Submit'"
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

const props = defineProps({
    subPaymentType: {
        type: Object,
        default: null,
    },
    paymentTypeId: {
        type: Number,
        default: null,
    },
    subPaymentTypeImages: {
        type: Array,
        required: true,
    }
});

const subPaymentTypeForm = useForm({
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
    is_available_in_pos: true,
});

const selectSubPaymentTypeImages = (imageName) => {
    subPaymentTypeForm.image_name = imageName;
};

const saveSubPaymentType = () => {
    if (props.subPaymentType) {
        subPaymentTypeForm.put(route('admin.sub_payment_types.update', [props.paymentTypeId, props.subPaymentType.id]));
        return;
    }
    subPaymentTypeForm.post(route('admin.sub_payment_types.store', props.paymentTypeId));
};

const disableTriggerKey = () => {
    if (subPaymentTypeForm.trigger_card_payment_machine === true || subPaymentTypeForm.trigger_qr_code_payment_machine === true) {
        subPaymentTypeForm.trigger_card_affin_payment_machine = false;
        return true;
    }
    return false;
};

const disableTriggerAffinKey = () => {
    if (subPaymentTypeForm.trigger_card_affin_payment_machine === true) {
        subPaymentTypeForm.trigger_card_payment_machine = false;
        subPaymentTypeForm.trigger_qr_code_payment_machine = false;
        return true;
    }
};

onMounted(() => {
    if (props.subPaymentType) {
        Object.assign(subPaymentTypeForm, props.subPaymentType);
    }
});
</script>

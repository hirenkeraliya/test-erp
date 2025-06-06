<template>
    <Modal
        size="modal-lg"
        :show="displayEInvoiceFormModal"
        @hidden="hideEInvoiceFormModal"
    >
        <ModalHeader>
            <h2 class="font-medium text-base mr-auto pr-8">
                E-Invoice
            </h2>

            <a
                class="absolute right-0 top-0 mt-2 mr-3"
                href="javascript:;"
                @click="hideEInvoiceFormModal"
            >
                <X class="w-7 h-7 sm:w-8 sm:h-8 text-slate-400" />
            </a>
        </ModalHeader>

        <ModalBody class="p-5 sm:p-10">
            <strong>Sequence Number: {{ sequenceNumber }}</strong> <br>
            <strong>Receipt Number: {{ receiptNumber }}</strong> <br>
            <strong>Member Name: {{ memberName }}</strong> <br>
            <strong>Location Name: {{ locationName }}</strong> <br>
            <div class="grid grid-cols-12 gap-0 sm:gap-6">
                <div
                    class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-6 xl:col-span-6"
                >
                    <FormInput
                        v-model:input-value="eInvoiceForm.buyer_name"
                        input-name="buyer_number"
                        input-label="Buyer's Name"
                        :required="true"
                    />
                </div>
                <div
                    class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-6 xl:col-span-6"
                >
                    <FormInput
                        v-model:input-value="eInvoiceForm.buyer_tin"
                        input-name="buyer_tin"
                        input-label="Buyer's TIN"
                        :required="true"
                    />
                </div>
                <div
                    class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-6 xl:col-span-6"
                >
                    <FormInput
                        v-model:input-value="
                            eInvoiceForm.buyer_identification_number
                        "
                        input-name="buyer_identification_number"
                        input-label="Buyer's Identification Number"
                        :required="true"
                        title="Buyer's Registration / Identification / Passport Number"
                    />
                </div>
                <div
                    class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-6 xl:col-span-6"
                >
                    <FormInput
                        v-model:input-value="eInvoiceForm.buyer_sst_number"
                        input-name="buyer_sst_number"
                        input-label="Buyer's SST Registration"
                        :required="true"
                    />
                </div>
                <div
                    class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-12 xl:col-span-12"
                >
                    <FormTextarea
                        v-model:input-value="eInvoiceForm.buyer_address"
                        input-name="buyer_address"
                        input-label="Buyer's Address"
                        :required="true"
                    />
                </div>
                <div
                    class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-6 xl:col-span-6"
                >
                    <FormInput
                        v-model:input-value="eInvoiceForm.buyer_contact"
                        input-name="buyer_contact"
                        input-label="Buyer's Contact"
                        :required="true"
                    />
                </div>
                <div
                    class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-6 xl:col-span-6"
                >
                    <FormInput
                        v-model:input-value="eInvoiceForm.buyer_email"
                        input-name="buyer_email"
                        input-label="Buyer's Email"
                    />
                </div>
            </div>
            <div
                v-if="!digitalInvoiceSubmitted"
                class="text-left mt-5"
            >
                <PrimaryButton
                    type="button"
                    text="Save"
                    class="w-24"
                    @click="saveEInvoiceForm"
                />
            </div>
        </ModalBody>
    </Modal>
</template>

<script setup>
import "@left4code/tw-starter/dist/js/modal";
import { Modal, ModalHeader, ModalBody } from "@commonVendor/model";
import { X } from "lucide-vue-next";
import { route } from "ziggy";
import PrimaryButton from "@commonComponents/PrimaryButton.vue";
import FormInput from "@commonComponents/FormInput.vue";
import {
    showErrorNotification,
    showSuccessNotification,
} from "@commonServices/notifier";
import { useForm } from "@inertiajs/vue3";
import FormTextarea from "@commonComponents/FormTextarea.vue";

import axios from "axios";

const props = defineProps({
    displayEInvoiceFormModal: {
        type: Boolean,
        default: false,
    },
    moduleId: {
        type: Number,
        required: true,
    },
    moduleType: {
        type: String,
        required: true,
    },
    sequenceNumber: {
        type: String,
        required: true,
    },
    receiptNumber: {
        type: String,
        required: true,
    },
    memberName: {
        type: String,
        required: true,
    },
    locationName: {
        type: String,
        required: true,
    },
    digitalInvoiceSubmitted: {
        type: String,
        required: true,
    },
});

const emits = defineEmits([
    "update:hide-e-invoice-modal",
    "refresh:tableRefresh",
]);

const hideEInvoiceFormModal = () => {
    emits("update:hide-e-invoice-modal", false);
};

const eInvoiceForm = useForm({
    module_id: props.moduleId,
    module_type: props.moduleType,
    buyer_name: null,
    buyer_tin: null,
    buyer_identification_number: null,
    buyer_sst_number: null,
    buyer_email: null,
    buyer_address: null,
    buyer_contact: null,
});

const saveEInvoiceForm = () => {
    axios
        .post(
            route("admin.digital_invoices.digital_invoice_store"),
            eInvoiceForm
        )
        .then(() => {
            hideEInvoiceFormModal();
            emits("refresh:tableRefresh");
            showSuccessNotification("E-invoice is generated successfully.");
        })
        .catch((error) => {
            if (error.response.data.message) {
                showErrorNotification(error.response.data.message);
            }
        });
};
</script>

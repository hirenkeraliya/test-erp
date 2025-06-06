<template>
    <Modal
        size="modal-xl"
        :show="isDisplayReUploadImportRecordFileModal"
        @hidden="hideModal(false)"
    >
        <ModalHeader>
            <h2 class="font-medium text-base mr-auto pr-8">
                Re Upload Import Record File
            </h2>

            <a
                class="absolute right-0 top-0 mt-3 mr-3"
                href="javascript:;"
                @click="hideModal(false)"
            >
                <X class="w-6 h-6 text-slate-400" />
            </a>
        </ModalHeader>

        <ModalBody class="p-5">
            <form @submit.prevent="saveStockAdjustmentItems();">
                <div class="grid grid-cols-12 gap-0 sm:gap-6">
                    <div
                        class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-6 xl:col-span-6"
                    >
                        <FileUploadAndDisplayRecords
                            :selected-products="state.selectedProducts"
                            :unmatched-products="state.unmatchedProducts"
                            :product-upc-url="productMatchingUpcUrl"
                            get-record-name="quantity"
                            input-label="Re Upload File"
                            validation-field-name="uploaded_file"
                            @display-selected-products-modal="openSelectedProductsModal"
                            @update:column-details="updateColumnDetails"
                            @display-unmatched-products-modal="openUnmatchedProductsModal"
                            @get-upload-file="getUploadFile"
                        />
                    </div>
                </div>

                <div class="mt-5">
                    <SecondaryButton
                        type="button"
                        text="Cancel"
                        class="w-24 mr-1"
                        @click="hideModal(false)"
                    />

                    <PrimaryButton
                        type="submit"
                        text="Submit"
                        class="w-24"
                    />
                </div>
            </form>
        </ModalBody>
    </Modal>

    <SelectedProducts
        :modal-show="state.displaySelectedProductsModal"
        :columns="state.fields"
        :records="state.selectedProducts"
        @close-modal="closeModal"
    />

    <UnmatchedProducts
        :modal-show="state.displayUnmatchedProductsModal"
        :records="state.unmatchedProducts"
        @close-modal="closeModal"
    />
</template>

<script setup>
import { X } from 'lucide-vue-next';
import { Modal, ModalHeader, ModalBody } from '@commonVendor/model';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import SecondaryButton from '@commonComponents/SecondaryButton.vue';
import { route } from 'ziggy';
import { showSuccessNotification } from '@commonServices/notifier';
import { useForm } from '@inertiajs/vue3';
import SelectedProducts from '@commonComponents/SelectedProducts.vue';
import UnmatchedProducts from '@commonComponents/UnmatchedProducts.vue';
import FileUploadAndDisplayRecords from '@commonComponents/FileUploadAndDisplayRecords.vue';
import { reactive } from 'vue';

const props = defineProps({
    isDisplayReUploadImportRecordFileModal: {
        type: Boolean,
        default: false,
    },
    modalId: {
        type: Number,
        required: true,
    },
    fetchPendingStatusesCountUrl: {
        type: String,
        required: true,
    },
    failedFileUrl: {
        type: String,
        required: true,
    },
    productMatchingUpcUrl: {
        type: String,
        required: true,
    },
});

const state = reactive({
    fields: [
        {
            key: 'name',
        },
        {
            key: 'quantity',
        },
    ],

    selectedProducts: [],
    unmatchedProducts: [],
    displayUnmatchedProductsModal: false,
    displaySelectedProductsModal: false,
});

const openSelectedProductsModal = () => {
    state.displaySelectedProductsModal = true;
};

const updateColumnDetails = (details) => {
    state[details.column_name] = details.value;
};

const getUploadFile = (fileName) => {
    failedReUploadForm.uploaded_file = fileName;
};

const openUnmatchedProductsModal = () => {
    state.displayUnmatchedProductsModal = true;
};

const closeModal = () => {
    if (state.displaySelectedProductsModal) {
        state.displaySelectedProductsModal = false;
        return;
    }

    if (state.displayUnmatchedProductsModal) {
        state.displayUnmatchedProductsModal = false;
    }
};

const emits = defineEmits(['close-modal']);

const hideModal = (closeWithRefresh) => {
    emits('close-modal', closeWithRefresh);
    failedReUploadForm.reset();
};

const failedReUploadForm = useForm({
    uploaded_file: null,
});

const saveStockAdjustmentItems = () => {
    failedReUploadForm.put(route(props.fetchPendingStatusesCountUrl, props.modalId), {
        onSuccess: (page) => {
            if (page.props.flash.error) {
                return;
            }

            showSuccessNotification('Re Upload File Is In progress.');
            hideModal(true);
        },
    });
};
</script>
